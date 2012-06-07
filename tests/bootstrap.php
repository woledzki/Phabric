<?php
/**
 * This file is part of the Phabric.
 * (c) Ben Waine <ben@ben-waine.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

date_default_timezone_set('UTC');

// include proper autoloader
if (file_exists(__DIR__ . '/autoloader.php'))
{
    require_once 'autoloader.php';
}
else if (file_exists(__DIR__ . '/autoloader.dist.php'))
{
    require_once 'autoloader.dist.php';
}

