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
    
    public function testGetMappingAfterAdd()
    {
        $this->object->addTableMapping('event', 't_event', 'id', 'name');
        
        $expected = array(
            'event' => array(
                'tableName' => 't_event',
                'primaryKey' => 'id',
                'nameCol' => 'name'
            )
        );
        
        $this->assertEquals($expected, $this->object->getMappings());
    }
    
    public function testGetMappingAfterSet()
    {
        $expected = array(
            'event' => array(
                'tableName' => 't_event',
                'primaryKey' => 'id',
                'nameCol' => 'name'
            )
        );
        
        $this->object->setTableMappings($expected);
        
        $this->assertEquals($expected, $this->object->getMappings());
    }
    
    public function testSetTableMappingsOvveridesExistingMappings()
    {
        
        $this->object->addTableMapping('session', 't_session', 'id', 'name');
        
        $expected = array(
            'event' => array(
                'tableName' => 't_event',
                'primaryKey' => 'id',
                'nameCol' => 'name'
            )
        );
        
        $this->object->setTableMappings($expected);
        
        $this->assertEquals($expected, $this->object->getMappings());
    }
    
    public function testAddMappingsAppendsNotOverrides()
    {
        $this->object->addTableMapping('event', 't_event', 'id', 'name');
        $this->object->addTableMapping('session', 't_session', 'id', 'name');
        
        $expected = array(
            'event' => array(
                'tableName' => 't_event',
                'primaryKey' => 'id',
                'nameCol' => 'name'
            ),
            'session' => array(
                'tableName' => 't_session',
                'primaryKey' => 'id',
                'nameCol' => 'name'
            )
        );
        
        $this->assertEquals($expected, $this->object->getMappings());
    }
    
    public function testInsert()
    {
        $mEntity = m::mock('\Phabric\Entity');
        
        $mEntity->shouldReceive('getName')
                ->andReturn('event');
        
        $this->mockedConnection->shouldReceive('query');
        $this->mockedConnection->shouldReceive('lastInsertId')
             ->andReturn(12);
        
        $values = array(
            'name' => 'PHPNW',
            'desc' => 'A Great Conf!',
            'date' => '2011-10-08 12:00:00'
        );
        
        // Set the table mapping
        $this->object->addTableMapping('event', 't_event', 'id', 'name');
        
        $this->assertEquals(12, $this->object->insert($mEntity, $values));
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testInsertOnUnmappedTable()
    {
     $mEntity = m::mock('\Phabric\Entity');
        
        $mEntity->shouldReceive('getName')
                ->withNoArgs()
                ->andReturn('event');
        
        $values = array(
                        'name' => 'PHPNW',
                        'desc' => 'A Great Conf!',
                        'date' => '2011-10-08 12:00:00');
             
        $this->mockedConnection->shouldReceive('query');
        $this->mockedConnection->shouldReceive('lastInsertId')
             ->andReturn(12);
        
        // No mapping added
        
        $this->object->insert($mEntity, $values);   
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testInsertOnDataWithoutNameColumn()
    {
     $mEntity = m::mock('\Phabric\Entity');
        
        $mEntity->shouldReceive('getName')
                ->andReturn('event');
        
        $values = array(
                        'desc' => 'A Great Conf!',
                        'date' => '2011-10-08 12:00:00');
             
        $this->mockedConnection->shouldReceive('query');
        $this->mockedConnection->shouldReceive('lastInsertId')
             ->andReturn(12);
        
        $this->object->addTableMapping('event', 't_event', 'id', 'name');
        
        $this->assertEquals(12, $this->object->insert($mEntity, $values));          
    }
}