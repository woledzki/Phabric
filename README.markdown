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

| id | ev_name | ev_desc           | ev_date             | ev_soldout |
| 1  | PHPNW   | A hella cool gig! | 2011-10-08 09:00:00 | NO         |
| 2  | PHPUK   | A great event!    | 2012-02-26 09:00:00 | NO         |

</pre>

We decide we would like to describe it using a Gherkin table as follows:

<pre>

Given the following event exists:

| Name  | Description       | Start Date      | 
| PHPNW | A hella cool gig! | 08/10/2011 9:00 | 
| PHPUK | A great event!    | 26/02/2011 9:00 |

</pre>
*Note*: The column ev_sold out will have a default value of 'No' unless 
otherwise specified.

The table above models the data in a more business friendly way and abstracts 
away the underlying database implimentation. Testers, BA's and developers can 
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

First create an entity:

    $event = pFactory::createPhabric('event');
