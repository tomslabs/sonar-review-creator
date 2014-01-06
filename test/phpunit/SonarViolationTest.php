<?php

class SonarViolationTest extends PHPUnit_Framework_TestCase {

  private $violationId = 22915521;
  private $violationLineNumber = 249;
  private $violatedFullFilePath = "lib/helper/TomsLabsPager.class.php";
  private $sonarViolation;
  private $sonarQubeClient;
  
  public function setUp() {
    $this->sonarQubeClient = $this->getMock('SonarQubeClient', array('executeCreateReview'));
    $this->sonarViolation = new SonarViolation($this->sonarQubeClient, $this->violationId, $this->violationLineNumber, $this->violatedFullFilePath);
  }
  
  /** @test */
  public function getAnnotationFromViolatedFileAndLineNumber() {
    $sourceDirectory = "/home/tomslabs/workspace/sonar-review-creator/app";
    $sonarViolation = $this->mockSonarViolationToExecGitBlameAndStubLdapMatcher();
    $sonarViolation->computeAssignee($sourceDirectory);
    assertThat($sonarViolation->getAssignee(), equalTo('smartin'));
  }
  
  private function mockSonarViolationToExecGitBlameAndStubLdapMatcher() {
    $sonarViolation = $this->getMock('SonarViolation', array('executeGitBlameCommand'), array(), '', false);
    $sonarViolation->expects($this->once())
                   ->method('executeGitBlameCommand')
                   ->will($this->returnValue('4463b322 (Sébastien M 2013-10-23 10:49:10 +0200 249)   private static function getMarkupWhenLayer($page, $pageQuantity, $maxPageFirstLine, $route, $routeOptions, $pagerId, $fi'));    
    
    $ldapUserAliasesMatcher = $this->getMock('LdapUserAliasesMatcher', array('getLdapAliasesFile'));
    $ldapUserAliasesMatcher->expects($this->once())
                           ->method('getLdapAliasesFile')
                           ->will($this->returnValue(dirname(__FILE__).'/_fixtures/ldap-aliases-for-tests.json'));    
    
    $sonarViolation->setLdapUserAliasesMatcher($ldapUserAliasesMatcher);
    return $sonarViolation;
  }
  
  /** @test */
  public function getDeveloperFromGitBlameOutput() {
    $blameOutput = "4463b322 (Sébastien M 2013-10-23 10:49:10 +0200 249)   private static function getMarkupWhenLayer(";
    
    $assignee = $this->sonarViolation->extractDeveloperFromGitBlameOutput($blameOutput);
    assertThat($assignee, equalTo("Sébastien M"));
  }
  
  /** @test */
  public function createReviewForPickedViolation() {
    $sonarViolation = new SonarViolation($this->sonarQubeClient, $this->violationId, $this->violationLineNumber, $this->violatedFullFilePath);
    $sonarViolation->setAssignee('Bernard');
    $sonarViolation->createReview();
  }
  
}