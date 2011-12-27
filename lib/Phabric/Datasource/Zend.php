<?php

namespace Phabric\Datasource;

use \Phabric\Datasource\IDatasource;
use \Phabric\Entity;
use \Zend_Db_Adapter_Abstract as Conn;

/**
 * This file is part of the Phabric.
 * (c) Ben Waine <ben@ben-waine.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A Doctrine database adapter.
 *
 * @package    Phabric
 * @subpackage Datasource
 * @author     Ben Waine <ben@ben-waine.co.uk>
 */
class Zend implements IDatasource
{
    /**
     * Initialises an instance of the Zend datasource class.
     *
     * @param \Zend_Db_Adapter_Abstract $connection
     * @param array                     $config
     *
     * @return void
     */
    public function __construct(Conn $connection, $config = null)
    {
        $this->connection = $connection;

        if (isset($config))
        {
            foreach ($config as $name => $entity)
            {
                $this->addTableMapping($name, $entity['tableName'], $entity['primaryKey'], $entity['nameCol']);
            }
        }
    }
    
    /**
     * Returns an array of all the table mappings used when updating and
     * inserting into the database.
     *
     * @return array
     */
    public function getMappings()
    {
        return $this->tableMappings;
    }
    
    /**
     * Sets all table mappings.
     *
     * @param array $mappings
     * @return void
     */
    public function setTableMappings(array $mappings)
    {
        $this->tableMappings = $mappings;
    }
    
    /**
     * Add a mapping definition.
     *
     * @param string $entityName The name of the entity
     * @param string $tableName  The table name the entity maps to
     * @param string $pKeyCol    The name of the tables primary key column
     * @param string $nameCol    The name of the column Phabric uses to ID the
     *                           data
     *
     * @return void
     */
    public function addTableMapping($entityName, $tableName, $pKeyCol, $nameCol)
    {
        $this->tableMappings[$entityName] = array(
            'tableName' => $tableName,
            'primaryKey' => $pKeyCol,
            'nameCol' => $nameCol
        );
    }
    
    /**
     * Resets the data to it's previous state
     */
    public function reset()
    {
        
    }

    /**
     * Inserts Data into the data source.
     */
    public function insert(Entity $entity, array $data)
    {
        $name = $entity->getName();
        $columns = implode('`,`', array_keys($data));
        $values = implode("','", array_values($data));
        
        $this->connection->query(
            "INSERT INTO (`$columns`) `$name` VALUES ('$values')"
        );
        
        return $this->connection->lastInsertId();
    }

    /**
     * Updates data in the datasource.
     */
    public function update(Entity $entity, array $data)
    {
        
    }

    /**
     * Delete data from the datasource.
     */
    public function delete($enityName)
    {
        
    }

    /**
     * Select data from the data.
     */
    public function select()
    {
        
    }

    /**
     * Gets the unique identifier for previously inserted item by its name.
     */
    public function getNamedItemId(Entity $entity, $name)
    {
        
    }
}