<?php

namespace DataProviderObject;

class MetadataFormats extends DataProviderObject {
  private $identifier;
  private $metadataFormats;
  private $prefixes;
  public function __construct($identifier = null) {
    $this->setIdentifier ( $identifier );
    $this->metadataFormats = array ();
    $this->prefixes = array ();
  }
  public function getMetadataFormats() {
    return $this->metadataFormats;
  }
  public function addMetadataFormat($metadataFormat) {
    if ($metadataFormat instanceof MetadataFormat) {
      if (! in_array ( $metadataFormat->getMetadataPrefix (), $this->prefixes )) {
        $this->metadataFormats [] = $metadataFormat;
        $this->prefixes [] = $metadataFormat->getMetadataPrefix ();
      } else {
        die ( "duplicate metadataFormat definition " . $metadataFormat->getMetadataPrefix () );
      }
    } else {
      die ( "incorrect call" );
    }
  }
  public function setIdentifier($value) {
    if ($value == null || is_string ( $value )) {
      $this->identifier = $value;
    } else {
      die ( "incorrect call setIdentifier" );
    }
  }
  public function metadataPrefixAvailable($metadataPrefix) {
    return $metadataPrefix !== null && is_string ( $metadataPrefix ) && in_array ( $metadataPrefix, $this->prefixes );
  }
  public function getMetadataFormat($metadataPrefix) {
    if(($position = array_search($metadataPrefix, $this->prefixes))>=0) {
      return $this->metadataFormats[$position];
    } else {
      return null;
    }
  }
}