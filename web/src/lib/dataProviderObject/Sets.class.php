<?php

namespace DataProviderObject;

class Sets extends DataProviderObject {
  private $sets;
  private $cursor = 0;
  private $stepSize = self::DEFAULT_STEPSIZE;
  private $completeListSize = 0;
  public function __construct($stepSize = null) {
    $this->sets = array ();
    $this->setError ( \OAIPMH\Server::ERROR_NOSETHIERARCHY );
    if($stepSize!==null) {
      if(is_numeric($stepSize)) {
        $this->stepSize = intval($stepSize);
      } else {
        die("incorrect stepSize");
      }
    }
  }
  public function getSets() {
    return $this->sets;
  }
  public function getCursor() {
    return $this->cursor;
  }
  public function getStepSize() {
    return $this->stepSize;
  }
  public function getCompleteListSize() {
    return $this->completeListSize;
  }
  public function addSet($set) {
    if ($set instanceof Set) {
      if ($this->error () && $this->getErrorCode () == \OAIPMH\Server::ERROR_NOSETHIERARCHY) {
        $this->resetError ();
      }
      $this->sets [] = $set;
    } else {
      die ( "incorrect call addSet" );
    }
  }
  public function setCursor($value) {
    if ($value !== null && is_numeric ( $value ) && intval ( $value ) >= 0) {
      $this->cursor = intval ( $value );
    } else {
      die ( "incorrect call setCursor" );
    }
  }
  public function setStepSize($value) {
    if ($value !== null && is_numeric ( $value ) && intval ( $value ) >= 0) {
      $this->stepSize = intval ( $value );
    } else {
      die ( "incorrect call setStepSize" );
    }
  }
  public function setCompleteListSize($value) {
    if ($value !== null && is_numeric ( $value ) && intval ( $value ) >= 0) {
      $this->completeListSize = intval ( $value );
    } else {
      die ( "incorrect call setCompleteListSize" );
    }
  }
  public function needResumption() {
    return $this->cursor + $this->stepSize < $this->completeListSize;
  }
}