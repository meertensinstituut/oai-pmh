<?php

namespace DataProviderObject;

class Record extends DataProviderObject {
  private $identifier;
  private $metadataPrefix;
  private $header;
  private $metadata;
  const RECORD = "record";  
  public function __construct() {
    $this->identifier = null;
    $this->metadataFormat = null;
    $this->header = null;
  }
  public function getHeader() {
    return $this->header;
  }
  public function getIdentifier() {
    return $this->identifier;
  }
  public function getMetadataPrefix() {
    return $this->metadataPrefix;   
  }
  public function getMetadata() {
    return $this->metadata;
  }
  public function setHeader($value) {
    if ($value !== null && $value instanceof Header) {
      $this->header = $value ;
      if($this->identifier==null) {
        $this->identifier = $this->header->getIdentifier();
      } else if ($this->identifier !== $this->header->getIdentifier()) {
        $this->setError(\OAIPMH\Server::ERROR_IDDOESNOTEXIST);
      }
    } else {
      die ( "incorrect call setHeader" );
    }
  }
  public function setMetadata($value) {
    if ($value !== null && $value instanceof Metadata) {
      $this->metadata = $value ;      
    } else {
      die ( "incorrect call setMetadata" );
    }
  }
  public function setIdentifier($value) {
    if ($value !== null && is_string($value) && $this->header==null) {
      $this->identifier = $value ;
    } else {
      die ( "incorrect call setIdentifier" );
    }
  }
  public function setMetadataPrefix($value) {
    if ($value !== null && is_string($value)) {
      $this->metadataPrefix = $value ;
    } else {
      die ( "incorrect call setMetadataPrefix" );
    }
  }
}