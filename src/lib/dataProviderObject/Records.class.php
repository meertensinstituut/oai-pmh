<?php

namespace DataProviderObject;

class Records extends Items {
  private $records;
  public function __construct($metadataPrefix, $set = null, $from = null, $until = null) {
    parent::__construct($metadataPrefix, $set, $from, $until);
    $this->records = array ();
  }
  public function getRecords() {
    return $this->records;
  }
  public function addRecord($record) {
    if ($record instanceof Record) {
      $this->records [] = $record;
    } else {
      die ( "incorrect call addRecord" );
    }
  }   
}