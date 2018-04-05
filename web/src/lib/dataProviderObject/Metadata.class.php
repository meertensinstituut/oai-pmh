<?php

namespace DataProviderObject;

class Metadata extends DataProviderObject {
  private $data;
  const METADATA = "metadata";
  public function __construct($data) {
    if($data!==null && is_array($data)) {
      $this->data = $data;
    } else {
      die("invalid data");
    }
  }  
  public function variableSet($name) {
    return isset($this->data[$name]) && $this->data[$name]!=null;
  }
  public function get($name) {
    return $this->data[$name];
  }
}