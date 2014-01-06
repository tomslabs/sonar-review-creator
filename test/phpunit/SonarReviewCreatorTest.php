<?php

class SonarReviewCreatorTest extends PHPUnit_Framework_TestCase {
  
  private $sonarReviewCreator;
  private $projectViolationsJson;
  
  public function setUp() {
    $this->sonarReviewCreator = new SonarReviewCreator();
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
    assertThat($this->sonarReviewCreator->getDateCreatedAfter(), equalTo('2013-10-24'));
    assertThat($this->sonarReviewCreator->getSourceDirectory(), equalTo('/home/tomslabs/workspace/sonar-review-creator'));
  }  
  
  /** @test */
  public function retrieveViolationsCreatedAfterTheGivenDate() {
    $violations = json_decode($this->projectViolationsJson);
    
    $violationsCreatedAfter = array();
    foreach ($violations as $violation) {
      if ($this->sonarReviewCreator->violationWasCreatedAfterTheGivenDate($violation->createdAt)) {
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