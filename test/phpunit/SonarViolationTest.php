<?php

class SonarViolationTest extends PHPUnit_Framework_TestCase {

  private $sonarQubeClient;
  private $sonarViolation;
  private $phpStdClassViolation;
  
  public function setUp() {
    $this->sonarQubeClient = $this->getMock('SonarQubeClient', array('executeCreateReview'), array(), '', false);
    $this->phpStdClassViolation = $this->newPhpStdClassViolation();
    $this->sonarViolation = new SonarViolation($this->sonarQubeClient, $this->phpStdClassViolation);
  }
  
  /** @test */
  public function sonarViolationAttributes() {
    $violation = $this->phpStdClassViolation;
    assertThat($this->sonarViolation->getId(), equalTo($violation->id));
    assertThat($this->sonarViolation->getLineNumber(), equalTo($violation->line));
    assertThat($this->sonarViolation->getFileName(), equalTo($violation->resource->name));
    assertThat($this->sonarViolation->getFileFullKey(), equalTo($violation->resource->key));
    assertThat($this->sonarViolation->getLanguage(), equalTo($violation->resource->language));
  }
  
  private function newPhpStdClassViolation() {
    $violationRule = (object) array(
        'key' => 'phppmd_rules:Code Size Rules/NPathComplexity',
        'name' => 'Class NPath Complexity exceeds maximum'
    );
    
    $violationResource = (object) array(
        'key' => 'com.tomslabs.tools:sonar-review-creator:modules/thirdParty/actions/components.class.php',
        'name' => 'components.class.php',
        'scope' => 'FIL',
        'qualifier' => 'FIL',
        'language' => 'php'
    );
    
    $violation = (object) array(
        'id' => 22978143, 
        'message' => 'The method defineGuaVariables() has an NPath complexity of 15625. The configured NPath complexity threshold is 200.',
        'line' => 251,
        'priority' => 'CRITICAL',
        'createdAt' => '2014-01-10T11:07:27+0100',
        'rule' => $violationRule,
        'resource' => $violationResource);    
    return $violation;
  }

  private function newJavaStdClassViolation() {
      $violationRule = (object) array(
          'key' => "pmd:UnusedLocalVariable",
          'name' => "Unused local variable"
      );

      $violationResource = (object) array(
          'key' => "com.bom.fe:tagPages-webservice:com.bom.fe.tagpages.TagPagesController",
          'name' => 'TagPagesController',
          'scope' => 'FIL',
          'qualifier' => 'CLA',
          'language' => 'java'
      );

      $violation = (object) array(
          'id' => 22970858,
          'message' => "Avoid unused local variables such as 'fakeVarToTriggerViolation'.",
          'line' => 61,
          'priority' => 'MAJOR',
          'createdAt' => '2014-01-15T11:07:27+0100',
          'rule' => $violationRule,
          'resource' => $violationResource);
      return $violation;
  }

  /** @test */
  public function findPathToPhpViolatedFile() {
    $sourceDirectory = "/home/tomslabs/workspace/sonar-review-creator/app";
    $sonarViolation = $this->getMock('SonarViolation', array('find', 'changeToDirectory'), array(), '', false);
    $sonarViolation->expects($this->once())
                   ->method('find')
                   ->will($this->returnValue(array('./modules/thirdParty/actions/components.class.php')));     
    $sonarViolation->computeFileNameFullPath($sourceDirectory);
    assertThat($sonarViolation->getFileNameFullPath(), equalTo('./modules/thirdParty/actions/components.class.php'));
  }

  /** @test */
  public function findPathToJavaViolatedFile() {
    $sourceDirectory = "/home/tomslabs/worspace/javaApp";
    $sonarViolation = $this->getMock('SonarViolation', array('find', 'changeToDirectory'), array(), '', false);
    $sonarViolation->expects($this->once())
        ->method('find')
        ->will($this->returnValue(array('./webservice/src/main/java/com/bom/tomslabs/javaApp/appController.java')));
    $sonarViolation->computeFileNameFullPath($sourceDirectory);
    assertThat($sonarViolation->getFileNameFullPath(), equalTo('./webservice/src/main/java/com/bom/tomslabs/javaApp/appController.java'));
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
    $this->sonarViolation->setAssignee('Bernard');
    $this->sonarViolation->createReview();
  }
  
}