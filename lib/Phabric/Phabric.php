<?php
namespace Phabric;
use Doctrine\DBAL\Connection;
/**
 * Abstract class when implementing a creator.
 *
 * @package Phabric
 * @author  Ben Waine <ben@ben-waine.co.uk>
 */
class Phabric
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
    protected $nameTranslations = array();

    /**
     * Data Translations - An array of database col names and transformation types.
     *
     * @var array
     */
    protected $dataTranslations = array();

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
     * Initialises an instance of the Phabric class.
     *
     * @param Doctrine\DBAL\Connection $db
     * @param array                    $config
     *
     */
    public function __construct(Connection $db, $config = null)
    {
        $this->db = $db;

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

            if(isset($config['nameTranslations']))
            {
                $this->setNameTranslations($config['nameTranslations']);
            }

            if(isset($config['dataTranslations']))
            {
                $this->setDataTranslations($config['dataTranslation']);
            }

            if(isset($config['defaults']))
            {
                $this->setDefaults($defaults);
            }
        }
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

    }

    /**
     * Sets the translations used to map human readable gherkin table headers
     * to the columns in the database.
     *
     * @return void
     */
    public function setNameTranslations($translations)
    {
        $this->nameTranslations = $translations;
    }

    /**
     * Sets the translations used to transform values in the gherkin text.
     * EG - A date transformation d/m/y > Y-m-d H:i:s
     *
     * @return void
     */
    public function setDataTranslations()
    {
        
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
    public function create($data, $defaultFlag = true)
    {

        $header = array_shift($data);

        $this->transformHeader($header);
        

        foreach($data as &$row)
        {
            $row = array_combine($header, $row);

            $this->db->insert($this->tableName, $row);
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
            if(isset($this->nameTranslations[$colName]))
            {
                $colName = $this->nameTranslations[$colName];
            }
        }

        array_walk($header, function(&$value){$value = \strtolower($value);});        
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

    
}

