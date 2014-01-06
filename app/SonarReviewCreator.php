<?php

class SonarReviewCreator {
  
  private $sonarHost;
  private $project;
  private $sourceDirectory;
  private $priorities;
  private $createdAfter;
  private $depth = -1;
  
  private $sonarQubeClient;
  
  public function __construct() {
    $ini_array = parse_ini_file("sonar-review-creator.ini", true);
    
    $this->sonarHost = $ini_array['sonar']['host'];
    $this->assignerUsername = $ini_array['assigner']['username'];
    $this->assignerPassword = $ini_array['assigner']['password'];    
    
    $this->project = $ini_array['project']['name'];
    $this->priorities = $ini_array['project']['priorities'];
    $this->createdAfter = $ini_array['project']['violationsCreatedAfter'];
    $this->sourceDirectory = $ini_array['project']['sourceDirectory'];    
    
    $this->sonarQubeClient = new SonarQubeClient($this->sonarHost, $this->assignerUsername, $this->assignerPassword);
  }

  public function run() {
    echo "Running SonarReviewCreator for project " . $this->project . "..";
    $nbOfReviewsCreated = 0;
    
    $violations = $this->sonarQubeClient->getViolations($this->project, $this->depth, $this->priorities);
    foreach ($violations as $violation) {
      if($this->violationWasCreatedAfterTheGivenDate($violation->createdAt)) {
        $sonarViolation = $this->newViolation($violation);
        $sonarViolation->computeAssignee($this->sourceDirectory);
        $nbOfReviewsCreated = $nbOfReviewsCreated + $sonarViolation->createReview();
      }
    }
    
    echo $nbOfReviewsCreated . " reviews were created during this run !";
  }
  
  public function violationWasCreatedAfterTheGivenDate($createdAt) {
    return strtotime($createdAt) >= strtotime($this->createdAfter);
  }
  
  private function newViolation($violation) {
    $violationId = $violation->id;
    $violationLineNumber = $violation->line;
    $violatedResource = $violation->resource;
    $violatedFile = $violatedResource->key;
    $violatedFullFilePath = array_pop(explode(':', $violatedFile));
    
    return new SonarViolation($this->sonarQubeClient, $violationId, $violationLineNumber, $violatedFullFilePath);
  }  

  public function getSonarHost() {
    return $this->sonarHost;
  }
  
  public function getAssignerUsername() {
    return $this->assignerUsername;
  }
  
  public function getAssignerPassword() {
    return $this->assignerPassword;
  }  
  
  public function getProjectName() {
    return $this->project;
  }
  
  public function getPriorities() {
    return $this->priorities;
  }
  
  public function getDateCreatedAfter() {
    return $this->createdAfter;
  }
  
  public function getSourceDirectory() {
    return $this->sourceDirectory;
  }

}
