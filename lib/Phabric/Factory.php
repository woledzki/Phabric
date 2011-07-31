<?php
namespace Phabric;
use Phabric\Bus;
use Doctrine\DBAL\Connection;
/**
 * A Factory class used to initialises the Phabric Bus and create phabric obejcts.
 * 
 * @package Phabric
 * @author  Ben Waine <ben@ben-waine.co.uk>
 */
class Factory {
   
    /**
     * Single instance of the Phabric Bus to be injected into each of the created
     * Phabric instances.
     *
     * @var Phabric\Bus
     */
    private static $bus;

    /**
     * Instance of Doctrine\DBAL\Connetion. This is supplied to new instances of
     * Phabric to enable create / update operations.
     * MUST be set before instances are created.
     *
     * @var \Doctrine\DBAL\Connection
     */
    private static $db;

    /**
     * Creates and returns an instance of Phabric\Phabric subscribed to the
     * Phabric\Bus. A new instance of the Phabric bus is created if no bus has 
     * previously been created or setIf config is passed then the instance is 
     * created based on this config. If no config is passed then the returned
     * instance is efectivley 'blank' and must be configured using the relevant
     * methods.
     *
     * @param array $config
     *
     * @return Phabric\Phabric
     */
    public static function createPhabric($name, $config = null)
    {
        if(!isset(self::$db))
        {
            throw new \RuntimeException('Database Connection must be set before instances of Phabric are created');
        }
        
        if(is_null(self::$bus))
        {
            self::$bus = new Bus();
        }

        if(isset($config))
        {
            $entity = new Phabric(self::$db, self::$bus, $config);
        }
        else
        {
            $entity = new Phabric(self::$db, self::$bus);
        }

        self::$bus->registerEntity($name, $entity);

        return $entity;
    }

    /**
     * Sets the instance of the Phabric\Bus to use.
     *
     * @param Bus $bus
     *
     * @return void
     */
    public static function setBus(Bus $bus)
    {
        self::$bus = $bus;
    }

    /**
     * Gets the Phabric\Bus instance
     *
     * @return Phabric\Bus
     */
    public static function getBus()
    {
        return self::$bus;
    }

    /**
     * Sets the database connection used bu new instances of Phabric.
     * This must be set before creating any phabric instances.
     *
     * @param Doctrine\DBAL\Connection $db
     *
     * @return void
     */
    public static function setDatabaseConnection(Connection $db)
    {
        self::$db = $db;
    }

    /**
     * Unregisters the bus and resets the class.
     *
     * @return void
     */
    public static function reset()
    {
        self::$bus = null;
        self::$db = null;
    }

}

