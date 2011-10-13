<?php
/**
 * This file is part of the Phabric.
 * (c) Ben Waine <ben@ben-waine.co.uk>
 *
 * @author Wojtek Oledzki <wojtek@hoborglabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
require_once __DIR__ . '/../lib/Vendor/mockery/library/Mockery/Loader.php';

$loader = new \Mockery\Loader;
$loader->register();

$phaLoader= new \Doctrine\Common\ClassLoader('Phabric', __DIR__ . '/../lib');
$phaLoader->register();

$docLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', __DIR__ . '/../lib/Vendor/Doctrine/lib');
$docLoader->register();

$docComLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', __DIR__ . '/../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib');
$docComLoader->register();

$gherkinLoader = new \Doctrine\Common\ClassLoader('Behat\Gherkin', __DIR__ . '/../vendor/Behat2/vendor/Gherkin/src');
$gherkinLoader->register();

$behatLoader = new \Doctrine\Common\ClassLoader('Behat', __DIR__ . '/../vendor/Behat2/src');
$behatLoader->register();