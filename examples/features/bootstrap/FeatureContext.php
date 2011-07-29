<?php

use Behat\Behat\Context\ClosuredContextInterface,
 Behat\Behat\Context\TranslatedContextInterface,
 Behat\Behat\Context\BehatContext,
 Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
 Behat\Gherkin\Node\TableNode,
 Behat\Behat\Event\SuiteEvent;

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';
require_once __DIR__ . '/../../../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
//require __DIR__ . '/../load.php';

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{

    private $eventsPhabric;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param   array   $parameters     context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $phaLoader = new \Doctrine\Common\ClassLoader('Phabric', __DIR__ . '/../../../lib');
        $phaLoader->register();

        $docLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', __DIR__ . '/../../../lib/Vendor/Doctrine/lib');
        $docLoader->register();

        $docComLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', __DIR__ . '/../../../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib');
        $docComLoader->register();

        $config = new \Doctrine\DBAL\Configuration();

        $dbCon = \Doctrine\DBAL\DriverManager::getConnection(array(
                    'dbname' => $parameters['database']['dbname'],
                    'user' => $parameters['database']['username'],
                    'password' => $parameters['database']['password'],
                    'host' => $parameters['database']['host'],
                    'driver' => $parameters['database']['driver'],
                ));

        $this->eventsPhabric = new \Phabric\Phabric($dbCon);
        $this->eventsPhabric->setEntityName('Event');
        $this->eventsPhabric->setTableName('event');

        $this->eventsPhabric->setNameTranslations(array('Date' => 'datetime',
            'Desc' => 'description'));

        $this->eventsPhabric->registerNamedDataTranslation('UKTOMYSQLDATE',
                function($date)
                {
                    $date = \DateTime::createFromFormat('d/m/Y H:i', $date);
                    return $date->format('Y-m-d H:i:s');
                }
        );

        $this->eventsPhabric->setDataTranslations(array('datetime' => 'UKTOMYSQLDATE'));
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
        $this->eventsPhabric->create($tableData);
    }

    /**
     * @When /^I select all records from the event table$/
     */
    public function iSelectAllRecordsFromTheEventTable()
    {
        throw new PendingException();
    }

    /**
     * @Then /^I should see the following records$/
     */
    public function iShouldSeeTheFollowingRecords(TableNode $table)
    {
        throw new PendingException();
    }

}
