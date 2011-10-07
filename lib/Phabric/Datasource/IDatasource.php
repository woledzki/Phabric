<?php
namespace Phabric\Datasource;
use \Phabric\Entity;
/**
 * This file is part of the Phabric.
 * (c) Ben Waine <ben@ben-waine.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The Datasource interface.
 *
 * @package    Phabric
 * @subpackage Datasource
 * @author     Ben Waine <ben@ben-waine.co.uk>
 */
interface IDatasource
{

    /**
     * Resets the data to it's previous state
     */
    public function reset();

    /**
     * Inserts Data into the data source.
     */
    public function insert(Entity $entity, array $data);

    /**
     * Updates data in the datasource.
     */
    public function update(Entity $entity, array $data);

    /**
     * Delete data from the datasource.
     */
    public function delete($enityName);

    /**
     * Select data from the data.
     */
    public function select();

    /**
     * Gets the unique identifier for previously inserted item by its name.
     */
    public function getNamedItemId(Entity $entity, $name);

}

