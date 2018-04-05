<?php

namespace DataProviderModule;

use OAIPMH\DataProviderMysql;
use DataProviderObject\MetadataFormatDC;
use DataProviderObject\MetadataFormatIsebel;

class Demo extends \OAIPMH\DataProviderMysql {
  const PREFIX_HEADER = "header.";
  const PREFIX_METADATA = "metadata.";
  public function listMetadataFormats($identifier = null) {
    $metadataFormats = parent::listMetadataFormats ( $identifier );
    $metadataFormats->addMetadataFormat ( new \DataProviderObject\MetadataFormatDC () );
    return $metadataFormats;
  }
  protected function getIdentifySql() {
    list ( $binds, $conditions ) = $this->createItemsConditions ();
    $sql = "SELECT 
              UNIX_TIMESTAMP(MIN(`changed`)) AS :fieldEarliestDatestamp 
              FROM `item`
              WHERE (" . implode ( ") AND (", $conditions ) . ")
              ";
    $binds [] = array (
        ":fieldEarliestDatestamp",
        self::FIELD_EARLIESTDATESTAMP 
    );
    return array (
        $sql,
        $binds 
    );
  }
  protected function getSetsNumberSql() {
    list ( $binds, $conditions ) = $this->createItemsConditions ();
    $sql = "SELECT
              COUNT(DISTINCT(`set`.`id`)) AS :fieldNumber
              FROM `item`
              INNER JOIN `set` 
              ON `set`.`id` = `item`.`set_id`
              WHERE (" . implode ( ") AND (", $conditions ) . ")
              ORDER BY `set`.`id`
              ";
    $binds [] = array (
        ":fieldNumber",
        self::FIELD_NUMBER 
    );
    return array (
        $sql,
        $binds 
    );
  }
  protected function getSetsListSql($cursor, $stepSize) {
    list ( $binds, $conditions ) = $this->createItemsConditions ();
    $sql = "SELECT
                `set`.`spec` AS `setSpec`,
                `set`.`name` AS `setName`,
                `set`.`description` AS `setDescription`
              FROM `set`
              INNER JOIN `item` 
              ON `set`.`id` = `item`.`set_id`
              WHERE (" . implode ( ") AND (", $conditions ) . ")
              GROUP BY `set`.`id`
              ORDER BY `set`.`id`
              LIMIT " . intval ( $cursor ) . "," . intval ( $stepSize );
    return array (
        $sql,
        $binds 
    );
  }
  protected function getIdentifiersNumberSql($metadataPrefix, $set, $from, $until) {
    list ( $binds, $conditions ) = $this->createItemsConditions ( $set, $from, $until );
    $sql = "SELECT
                COUNT(*) AS :fieldNumber
              FROM `item`
              LEFT JOIN `set`
              ON `item`.`set_id` = `set`.`id`
              WHERE (" . implode ( ") AND (", $conditions ) . ")";
    $binds [] = array (
        ":fieldNumber",
        self::FIELD_NUMBER 
    );
    return array (
        $sql,
        $binds 
    );
  }
  protected function getIdentifiersListSql($cursor, $stepSize, $metadataPrefix, $set, $from, $until) {
    list ( $binds, $conditions ) = $this->createItemsConditions ( $set, $from, $until );
    $sql = "SELECT
                `item`.`id` AS `" . self::PREFIX_HEADER . "identifier`,
                UNIX_TIMESTAMP(`item`.`changed`) AS `" . self::PREFIX_HEADER . "datestamp`,
                `set`.`spec` AS `" . self::PREFIX_HEADER . "setSpec`,
                `item`.`identifier` AS `" . self::PREFIX_METADATA . "identifier`
              FROM `item`
              LEFT JOIN `set`
              ON `item`.`set_id` = `set`.`id`
              WHERE (" . implode ( ") AND (", $conditions ) . ")
              ORDER BY `item`.`id`
              LIMIT " . intval ( $cursor ) . "," . intval ( $stepSize ) . "
              ";
    return array (
        $sql,
        $binds 
    );
  }
  protected function getRecordsNumberSql($metadataPrefix, $set, $from, $until) {
    return $this->getIdentifiersNumberSql ( $metadataPrefix, $set, $from, $until );
  }
  protected function getRecordsListSql($cursor, $stepSize, $metadataPrefix, $set, $from, $until) {
    list ( $binds, $conditions ) = $this->createItemsConditions ( $set, $from, $until );
    if ($metadataPrefix == MetadataFormatDC::METADATAPREFIX) {
      $sql = "SELECT
                `item`.`id` AS `" . self::PREFIX_HEADER . "identifier`,
                UNIX_TIMESTAMP(`item`.`changed`) AS `" . self::PREFIX_HEADER . "datestamp`,
                `set`.`spec` AS `" . self::PREFIX_HEADER . "setSpec`,
                `item`.`identifier` AS `" . self::PREFIX_METADATA . "identifier`,
                `item`.`title` AS `" . self::PREFIX_METADATA . "title`,
                `item`.`relation` AS `" . self::PREFIX_METADATA . "relation`
              FROM `item`
              LEFT JOIN `set`
              ON `item`.`set_id` = `set`.`id`
              WHERE (" . implode ( ") AND (", $conditions ) . ")
              ORDER BY `item`.`id`
              LIMIT " . intval ( $cursor ) . "," . intval ( $stepSize ) . "
              ";
    } else {
      die ( "unknown metadataPrefix" );
    }
    return array (
        $sql,
        $binds 
    );
  }
  protected function getRecordSql($identifier, $metadataPrefix) {
    list ( $binds, $conditions ) = $this->createItemsConditions ();
    $conditions [] = "`item`.`id` = :identifier";
    $binds [] = array (
        ":identifier",
        $identifier 
    );
    if ($metadataPrefix == MetadataFormatDC::METADATAPREFIX) {
      $sql = "SELECT
                `item`.`id` AS `" . self::PREFIX_HEADER . "identifier`,
                UNIX_TIMESTAMP(`item`.`changed`) AS `" . self::PREFIX_HEADER . "datestamp`,
                `set`.`spec` AS `" . self::PREFIX_HEADER . "setSpec`,
                `item`.`identifier` AS `" . self::PREFIX_METADATA . "identifier`,
                `item`.`title` AS `" . self::PREFIX_METADATA . "title`,
                `item`.`relation` AS `" . self::PREFIX_METADATA . "relation`
              FROM `item`
              LEFT JOIN `set`
              ON `item`.`set_id` = `set`.`id`
              WHERE (" . implode ( ") AND (", $conditions ) . ")              
              GROUP BY `item`.`id`
              ";
    } else {
      die ( "unknown metadataPrefix" );
    }
    return array (
        $sql,
        $binds 
    );
  }
  protected function filterIdentify($identifyData) {
    return $identifyData;
  }
  protected function filterSetsNumber($setsNumberData) {
    return $setsNumberData;
  }
  protected function filterSetsList($setsListData) {
    return $setsListData;
  }
  protected function filterIdentifiersNumber($identifiersNumberData) {
    return $identifiersNumberData;
  }
  protected function filterIdentifiersList($identifiersListData) {
    return $identifiersListData;
  }
  protected function filterRecordsNumber($recordsNumberData) {
    return $recordsNumberData;
  }
  protected function filterRecordsList($recordsListData) {
    return $recordsListData;
  }
  protected function filterRecord($recordData) {
    return $recordData;
  }
  protected function filterHeader($headerData) {
    $filteredData = array ();
    foreach ( $headerData as $key => $value ) {
      if (preg_match ( "/^" . preg_quote ( self::PREFIX_HEADER ) . "(.*?)$/", $key, $match )) {
        $filteredData [$match [1]] = $value;
      }
    }
    return $filteredData;
  }
  protected function filterMetadata($metadataData, $metadataPrefix) {
    $filteredData = array ();
    foreach ( $metadataData as $key => $value ) {
      if (preg_match ( "/^" . preg_quote ( self::PREFIX_METADATA ) . "(.*?)$/", $key, $match )) {
        $filteredData [$match [1]] = $value;
      }
    }
    return $filteredData;
  }
  private function createItemsConditions($set = null, $from = null, $until = null) {
    $binds = array ();
    $conditions = array ();
    $conditions [] = "1";
    if ($set !== null) {
      $conditions [] = "`set`.`spec` = :set";
      $binds [] = array (
          ":set",
          $set 
      );
    }
    if ($from !== null) {
      $conditions [] = "`item`.`changed` >= :from";
      $binds [] = array (
          ":from",
          date ( "Y-m-d H:i:s", $from ) 
      );
    }
    if ($until !== null) {
      $conditions [] = "`item`.`changed` <= :until";
      $binds [] = array (
          ":until",
          date ( "Y-m-d H:i:s", $until ) 
      );
    }    
    return array (
        $binds,
        $conditions 
    );
  }
}