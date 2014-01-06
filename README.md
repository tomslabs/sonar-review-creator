sonar-review-creator
====================

Php project using SonarQube API to create and assign reviews for the Sonar violations created after the specified date.

phpUnit tests
=============

To run phpUnit tests, first install and configure composer.json
1. curl -s http://getcomposer.org/installer | php
2. php composer.phar install

Then run 'phpunit -c test/phpunit/phpunit.xml test/phpunit/'
