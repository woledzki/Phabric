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

        if (isset($config)) {
            foreach ($config as $name => $entity) {
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
        
        if (!$this->verifyTableIsMapped($name)) {
            throw new \RuntimeException("The table: $name has not been mapped.");
        }

        if (!$this->verifyDataContainsNameCol($name, $data)) {
            throw new \RuntimeException('Table data does not have required name column');
        }
        
        $tableName = $this->tableMappings[$name]['tableName'];
        $phName = $this->tableMappings[$name]['nameCol'];
        
        $columns = implode('`,`', array_keys($data));
        $values = implode("','", array_values($data));
        
        $this->connection->insert($tableName, $data);
        
        return $this->connection->lastInsertId();
    }

    /**
     * Updates data in the datasource.
     */
    public function update(Entity $entity, array $data)
    {
        $name = $entity->getName();
        
        if (!$this->verifyTableIsMapped($name)) {
            throw new \RuntimeException("The table: $name has not been mapped.");
        }

        if (!$this->verifyDataContainsNameCol($name, $data)) {
            throw new \RuntimeException('Table data does not have required name column');
        }
        
        $tableName = $this->tableMappings[$name]['tableName'];
        $phName = $this->tableMappings[$name]['nameCol'];
        $idCol = $this->tableMappings[$name]['primaryKey'];

        if (!isset($this->nameIdMap[$tableName][$data[$phName]])) {
            $initData = $this->selectPreloadedData($tableName, $phName, $data);

            // @todo decide if we want to insert or throw exception and let end
            // user to deal with that.
            if (empty($initData)) {
                //return $this->insert($entity, $data);
                throw new \Exception("Entity $name for name $phName not found");
            }

            $this->resetMap[$tableName]['update'][$initData[$idCol]] = $initData;
            $whereAr = array($idCol => $initData[$idCol]);
        } else {
            $whereAr = array($idCol => $this->nameIdMap[$tableName][$data[$phName]]);
        }
        
        return $this->connection->update($tableName, $data, $whereAr);
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
    
    /**
     * Verify data has the required name col to identify it with.
     * Used to record an insert or to get the id to use in an update.
     *
     * @param string $entityName
     * @param array  $data
     *
     * @return boolean
     */
    protected function verifyDataContainsNameCol($entityName, $data)
    {
        if (isset($this->tableMappings[$entityName]['nameCol']))
        {
            return isset($data[$this->tableMappings[$entityName]['nameCol']]);
        }

        return false;
    }

    /**
     * Verifies that the required table meta data is present.
     *
     * @param string $entityName
     *
     * @return boolean
     */
    protected function verifyTableIsMapped($entityName)
    {
        return isset($this->tableMappings[$entityName]);
    }
    
    /**
     * Selects data from the database not managed by Phabric.
     * Used to select a copy of the data before update in order to allow
     * roll back.
     *
     * @param string $tableName Name of table to query
     * @param string $phName    Name of the Phabric entity
     * @param array  $data      Data from the Gherkin
     *
     * @return array
     */
    protected function selectPreloadedData($tableName, $phName, $data)
    {
        $builder = $this->connection->select();
        $nValue = $this->connection->quote($data[$phName]);

        $builder->from($tableName, 'a')
                ->where("a.`$phName` = $nValue");
        $result = $builder->query();

        $initalData = $result->fetchAll();

        if (count($initalData) > 1)
        {
            throw new \RuntimeException('
                More than one row returned when trying to manage unmanaged
                (preloaded) data. Value in the name column (set in config) must be unique.');
        }

        return reset($initalData);
    }
}