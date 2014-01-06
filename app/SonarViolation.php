<?php

class SonarViolation {
  
  private $lineNumber;
  private $fullFilePath;
  private $assignee;
  private $violationId;
  private $sonarQubeClient;
  private $ldapUserAliasesMatcher;
  
  public function __construct($sonarQubeClient, $violationId, $violationLineNumber, $violatedFullFilePath) {
    $this->sonarQubeClient = $sonarQubeClient;
    $this->violationId = $violationId;
    $this->lineNumber = $violationLineNumber;
    $this->fullFilePath = $violatedFullFilePath;
    $this->ldapUserAliasesMatcher = new LdapUserAliasesMatcher();
  }
  
  public function computeAssignee($sourceDirectory) {
    $outputGitBlame = $this->executeGitBlameCommand($sourceDirectory);
    $rawAssignee = $this->extractDeveloperFromGitBlameOutput($outputGitBlame);
    $this->assignee=$this->ldapUserAliasesMatcher->getLdapUserFromPickedNickName($rawAssignee);
  }
  
  public function executeGitBlameCommand($sourceDirectory) {
    chdir($sourceDirectory);
    return exec("git blame -L".$this->lineNumber.",".$this->lineNumber." ". $this->fullFilePath);
  }
  
  public function extractDeveloperFromGitBlameOutput($gitBlameOutput) {
    $match = array();
    preg_match('/([0-9a-z]+ \()(.*)( 201[0-9]-)/', $gitBlameOutput, $match);
    $rawAssignee = "";
    if(count($match) < 2) {
      echo "Assignee cannot be found on line " . $this->lineNumber . " of file " . $this->fullFilePath;
    } else {
      $rawAssignee = $match[2];
    }
    return $rawAssignee;
  }
  
  public function createReview() {
    echo "Creating review for violation (id, lineNumber, assigner, fullFilePath) : (".$this->violationId.", ".$this->lineNumber.", "
            .$this->assignee.", ".$this->fullFilePath;
    if ($this->assignee != null && $this->assignee != LdapUserAliasesMatcher::DEFAULT_JOHN_DOE_USER) {
      $this->sonarQubeClient->executeCreateReview($this->violationId, $this->assignee);
      return 1;
    } else {
      echo "Could not create Review for violation " . $this->violationId . " and assignee " . $this->assignee;
      return 0;
    }
  }
  
  public function getAssignee() {
    return $this->assignee;
  }
  
  public function setAssignee($assignee) {
    $this->assignee = $assignee;
  }
  
  public function setLdapUserAliasesMatcher($ldapUserAliasesMatcher) {
    $this->ldapUserAliasesMatcher = $ldapUserAliasesMatcher;
  }
}
