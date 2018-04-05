<?php

namespace DataProviderObject;

abstract class MetadataFormat extends DataProviderObject {
  private $metadataPrefix;
  private $schema;
  private $metadataNamespace;
  const METADATAFORMAT = "metadataFormat";
  const METADATAFORMAT_METADATAPREFIX = "metadataPrefix";
  const METADATAFORMAT_SCHEMA = "schema";
  const METADATAFORMAT_METADATANAMESPACE = "metadataNamespace";
  public function __construct($metadataPrefix, $schema, $metadataNamespace) {
    if ($metadataPrefix !== null && is_string ( $metadataPrefix )) {
      $this->metadataPrefix = $metadataPrefix;
    } else {
      die ( "no ".MetadataFormat::METADATAFORMAT_METADATAPREFIX );
    }
    if ($schema !== null && is_string ( $schema )) {
      $this->schema = $schema;
    } else {
      die ( "no ".MetadataFormat::METADATAFORMAT_SCHEMA );
    }
    if ($metadataNamespace !== null && is_string ( $metadataNamespace )) {
      $this->metadataNamespace = $metadataNamespace;
    } else {
      die ( "no ".MetadataFormat::METADATAFORMAT_METADATANAMESPACE );
    }
  }
  public function getMetadataPrefix() {
    return $this->metadataPrefix;
  }
  public function getSchema() {
    return $this->schema;
  }
  public function getMetadataNamespace() {
    return $this->metadataNamespace;
  }
  abstract public function createMetadata($data, $dom);
}