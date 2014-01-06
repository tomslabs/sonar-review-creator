<?php

class LdapUserAliasesMatcher {
  
    const DEFAULT_JOHN_DOE_USER = "John Doe";
    
    public function getLdapUserFromPickedNickName($blamedDeveloper) {
      $ldapUsersJsonDecoded = json_decode($this->readJsonFileContainingLdapAliases());
      foreach ($ldapUsersJsonDecoded as $ldapUsers ) {
        foreach ($ldapUsers as $ldapUser => $nicknames) {
          foreach ($nicknames as $nickname) {
            if ($blamedDeveloper == $nickname) {
              return $ldapUser;
            }
          }
        }
      }
      
      echo "Could not match user with any LDAP user : " . $blamedDeveloper;
      return self::DEFAULT_JOHN_DOE_USER;
    }
    
  private function readJsonFileContainingLdapAliases() {
    $file = $this->getLdapAliasesFile();
    if (file_exists($file)) {
      return file_get_contents($file);  
    } else {
      echo "File ldap-aliases.json not found !";
    }
  }
  
  public function getLdapAliasesFile() {
    return dirname(__FILE__).'/config/ldap-aliases.json';
  }
    
}