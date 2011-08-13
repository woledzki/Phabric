Phabric
=======

A tool that translates Gherkin Tables into database inserts / updates.

It's for use with the BDD library Behat which can be found at: http://behat.org/

The aim of this project is to allow the user to define fixtures 'on the fly' 
using Gherkin tables without having to undergo the painful process of 
maintaining an SQL file used to drive Behat test suites.
 

Install
=======

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


DOCS
====

Setting Up Phabric 
------------------

Phabric requires some setting up in the main feature context file of your behat 
tests folder.

Phabric makes use of the Doctrine DBAL. This allows support for many databases 
'out of the box'. Most popular databases are supported including MySQL, Oracle 
and MSSQL.

**Autoloading**
Classes are loaded using the Doctrine Projects autoloader.

This needs to be included and set up: 


    require_once __DIR__ . '/PATH/TO/PHABRIC/lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';


Doctrine and Phabric Classes need to be registered with the auto loader in the 
Feature Contexts Constructor:

    public function __construct(array $parameters) {

        $phaLoader = new \Doctrine\Common\ClassLoader('Phabric', realpath(__DIR__ . '/../../../lib/'));
        $phaLoader->register();

        $docLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', __DIR__ . '/../../../lib/Vendor/Doctrine/lib');
        $docLoader->register();

        $docComLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', __DIR__ . '/../../../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib');
        $docComLoader->register();
    }

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



        \Phabric\Factory::setDatabaseConnection(self::$db);

    }

This should be all the setup required to use Phabric. We can now define Phabric 
entities, these represent the mapping between Gherkin tables of data and data in
our database.

Phabric Entities
================

A Phabric entity encapsulates the mapping between a Gherkin table and a database 
table.

There are two ways to configure a Phabric entity: Programmatically and by using a
Configuration file. This documentation will show both methods. Those who prefer
cleaner feature files with less set up should consider using the configuration 
based approach.

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
column names to human readbable and business friends names.

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
the same mannor. 

In this example it is preferable to use and english representation of the date 
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


Relational Data
---------------
