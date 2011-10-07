<?php
namespace Phabric\Datasource;
use \Phabric\Datasource\IDatasource;
use \Phabric\Entity;
use \Doctrine\DBAL\Connection as Conn;


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
class Doctrine implements IDatasource
{
    /** 
     * Database connection
     * 
     * @var \Doctrine\DBAL\Connection 
     */    
    protected $connection;
        
    /** 
     * Table Mapping data used in insert / update operations.
     *
     * @var array 
     */
    protected $tableMappings;
    
    /**
     * A map of names => id mappings keyed by table
     * 
     * @var array
     */
    protected $nameIdMap;
    
    /**
     * Map of inserts and updates with respective ID's
     * indexed by tablename => array('insert','update)'
     * 
     * @var array 
     */
    protected $resetMap = array();
    
    /**
     * Initialises an instance of the Doctrine datasource class.
     * 
     * @param \Doctrine\DBAL\Connection $connection
     * @param array                     $config 
     * 
     * @return void
     */
    public function __construct(Conn $connection, $config = null)
    {
        $this->connection = $connection;
        
        if(isset($config))
        {
            foreach($config as $name => $entity)
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
     * Gets the ID of a item from a previously inserted row.
     * 
     * @param Phabric\Entity $tableName
     * @param string         $name
     * 
     * @return int 
     */
    public function getNamedItemId(Entity $entity, $name)
    {
        if($this->verifyTableIsMapped($entity->getName()))
        {
            $tableName = $this->tableMappings[$entity->getName()]['tableName'];
            
            if(isset($this->nameIdMap[$tableName][$name]))
            {
                return $this->nameIdMap[$tableName][$name];
            }
            else
            {
                return false;
            }
        }
        else
        {
            throw new \RuntimeException('Attempt to use unmapped entity');
        }
        
    }
    
    /**
     * Inserts data into a database table.
     * Returns the inserted rows identifier.
     * 
     * @param string $entityName
     * @param array  $data 
     * 
     * @return string
     */
    public function insert(Entity $entity, array $data) 
    {
        $name = $entity->getName();
        
        if(!isset($this->tableMappings[$name]))
        {
            throw new \RuntimeException("The table: $name has not been mapped.");
        }
        
        $tableName = $this->tableMappings[$name]['tableName'];
        $phName = $this->tableMappings[$name]['nameCol'];

        if(!is_null($phName) && !isset($data[$phName]))
        {
            throw new \RuntimeException('Table data does not have required name column');
        }
        
        $this->connection->insert($tableName, $data);
        
        $insertId = $this->connection->lastInsertId();
        
        
        if(!is_null($phName))
        {
            $this->addManagedData($name, $data[$phName], $insertId);
        }
        
        return $insertId;
    }
        
    /**
     * Update data in a database table.
     * 
     * @param string $entityName
     * @param array  $data
     * @param array  $conditionals 
     * 
     * @return void
     */
    public function update(Entity $entity, array $data)
    {
        $name = $entity->getName();
        
        if(!$this->verifyTableIsMapped($name))
        {
            throw new \RuntimeException("The table: $name has not been mapped.");
        }
        
        if(!$this->verifyDataContainsNameCol($name, $data))
        {
            throw new \RuntimeException('Table data does not have required name column');
        }
        
        $tableName = $this->tableMappings[$name]['tableName'];
        $phName    = $this->tableMappings[$name]['nameCol'];
        $idCol     = $this->tableMappings[$name]['primaryKey'];

        if(!isset($this->nameIdMap[$tableName][$data[$phName]]))
        {
            throw new \RuntimeException('Attempt to update data not managed by Phabric');
        }
        
        $whereAr = array($idCol => $this->nameIdMap[$tableName][$data[$phName]]);
        
        $this->connection->update($tableName, $data, $whereAr);
    }
    
    
    /**
     * Adds an entry into the array used to track data Phabric has inserted.
     * 
     * @param type $tableName
     * @param type $name
     * @param type $id 
     * 
     * @return void
     */
    protected function addManagedData($entity, $nameData, $id)
    {        
        $tableName = $this->tableMappings[$entity]['tableName'];
        
        $this->nameIdMap[$tableName][$nameData] = $id;
        $this->resetMap[$entity]['insert'][] = $id;
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
        if(isset($this->tableMappings[$entityName]['nameCol']))
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
     * Delete data from a database table.
     * 
     * @param type $entityName 
     * 
     * @return void
     */
    public function delete($entityName) 
    {
        
    }

    /**
     * Resets the database state to the point of initial insert or update query.
     * 
     * @retun void
     */
    public function reset() 
    {
        var_dump($this->resetMap);
        foreach($this->resetMap as $entity)
        {
            
            if(isset($entity['insert']))
            {
                foreach($entity['insert'] as $record)
                {
                    $tableName = $this->tableMappings[$entity]['tableName'];
                    $pKeyCol = $this->tableMappings[$entity]['primaryKey'];
                    var_dump($tableName, $pKeyCol, $record);
                    $this->connection->delete($tableName, array($pKeyCol => $record));
                }
            }
            if(isset($entity['update']))
            {
                
            }
        }
    }

    /**
     * Selects table from the database.
     * 
     * @return void
     */
    public function select() 
    {
        
    }
   
}


