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