<?php

namespace DataProviderObject;

class Identifier extends DataProviderObject {
  private $header;
  const IDENTIFIER = "identifier";  
  public function __construct($header) {
    if ($header!==null && $header instanceof Header) {
      $this->header = $header;
    } else {
      die ( "incorrect call identifier");
    }
  }
  public function getHeader() {
    return $this->header;
  }
}