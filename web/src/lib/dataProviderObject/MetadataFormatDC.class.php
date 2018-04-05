<?php

namespace DataProviderObject;

class MetadataFormatDC extends MetadataFormat {
  const METADATAPREFIX = "oai_dc";
  const SCHEMA = "http://www.openarchives.org/OAI/2.0/oai_dc.xsd";
  const METADATANAMESPACE = "http://www.openarchives.org/OAI/2.0/oai_dc/";
  const PREFIX = "dc";
  const MAINELEMENTS = array (
      "contributor",
      "coverage",
      "creator",
      "date",
      "description",
      "format",
      "identifier",
      "language",
      "publisher",
      "relation",
      "rights",
      "source",
      "subject",
      "title",
      "type" 
  );
  public function __construct() {
    parent::__construct ( MetadataFormatDC::METADATAPREFIX, MetadataFormatDC::SCHEMA, MetadataFormatDC::METADATANAMESPACE );
  }
  public function createMetadata($data, $dom) {
    if ($data != null && $data instanceof Metadata) {
      $response = $dom->createElement ( \DataProviderObject\Metadata::METADATA );
      $metadata = $dom->createElement ( MetadataFormatDC::METADATAPREFIX . ":" . MetadataFormatDC::PREFIX );
      // attributes
      $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:" . MetadataFormatDC::METADATAPREFIX, MetadataFormatDC::METADATANAMESPACE ) );
      $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:" . MetadataFormatDC::PREFIX, "http://purl.org/dc/elements/1.1/" ) );
      $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance" ) );
      $metadata->appendChild ( $this->createAttribute ( $dom, "xsi:schemaLocation", MetadataFormatDC::METADATANAMESPACE . "\n " . MetadataFormatDC::SCHEMA ) );
      foreach ( MetadataFormatDC::MAINELEMENTS as $mainElement ) {
        if ($data->variableSet ( $mainElement )) {
          $value = $data->get ( $mainElement );
          if (is_string ( $value )) {
            $metadata->appendChild ( $this->createItem ( $dom, MetadataFormatDC::PREFIX . ":" . $mainElement, $value ) );
          } else if (is_array ( $value )) {
            foreach ( $value as $subValue ) {
              if (is_string ( $subValue )) {
                $metadata->appendChild ( $this->createItem ( $dom, MetadataFormatDC::PREFIX . ":" . $mainElement, $subValue ) );
              }
            }
          }
        }
      }
      // add to response
      $response->appendChild ( $metadata );
      return $response;
    } else {
      die ( "incorrect call createMetadata" );
    }
  }
  private function createAttribute($dom, $name, $value) {
    $attribute = $dom->createAttribute ( $name );
    $attribute->appendChild ( $dom->createTextNode ( $value ) );
    return $attribute;
  }
  private function createItem($dom, $name, $value) {
    $item = $dom->createElement ( $name );
    $item->appendChild ( $dom->createTextNode ( $value ) );
    return $item;
  }
}