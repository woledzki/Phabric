Phabric
=======

A tool that translates Gherkin Tables into database inserts / updates.

It's for use with the BDD library Behat which can be found at: http://behat.org/

The aim of this project is to allow the user to define fixtures 'on the fly' 
using Gherkin tables without having to undergo the painful process of 
maintaining an SQL file used to drive Behat test suites.

Introduction
============

When adopting Behat at the company I work for we quickly found that in order to 
write clear tests we needed to set the state of the database in scenarios rather
than in monolithic fixture files. 

Problems with fixture files: 

* They are difficult to maintain
* It's easy to wreck an existing test by modifying it's data in the fixture
* The semantics of the data are lost in the fixture file rather than being 
explicitly stated in a scenario.


The solution we settled on was to load an initial fixture containing just the 
basic DB structure and to define all the data in Gherkin tables within the 
scenarios of our test.

Enter Phabric... 


Phabric
=======

Phabric allows the user to mark up data for insertion into the database in a 
scenario. Like So:

<pre>

Given The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |

</pre>

To make the data as readable as possible Phabric supports the following:

**Column Name Translations** - You can map the name of a column in Gherkin to a 
database column name. EG Desc > conf_description

**Column Data Translations** - You can translate the data in the column by 
registering functions. EG 08/10/2011 09:00 > 2011-10-08 09:00:00

**Default Values** - You can assign default values to columns so you do not have
to explicitly include them in the gherkin.

**Relational data** is supported. EG An Event with many Attendees

The aim of these features is to assist the user in setting up a scenario in a 
readable and maintainable way. It should facilitate behaviour driven development
as once the initial creator steps have been anyone will be able to mark up 
entities in your system (tester and BA's included!).  

Preview
------- 

The documentation below outlines how to configure and use Phabric. Here is a 
quick preview of whats achievable when Phabric is installed, configured and 
running:

The scenario:

<pre>

Scenario:
    Given The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |

</pre>

*Note:* The example contains name and data translations.

The step:

    /**
     * @Given /^The following events exist$/
     */
    public function theFollowingEventsExist(TableNode $table) {
        $tableData = $table->getRows();

        $eventPh = $this->phabricBus->getEntity('event');
        $eventPh->create($tableData);
    }


The database table after data creation: 

<pre>
    | name  | datetime            | venue                  | description      |
    | PHPNW | 2011-10-08 09:00:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 2012-02-27 09:00:00 | London Business Center | Quite good conf. |
</pre>

*Note:* Gherkin column names mapped to database coulm names and some data 
(datetime) transformed.

For those keen on doing rather than reading there are working examples in the 
'examples' folder. See section below for instructions on setting up the 
examples.


DOCS
====

Install
-------

Currently the only supported method of installing Phabric is via git.

Clone the git hub repository onto your local machine.

* git clone git@github.com:benwaine/Phabric.git

Change directory into the newley cloned repository.

* cd Phabric/

Phabric has a number of dependencies these can be met by initializing the 
following submodules: 

* git submodule init lib/Vendor/mockery/
* git submodule init lib/Vendor/Doctrine/
* git submodule update --recursive

Then Doctrines submodules

* cd lib/Vendor/Doctrine/

* git submodule init lib/vendor/doctrine-common/
* git submodule init --recursive


Setting Up Phabric 
------------------

Phabric requires some setting up in the main feature context file of your behat 
tests folder.

Phabric makes use of the Doctrine DBAL. This allows support for many databases 
'out of the box'. Most popular databases are supported including MySQL, Oracle 
and MSSQL.

**Autoloading**
Classes are loaded using the Doctrine Projects autoloader.

Doctrine and Phabric Classes need to be registered with the auto loader in the 
Feature Contexts File:

```PHP
    require_once __DIR__ . '/PATH/TO/PHABRIC/lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

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
    
    public function __construct(array $parameters) {

    }

    // Rest of feature file.... 
```PHP

A Doctrine DBAL connection (database connection class) needs to be created and assigned the Phabric\Factory,
this class manages your interactions with Phabric. Database connection parameters
should be added to your behat.yml config file.

<pre>
default:
  context:
    class: 'FeatureContext'
    parameters:
      database:
        username: 'root'
        password: ''
        dbname:   'behat-demo'
        host:     '127.0.0.1'
        driver:   'pdo_mysql'

 </pre>

Creating the DBAL Connections and setting it as the database connection used by 
Phabric:

    public function __construct(array $parameters) {

        $config = new \Doctrine\DBAL\Configuration();

        self::$db = \Doctrine\DBAL\DriverManager::getConnection(array(
                    'dbname' => $parameters['database']['dbname'],
                    'user' => $parameters['database']['username'],
                    'password' => $parameters['database']['password'],
                    'host' => $parameters['database']['host'],
                    'driver' => $parameters['database']['driver'],
                ));



        \Phabric\Factory::setDatabaseConnection(self::$db);

    }

This should be all the setup required to use Phabric. We can now define Phabric 
entities, these represent the mapping between Gherkin tables of data and data in
our database.

Phabric Entities
================

A Phabric entity encapsulates the mapping between a Gherkin table and a database 
table.

There are two ways to configure a Phabric entity: Programmatically and by using 
a Configuration file. This documentation will show both methods. Those who 
prefer cleaner feature files with less set up should consider using the 
configuration based approach.

The Phabric Factory Class & Phabric Bus
---------------------------------------

The bus is an object which is injected into each instance of a Phabric entity.
It enables communication between each entity in a clean and testable way. 

A factory is used to obtain Phabric instances, in the background the bus is 
configured and injected each time the factory is is used to obtain an entity.

Before using the factory you must set the database connection to use 
(as in the previous example and assuming autoloading is set up).


    $config = new \Doctrine\DBAL\Configuration();

    self::$db = \Doctrine\DBAL\DriverManager::getConnection(array(
                'dbname' => $parameters['database']['dbname'],
                'user' => $parameters['database']['username'],
                'password' => $parameters['database']['password'],
                'host' => $parameters['database']['host'],
                'driver' => $parameters['database']['driver'],
            ));



    \Phabric\Factory::setDatabaseConnection(self::$db);


Example Domain
--------------

For the purposes of the following examples we will use the database 
tables and Gherkin below.

The system we are modeling describes events.

An event database table exists:

Database Table:

<pre>

| id | ev_name | ev_desc           | ev_date             | ev_disp |
| 1  | PHPNW   | A hella cool gig! | 2011-10-08 09:00:00 | 1       |
| 2  | PHPUK   | A great event!    | 2012-02-26 09:00:00 | 0       |

</pre>

We decide we would like to describe it using a Gherkin table as follows:

<pre>

Given the following event exists:

| Name  | Description       | Start Date      | 
| PHPNW | A hella cool gig! | 08/10/2011 9:00 | 
| PHPUK | A great event!    | 26/02/2011 9:00 |

</pre>
*Note*: The column 'ev_disp' will have a default value of 1 unless 
otherwise specified.

The table above models the data in a more business friendly way and abstracts 
away the underlying database implementation. Testers, BA's and developers can 
now concentrate on modeling the data in the context of the business case rather 
than the database. 

Creating an Entity
------------------

With the factory set up you can now obtain Phabric entity instances like so:

    $event = pFactory::createPhabric('event', $config);

This creates and returns a entity with the name 'event'. The second argument 
$config is optional. Supplying it will configure the instance according to the 
configuration provided. 

*Note*: An Explanation of $config parameters will be provided in the following
sections.


Column Name Translations
------------------------

The goal of column name translations is to change often ugly looking database 
column names to human readable and business friends names.

In this example we want to change column names like 'ev_name' and 
'ev_description' to the more friendly 'Name' and 'Description'.

First create an entity:

    $event = pFactory::createPhabric('event');

The register some column name translations:

    $event->setNameTranslations(array(
                                'Name' => 'ev_name',
                                'Description' => 'ev_description',
                                'Start Date' => 'ev_date',
                                'Displayed' => 'ev_disp'
                                ));

** Important: Column Name translations get applied first. When referencing 
columns in subsequent methods / configs use the database column name ** 

Column Data Translations
------------------------

In the same way it is preferable to represent column names as human readable and
business friendly as possible we should also represent the data in the column in
the same manner. 

In this example it is preferable to use and English representation of the date 
rather than a MySQL date time (08/10/2011 9:00 > 2011-10-08 09:00:00). Also
in the Sold Out field 'YES' and 'NO' can be used to represent '0' and '1'.

This is achieved by registering closures with the Phabric bus (so every Phabric 
instance can share the functionality defined in them). The closures are 
registered against a name. The closure name and the name of the column to be 
translated is then registered with the entity representing the table. 

First get the Phabric bus:
    
    // In a FeatureContext Constructor
    $this->phabricBus = pFactory::getBus(); 

Register a closure with the bus a name. Conventionally names are in CAPS.
The closure accepts the data from a column and returns its translated form. 

    $this->phabricBus->registerNamedDataTranslation(
                'UKTOMYSQLDATE', function($date) {
                    $date = \DateTime::createFromFormat('d/m/Y H:i', $date);
                    return $date->format('Y-m-d H:i:s');
                }
        );

Then register the translation(s) with the entity.

    $event->setDataTranslations(array(
                                'ev_date' => 'UKTOMYSQLDATE'
                                ));

*Note* The use of the real database column name when registering data 
translation closures.

** Important: Registration of closures with the bus and registering translations
can be carried out in any order. However, remember that bus registration must 
occur before data translation actually occurs. **

Column Default Values
---------------------

Default values can be useful to reduce the number of columns in the Gherkin 
representation of the database table data. 

In this example the 'ev_disp' column in the database is used to indicate if a 
an event should be displayed on the fron end of the application. By default we 
would like to set this to 1 (events should be displayed). We can always override 
this by including the column in our Gherkin.

Defaults are set using an array of database column names and values:


    $event->setDefaults(array(
                        'ev_disp' => 1
                        ));


To override the default ensure a name translation is set up (optionally 
with a data translation) and include the column in the Gherkin table.

    $event->setNameTranslations(array(
                                'Name' => 'ev_name',
                                'Description' => 'ev_description',
                                'Start Date' => 'ev_date',
                                'Displayed' => 'ev_disp'
                                ));
    
    $this->phabricBus->registerNamedDataTranslation(
                'YESNOFLAG', function($ynFlag) {
                    switch($ynFlag) {
                    case 'YES':
                        return 1;
                    case 'NO':
                        return 0;
                    default:
                        throw new \Exception('Invalid Y/N Flag. Use: YES or NO);
                    } 
                },
                'UKTOMYSQLDATE', function($date) {
                    $date = \DateTime::createFromFormat('d/m/Y H:i', $date);
                    return $date->format('Y-m-d H:i:s');
                }
        );
    
    $event->setDataTranslations(array(
                                'ev_date' => UKTOMYSQLDATE
                                'ev__disp' => 'YESNOFLAG'
                                ));


<pre>

| Name  | Description       | Start Date      | Displayed |
| PHPNW | A hella cool gig! | 08/10/2011 9:00 | YES       |
| PHPUK | A great event!    | 26/02/2011 9:00 | NO        |

</pre>


Inserting Data
==============

With a Phabric entity set up it's now possible to translate data from a Gherkin
table into the database. 


Inserting Unrelated Data (Basic Insert)
---------------------------------------

From a Behat feature file:

<pre>

Scenario:
    Given The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |

</pre>

And in the corresponding Behat step:

    
    /**
     * @Given /^The following events exist$/
     */
    public function theFollowingEventsExist(TableNode $table) {
        
        // Get the Raw gherkin data in array form.
        $tableData = $table->getRows();
        

        // Get the event entity from the Phabric bus.
        $eventPh = $this->phabricBus->getEntity('event');
        
        // Pass in the raw table data to the create method to create a DB record
        $eventPh->create($tableData);
    }


Relational Data
---------------

Phabric supports the entry of relational data. For example linking multiple 
attendees to an event.

Internally Phabric keeps track of the database entries it makes. It maps the 
last inserted ID returned from the database to the value of the left most column.

It's possible to access this id by using the 'getNamedItemId()' method on the 
Phabric entity. Id's can be substituted for entity names by registering a 
standard data translation.  

*Note:* When using relational data you should ensure the left most column is 
consistently the same. In this instance 'Name' is the left most column and ID's 
are mapped against this value (EG -'PHPNW' => 1).

In the following example attendees are asked to vote for their favorite session.
The vote database table looks like this:

| id | session_id | attendee_id | vote |
| 1  | 1          | 1           | 1    |

*Note: * The following example just shows relational data functionality. Other 
translations are required on names and data for the example to work.
 
From a Behat feature file: 

<pre>

Scenario: 
    Given the following sessions exist
    | Session Code | name                  | time  | description                               |
    | BDD          | BDD with behat        | 12:50 | Test driven behaviour development is cool |
    | CI           | Continous Integration | 13:30 | Integrate this!                           |
    And the following attendees exist
    | name                  |
    | Jack The Lad          |
    | Simple Simon          |
    | Peter Pan             |
    And the following votes exist
    | Attendee     | Session | Vote | 
    | Jack The Lad | BDD     | UP   |
    | Simple Simon | BDD     | UP   |
    | Peter Pan    | BDD     | UP   |
    | Jack The Lad | CI      | UP   |
    | Simple Simon | CI      | UP   |
    | Peter Pan    | CI      | DOWN |

</pre>


When setting up the Phabric bus data translations are registered for translating
Attendee names and session names with there ID's:

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

And the name and data translations are registered with the vote entity:

    $vote->setNameTranslations(array(
                                'Session' => 'session_id',
                                'Attendee' => 'attendee_id'));

    $vote->setDataTranslations(array(
                                'session_id' => 'SESSIONLOOKUP',
                                'attendee_id' => 'ATTENDEELOOKUP'));

The create() method is used as in the previous example:

    /**
     * @Given /^the following votes exist$/
     */
    public function theFollowingVotesExist(TableNode $table)
    {
        $tableData = $table->getRows();

        $attePh = $this->phabricBus->getEntity('vote');
        $attePh->create($tableData);
    }


Configuration Approach
======================

The previous examples have shown how to configure a Phabric entity 
programmatically. While this is effective it's also very verbose. Phabric 
configuration can be stored in a test suites 'behat.yml' file and used to 
succinctly create and configure entities.

An example of a 'behat.yml' configuration file with Phabric config in:

<pre>

default:
  context:
    class: 'FeatureContext'
    parameters:
      database:
        username: 'root'
        password: ''
        dbname:   'behat-demo'
        host:     '127.0.0.1'
        driver:   'pdo_mysql'
      baseurl: 'http://behat-demo.dev/'
      registry:
        baseurl: 'http://behat-demo.dev/'
        eventsResourceUri: events
        eventsResourceMethod: GET
      Phabric:
        entities:
          event:
            tableName: 'event'
            entityName: 'Event'
            nameTranslations:
              Date: datetime
              Desc: description
            dataTranslations:
              datetime: UKTOMYSQLDATE
          session:
            tableName: 'session'
            entityName: 'Session'
            nameTranslations:
              Session Code: session_code
          attendee:
            tableName: 'attendee'
            entityName: 'Attendee'
          vote:
            tableName: 'vote'
            entityName: 'Vote'
            nameTranslations:
              Attendee: attendee_id
              Session: session_id
            dataTranslations:
              attendee_id: ATTENDEELOOKUP
              session_id: SESSIONLOOKUP
              vote: UPDOWNTOINT

</pre>    

By putting Phabric config under the FeatureContext>Parameters section it is 
available in the $parameters array of the Behat FeatureContext constructor.
This is where all the configuration of the Phabric bus and entities occurs.

As you can see name and data translations, the database table an entity maps to 
and default values can be included in config.

In the constructor of the FeatureContext class:

    $event = pFactory::createPhabric('event', $parameters['Phabric']['entities']['event']);
    $attendee = pFactory::createPhabric('attendee', $parameters['Phabric']['entities']['attendee']);
    $session = pFactory::createPhabric('session', $parameters['Phabric']['entities']['session']);
    $vote = pFactory::createPhabric('vote', $parameters['Phabric']['entities']['vote']);

The factor methods return the Phabric entity instances but they can also be 
retrieved in step methods by using the bus:

    $eventPh = $this->phabricBus->getEntity('event');

Examples
========

Some basic set up is required to run the examples. 

* Set up a database compatible with the database config in the 'behat.yml'


 