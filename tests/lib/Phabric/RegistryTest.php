<?php
namespace Phabric;

/**
 * This file is part of the Phabric.
 * (c) Ben Waine <ben@ben-waine.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Test class for Registry.
 *
 * @author Wojtek Oledzki <wojtek@hoborglabs.com>
 */
class RegistryTest extends \PHPUnit_Framework_TestCase {

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->fixture = new Registry();
    }

    protected function exampleValues() {
        $obj = new \stdClass();
        $obj->name = 'Lorem Ipsum';

        return array(
            'a' => 'A',
            1 => 'one',
            2 => 'two',
            'array' => array(1, 2, 3),
        );
    }

    public function testConstructor() {
        $testValues = $this->exampleValues();
        $data = array(
            'test1' => $testValues,
        );

        $reg = new Registry();
        $this->assertNull($reg->get('test1', 'a'), 'empty registry should return null');

        $reg = new Registry($data);
        $this->assertEquals('A', $reg->get('test1', 'a'));
    }

    public function testChaining() {
        $actual = $this->fixture->add('t', 'x', 'y')
                ->add('t', 'a', 'b')
                ->get('t', 'x');

        $this->assertEquals('y', $actual);
    }

    /**
     *
     * @dataProvider registriesProvider
     */
    public function testLookup($registries) {
        foreach ($registries as $registryName => $values) {
            foreach ($values as $key => $value) {
                $this->fixture->add($registryName, $key, $value);
            }
        }

        foreach ($registries as $registryName => $values) {
            foreach ($values as $key => $value) {
                $this->assertEquals(
                    $value,
                    $this->fixture->get($registryName, $key)
                );
            }
        }
    }

    public function registriesProvider() {
        $exampleData = $this->exampleValues();

        return array(
            array(
                array(
                    'test_one' => $exampleData,
                )
            ),
            array(
                array(
                    1 => $exampleData,
                    2 => $exampleData,
                )
            )
        );
    }
}
