<?php

namespace DataProviderObject;

use DateTime;

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
    const UNKNOWN = 'No name';

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
                    if (!empty($value) && (is_string($value) || is_numeric($value))) {
                        $metadata->appendChild($this->createAttribute($dom, "xml:id", "nl.verhalenbank." . $value));
                        $metadata->appendChild($this->createAttribute($dom, "xml:lang", "nl"));
                        $this->createItem($dom, "dc:identifier", "nl.verhalenbank." . $value, null, $metadata);
                        $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":purl", $data->get("url"), null, $metadata);
                        $this->createItem($dom, "dc:type", $data->get("subgenre"), array(array("xml:lang", "nl")), $metadata);
                        /* ttt stands for tale type text */
                        $this->createTaleTypeItem($dom, $data->get("ttt"), $metadata);
                        $this->createItemGroup($dom, MetadataFormatIsebel::METADATAPREFIX . ":content", $string = $this->clearString($data->get('text')), array(array("xml:lang", "nl")), $metadata, MetadataFormatIsebel::METADATAPREFIX . ":contents", "mainattributes", "mainmetadata");
                        $this->createGeoItem($dom, $data->get("location"), $metadata);
                        $this->createPersonItem($dom, $data->get("narrator"), $metadata);
                        $this->createEventItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":event", $data->get("date"), $data->get("thro"), "narration", $metadata);
                        $this->createItemGroup($dom, MetadataFormatIsebel::METADATAPREFIX . ":keyword", $data->get("keyword"), array(array("xml:lang", "nl")), $metadata, MetadataFormatIsebel::METADATAPREFIX . ":keywords");
                        $this->createImageResources($dom, MetadataFormatIsebel::METADATAPREFIX . ":imageResource", $data->get("resources"), $this->prepareAttribute( "id", $this->getIdFromUrlAsArray($data->get("resources"))), $metadata, MetadataFormatIsebel::METADATAPREFIX . ":imageResources");
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

    private function prepareAttribute($key, $values)
    {

        if (is_null($values)) {
            return null;
        }

        if (is_array($values)) {
            if ($this->stringNotEmpty($key)) {
                $result = array();
                foreach ($values as $value) {
                    array_push($result, array($key, $value));
                }
//                if ($id == '8610') {
//                    print('<pre>');
//                    print_r($result);
//                    die();
//                }
                return $result;
            }
            return null;
        } else {
            return null;
        }
    }

    private function getIdFromUrlAsArray($urls)
    {
        if ($urls) {
            if (is_string($urls)) {
                $result = explode("/", $urls);
                $result = end($result);
                return array($result);
            } elseif (is_array($urls)) {
                $result = array();
                foreach ($urls as $i) {
                    $tmp = explode("/", $i);
                    array_push($result, end($tmp));
                }
                return $result;
            } else {
                die("Attributes invalid");
            }
        }
        return null;
    }

    /*
     * This function clears the string according to the regular expression given
     *
     * @param string $orgString *The original string to be cleared*
     * @param string $format *Default set to remove control characters*
     *
     * @return string *cleared string*
     *
     */
    private function clearString($orgString, $format = '/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u')
    {
        return preg_replace($format, '', $orgString);
    }

    private function createAttribute($dom, $name, $value)
    {
        try {

            $attribute = $dom->createAttribute($name);
        } catch (\Exception $ex) {
            print("name is [${name}]; value is [${value}]");
            print($ex->getMessage());
            die();
        }
        $attribute->appendChild($dom->createTextNode($value));
        return $attribute;
    }

    private function createItem($dom, $name, $value, $attributes, $metadata)
    {
        if ($value && $metadata) {
            if (!empty($value) && (is_numeric($value) || is_string($value))) {
                $item = $dom->createElement($name);

                $item->appendChild($dom->createTextNode($value));
                if ($attributes && is_array($attributes)) {
                    foreach ($attributes AS $attribute) {
                        try {
                            $item->appendChild($this->createAttribute($dom, $attribute[0], $attribute[1]));
                        } catch (\Exception $ex) {
                            die($ex);
                        }
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

    private function createItemGroup($dom, $name, $value, $attributes, $metadata, $mainName)
    {
        if ($value && $metadata && $mainName) {
            $mainItem = $dom->createElement($mainName);
            $this->createItem($dom, $name, $value, $attributes, $mainItem);
            $metadata->appendChild($mainItem);
        }
    }

    private function createImageResource($dom, $name, $value, $attributes, $metadata, $mainName)
    {
        if ($value && $metadata && $mainName) {
            $mainItem = $dom->createElement($mainName);
            $mainItem->appendChild($this->createAttribute($dom, $attributes[0], $attributes[1]));
            $this->createItem($dom, $name, $value, null, $mainItem);
            $metadata->appendChild($mainItem);
        }
    }

    private function createImageResources($dom, $name, $value, $attributes, $metadata, $mainName)
    {
        if ($value && $metadata && $mainName) {
            $mainItem = $dom->createElement($mainName);
            if ($this->stringNotEmpty($value)) {
                foreach ($attributes as $attribute) {
                    $this->createImageResource($dom, MetadataFormatIsebel::METADATAPREFIX . ":purl", $value, $attribute, $mainItem, $name);
                }
            } elseif (is_array($value)) {
                for ($i = 0; $i < count($value); $i++) {
                    $this->createImageResource($dom, MetadataFormatIsebel::METADATAPREFIX . ":purl", $value[$i], $attributes[$i], $mainItem, $name);
                }
            }

            $metadata->appendChild($mainItem);
        }
    }

    /*
     * This function creates keywords and it's subtype keyword in XML of an ISEBEL story
     *
     * @param Dom $dom *the root dom*
     * @param string $name *Name of current main element, tag name or name of the sub element*
     * @param string || array $value *The content of the the current tag. The keywords (in case of array
     * or the keyword (in case of string)*
     * @param string || array $attributes *Attributes of the tag*
     * @param Node $metadata *Parent node of the current node*
     * @param string $mainName *The container of the current elements, in this case, it is <keywords/> and the
     * sub elements will be <keyword/>
     *
     * @return void
     */
    private function createKeywords($dom, $name, $value, $attributes, $metadata, $mainName)
    {
        // TODO: split words using space
        if ($this->isValidString($value)) {
            // explode the $value into new array
        } elseif (is_array($value)) {
            // expload every $value into new array
        }
        $this->createItemGroup($dom, $name, $value, $attributes, $metadata, $mainName);
    }

    /*
     * This function checks whether a given string is a valid, non empty string
     * The non empty check can be turned on or off using the second parameter
     *
     * @param string $testString *The string to be tested*
     * @param boolean $emptyString *Default set to false, which means the string cannot be empty ('')
     *
     * @return boolean
     */
//    private function isValidString($testString, $emptyString = false): bool
//    {
//        return $emptyString ? isset($testString) && is_string($testString) : isset($testString) && is_string($testString) && trim($testString) != '';
//    }
    private function stringNotEmpty($testString): bool
    {
        return isset($testString) && is_string($testString) && trim($testString) != '';
    }

    private function filledStringNumber($testStringNumber): bool
    {
        return isset($testStringNumber) && (is_numeric($testStringNumber) || is_string($testStringNumber)) && trim($testStringNumber) != '';
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
                if (!empty($value[1]) && is_string($value[1])) {
                    $this->createItem($dom, "dc:title", $value[1], array(array("xml:lang", "nl")), $geoLocation);
                } else {
                    $this->createItem($dom, "dc:title", self::UNKNOWN, array(array("xml:lang", "nl")), $geoLocation);
                }
                if ($this->filledStringNumber($value[2]) && $this->filledStringNumber($value[3])) {
                    $geoLocationPoint = $dom->createElement("isebel:point");
                    if ($value[2] == '0') $value[2] = '0.0';
                    $this->createItem($dom, "datacite:pointLatitude", $value[2], null, $geoLocationPoint);

                    if ($value[3] == '0') $value[3] = '0.0';
                    $this->createItem($dom, "datacite:pointLongitude", $value[3], null, $geoLocationPoint);

                    $geoLocation->appendChild($geoLocationPoint);
                }

                $this->createItem($dom, "isebel:role", "narration", null, $geoLocation);
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

    private function createPersonItem($dom, $value, $metadata, $mainRequest = true)
    {
        $genderArray = array(
            "m" => "male",
            "v" => "female"
        );

        if ($value && is_array($value) && count($value) > 0) {
            if (is_array($value[0])) {
                $persons = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":persons");
                foreach ($value AS $subValue) {
                    $this->createPersonItem($dom, $subValue, $persons, false);
                }
                $metadata->appendChild($persons);
            } else {
                if ($mainRequest) {
                    $persons = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":persons");
                    $metadata->appendChild($persons);
                } else {
                    $persons = $metadata;
                }
                $person = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":person");
                $person->appendChild($this->createAttribute($dom, "xml:lang", "nl"));
                $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":name", $value[0], null, $person);
                if (array_key_exists($value[1], $genderArray)) {
                    $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":gender", $genderArray[$value[1]], null, $person);
                } else {
                    $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":gender", $value[1], null, $person);
                }
                $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":role", "narrator", null, $person);

                $persons->appendChild($person);

            }

        }
    }

    /*
     * Check the given string is a valid date according to the format specified.
     * The function checks not only if the string conforms to the format but also whether it is indeed a valid date.
     *
     * @param string $dataString *The string to be checked*
     * @param string $format *The format of the date*
     *
     * @return boolean
     */
    private function is_date($dateString, $format = 'Y-m-d'): bool
    {
        if ($this->stringNotEmpty($dateString)) {
            $d = DateTime::createFromFormat($format, $dateString);
            return $d && $d->format($format) === $dateString;
        }
        return false;
    }

    private function createEventItem($dom, $name, $value, $value2, $role, $metadata, $mainRequest = true)
    {
        if (($value && $this->is_date($value)) || ($value2 && $this->is_date($value2))) {
            if ($mainRequest) {
                $persons = $dom->createElement(MetadataFormatIsebel::METADATAPREFIX . ":events");
                $this->createEventItem($dom, $name, $value, $value2, $role, $persons, false);
                $metadata->appendChild($persons);
            } else {
                $event = $dom->createElement($name);

                if ($value && $this->is_date($value)) {
                    $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":date", $value, null, $event);
                }
                if ($value2 && $this->is_date($value2)) {
                    $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":thru", $value2, null, $event);
                }

                $this->createItem($dom, MetadataFormatIsebel::METADATAPREFIX . ":role", $role, null, $event);
                $metadata->appendChild($event);
            }
        }
    }

}