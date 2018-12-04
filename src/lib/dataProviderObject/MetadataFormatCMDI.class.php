<?php

namespace DataProviderObject;

class MetadataFormatCMDI extends MetadataFormat {
    const METADATAPREFIX = "oai_cmdi";
    const SCHEMA = "http://www.example.com/placeholder/for/nederlab/componentregistry/entry/in/clarin/repo";
    const METADATANAMESPACE = "http://www.clarin.eu/cmdi/";
    const PREFIX = "cmdi";
    const MAINELEMENTS = array (
        "creator",
        "date",
        "displayName",
        "profile",
        "self"
    );

    public function __construct() {
        parent::__construct(MetadataFormatCMDI::METADATAPREFIX, MetadataFormatCMDI::SCHEMA, MetadataFormatCMDI::METADATANAMESPACE);
    }

    public function createMetadata($data, $dom) {
        if ($data != null && $data instanceof Metadata) {
            $metadata = $dom->createElement ( MetadataFormatCMDI::METADATAPREFIX . ":" . MetadataFormatCMDI::PREFIX);
            // attributes
            $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:" . MetadataFormatCMDI::METADATAPREFIX, MetadataFormatCMDI::METADATANAMESPACE ) );
            $metadata->appendChild ( $this->createAttribute ( $dom, "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance" ) );
            $metadata->appendChild ( $this->createAttribute ( $dom, "xsi:schemaLocation", MetadataFormatCMDI::METADATANAMESPACE . " " . MetadataFormatCMDI::SCHEMA ) );

            // elements
            foreach ( MetadataFormatCMDI::MAINELEMENTS as $mainElement ) {
                error_log("mainElement: " . $mainElement);
            }

            // response
            $response = $dom->createElement ( \DataProviderObject\Metadata::METADATA );
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