<?php

namespace DataProviderObject;

abstract class Items extends DataProviderObject {
  private $metadataPrefix;
  private $set;
  private $from;
  private $until;
  private $cursor = 0;
  private $stepSize = self::DEFAULT_STEPSIZE;
  private $completeListSize = 0;
  public function __construct($metadataPrefix, $set = null, $from = null, $until = null, $stepSize = null) {
    $this->setMetadataPrefix ( $metadataPrefix );
    $this->setSet ( $set );
    $this->setFrom ( $from );
    $this->setUntil ( $until );
    if($stepSize!==null) {
      if(is_numeric($stepSize)) {
        $this->stepSize = intval($stepSize);
      } else {
        die("incorrect stepSize");
      }
    }
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
  public function getMetadataPrefix() {
    return $this->metadataPrefix;
  }
  public function getSet() {
    return $this->set;
  }
  public function getFrom() {
    return $this->from;
  }
  public function getUntil() {
    return $this->until;
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
  private function setMetadataPrefix($value) {
    if ($value == null || is_string ( $value )) {
      $this->metadataPrefix = $value;
    } else {
      die ( "incorrect call setMetadataPrefix" );
    }
  }
  private function setSet($value) {
    if ($value == null || is_string ( $value )) {
      $this->set = $value;
    } else {
      die ( "incorrect call setSet" );
    }
  }
  private function setFrom($value) {
    if ($value == null || is_numeric ( $value )) {
      $this->from = $value;
    } else {
      die ( "incorrect call setFrom" );
    }
  }
  private function setUntil($value) {
    if ($value == null || is_numeric ( $value )) {
      $this->until = $value;
    } else {
      die ( "incorrect call setUntil" );
    }
  }
  public function needResumption() {
    return $this->cursor + $this->stepSize < $this->completeListSize;
  }
}