<?php

namespace DataProviderObject;

class Identify extends DataProviderObject {
  private $responseName = null;
  private $earliestDatestamp = null;
  private $deletedRecord = null;
  private $granularity = null;
  private $adminEmail = array ();
  public function __construct($granularity) {
    $this->setGranularity($granularity);
  }
  public function getResponseName() {
    return $this->responseName != null ? $this->responseName : "";
  }
  public function getEarliestDatestamp() {
    return $this->earliestDatestamp != null ? $this->earliestDatestamp : 0;
  }
  public function getDeletedRecord() {
    return $this->deletedRecord != null ? $this->deletedRecord : "no";
  }
  public function getGranularity() {
    return $this->granularity != null ? $this->granularity : "YYYY-MM-DDThh:mm:ssZ";
  }
  public function getAdminEmail() {
    return count ( $this->adminEmail ) > 0 ? $this->adminEmail : array (
        "" 
    );
  }
  public function setResponseName($value) {
    if($value!=null && is_string($value)) {
      $this->responseName = $value;
    } else {
      die("invalid responseName");
    }
  }
  public function setEarliestDatestamp($value) {
    if($value!=null && is_numeric($value)) {
      $this->earliestDatestamp = $value;
    } else {
      die("invalid earliestDatestamp");
    }
  }
  public function setDeletedRecord($value) {
    if($value!=null && is_string($value)) {
      $this->deletedRecord = $value;
    } else {
      die("invalid deletedRecord");
    }
  }
  public function setGranularity($value) {
    if($value!=null && is_string($value) && ($value==\OAIPMH\DataProvider::GRANULARITY_DATE || $value==\OAIPMH\DataProvider::GRANULARITY_DATETIME)) {
      $this->granularity = $value;
    } else {
      die("invalid granularity");
    }
  }
  public function setAdminEmail($value) {
    if($value!=null && is_string($value)) {
      $this->adminEmail = array($value);
    } else if($value!=null && is_array($value) && count($value)>0) {
      $this->adminEmail = array();
      foreach($value AS $item) {
        if($item!=null && is_string($item)) {
          $this->adminEmail[] = $item;
        } else {
          die("invalid adminEmail");
        }
      }
    } else {    
      die("invalid adminEmail");
    }
  }
}