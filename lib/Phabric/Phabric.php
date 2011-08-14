<?php
namespace Phabric;
/**
 * The Phabric bus manages the registration of translations for use on all
 * subscribing instances of Phabric. It also allows the creation of relational
 * data by providing one instance of Phabric with access to all others via a single interface.
 *
 * @package    Phabric
 * @author     Ben Waine <ben@ben-waine.co.uk>
 */
class Phabric
{
    /**
     * An array of registered lambda functions.
     *
     * @var array
     */
    protected $registeredDataTranslations = array();

    /**
     * An array of registered phabric instances.
     *
     * @var array
     */
    protected $registeredPhabricEntities = array();
    
    /**
     * Datasource used to insert / update records into.
     * 
     * @var \Doctrine\Connection
     */
    protected $datasource;

    /**
     * Initialises an instance of the Phabric Bus class.
     *
     * @param $ds The Datasource
     * 
     * @return void
     */
    public function __construct($ds)
    {
        $this->datasource = $ds;
    }
    
    /**
     * Creates and registers a Phabric entity with the bus.
     * 
     * @param string $name   Name to register the entity with.
     * @param array  $config Configuration array.
     * 
     * @return \Phabric\Entity
     */
    public function createEntity($name, $config = null)
    {
        $entity = new Entity($this->datasource, $this, $config);
        
        $this->addEntity($name, $entity);
        
        return $entity;
    }

    /**
     * Registeres an lambda function against a named key for use in subscribed
     * Phabric instances.
     *
     * @param string   $name
     * @param function $translation
     *
     * @return void
     */
    public function addDataTranslation($name, $translation)
    {
        if(!\is_callable($translation))
        {
            throw new \InvalidArgumentException('Translation passed to ' . __METHOD__ . ' is not callable');
        }

        $this->registeredDataTranslations[$name] = $translation;
    }

    /**
     * Get a named data translation for use in a subscribed phabric instance.
     *
     * @param string $name
     *
     * @return function
     */
    public function getDataTranslation($name)
    {
        if(!isset($this->registeredDataTranslations[$name]))
        {
            throw new \InvalidArgumentException('Data translation function not registered');
        }

        return $this->registeredDataTranslations[$name];
    }

    /**
     * Registers an entity by name for retrieval later by other phabric
     * instances.
     * 
     * @param string Entity name 
     * @param Entity $phabric
     *
     * @return void
     */
    protected function addEntity($name, Entity $phabric)
    {
        $this->registeredPhabricEntities[$name] = $phabric;
    }

    /**
     * Get a named Phabric entity from the bus.
     *
     * @param string $name 
     *
     * @throws \InvalidArgumentException
     *
     * @return Phabric\Entity
     */
    public function getEntity($name)
    {
        if(isset($this->registeredPhabricEntities[$name]))
        {
            return $this->registeredPhabricEntities[$name];
        }
        else
        {
            throw new \InvalidArgumentException('Entity not registered with the bus');
        }
    }


}

