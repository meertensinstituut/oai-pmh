<?php

namespace DataProviderObject;

class Identifiers extends Items {
  private $identifiers;
  public function __construct($metadataPrefix, $set = null, $from = null, $until = null) {
    parent::__construct($metadataPrefix, $set, $from, $until);
    $this->identifiers = array (); 
  }
  public function getIdentifiers() {
    return $this->identifiers;
  }
  public function addIdentifier($identifier) {
    if ($identifier instanceof Identifier) {
      $this->identifiers [] = $identifier;
    } else {
      die ( "incorrect call addIdentifier" );
    }
  }  
}