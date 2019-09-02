<?php

namespace DataProviderObject;

abstract class DataProviderObject {
  private $errorCode;
  private $errorText;
  const DEFAULT_STEPSIZE = 1000;
  public function __construct() {
    $this->resetError();
  }
  public function error() {
    return $this->errorCode != null;
  }
  public function resetError() {
    $this->errorCode = null;
    $this->errorText = null;
  }
  public function getErrorCode() {
    return $this->errorCode;
  }
  public function getErrorText() {
    return $this->errorText;
  }
  public function setError($code, $text = null) {
    if ($code != null && is_string ( $code )) {
      if ($this->errorCode == null) {
        $this->errorCode = $code;
        if($text!=null) {
          if(is_string($text)) {
            $this->errorText = $text;
          } else {
            die("incorrect call setError");
          }
        } 
      }
    } else {
      die("incorrect call setError");
    }
  }
  public function needResumption() {
    return false;
  }
}