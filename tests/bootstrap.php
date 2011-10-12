<?php

/**
 * This file is part of the Phabric.
 * (c) Ben Waine <ben@ben-waine.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Mockery Autoloader required mockery to be on the include path.

set_include_path(implode(PATH_SEPARATOR, array(get_include_path(),
        __DIR__ . '/../lib/Vendor/mockery/library/')));

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

