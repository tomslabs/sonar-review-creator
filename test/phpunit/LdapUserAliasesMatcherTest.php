<?php

class LdapUserAliasesMatcherTest extends PHPUnit_Framework_TestCase {

  /** @test */
  public function getLdapLoginMatchForBlamedDeveloper() {
    $blamedDeveloper = "Bernard";
    
    $ldapUserAliasesMatcher = $this->getMock('LdapUserAliasesMatcher', array('getLdapAliasesFile'));
    $ldapUserAliasesMatcher->expects($this->once())
                           ->method('getLdapAliasesFile')
                           ->will($this->returnValue(dirname(__FILE__).'/_fixtures/ldap-aliases-for-tests.json'));
    
    assertThat($ldapUserAliasesMatcher->getLdapUserFromPickedNickName($blamedDeveloper), equalTo("bdupont"));
  }
  
}