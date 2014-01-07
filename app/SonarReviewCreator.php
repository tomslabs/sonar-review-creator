<?php

class SonarReviewCreator {
  
  const INIFILE_DEFAULT_FULL_PATH = 'app/config/sonar-review-creator.ini';
  
  private $sonarHost;
  private $project;
  private $sourceDirectory;
  private $priorities;
  private $nbDaysBackward;
  private $depth = -1;
  
  private $sonarQubeClient;
  
  public function __construct($iniFileFullPath = self::INIFILE_DEFAULT_FULL_PATH) {
    $this->extractConfsFromIniFile($iniFileFullPath);
    $this->sonarQubeClient = new SonarQubeClient($this->sonarHost, $this->assignerUsername, $this->assignerPassword);
  }
  
  private function extractConfsFromIniFile($iniFileFullPath) {
    try {
      $ini_array = parse_ini_file($iniFileFullPath, true);
    } catch (Exception $exc) {
      echo 'File ' . $iniFileFullPath . ' could not be found or parsed. Make sure it is correctly written and suffix -template was removed.';
      echo $exc->getTraceAsString();
    }

    $this->sonarHost = $ini_array['sonar']['host'];
    $this->assignerUsername = $ini_array['assigner']['username'];
    $this->assignerPassword = $ini_array['assigner']['password'];    
    
    $this->project = $ini_array['project']['name'];
    $this->priorities = $ini_array['project']['priorities'];
    $this->nbDaysBackward = $ini_array['project']['nbDaysBackward'];
    $this->sourceDirectory = $ini_array['project']['sourceDirectory'];    
  }
  
  public function run() {
    echo "Running SonarReviewCreator for project " . $this->project . "..";
    $nbOfReviewsCreated = 0;
    
    $violations = $this->sonarQubeClient->getViolations($this->project, $this->depth, $this->priorities);
    $createdAfterLimitDate = $this->computeCreateAfterLimitDateFromNbDaysConf($this->nbDaysBackward, new DateTime());
    if (count($violations) == 0) {
      echo "\n0 violations were found, no reviews will be created.\n";
      exit(1);
    }
    foreach ($violations as $violation) {
      if($this->violationWasCreatedAfterTheGivenDate($createdAfterLimitDate, $violation->createdAt)) {
        $sonarViolation = $this->newViolation($violation);
        $sonarViolation->computeAssignee($this->sourceDirectory);
        $nbOfReviewsCreated = $nbOfReviewsCreated + $sonarViolation->createReview();
      }
    }
    
    echo $nbOfReviewsCreated . " reviews were created during this run !";
  }
  
  public function computeCreateAfterLimitDateFromNbDaysConf($nbDaysBackward, $createdAfterLimitDate) {
    $createdAfterLimitDate->setTimezone(new DateTimeZone('UTC'));
    
    $nbDaysBackwardDateInterval = new DateInterval( "P".$nbDaysBackward."D" );
    $nbDaysBackwardDateInterval->invert = 1; //Make it negative. 
    
    return $createdAfterLimitDate->add($nbDaysBackwardDateInterval);
  }
  
  public function violationWasCreatedAfterTheGivenDate($createdAfterLimitDate, $createdAt) {
    $violationCreatedDate = new DateTime($createdAt, new DateTimeZone('UTC'));
    return $violationCreatedDate >= $createdAfterLimitDate;
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
  
  public function getNbDaysBackward() {
    return $this->nbDaysBackward;
  }
  
  public function getSourceDirectory() {
    return $this->sourceDirectory;
  }

}
