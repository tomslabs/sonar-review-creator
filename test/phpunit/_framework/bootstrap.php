<?php 

include_once('AutoLoader.php');
// Register the directory to your include files
AutoLoader::registerDirectory('app');

// composer autoloader
require_once 'lib/vendor/composer/autoload.php';
require_once 'lib/vendor/composer/hamcrest/hamcrest-php/hamcrest/Hamcrest.php';