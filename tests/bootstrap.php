<?php

require_once __DIR__ . '/../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

require_once __DIR__ . '/../lib/Vendor/mockery/library/Mockery/Loader.php';

// Mockery Autoloader required mockery to be on the include path.

set_include_path(implode(PATH_SEPARATOR, array(get_include_path(),
                                               __DIR__ . '/../lib/Vendor/mockery/library/')));

$loader = new \Mockery\Loader;
$loader->register();

$phaLoader= new \Doctrine\Common\ClassLoader('Phabric', __DIR__ . '/../lib');
$phaLoader->register();

$docLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', __DIR__ . '/../lib/Vendor/Doctrine/lib');
$docLoader->register();

$docComLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', __DIR__ . '/../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib');
$docComLoader->register();
