sonar-review-creator
====================

Php project using SonarQube API to create and assign reviews for the Sonar violations created after the specified date.<br>
So far, it only works with **Git** projects.

Prerequisites
=============

* Sonar configured with LDAP and SCM Activity plugins

step by step
============

1. Rename `app/config/ldap-aliases-template.json` to `app/config/ldap-aliases.json`

    `[{"smartin":["S\u00e9bastien M","smartin@mycompany.com"]},{"bdupont":["Bernard","bdupont@mycompany.com"]}]`<br>
    `smartin` and `bdupont` are Ldap user identifiers.<br>
    User identifiers are followed by the list of SCM aliases used by the user (cf .gitconfig file).

2. Configure `sonar-review-creator.ini`

3. Run sonar-review-creator : `php runSonarReviewCreator.php`

phpUnit tests
=============

To run phpUnit tests, first install and configure composer.json

1. `curl -s http://getcomposer.org/installer | php`

2. `php composer.phar install`

Then run `tools/composer/bin/phpunit -c test/phpunit/phpunit.xml test/phpunit/`
