<?php

class SonarReviewCreatorTest extends PHPUnit_Framework_TestCase {
  
  private $sonarReviewCreator;
  private $projectViolationsJson;
  
  public function setUp() {
    $this->sonarReviewCreator = new SonarReviewCreator(dirname(__FILE__).'/_fixtures/sonar-review-creator.ini');
    $this->projectViolationsJson = $this->readJsonResultFromFile('_fixtures/projectViolationsJson.json');
  }
  
  private function readJsonResultFromFile($fileName) {
    $file = dirname(__FILE__).'/'.$fileName;
    return file_get_contents($file);  
  }  
  
  /** @test */
  public function getConfFromIniFile() {
    assertThat($this->sonarReviewCreator->getSonarHost(), equalTo('sonar.mycompany.com'));
    assertThat($this->sonarReviewCreator->getAssignerUsername(), equalTo('sonaradmin'));
    assertThat($this->sonarReviewCreator->getAssignerPassword(), equalTo('password'));
    
    assertThat($this->sonarReviewCreator->getProjectName(), equalTo('com.tomslabs.tools:sonar-review-creator'));
    assertThat($this->sonarReviewCreator->getPriorities(), equalTo('BLOCKER,CRITICAL,MAJOR'));
    assertThat($this->sonarReviewCreator->getNbDaysBackward(), equalTo(1));
    assertThat($this->sonarReviewCreator->getSourceDirectory(), equalTo('/home/tomslabs/workspace/sonar-review-creator'));
    assertThat($this->sonarReviewCreator->getCodeLanguage(), equalTo('php'));
  }  

  /** @test */
  public function givenPhpProjectThenComputeViolationFullFilePath() {
    $violationFile = "com.tomslabs.tools:sonar-review-creator:lib/helper/TomsLabsPager.class.php";
    assertThat($this->sonarReviewCreator->computeViolationFullFilePath('php', $violationFile), equalTo('lib/helper/TomsLabsPager.class.php'));
  }

  /** @test */
  public function givenJavaProjectThenComputeViolationFullFilePath() {
    $violationFile = "com.tomslabs.de:toms-webservice:com.tomslabs.de.toms.TomsController";
    assertThat($this->sonarReviewCreator->computeViolationFullFilePath('java', $violationFile), equalTo('webservice/src/main/java/com/tomslabs/de/toms/TomsController.java'));
  }

  /** @test */
  public function computeCreateAfterLimitDateFromNbDaysConf() {
    $createAfterLimitDate = $this->sonarReviewCreator->computeCreateAfterLimitDateFromNbDaysConf(4, new DateTime('2013-10-24', new DateTimeZone('UTC')));
    assertThat($createAfterLimitDate->format('Y-m-d'), equalTo("2013-10-20"));
  }
  
  /** @test */
  public function retrieveViolationsCreatedAfterTheGivenDate() {
    $violations = json_decode($this->projectViolationsJson);
    $createdAfterLimitDate = new DateTime('2013-10-24', new DateTimeZone('UTC'));
    
    $violationsCreatedAfter = array();
    foreach ($violations as $violation) {
      if ($this->sonarReviewCreator->violationWasCreatedAfterTheGivenDate($createdAfterLimitDate, $violation->createdAt)) {
        array_push($violationsCreatedAfter, $violation);
      }
    }
    
    assertThat(count($violationsCreatedAfter), equalTo(1));
  }
  
  /** @ignore !!!! activating it will create reviews for real */
  public function runSonarReviewCreatorForTests() {
    $this->sonarReviewCreator->run();
  }  
}