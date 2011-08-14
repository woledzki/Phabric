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

$phaLoader = new \Doctrine\Common\ClassLoader('Phabric', realpath(__DIR__ . '/../../../lib/'));
$phaLoader->register();

$docLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', __DIR__ . '/../../../lib/Vendor/Doctrine/lib');
$docLoader->register();

$docComLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', __DIR__ . '/../../../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib');
$docComLoader->register();


/**
 * Features context.
 */
class FeatureContext extends BehatContext {

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
    public function __construct(array $parameters) {
        
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
        $attendee = pFactory::createPhabric('attendee', $parameters['Phabric']['entities']['attendee']);
        $session = pFactory::createPhabric('session', $parameters['Phabric']['entities']['session']);
        $vote = pFactory::createPhabric('vote', $parameters['Phabric']['entities']['vote']);

        $this->phabricBus = pFactory::getBus();

        $this->phabricBus->registerNamedDataTranslation(
                'UKTOMYSQLDATE', function($date) {
                    $date = \DateTime::createFromFormat('d/m/Y H:i', $date);
                    return $date->format('Y-m-d H:i:s');
                }
        );

        $this->phabricBus->registerNamedDataTranslation(
                'ATTENDEELOOKUP', function($attendeeName, $bus) {
                    $ent = $bus->getEntity('attendee');

                    $id = $ent->getNamedItemId($attendeeName);

                    return $id;
                });

        $this->phabricBus->registerNamedDataTranslation(
                'SESSIONLOOKUP', function($sessionName, $bus) {
                    $ent = $bus->getEntity('session');

                    $id = $ent->getNamedItemId($sessionName);
                    
                    return $id;
                });

        $this->phabricBus->registerNamedDataTranslation(
                'UPDOWNTOINT', function($action) {
                    $action = strtoupper($action);
                    switch ($action) {
                        case 'UP':
                            return +1;
                            break;
                        case 'DOWN':
                            return -1;
                        case 'NO VOTE':
                            return 0;
                    }
                });
    }

    /**
     * @BeforeScenario
     */
    public function functionsetDB() {
        $sql = file_get_contents(__DIR__ . '/../../fixture.sql');
        self::$db->query($sql);
    }

    /**
     * @BeforeSuite
     */
    public static function prepare(SuiteEvent $event) {
        
    }

    /**
     * @Given /^The following events exist$/
     */
    public function theFollowingEventsExist(TableNode $table) {
        $tableData = $table->getRows();

        $eventPh = $this->phabricBus->getEntity('event');
        $eventPh->create($tableData);
    }

    /**
     * @When /^I select all records from the event table$/
     */
    public function iSelectAllRecordsFromTheEventTable() {
        $sql = 'SELECT * FROM event';

        $rows = self::$db->fetchAll($sql);

        $this->qResult = $rows;
    }

    /**
     * @Then /^I should see the following records$/
     */
    public function iShouldSeeTheFollowingRecords(TableNode $table) {
        // Get the col names
        $topRow = reset($this->qResult);

        // Col names - id
        $cols = array_keys($topRow);
        array_shift($cols);

        $actualResults = array($cols);

        foreach ($this->qResult as $row) {
            // Remove the id from the results
            array_shift($row);
            $actualResults[] = array_values($row);
        }

        $expectedResults = $table->getRows();

        assertEquals($expectedResults, $actualResults);
    }
    
        /**
     * @Given /^the following sessions exist$/
     */
    public function theFollowingSessionsExist(TableNode $table)
    {
        $tableData = $table->getRows();

        $sesPh = $this->phabricBus->getEntity('session');
        $sesPh->create($tableData);
    }

    /**
     * @Given /^the following attendees exist$/
     */
    public function theFollowingAttendeesExist(TableNode $table)
    {
        $tableData = $table->getRows();

        $attePh = $this->phabricBus->getEntity('attendee');
        $attePh->create($tableData);
    }

    /**
     * @Given /^the following votes exist$/
     */
    public function theFollowingVotesExist(TableNode $table)
    {
        $tableData = $table->getRows();

        $attePh = $this->phabricBus->getEntity('vote');
        $attePh->create($tableData);
    }
    
    /**
     * @Then /^the session "([^"]*)" should have a score of (\d+)$/
     */
    public function theSessionShouldHaveAScoreOf($session, $score)
    {
        $sesPh = $this->phabricBus->getEntity('session');
        $sessionId = $sesPh->getNamedItemId($session);
        
        $sql = 'SELECT sum(vote) as votes FROM vote WHERE session_id = :id';
        $stmt = self::$db->prepare($sql);
        $stmt->bindValue(':id', $sessionId);
        
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        assertequals($score, $result[0]);
    }

}
