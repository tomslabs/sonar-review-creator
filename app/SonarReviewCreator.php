<?php

class SonarReviewCreator {
  
  const INIFILE_DEFAULT_FULL_PATH = 'app/config/sonar-review-creator.ini';
  
  private $sonarHost;
  private $project;
  private $sourceDirectory;
  private $priorities;
  private $nbDaysBackward;
  private $vcs;
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
    $this->vcs = $ini_array['project']['vcs'];
  }
  
  public function run() {
    echo "\nRunning SonarReviewCreator for project " . $this->project . ". Scanning $this->nbDaysBackward days back..\n";

    $violations = $this->sonarQubeClient->getViolations($this->project, $this->depth, $this->priorities);
    $nbViolations = count($violations);
    echo "\n\nFound $nbViolations eligible violations for review creation..\n";
    if ($nbViolations == 0) {
      exit(1);
    }

    date_default_timezone_set('UTC');
    $createdAfterLimitDate = $this->computeCreateAfterLimitDateFromNbDaysConf($this->nbDaysBackward, new DateTime());
    $nbOfReviewsCreated = 0;
    $nbViolationsCreatedAfterLimitDate = 0;

    foreach ($violations as $violation) {
      if($this->violationWasCreatedAfterTheGivenDate($createdAfterLimitDate, $violation->createdAt)) {
        $nbViolationsCreatedAfterLimitDate = $nbViolationsCreatedAfterLimitDate + 1;
        $sonarViolation = $this->newViolation($violation);
        $sonarViolation->computeFileNameFullPath($this->sourceDirectory);
        $sonarViolation->computeAssignee($this->sourceDirectory, $this->vcs);
        $nbOfReviewsCreated = $nbOfReviewsCreated + $sonarViolation->createReview();
      }
    }
    echo "$nbViolationsCreatedAfterLimitDate violations were created since " . $createdAfterLimitDate->format('Y-m-d');
    echo "\n$nbOfReviewsCreated reviews were created during this run !\n";
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
    return new SonarViolation($this->sonarQubeClient, $violation);
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

  public function getVcs() {
    return $this->vcs;
  }

}
