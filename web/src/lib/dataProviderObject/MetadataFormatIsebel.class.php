<?php

namespace DataProviderObject;

class MetadataFormatIsebel extends MetadataFormat {
  const METADATAPREFIX = "isebel";
  const SCHEMA = "http://www.isebel.eu/ns/isebel.xsd";
  const METADATANAMESPACE = "http://www.isebel.eu/ns/isebel";
  const METADATAPREFIXLOCAL = "mi";
  const METADATANAMESPACELOCAL = "http://www.verhalenbank.nl/";
  const NAME = "story";
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
      if($data->get("type")=="story") {        
        $metadata = $dom->createElement ( MetadataFormatIsebel::METADATAPREFIX . ":" . MetadataFormatIsebel::NAME );
        // attributes
        $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:" . MetadataFormatIsebel::METADATAPREFIX, MetadataFormatIsebel::METADATANAMESPACE ) );
        $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:dcterms","http://purl.org/dc/terms/"));
        $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:datacite", "http://datacite.org/schema/kernel-4"));
        $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:" . MetadataFormatIsebel::METADATAPREFIXLOCAL, MetadataFormatIsebel::METADATANAMESPACELOCAL)); 
        $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance" ) );
        $metadata->appendChild ( $this->createAttribute ( $dom, "xsi:schemaLocation", MetadataFormatIsebel::METADATANAMESPACE . " " . MetadataFormatIsebel::SCHEMA ) );        
        if ($data->variableSet ( "id")) {
          $value = $data->get ( "id" );
          if (is_string ( $value )) {
            $metadata->appendChild ( $this->createAttribute ( $dom, "xml:id", "story".$value));
            $this->createItem($dom, "dcterms:identifier", $data->get("url"), null, $metadata);
            $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX.":content", $data->get("text"), array(array("xml:lang","nl")), $metadata);
            $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX.":keyword", $data->get("keyword"), array(array("xml:lang","nl")), $metadata);
            $this->createGeoItem($dom, $data->get("location"), $metadata);
            $this->createPersonItem($dom, MetadataFormatIsebel::METADATAPREFIX.":person", $data->get("narrator"), "narrator", $metadata);
            if($data->variableSet("identifier")) {
              $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIXLOCAL.":ref", $data->get("identifier"), null, $metadata);
            }            
          } else {
            die("no unique id story");
          }
        } else {
          die ("no id story");
        }
        // add to response
        $response->appendChild ( $metadata );
        
      } 
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
  private function createItem($dom, $name, $value, $attributes, $metadata) {
    if($value && $metadata) {
      if(is_string($value)) {
        $item = $dom->createElement ( $name );
        $item->appendChild ( $dom->createTextNode ( $value ) );
        if($attributes && is_array($attributes)) {
          foreach($attributes AS $attribute) {
            $item->appendChild ( $this->createAttribute ( $dom, $attribute[0], $attribute[1]));
          }
        }
        $metadata->appendChild($item);
      } else if(is_array($value)) {
        foreach($value AS $subValue) {
          $this->createItem($dom, $name, $subValue, $attributes, $metadata);
        }
      }
    }    
  }
  private function createGeoItem($dom, $value, $metadata, $mainRequest=true) {
    if($value && is_array($value) && count($value)>0) {
      if(is_array($value[0])) {
        $geo = $dom->createElement ( "datacite:geoLocations" ); 
        foreach($value AS $subValue) {
          $this->createGeoItem($dom, $subValue, $geo, false);
        }
        $metadata->appendChild($geo);
      } else {
        if($mainRequest) {
          $geo = $dom->createElement ( "datacite:geoLocations" );
          $metadata->appendChild($geo);          
        } else {
          $geo = $metadata;
        }
        $geoLocation = $dom->createElement ( "datacite:geoLocation" ); 
        $geoLocation->appendChild ( $this->createAttribute ( $dom, "xml:id", "geo".$value[0]));        
        if(isset($value[1]) && $value[1]!=null && is_string($value[1]) && trim($value[1])!="") {
          $this->createItem($dom, "datacite:geoLocationPlace", $value[1], array(array("xml:lang","nl")), $geoLocation);
        }
        
        if(isset($value[2])||isset($value[3])) {
          $geoLocationPoint = $dom->createElement ( "datacite:geoLocationPoint" ); 
          if(isset($value[2]) && $value[2]!=null && is_string($value[2]) && trim($value[2])!="") {
            $this->createItem($dom, "datacite:pointLatitude", $value[2], null, $geoLocationPoint);
          }
          if(isset($value[3]) && $value[3]!=null && is_string($value[3]) && trim($value[3])!="") {
            $this->createItem($dom, "datacite:pointLongitude", $value[3], null, $geoLocationPoint);
          }
          $geoLocation->appendChild($geoLocationPoint);
        }
        $geo->appendChild($geoLocation);
//         $geo = $dom->createElement ( MetadataFormatIsebel::METADATAPREFIX.":geo" );
//         $geo->appendChild ( $this->createAttribute ( $dom, "xml:id", "geo".$value[0]));
//         if(isset($value[0]) && $value[1]!=null && is_string($value[1]) && trim($value[1])!="") {
//           $this->createItem($dom, "dcterms:spatial", $value[1], array(array("xml:lang","nl")), $geo);
//         }
//         if(isset($value[1]) && $value[2]!=null && is_string($value[2]) && trim($value[2])!="") {
//           $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX.":latitude", $value[2], null, $geo);
//         }
//         if(isset($value[2]) && $value[3]!=null && is_string($value[3]) && trim($value[3])!="") {
//           $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX.":longitude", $value[3], null, $geo);
//         }
//         $metadata->appendChild($geo);
      }
    }
  }
  private function createPersonItem($dom, $name, $value, $role, $metadata) {
    if($value) {
      if(is_array($value)) {
        foreach($value AS $subValue) {
          $this->createPersonItem($dom, $name, $subValue, $role, $metadata);
        }
      } else if(is_string($value) && trim($value!="")) { 
        $person = $dom->createElement ( $name ); 
        $this->createItem($dom, "dcterms:contributor", $value, null, $person);
        $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX.":role", $role, null, $person);
        $metadata->appendChild($person);
      }
    }
  }
}