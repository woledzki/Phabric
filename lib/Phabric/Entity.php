<?php
namespace Phabric;
use Phabric\Datasource\IDatasource;
use Doctrine\DBAL\Connection;
use Behat\Gherkin\Node\TableNode;


/**
 * This file is part of the Phabric.
 * (c) Ben Waine <ben@ben-waine.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * Default name transformation
     *
     * @var string|callback
     */
    protected $defaultNameTransformation = 'strtolower';

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
     * Configurations options for the entity
     *
     * @var array
     */
    protected $options = array(
    );

    /**
     * Datasource.
     *
     * @var \Phabric\Datasource\IDatasource
     */
    protected $ds;

    /**
     * An instance of the Phabric Bus.
     * Used as a point of access to other instances of phabric representing
     * other database tables.
     *
     * @var Phabric\Bus
     */
    protected $bus;

    /**
     * Initialises an instance of the Phabric class.
     *
     * @param Doctrine\DBAL\Connection $db
     * @param array                    $config
     *
     */
    public function __construct(IDatasource $ds, Phabric $bus, $config = null)
    {
        $this->ds = $ds;

        $this->bus = $bus;

        if(isset($config))
        {
            if(isset($config['entityName']))
            {
                $this->setName($config['entityName']);
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

            if(isset($config['options']))
            {
                $this->setOptions($config['options']);
            }

            if(isset($config['defaultNameTransformation']))
            {
                $this->setDefaultNameTransformation($config['defaultNameTransformation']);
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
    public function setName($name)
    {
        $this->entityName = $name;
    }

    /**
     * Get the human readable name of the entity.
     *
     * @return string
     */
    public function getName()
    {
        return $this->entityName;
    }


    /**
     * Set some of the entities options
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options)
    {
        foreach($options as $opt => $value) {
            $this->setOption($opt, $value);
        }
    }

    /**
     * Get the entities configuration options
     *
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set one of the entities configuration options
     *
     * @param string $option
     * @param mixed $value
     * @return void
     * @throws InvalidArgumentException
     */
    public function setOption($option, $value)
    {
        if (!isset($this->options[$option])) {
            throw new \InvalidArgumentException("$option is not a valid entity option");
        }

        $this->options[$option] = $value;
    }

    /**
     * Get one of the entities configuration options
     *
     * @param string $option
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getOption($option)
    {
        if (!isset($this->options[$option])) {
            throw new \InvalidArgumentException("$option is not a valid entity option");
        }

        return $this->options[$option];
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
        foreach($transformations as $colName => $transformation)
        {
            if (is_array($transformation))
            {
                $this->dataTransformations[$colName] = $transformation;
            }
            else
            {
                $this->dataTransformations[$colName][] = $transformation;
            }
        }
    }

    /**
     * Set the default name transformation
     *
     * @param callback|string $nameTransformation
     * @return void
     */
    public function setDefaultNameTransformation($nameTransformation)
    {
        $this->defaultNameTransformation = $nameTransformation;
    }

    /**
     * Get the default name transformation
     *
     * @return callback|string
     */
    public function getDefaultNameTransformation()
    {
        return $this->defaultNameTransformation;
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
        $data = $this->processTable($table);

        foreach($data as &$row)
        {
            if($defaultFlag)
            {
                $this->mergeDefaults($row);
            }

            $this->ds->insert($this, $row);

            // execute callbacks / fire afterInsert event
        }
    }

    /**
     * Update a previously inserted entity with the new data from a gherkin table.
     *
     * @param array $data Gherkin table data. NB - Never augmented with default values.
     *
     * @return void
     */
    public function updateFromTable(TableNode $table)
    {
        $procData = $this->processTable($table);

        foreach($procData as $row)
        {
            $this->ds->update($this, $row);

            // execute callbacks / fire afterUpdate event
        }

    }

    /**
     * Processes a table node. COnverts it into an array and applies any
     * transformations configured.
     *
     * @param TableNode $table
     *
     * @return array
     */
    protected function processTable(TableNode $table)
    {

        $rows = array();

        foreach($table->getHash() as $row)
        {
            $cols = array();

            foreach($row as $colName => $colValue)
            {
                $k = $this->applyNameTransformation($colName);

                $cols[$k] = $this->applyDataTranslation($k, $colValue);
            }

            $rows[] = $cols;

        }

        return $rows;
    }

    /**
     * Looks for a name translation for the given key and if found applies it.
     * Returns the original key if no transformation is found.
     *
     * @param string $key
     *
     * @return string
     */
    protected function applyNameTransformation($key)
    {
        if(isset($this->nameTransformations[$key]))
        {
            return $this->nameTransformations[$key];
        }
        else if(null !== $this->defaultNameTransformation) 
        {

            if(is_callable($this->defaultNameTransformation))
            {
                $fn = $this->defaultNameTransformation;
                return call_user_func($fn, $key);
            }

            $fn = $this->bus->getDataTransformation($this->defaultNameTransformation);
            return call_user_func($fn, $key, $this->bus);
        }
            
        return $key;
    }

    /**
     * Looks for a data transformation for the given key and applies it to the
     * given value if found. Returns the original value if not found.
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    protected function applyDataTranslation($key, $value)
    {
        if(isset($this->dataTransformations[$key]))
        {
            foreach($this->dataTransformations[$key] as $transformationName)
            {
                $fn = $this->bus->getDataTransformation($transformationName);
                $value = $fn($value, $this->bus);
            }
        }

        return $value;
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
        if(is_null($this->defaults))
        {
            return;
        }

        $defaultsReq = array_diff_key($this->defaults, $row);
        $row = array_merge($row, $defaultsReq);
    }

    public function getNamedItemId($name)
    {
        return $this->ds->getNamedItemId($this, $name);
    }

    public function getNamedItem($name)
    {
        return $this->ds->getNamedItem($this, $name);
    }

}

