<?php

namespace Phabric\Datasource;

use Mockery as m;

require_once 'Zend/Db/Adapter/Abstract.php';

class ZendTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->mockedConnection = m::mock('\Zend_Db_Adapter_Abstract');
        $this->object = new Zend($this->mockedConnection);
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }
    
    public function testConstructorInitMappings()
    {
        $input = array(
            'event' => array(
                'tableName' => 't_event',
                'nameCol' => 'name',
                'primaryKey' => 'id'),
            'session' => array(
                'tableName' => 't_session',
                'nameCol' => 'name',
                'primaryKey' => 'id'),
            
        );
        
        $obj = new Zend($this->mockedConnection, $input);
        
        $this->assertEquals($input, $obj->getMappings());
    }
}