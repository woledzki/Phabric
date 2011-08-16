<?php
namespace Phabric;
use Doctrine\DBAL\Connection;
use Behat\Gherkin\Node\TableNode;
/**
 * Phabric base class. Encapsulates the basic single table create and
 * update behaviour used to translate Gherkin tables into database entries.
 *
 * @package Phabric
 * @author  Ben Waine <ben@ben-waine.co.uk>
 */
class Entity
{
    /**
     * Entity Name - Should be human readbale business term.
     *
     * @var string
     */
    protected $entityName;

    /**
     * Table Name - as in the database
     *
     * @var string
     */
    protected $tableName;

    /**
     * Tranlations - An array with human readable Gherkin table headers mapped to db columns.
     *
     * @var array
     */
    protected $nameTransformations = array();

    /**
     * Data Transformations - An array of database col names and transformation types.
     *
     * @var array
     */
    protected $dataTransformations = array();

    /**
     * Default values to augment Gherkin table data with.
     *
     * @var array
     */
    protected $defaults;

    /**
     * DBAL Instance.
     *
     * @var Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * An instance of the Phabric Bus.
     * Used as a point of access to other instances of phabric representing
     * other database tables.
     *
     * @var Phabric\Bus
     */
    protected $bus;
    
    /**
     * A registry of the items created 
     * 
     * @var array 
     */
    protected $namedItemsNameIdMap;

    /**
     * Initialises an instance of the Phabric class.
     *
     * @param Doctrine\DBAL\Connection $db
     * @param array                    $config
     *
     */
    public function __construct(Connection $db, Phabric $bus, $config = null)
    {
        $this->db = $db;

        $this->bus = $bus;

        if(isset($config))
        {
            if(isset($config['entityName']))
            {
                $this->setEntityName($config['entityName']);
            }

            if(isset($config['tableName']))
            {
                $this->setTableName($config['tableName']);
            }

            if(isset($config['nameTransformations']))
            {
                $this->setNameTransformations($config['nameTransformations']);
            }

            if(isset($config['dataTransformations']))
            {
                $this->setDataTransformations($config['dataTransformations']);
            }

            if(isset($config['defaults']))
            {
                $this->setDefaults($defaults);
            }
        }
    }

    /**
     * Sets the instance of Phabric\Bus in use by this instance of Phabric.
     *
     * @param Bus $bus
     *
     * @return void.
     * 
     */
    public function setBus(Phabric $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Set the human readable name of the entity
     * 
     * @param string $name
     *
     * @return void
     */
    public function setEntityName($name)
    {
        $this->entityName = $name;
    }

    /**
     * Set the database table name for this entity.
     *
     * @param string $name
     *
     * @return void
     */
    public function setTableName($name)
    {
        $this->tableName = $name;
    }

    /**
     * Set the default values for this entity.
     * These are used to 'fill in the gaps' left by the gherkin tables.
     *
     * @param array $defaults
     *
     * @return void
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * Sets the transformations used to map human readable gherkin table headers
     * to the columns in the database.
     *
     * @return void
     */
    public function setNameTransformations($transformations)
    {
        $this->nameTransformations = $transformations;
    }
        

    /**
     * Sets the transformations used to transform values in the gherkin text.
     * EG - A date transformation d/m/y > Y-m-d H:i:s
     * Note: These must map to functions registered with the Phabric\Bus
     *
     * @return void
     */
    public function setDataTransformations($transformations)
    {
        foreach($transformations as $colName => $transformationName)
        {
            $this->dataTransformations[$colName] = $transformationName;
        }
    }

    /**
     * Creates an entity based on data rom a gherkin table.
     * By default the data is augmented by the default values supplied.
     *
     * @param array   $data        Data from a gherkin table (top row of header with subsequent rows of data)
     * @param boolean $defaultFlag
     *
     * @return void
     */
    public function insertFromTable(TableNode $table, $defaultFlag = true)
    {
        
        $data = $table->getRows();

        $header = array_shift($data);

        $this->transformHeader($header);
        

        foreach($data as &$row)
        {
            $row = array_combine($header, $row);

            if(isset($this->defaults))
            {
                $this->mergeDefaults($row);
            }

            foreach($row as $colName => &$colValue)
            {
                if(isset($this->dataTransformations[$colName]))
                {
                    $fn = $this->bus->getDataTransformation($this->dataTransformations[$colName]);
                    $colValue = $fn($colValue, $this->bus);
                }
            }

            $this->db->insert($this->tableName, $row);
            
            $firstElement = reset($row);
            
            $this->namedItemsNameIdMap[$firstElement] = $this->db->lastInsertId();
        }
        

    }

    /**
     * Applies an column name transformations.
     * Lower cases the column names.
     *
     * @param array $header
     *
     * @return void
     */
    protected function transformHeader(&$header)
    {
        
        foreach($header as &$colName)
        {
            if(isset($this->nameTransformations[$colName]))
            {
                $colName = $this->nameTransformations[$colName];
            }
        }

        array_walk($header, function(&$value){$value = \strtolower($value);});        
    }

    /**
     * Method used to merge the set default parameters with a row of data from
     * a Gherkin table.
     *
     * This method should be used after an name based transformations.
     * 
     * @param array $row
     *
     * @return void
     */
    protected function mergeDefaults(& $row)
    {
        $defaultsReq = array_diff_key($this->defaults, $row);   
        $row = array_merge($row, $defaultsReq);
    }

    /**
     * Update a previously inserted entity with the new data from a gherkin table.
     *
     * @param string $name Human readable entity name
     * @param array $data Gherkin table data. NB - Never augmented with default values.
     *
     * @return void
     */
    public function update($name, $data)
    {
        
    }
        
    /**
     * Gets the ID of a named item inserted into the database previously.
     * 
     * @param string $name
     * 
     * @return integer|false 
     */
    public function getNamedItemId($name)
    {
        if(isset($this->namedItemsNameIdMap[$name]))
        {
            return $this->namedItemsNameIdMap[$name];
        }
        else
        {
            return false;
        }
    }

}

