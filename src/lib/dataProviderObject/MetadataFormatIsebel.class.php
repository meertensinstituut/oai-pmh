<?php

namespace DataProviderObject;

class MetadataFormatIsebel extends MetadataFormat {
  const METADATAPREFIX = "oai_isebel";
  const SCHEMA = "http://www.isebel.eu/OAI/2.0/oai_isebel.xsd";
  const METADATANAMESPACE = "http://www.isebel.eu/OAI/2.0/oai_isebel/";
  const PREFIX = "isebel";
  const MAINELEMENTS = array (
      "identifier",
      "url",
      "text",
      "location",
      "narrator",
      "timeOfNarration",
      "keyWord" 
  );
  public function __construct() {
    parent::__construct ( MetadataFormatIsebel::METADATAPREFIX, MetadataFormatIsebel::SCHEMA, MetadataFormatIsebel::METADATANAMESPACE );
  }
  public function createMetadata($data, $dom) {
    if ($data != null && $data instanceof Metadata) {
      $response = $dom->createElement ( \DataProviderObject\Metadata::METADATA );
      $metadata = $dom->createElement ( MetadataFormatIsebel::METADATAPREFIX . ":" . MetadataFormatIsebel::PREFIX );
      // attributes
      $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:" . MetadataFormatIsebel::METADATAPREFIX, MetadataFormatIsebel::METADATANAMESPACE ) );
      $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:" . MetadataFormatIsebel::PREFIX, "http://purl.org/dc/elements/1.1/" ) );
      $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance" ) );
      $metadata->appendChild ( $this->createAttribute ( $dom, "xsi:schemaLocation", MetadataFormatIsebel::METADATANAMESPACE . "\n " . MetadataFormatIsebel::SCHEMA ) );
      foreach ( MetadataFormatIsebel::MAINELEMENTS as $mainElement ) {
        if ($data->variableSet ( $mainElement )) {
          $value = $data->get ( $mainElement );
          if (is_string ( $value )) {
            $metadata->appendChild ( $this->createItem ( $dom, MetadataFormatIsebel::PREFIX . ":" . $mainElement, $value ) );
          } else if (is_array ( $value )) {
            foreach ( $value as $subValue ) {
              if (is_string ( $subValue )) {
                $metadata->appendChild ( $this->createItem ( $dom, MetadataFormatIsebel::PREFIX . ":" . $mainElement, $subValue ) );
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