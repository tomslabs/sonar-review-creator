<?php

include_once('test/phpunit/_framework/AutoLoader.php');
// Register the directory to your include files
AutoLoader::registerDirectory('app');

$sonarReviewCreator = new SonarReviewCreator();
$sonarReviewCreator->run();

?>
