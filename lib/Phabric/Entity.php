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
           
        $data = $this->processTable($table);
        
        foreach($data as &$row)
        {
            
            if($defaultFlag)
            {
                $this->mergeDefaults($row);
            }
            
            $this->ds->insert($this, $row);

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
            return strtolower($this->nameTransformations[$key]);
        }
        else
        {
            return strtolower($key);
        }
            
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
            $fn = $this->bus->getDataTransformation($this->dataTransformations[$key]);
            return $fn($value, $this->bus);
        }
        else
        {
            return $value;
        }
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
        

}

