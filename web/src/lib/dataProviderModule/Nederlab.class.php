<?php

namespace DataProviderModule;

use OAIPMH\DataProviderBroker;
use DataProviderObject\MetadataFormatDC;

class Nederlab extends \OAIPMH\DataProviderBroker {
  const PREFIX_HEADER = "header.";
  const PREFIX_METADATA = "metadata.";
  public function listMetadataFormats($identifier = null) {
    $metadataFormats = parent::listMetadataFormats ( $identifier );
    $metadataFormats->addMetadataFormat ( new \DataProviderObject\MetadataFormatDC () );
    return $metadataFormats;
  }
  protected function getIdentifiyBrokerQuery() {
    $request = array ();
    $request ["response"] = array ();
    $request ["response"] ["stats"] = array ();
    $request ["response"] ["stats"] ["statsfields"] = array ();
    $request ["response"] ["stats"] ["statsfields"] [] = array (
        "field" => "NLCore_NLAdministrative_ingestTime",
        "key" => "earliestDatestamp" 
    );
    $request ["cache"] = false;
    return json_encode ( $request );
  }
  protected function getSetsNumberBrokerQuery() {
    $request = array ();
    $request ["response"] = array ();
    $request ["response"] ["stats"] = array ();
    $request ["response"] ["stats"] ["statsfields"] = array ();
    $request ["response"] ["stats"] ["statsfields"] [] = array (
        "field" => "NLCore_NLAdministrative_sourceCollection",
        "countDistinct" => true,
        "key" => "numberOfSets" 
    );
    $request ["cache"] = false;
    return json_encode ( $request );
  }
  protected function getSetsListBrokerQuery($cursor, $stepSize) {
    $request = array ();
    $request ["response"] = array ();
    $request ["response"] ["facets"] = array ();
    $request ["response"] ["facets"] ["facetfields"] = array ();
    $request ["response"] ["facets"] ["facetfields"] [] = array (
        "field" => "NLCore_NLAdministrative_sourceCollection",
        "sort" => "index",
        "limit" => intval ( $stepSize ),
        "offset" => intval ( $cursor ),
        "key" => "setsList" 
    );
    $request ["cache"] = false;
    return json_encode ( $request );
  }
  protected function getIdentifiersListBrokerQuery($cursor, $stepSize, $metadataPrefix, $set, $from, $until) {
    $request = array ();
    $request ["filter"] = $this->createItemsConditions ( $set, $from, $until );
    $request ["response"] = array ();
    $request ["response"] ["documents"] = array ();
    $request ["response"] ["documents"] ["start"] = intval ( $cursor );
    $request ["response"] ["documents"] ["rows"] = intval ( $stepSize );
    $request ["response"] ["documents"] ["fields"] = array (
        self::PREFIX_HEADER . "identifier:NLCore_NLIdentification_versionID",
        self::PREFIX_HEADER . "datestamp:NLCore_NLAdministrative_ingestTime",
        self::PREFIX_HEADER . "setSpec:NLCore_NLAdministrative_sourceCollection" 
    );
    $request ["cache"] = false;
    return json_encode ( $request );
  }
  protected function getRecordsListBrokerQuery($cursor, $stepSize, $metadataPrefix, $set, $from, $until) {
    $request = array ();
    $request ["filter"] = $this->createItemsConditions ( $set, $from, $until );
    $request ["response"] = array ();
    $request ["response"] ["documents"] = array ();
    $request ["response"] ["documents"] ["start"] = intval ( $cursor );
    $request ["response"] ["documents"] ["rows"] = intval ( $stepSize );
    if ($metadataPrefix == MetadataFormatDC::METADATAPREFIX) {
      $request ["response"] ["documents"] ["fields"] = array (
          self::PREFIX_HEADER . "identifier:NLCore_NLIdentification_versionID",
          self::PREFIX_HEADER . "datestamp:NLCore_NLAdministrative_ingestTime",
          self::PREFIX_HEADER . "setSpec:NLCore_NLAdministrative_sourceCollection",
          self::PREFIX_METADATA . "contributor:NLCore_NLAdministrative_sourceCollection",
          self::PREFIX_METADATA . "identifier:NLCore_NLIdentification_nederlabID",
          self::PREFIX_METADATA . "language:NLTitle_primaryLanguage",
          self::PREFIX_METADATA . "subject:NLTitle_genre",
          self::PREFIX_METADATA . "title.0:NLTitle_title",
          self::PREFIX_METADATA . "title.1:NLDependentTitle_title",
          self::PREFIX_METADATA . "title.2:NLPerson_NLPersonName_preferredFullName",
          self::PREFIX_METADATA . "type:NLProfile_name",
          self::PREFIX_METADATA . "source:NLCore_NLIdentification_sourceRef" 
      );
    } else {
      die ( "unknown metadataPrefix" );
    }
    $request ["cache"] = false;
    return json_encode ( $request );
  }
  protected function getRecordBrokerQuery($identifier, $metadataPrefix) {
    $request = array ();
    $request ["filter"] = array ();
    $request ["filter"] ["condition"] = array ();
    $request ["filter"] ["condition"] ["type"] = "equals";
    $request ["filter"] ["condition"] ["field"] = "NLCore_NLIdentification_versionID";
    $request ["filter"] ["condition"] ["value"] = $identifier;
    $request ["response"] = array ();
    $request ["response"] ["documents"] = array ();
    $request ["response"] ["documents"] ["start"] = 0;
    $request ["response"] ["documents"] ["rows"] = 1;
    if ($metadataPrefix == MetadataFormatDC::METADATAPREFIX) {
      $request ["response"] ["documents"] ["fields"] = array (
          self::PREFIX_HEADER . "identifier:NLCore_NLIdentification_versionID",
          self::PREFIX_HEADER . "datestamp:NLCore_NLAdministrative_ingestTime",
          self::PREFIX_HEADER . "setSpec:NLCore_NLAdministrative_sourceCollection",
          self::PREFIX_METADATA . "contributor:NLCore_NLAdministrative_sourceCollection",
          self::PREFIX_METADATA . "identifier:NLCore_NLIdentification_nederlabID",
          self::PREFIX_METADATA . "language:NLTitle_primaryLanguage",
          self::PREFIX_METADATA . "subject:NLTitle_genre",
          self::PREFIX_METADATA . "title.0:NLTitle_title",
          self::PREFIX_METADATA . "title.1:NLDependentTitle_title",
          self::PREFIX_METADATA . "title.2:NLPerson_NLPersonName_preferredFullName",
          self::PREFIX_METADATA . "type:NLProfile_name",
          self::PREFIX_METADATA . "source:NLCore_NLIdentification_sourceRef" 
      );
    } else {
      die ( "unknown metadataPrefix" );
    }
    $request ["cache"] = false;
    return json_encode ( $request );
  }
  protected function filterIdentify($identifyData) {
    $filteredData = array ();
    list ( $newValue, $granularity ) = $this->convertDateTimeToTimeStamp ( $identifyData ["stats"] ["stats_fields"] ["earliestDatestamp"] ["min"] );
    if ($granularity !== null) {
      $filteredData [self::FIELD_EARLIESTDATESTAMP] = $newValue;
    }
    return $filteredData;
  }
  protected function filterSetsNumber($setsNumberData) {
    $filteredData = array ();
    $filteredData [self::FIELD_NUMBER] = intval ( $setsNumberData ["stats"] ["stats_fields"] ["numberOfSets"] ["countDistinct"] );
    return $filteredData;
  }
  protected function filterSetsList($setsListData) {
    $filteredList = array();
    $list = $setsListData ["facet_counts"] ["facet_fields"] ["setsList"];
    for($i = 0; $i < count ( $list ); $i += 2) {
      $filteredList[] = array(\DataProviderObject\Set::SET_SPEC => $list[$i], \DataProviderObject\Set::SET_NAME => $list[$i]);
    }
    return $filteredList;
  }
  protected function filterIdentifiersNumber($identifiersNumberData) {
    $filteredData = array ();
    $filteredData [self::FIELD_NUMBER] = intval ( $identifiersNumberData ["response"] ["numFound"] );
    return $filteredData;
  }
  protected function filterIdentifiersList($identifiersListData) {
    return $identifiersListData["response"] ["docs"];
  }
  protected function filterRecordsNumber($recordsNumberData) {
    return $this->filterIdentifiersNumber($recordsNumberData);
  }
  protected function filterRecordsList($recordsListData) {
    return $recordsListData["response"] ["docs"];
  }
  protected function filterRecord($recordData) {
    if(($list = $recordData ["response"] ["docs"]) && count($list)>0) {
      return $list[0];
    } else {
      return null;
    }
  }
  protected function filterHeader($headerData) {
    $filteredData = array ();
    foreach ( $headerData as $key => $value ) {
      if (preg_match ( "/^" . preg_quote ( self::PREFIX_HEADER ) . "(.*?)$/", $key, $match )) {
        if ($match [1] == "datestamp") {
          list ( $newValue, $granularity ) = $this->convertDateTimeToTimeStamp ( $value, self::GRANULARITY_DATETIME );
          if ($granularity !== null) {
            $filteredData [$match [1]] = $newValue;
          }
        } else {
          $filteredData [$match [1]] = $value;
        }
      }
    }
    return $filteredData;
  }
  protected function filterMetadata($metadataData, $metadataPrefix) {
    $filteredData = array ();
    foreach ( $metadataData as $key => $value ) {
      if (preg_match ( "/^" . preg_quote ( self::PREFIX_METADATA ) . "([^\.]*?)(|\.(.*?))$/", $key, $match )) {
        if (isset ( $filteredData [$match [1]] )) {
          if (! is_array ( $filteredData [$match [1]] )) {
            $filteredData [$match [1]] = array (
                $filteredData [$match [1]] 
            );
          }
          if (is_array ( $value )) {
            $filteredData [$match [1]] = array_merge ( $filteredData [$match [1]], $value );
          } else {
            $filteredData [$match [1]] [] = $value;
          }
        } else {
          $filteredData [$match [1]] = $value;
        }
      }
    }
    return $filteredData;
  }
  private function createItemsConditions($set = null, $from = null, $until = null) {
    $filter = array ();
    if ($set !== null) {
      $filterItem = array ();
      $filterItem ["condition"] = array ();
      $filterItem ["condition"] ["type"] = "equals";
      $filterItem ["condition"] ["field"] = "NLCore_NLAdministrative_sourceCollection";
      $filterItem ["condition"] ["value"] = $set;
      $filter [] = $filterItem;
    }
    if ($from !== null) {
      $filterItem = array ();
      $filterItem ["condition"] = array ();
      $filterItem ["condition"] ["type"] = "range";
      $filterItem ["condition"] ["field"] = "NLCore_NLAdministrative_ingestTime";
      $filterItem ["condition"] ["start"] = $this->convertTimeStampToDateTime ( $from );
      $filter [] = $filterItem;
    }
    if ($until !== null) {
      $filterItem = array ();
      $filterItem ["condition"] = array ();
      $filterItem ["condition"] ["type"] = "range";
      $filterItem ["condition"] ["field"] = "NLCore_NLAdministrative_ingestTime";
      $filterItem ["condition"] ["end"] = $this->convertTimeStampToDateTime ( $until );
      $filter [] = $filterItem;
    }
    return $filter;
  }
}