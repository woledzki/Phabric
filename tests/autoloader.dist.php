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

$vendorDir = realpath(__DIR__ . '/../vendor/');
if (file_exists("{$vendorDir}/autoload.php")) {
    // use vendors installed with composer
    require_once "{$vendorDir}/autoload.php";
} else {
    throw new Exception("`autoload.php` not found in `{$vendorDir}`");
}
