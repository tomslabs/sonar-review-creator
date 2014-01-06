<?php

class SonarQubeClient {
  
  private $sonarHost;
  private $assignerUsername;
  private $assignerPassword;
  
  public function __construct() {
    $ini_array = parse_ini_file("sonar-review-creator.ini", true);
    
    $this->sonarHost = $ini_array['sonar']['host'];
    $this->assignerUsername = $ini_array['assigner']['username'];
    $this->assignerPassword = $ini_array['assigner']['password'];
  }
  
  public function getViolations($project, $depth, $priorities) {
    $url = $this->buildGetViolationsUrl($project, $depth, $priorities);
    return json_decode($this->executeGet($url));
  }
  
  public function buildGetViolationsUrl($project, $depth, $priorities) {
    return "http://".$this->sonarHost."/api/violations?resource=".$project."&depth=".$depth."&priorities=".$priorities."&format=json";
  }  
  
  protected function executeGet($url)
  {
    $ch = curl_init();
    $output = null;
    try
    {
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//      curl_setopt($ch, CURLOPT_USERPWD, self::CURL_TOKENIZED_CREDENTIALS);
      $output = curl_exec($ch);
    }
    catch(Exception $e)
    {
      $this->log($e);
    }
    curl_close($ch);
    return $output;
  }   
  
  public function executeCreateReview($violationId, $assignee) {
    //curl -u admin:admin -d "violation_id=123&status=OPEN&comment=myComment&assignee=admin" -X POST http://localhost:9000/api/reviews
    $url = 'http://' . $this->sonarHost . '/api/reviews';
    $fields = array(
                'violation_id' => $violationId,
                'status' => 'OPEN',
//                'comment' => 'Consider that the next person who will read your code is a psychopath and he knows your home address !',
                'comment' => 'please fix me',
                'assignee' => $assignee
            );

    $fields_string = '';
    //url-ify the data for the POST
    foreach($fields as $key=>$value) { 
      $fields_string .= $key.'='.$value.'&';
    }
    rtrim($fields_string, '&');

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $this->assignerUsername . ':' . $this->assignerPassword);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    //execute post
    $result = curl_exec($ch);

    //close connection
    curl_close($ch);    
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
  
}
