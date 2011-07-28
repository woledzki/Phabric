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
     * An array of functions used to transform data.
     * The key of the array should be a unique reference used to identify the function.
     *
     * @var array
     */
    protected $dataTranslationFunctions = array();

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
        $this->defaults = $defaults;
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
    public function setDataTranslations($translations)
    {
        foreach($translations as $colName => $translationName)
        {
            $this->dataTranslations[$colName] = $translationName;
        }
    }

    /**
     * Registers a function to translate data from the format used in a gherkin
     * table to the data to be entered into the DB.
     *
     * @param string $name        This should match with the name of the column after name translation.
     * @param lambda $translation This should be a lambda. It should take one argument: the data to be transformed.
     *
     * @return void
     */
    public function registerNamedDataTranslation($name, $translation)
    {
        if(!\is_callable($translation))
        {
            throw new \InvalidArgumentException('Translation passed to ' . __METHOD__ . ' is not callable');
        }
        
        $this->dataTranslationFunctions[$name] = $translation;
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

            if(isset($this->defaults))
            {
                $this->mergeDefaults($row);
            }

            foreach($row as $colName => &$colValue)
            {
                if(isset($this->dataTranslations[$colName]))
                {
                    var_dump($colName, $colValue);
                    $colValue = $this->dataTranslationFunctions[$this->dataTranslations[$colName]]($colValue);
                }
            }
            //var_dump($row);
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

}

