<?php

namespace OAIPMH;

abstract class DataProviderMysql extends DataProvider {
  private $configuration;
  private $database;
  const FIELD_NUMBER = "number";
  const FIELD_EARLIESTDATESTAMP = "earliestDatestamp";
  const CONCAT_LENGTH = "20480";

  public function __construct($cacheDirectory, $configuration) {
    parent::__construct ( $cacheDirectory, $configuration );
    $this->configuration = $configuration;
    $this->database = new \PDO ( "mysql:dbname=" . $this->configuration->get ( "mysqlDatabase" ) . "; host=" . $this->configuration->get ( "mysqlHost" ), $this->configuration->get ( "mysqlUsername" ), $this->configuration->get ( "mysqlPassword" ) );
    $this->database->setAttribute ( \PDO::ATTR_TIMEOUT, 5000 );
    $this->database->exec("SET NAMES 'utf8';");
    $this->database->exec("SET SESSION group_concat_max_len=" . self::CONCAT_LENGTH . ";");
  }
  public function identify() {
    $identify = parent::identify();
    $query = $this->createQuery ( $this->getIdentifySql () );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      $result = $this->filterIdentify($result);
      $identify->setEarliestDatestamp(intval ( $result [self::FIELD_EARLIESTDATESTAMP] ));      
    } 
    return $identify;
  }
  public function listSets($resumptionToken = null) {
    $listSets = parent::listSets ( $resumptionToken );
    if ($resumptionToken == null) {
      $listSets->setCursor ( 0 );
      $query = $this->createQuery ( $this->getSetsNumberSql () );
      if ($query->execute ()) {
        $result = $query->fetch ( \PDO::FETCH_ASSOC );
        unset ( $query );
        $result = $this->filterSetsNumber($result);
        $listSets->setCompleteListSize ( intval ( $result [self::FIELD_NUMBER] ) );
      } else {
        $listSets->setCompleteListSize ( 0 );
      }
    }
    $query = $this->createQuery ( $this->getSetsListSql ( $listSets->getCursor (), $listSets->getStepSize () ) );
    if ($query->execute ()) {
      $result = $this->filterSetsList($query->fetchAll ( \PDO::FETCH_ASSOC ));
      foreach ( $result as $set ) {
        $listSets->addSet ( new \DataProviderObject\Set ( $set ) );
      }
      unset ( $query );
    } else {
      die ( "couldn't get sets from database" );
    }
    return $listSets;
  }
  public function listIdentifiers($resumptionToken = null, $metadataPrefix = null, $set = null, $from = null, $until = null) {
    $listIdentifiers = parent::listIdentifiers ( $resumptionToken, $metadataPrefix, $set, $from, $until );
    if ($resumptionToken == null) {
      $listIdentifiers->setCursor ( 0 );
      $query = $this->createQuery ( $this->getIdentifiersNumberSql ( $listIdentifiers->getMetadataPrefix (), $listIdentifiers->getSet (), $listIdentifiers->getFrom (), $listIdentifiers->getUntil () ) );
      if ($query->execute ()) {
        $result = $query->fetch ( \PDO::FETCH_ASSOC );
        unset ( $query );
        $result = $this->filterIdentifiersNumber($result);        
        $listIdentifiers->setCompleteListSize ( intval ( $result [self::FIELD_NUMBER] ) );
      } else {
        die ( "couldn't get completeListSize" );
      }
    }
    if(!$listIdentifiers->error()) {
      $query = $this->createQuery ( $this->getIdentifiersListSql ( $listIdentifiers->getCursor (), $listIdentifiers->getStepSize (), $listIdentifiers->getMetadataPrefix (), $listIdentifiers->getSet (), $listIdentifiers->getFrom (), $listIdentifiers->getUntil () ) );
      if ($query->execute ()) {
        $result = $this->filterIdentifiersList($query->fetchAll ( \PDO::FETCH_ASSOC ));
        foreach ( $result as $identifierData ) {
          $header = new \DataProviderObject\Header ( $this->filterHeader($identifierData) );
          $identifier = new \DataProviderObject\Identifier ( $header );
          $listIdentifiers->addIdentifier ( $identifier );
        }
        unset ( $query );
      } else {
        die ( "couldn't get identifiers from database" );
      }
    }  
    return $listIdentifiers;
  }
  public function listRecords($resumptionToken = null, $metadataPrefix = null, $set = null, $from = null, $until = null) {
    $listRecords = parent::listRecords ( $resumptionToken, $metadataPrefix, $set, $from, $until );
    if ($resumptionToken == null) {
      $listRecords->setCursor ( 0 );
      $query = $this->createQuery ( $this->getRecordsNumberSql ( $listRecords->getMetadataPrefix (), $listRecords->getSet (), $listRecords->getFrom (), $listRecords->getUntil () ) );
      if ($query->execute ()) {
        $result = $query->fetch ( \PDO::FETCH_ASSOC );
        unset ( $query );
        $result = $this->filterRecordsNumber($result);       
        $listRecords->setCompleteListSize ( intval ( $result [self::FIELD_NUMBER] ) );
      } else {
        die ( "couldn't get completeListSize" );
      }
    }
    if (! $listRecords->error ()) {
      $query = $this->createQuery ( $this->getRecordsListSql ( $listRecords->getCursor (), $listRecords->getStepSize (), $listRecords->getMetadataPrefix (), $listRecords->getSet (), $listRecords->getFrom (), $listRecords->getUntil () ) );
      if ($query->execute ()) {
        foreach ( $query->fetchAll ( \PDO::FETCH_ASSOC ) as $recordData ) {
          $record = new \DataProviderObject\Record ();
          $record->setMetadataPrefix($listRecords->getMetadataPrefix());
          $header = new \DataProviderObject\Header ( $this->filterHeader($recordData) );
          $record->setHeader ( $header );
          $record->setMetadata ( new \DataProviderObject\Metadata ( $this->filterMetadata($recordData, $listRecords->getMetadataPrefix ()) ) );
          $listRecords->addRecord ( $record );
        }
        unset ( $query );
      } else {
        die ( "couldn't get records from database" );
      }
    }
//    die($listRecords);
    return $listRecords;
  }
  public function getRecord($identifier, $metadataPrefix) {
    $getRecord = parent::getRecord ( $identifier, $metadataPrefix );
    $query = $this->createQuery ( $this->getRecordSql ( $getRecord->getIdentifier (), $getRecord->getMetadataPrefix () ) );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      if ($result && ($result = $this->filterRecord($result))) {
         $getRecord->setHeader ( new \DataProviderObject\Header ( $this->filterHeader($result) ) );
         $getRecord->setMetadata ( new \DataProviderObject\Metadata ( $this->filterMetadata($result, $metadataPrefix) ) );
      } else {
        $getRecord->setError ( \OAIPMH\Server::ERROR_IDDOESNOTEXIST );
      }
    } else {
      die ( "couldn't get record" );
    }
    return $getRecord;
  }
  private function createQuery($sqlData) {
    if (is_string ( $sqlData )) {
      return $this->database->prepare ( $sqlData );
    } else if (is_array ( $sqlData ) && count ( $sqlData ) == 2) {
      $sql = $sqlData [0];
      $binds = $sqlData [1];
      if (is_string ( $sql ) && is_array ( $binds )) {
        $query = $this->database->prepare ( $sql );
        foreach ( $binds as $bind ) {
          if (is_array ( $bind ) && count ( $bind ) == 2) {
            if (is_string ( $bind [0] )) {
              $query->bindValue ( $bind [0], $bind [1] );
            } else {
              die ( "incorrect call createQuery (bind)" );
            }
          } else {
            die ( "incorrect call createQuery (bind)" );
          }
        }
      } else {
        die ( "incorrect call createQuery" );
      }
      return $query;
    } else {
      die ( "incorrect call createQuery" );
    }
  }
  protected abstract function getIdentifySql();
  protected abstract function getSetsNumberSql();
  protected abstract function getSetsListSql($cursor, $stepSize);
  protected abstract function getIdentifiersNumberSql($metadataPrefix, $set, $from, $until);
  protected abstract function getIdentifiersListSql($cursor, $stepSize, $metadataPrefix, $set, $from, $until);
  protected abstract function getRecordsNumberSql($metadataPrefix, $set, $from, $until);
  protected abstract function getRecordsListSql($cursor, $stepSize, $metadataPrefix, $set, $from, $until);
  protected abstract function getRecordSql($identifier, $metadataPrefix);
  protected abstract function filterIdentify($identifyData);
  protected abstract function filterSetsNumber($setsNumberData);
  protected abstract function filterSetsList($setsListData);
  protected abstract function filterIdentifiersNumber($identifiersNumberData);
  protected abstract function filterIdentifiersList($identifiersListData);
  protected abstract function filterRecordsNumber($recordsNumberData);
  protected abstract function filterRecordsList($recordsListData);
  protected abstract function filterRecord($recordData);
  protected abstract function filterHeader($headerData);
  protected abstract function filterMetadata($metadataData, $metadataPrefix);  
}