<?php

class SonarViolation {
  
  private $id;
  private $lineNumber;
  private $fileName;
  private $language;
  private $fileFullKey;
  
  private $fileNameFullPath;
  
  private $assignee;
  private $sonarQubeClient;
  private $ldapUserAliasesMatcher;
  
  public function __construct($sonarQubeClient, $violation) {
    $this->sonarQubeClient = $sonarQubeClient;
    
    $this->id = $violation->id;
    $this->lineNumber = $violation->line;
    $violationResource = $violation->resource;
    $this->fileFullKey = $violationResource->key;    
    $this->fileName = $violationResource->name;
    $this->language = $violationResource->language;
    
    $this->ldapUserAliasesMatcher = new LdapUserAliasesMatcher();
  }
  
  public function computeFileNameFullPath($sourceDirectory) {
    $this->changeToDirectory($sourceDirectory);
    $pattern = $this->language == 'php' ? array_pop(explode(':', $this->fileFullKey)) : $this->fileName .'.'. $this->language;
    $findResults = $this->find('.', $pattern);
    $firstResult = $findResults[0];
    echo "\nFound file $firstResult";
    $this->fileNameFullPath = $firstResult;
  }
  
  /**
   * find files matching a pattern
   * using PHP "glob" function and recursion
   *
   * @return array containing all pattern-matched files
   *
   * @param string $dir     - directory to start with
   * @param string $pattern - pattern to glob for
   */
  public function find($dir, $pattern){
      // escape any character in a string that might be used to trick
      // a shell command into executing arbitrary commands
      $dir = escapeshellcmd($dir);
      // get a list of all matching files in the current directory
      $files = glob("$dir/$pattern");
      // find a list of all directories in the current directory
      // directories beginning with a dot are also included
      foreach (glob("$dir/{.[^.]*,*}", GLOB_BRACE|GLOB_ONLYDIR) as $sub_dir){
          $arr   = $this->find($sub_dir, $pattern);  // resursive call
          $files = array_merge($files, $arr); // merge array with files from subdirectory
      }
      // return all found files
      return $files;
  }    
  
  public function computeAssignee($sourceDirectory, $vcs) {
    if ($vcs == 'svn') {
        $outputSvnBlame = $this->executeSvnBlameCommand($sourceDirectory);
        $rawAssignee = $this->extractDeveloperFromSvnBlameOutput($outputSvnBlame);
    } else {
        $outputGitBlame = $this->executeGitBlameCommand($sourceDirectory);
        $rawAssignee = $this->extractDeveloperFromGitBlameOutput($outputGitBlame);
    }
    $this->assignee=$this->ldapUserAliasesMatcher->getLdapUserFromPickedNickName($rawAssignee);
  }
  
  public function executeGitBlameCommand($sourceDirectory) {
    $this->changeToDirectory($sourceDirectory);
    return exec("git blame -L".$this->lineNumber.",".$this->lineNumber." ". $this->fileNameFullPath);
  }

  public function executeSvnBlameCommand($sourceDirectory) {
    $this->changeToDirectory($sourceDirectory);
    return exec("svn blame $this->fileNameFullPath | sed -n '".$this->lineNumber."p'");
  }

  public function changeToDirectory($sourceDirectory) {
    echo "\ncd $sourceDirectory\n";
    $chdir = chdir($sourceDirectory);
    if (!$chdir) {
      echo "\nFailed to run command : cd $sourceDirectory \n";
    }
  }
  
  public function extractDeveloperFromGitBlameOutput($gitBlameOutput) {
    $match = array();
      //4463b322 (Sébastien M 2013-10-23 10:49:10 +0200 249)   private static function getMarkupWhenLayer(
    preg_match('/([0-9a-z]+ \()(.*)( 201[0-9]-)/', $gitBlameOutput, $match);
    $rawAssignee = "";
    if(count($match) < 2) {
      echo "\nAssignee cannot be found on line " . $this->lineNumber . " of file " . $this->fileNameFullPath . "\n";
    } else {
      $rawAssignee = $match[2];
    }
    return $rawAssignee;
  }

  public function extractDeveloperFromSvnBlameOutput($svnBlameOutput) {
    $match = array();
      //" 26965    smartin     long nextLong = abs(random.nextLong());"
    preg_match('/(\s+[0-9]+\s+)([a-z]+)(.*)/', $svnBlameOutput, $match);
    $rawAssignee = "";
    if(count($match) < 2) {
      echo "\nAssignee cannot be found on line " . $this->lineNumber . " of file " . $this->fileNameFullPath . "\n";
    } else {
      $rawAssignee = $match[2];
    }
    return $rawAssignee;
  }

  public function createReview() {
    echo "\nCreating review for violation (id, lineNumber, assigner, fileNameFullPath) : (".$this->id.", ".$this->lineNumber.", "
            .$this->assignee.", ".$this->fileNameFullPath . "\n";
    if ($this->assignee != null && $this->assignee != LdapUserAliasesMatcher::DEFAULT_JOHN_DOE_USER) {
      $this->sonarQubeClient->executeCreateReview($this->id, $this->assignee);
      return 1;
    } else {
      echo "\nCould not create Review for violation " . $this->id . " and assignee " . $this->assignee . "\n";
      return 0;
    }
  }
  
  public function getId() {
    return $this->id;
  }
  
  public function getLineNumber() {
    return $this->lineNumber;
  }
  
  public function getFileName() {
    return $this->fileName;
  }
  
  public function getLanguage() {
    return $this->language;
  }
  
  public function getFileFullKey() {
    return $this->fileFullKey;
  }
  
  public function getFileNameFullPath() {
    return $this->fileNameFullPath;
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
