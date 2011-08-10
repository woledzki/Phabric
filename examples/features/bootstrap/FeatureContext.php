<?php

use Behat\Behat\Context\ClosuredContextInterface,
 Behat\Behat\Context\TranslatedContextInterface,
 Behat\Behat\Context\BehatContext,
 Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
 Behat\Gherkin\Node\TableNode,
 Behat\Behat\Event\SuiteEvent;
use Phabric\Factory as pFactory;
use Phabric\Phabric;
use Phabric\Bus;

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';
require_once __DIR__ . '/../../../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';


/**
 * Features context.
 */
class FeatureContext extends BehatContext
{

    /**
     * The Phabric Bus
     *
     * @var Phabric\Bus
     */
    private $phabricBus;

    /**
     * The Databse Connection.
     *
     * @var Doctrine\DBAL\Connection
     */
    private static $db;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param   array   $parameters     context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
       
        $phaLoader = new \Doctrine\Common\ClassLoader('Phabric', realpath(__DIR__ . '/../../../lib/'));
        $phaLoader->register();

        $docLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', __DIR__ . '/../../../lib/Vendor/Doctrine/lib');
        $docLoader->register();

        $docComLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', __DIR__ . '/../../../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib');
        $docComLoader->register();

        $config = new \Doctrine\DBAL\Configuration();
        
        self::$db = \Doctrine\DBAL\DriverManager::getConnection(array(
                    'dbname' => $parameters['database']['dbname'],
                    'user' => $parameters['database']['username'],
                    'password' => $parameters['database']['password'],
                    'host' => $parameters['database']['host'],
                    'driver' => $parameters['database']['driver'],
                ));
        
        
        
        pFactory::setDatabaseConnection(self::$db);

        $event = pFactory::createPhabric('event', $parameters['Phabric']['entities']['event']);

        $this->phabricBus = pFactory::getBus();

        $this->phabricBus->registerNamedDataTranslation(
                'UKTOMYSQLDATE',
                function($date){
                    $date = \DateTime::createFromFormat('d/m/Y H:i', $date);
                    return $date->format('Y-m-d H:i:s');
                }
        );

    }
    
    /**
     * @BeforeScenario
     */
    public function functionsetDB()
    {
        $sql = file_get_contents(__DIR__ . '/../../fixture.sql');
        self::$db->query($sql);
    }

    /**
     * @BeforeSuite
     */
    public static function prepare(SuiteEvent $event)
    {

    }

    /**
     * @Given /^The following events exist$/
     */
    public function theFollowingEventsExist(TableNode $table)
    {
        $tableData = $table->getRows();

        $eventPh = $this->phabricBus->getEntity('event');
        $eventPh->create($tableData);
    }

    /**
     * @When /^I select all records from the event table$/
     */
    public function iSelectAllRecordsFromTheEventTable()
    {
        $sql = 'SELECT * FROM event';

        $rows = self::$db->fetchAll($sql);

        $this->qResult = $rows;
    }

    /**
     * @Then /^I should see the following records$/
     */
    public function iShouldSeeTheFollowingRecords(TableNode $table)
    {
        // Get the col names
        $topRow = reset($this->qResult);

        // Col names - id
        $cols = array_keys($topRow);
        array_shift($cols);

        $actualResults = array($cols);

        foreach($this->qResult as $row)
        {
            // Remove the id from the results
            array_shift($row);
            $actualResults[] = array_values($row);
        }

        $expectedResults = $table->getRows();

        assertEquals($expectedResults, $actualResults);
    }

}
