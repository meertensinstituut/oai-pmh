<?php

namespace DataProviderObject;

class MetadataFormatIsebel extends MetadataFormat
{
    const METADATAPREFIX = "isebel";
    const SCHEMA = "http://www.isebel.eu/ns/isebel2.xsd";
    const METADATANAMESPACE = "http://www.isebel.eu/ns/isebel";
    const METADATAPREFIXLOCAL = "mi";
    const METADATANAMESPACELOCAL = "http://www.verhalenbank.nl/";
    const NAME = "story";
    const MAINELEMENTS = array(
        "identifier",
        "url",
        "text",
        "location",
        "narrator",
        "timeOfNarration",
        "keyWord",
        "subgenre"
    );

    public function __construct()
    {
        parent::__construct(MetadataFormatIsebel::METADATAPREFIX, MetadataFormatIsebel::SCHEMA, MetadataFormatIsebel::METADATANAMESPACE);
    }

    public function createMetadata($data, $dom)
    {
        if ($data != null && $data instanceof Metadata) {
            $response = $dom->createElement(\DataProviderObject\Metadata::METADATA);
            if ($data->get("type") == "story") {
                $metadata = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":" . MetadataFormatIsebel::NAME);
                // attributes
                $metadata->appendChild($this->createAttribute($dom, "xmlns:" . MetadataFormatIsebel::METADATAPREFIX, MetadataFormatIsebel::METADATANAMESPACE));
                $metadata->appendChild($this->createAttribute($dom, "xmlns:dc", "http://purl.org/dc/elements/1.1/"));
                $metadata->appendChild($this->createAttribute($dom, "xmlns:dcterms", "http://purl.org/dc/terms/"));
                $metadata->appendChild($this->createAttribute($dom, "xmlns:datacite", "http://datacite.org/schema/kernel-4"));
                $metadata->appendChild($this->createAttribute($dom, "xmlns:" . MetadataFormatIsebel::METADATAPREFIXLOCAL, MetadataFormatIsebel::METADATANAMESPACELOCAL));
                $metadata->appendChild($this->createAttribute($dom, "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance"));
                $metadata->appendChild($this->createAttribute($dom, "xsi:schemaLocation", MetadataFormatIsebel::METADATANAMESPACE . " " . MetadataFormatIsebel::SCHEMA));
                if ($data->variableSet("id")) {
                    $value = $data->get("id");
                    if (is_string($value)) {
                        $metadata->appendChild($this->createAttribute($dom, "xml:id", "story" . $value));
                        $metadata->appendChild($this->createAttribute($dom, "xml:lang", "nl"));
                        $this->createItem($dom, "dc:identifier", $data->get("url"), null, $metadata);
                        $this->createItem($dom, "dc:type", $data->get("subgenre"), array(array("xml:lang", "nl")), $metadata);
                        /* ttt stands for tale type text */
                        $this->createTaleTypeItem($dom, $data->get("ttt"), $metadata);
                        $this->createItemGroup($dom, MetadataFormatIsebel::METADATAPREFIX . ":content", $string = preg_replace('/[\x00-\x1F\x7F]/u', '', $data->get("text")), array(array("xml:lang", "nl")), $metadata, MetadataFormatIsebel::METADATAPREFIX . ":contents", "mainattributes", "mainmetadata");
                        $this->createGeoItem($dom, $data->get("location"), $metadata);
                        $this->createPersonItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":person", $data->get("narrator"), "narrator", $metadata);
                        $this->createEventItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":event", $data->get("date"), "narration", $metadata);
                        $this->createItemGroup($dom, MetadataFormatIsebel::METADATAPREFIX . ":keyword", $data->get("keyword"), array(array("xml:lang", "nl")), $metadata, MetadataFormatIsebel::METADATAPREFIX . ":keywords");

                    } else {
                        die("no unique id story");
                    }
                } else {
                    die ("no id story");
                }
                // add to response
                $response->appendChild($metadata);

            }
            return $response;
        } else {
            die ("incorrect call createMetadata");
        }
    }

    private function createAttribute($dom, $name, $value)
    {
        $attribute = $dom->createAttribute($name);
        $attribute->appendChild($dom->createTextNode($value));
        return $attribute;
    }

    private function createItem($dom, $name, $value, $attributes, $metadata)
    {
        if ($value && $metadata) {
            if (is_string($value)) {
                $item = $dom->createElement($name);
                $item->appendChild($dom->createTextNode($value));
                if ($attributes && is_array($attributes)) {
                    foreach ($attributes AS $attribute) {
                        $item->appendChild($this->createAttribute($dom, $attribute[0], $attribute[1]));
                    }
                }
                $metadata->appendChild($item);
            } else if (is_array($value)) {
                foreach ($value AS $subValue) {
                    $this->createItem($dom, $name, $subValue, $attributes, $metadata);
                }
            }
        }
    }

    private function createItemGroup($dom, $name, $value, $attributes, $metadata, $mainName, $mainAttributes = null, $mainMetadata = null)
    {
        if ($value && $metadata) {
            $mainItem = $dom->createElement($mainName);

            if (is_string($value)) {
                $item = $dom->createElement($name);
                $item->appendChild($dom->createTextNode($value));
                if ($attributes && is_array($attributes)) {
                    foreach ($attributes AS $attribute) {
                        $item->appendChild($this->createAttribute($dom, $attribute[0], $attribute[1]));
                    }
                }
                $mainItem->appendChild($item);
            } else if (is_array($value)) {
                foreach ($value AS $subValue) {
                    $this->createItem($dom, $name, $subValue, $attributes, $mainItem);
                }
            }

            $metadata->appendChild($mainItem);
        }
    }

    private function createGeoItem($dom, $value, $metadata, $mainRequest = true)
    {
        if ($value && is_array($value) && count($value) > 0) {
            if (is_array($value[0])) {
                $geo = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":places");
                foreach ($value AS $subValue) {
                    $this->createGeoItem($dom, $subValue, $geo, false);
                }
                $metadata->appendChild($geo);
            } else {
                if ($mainRequest) {
                    $geo = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":places");
                    $metadata->appendChild($geo);
                } else {
                    $geo = $metadata;
                }
                $geoLocation = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":place");
                $geoLocation->appendChild($this->createAttribute($dom, "id", "geo" . $value[0]));
                $geoLocation->appendChild($this->createAttribute($dom, "xml:lang", "nl"));
                if (isset($value[1]) && $value[1] != null && is_string($value[1]) && trim($value[1]) != "") {
                    $this->createItem($dom, "dc:title", $value[1], array(array("xml:lang", "nl")), $geoLocation);
                }

                if (isset($value[2]) || isset($value[3])) {
                    $geoLocationPoint = $dom->createElement("isebel:point");
                    if (isset($value[2]) && $value[2] != null && is_string($value[2]) && trim($value[2]) != "") {
                        $this->createItem($dom, "datacite:pointLatitude", $value[2], null, $geoLocationPoint);
                    }
                    if (isset($value[3]) && $value[3] != null && is_string($value[3]) && trim($value[3]) != "") {
                        $this->createItem($dom, "datacite:pointLongitude", $value[3], null, $geoLocationPoint);
                    }
                    $geoLocation->appendChild($geoLocationPoint);
                }
                $geo->appendChild($geoLocation);

            }
        }
    }

    private function createTaleTypeItem($dom, $value, $metadata, $mainRequest = true)
    {
        if ($value && is_array($value) && count($value) > 0) {
            if (is_array($value[0])) {
                $geo = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":taleTypes");
                foreach ($value AS $subValue) {
                    $this->createTaleTypeItem($dom, $subValue, $geo, false);
                }
                $metadata->appendChild($geo);
            } else {
                if ($mainRequest) {
                    $geo = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":taleTypes");
                    $metadata->appendChild($geo);
                } else {
                    $geo = $metadata;
                }
                $geoLocation = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":taleType");
                $geoLocation->appendChild($this->createAttribute($dom, "number", $value[0]));
                $geoLocation->appendChild($this->createAttribute($dom, "title", $value[1]));

                $geo->appendChild($geoLocation);

            }
        }
    }

    private function createPersonItem($dom, $name, $value, $role, $metadata, $mainRequest = true)
    {
        if ($value) {
            if ($mainRequest) {
                $persons = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":persons");
                $this->createPersonItem($dom, $name, $value, $role, $persons, false);
                $metadata->appendChild($persons);
            } else {

                if (is_array($value)) {
                    foreach ($value AS $subValue) {
                        $this->createPersonItem($dom, $name, $subValue, $role, $metadata, false);
                    }
                } else if (is_string($value) && trim($value != "")) {
                    $person = $dom->createElement($name);
                    $this->createItem($dom, "dcterms:contributor", $value, null, $person);
                    $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":role", $role, null, $person);
                    $metadata->appendChild($person);
                }
            }
        }
    }

    private function createEventItem($dom, $name, $value, $role, $metadata, $mainRequest = true)
    {
        if ($value) {
            if ($mainRequest) {
                $persons = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":events");
                $this->createEventItem($dom, $name, $value, $role, $persons, false);
                $metadata->appendChild($persons);
            } else {

                if (is_array($value)) {
                    foreach ($value AS $subValue) {
                        $this->createEventItem($dom, $name, $subValue, $role, $metadata, false);
                    }
                } else if (is_string($value) && trim($value != "")) {
                    $event = $dom->createElement($name);
                    $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":date", $value, null, $event);
                    $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":role", $role, null, $event);
                    $metadata->appendChild($event);
                }
            }
        }
    }


}