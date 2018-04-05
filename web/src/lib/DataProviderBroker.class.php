<?php

namespace OAIPMH;

abstract class DataProviderBroker extends DataProvider {
  private $configuration;
  private $brokerUrl;
  private $brokerKey;
  const FIELD_NUMBER = "number";
  const FIELD_EARLIESTDATESTAMP = "earliestDatestamp";  
  public function __construct($cacheDirectory, $configuration) {
    parent::__construct ( $cacheDirectory, $configuration );
    $this->configuration = $configuration;
    $this->brokerUrl = $configuration->get ( "brokerUrl" );
    $this->brokerKey = $configuration->get ( "brokerKey" );
  }
  public function identify() {
    $identify = parent::identify();
    if (($response = $this->executeBrokerQuery ( $this->getIdentifiyBrokerQuery () )) !== null) {
      $response = $this->filterIdentify($response) ;
      $identify->setEarliestDatestamp( intval ( $response [self::FIELD_EARLIESTDATESTAMP] ));
    } else {
      die ( "couldn't get earliestDatestamp" );
    }    
    return $identify;
  }
  public function listSets($resumptionToken = null) {
    $listSets = parent::listSets ( $resumptionToken );
    if ($resumptionToken == null) {
      $listSets->setCursor ( 0 );
      if (($response = $this->executeBrokerQuery ( $this->getSetsNumberBrokerQuery () )) !== null) {
        $numberResponse = $this->filterSetsNumber($response) ;
        $listSets->setCompleteListSize ( intval ( $numberResponse[self::FIELD_NUMBER] ) );
      } else {
        die ( "couldn't get completeListSize" );
      }
    }
    if (($response = $this->executeBrokerQuery ( $this->getSetsListBrokerQuery ( $listSets->getCursor (), $listSets->getStepSize () ) )) != null) {      
      $response = $this->filterSetsList($response) ;
      foreach ( $response as $set ) {
        $listSets->addSet ( new \DataProviderObject\Set ( $set ) );
      }      
    } else {
      die ( "couldn't get sets from broker" );
    }
    return $listSets;
  }
  public function listIdentifiers($resumptionToken = null, $metadataPrefix = null, $set = null, $from = null, $until = null) {
    $listIdentifiers = parent::listIdentifiers ( $resumptionToken, $metadataPrefix, $set, $from, $until );
    if (! $listIdentifiers->error ()) {
      if (($response = $this->executeBrokerQuery ( $this->getIdentifiersListBrokerQuery ( $listIdentifiers->getCursor (), $listIdentifiers->getStepSize (), $listIdentifiers->getMetadataPrefix (), $listIdentifiers->getSet (), $listIdentifiers->getFrom (), $listIdentifiers->getUntil () ) )) != null) {
        if ($resumptionToken == null) {
          $numberResponse = $this->filterIdentifiersNumber($response) ;          
          $listIdentifiers->setCursor ( 0 );
          $listIdentifiers->setCompleteListSize ( intval ( $numberResponse[self::FIELD_NUMBER] ) );
        }
        $listResponse = $this->filterIdentifiersList($response);
        foreach ( $listResponse as $identifierData ) {
          $header = new \DataProviderObject\Header ( $this->filterHeader ( $identifierData ) );
          $identifier = new \DataProviderObject\Identifier ( $header );
          $listIdentifiers->addIdentifier ( $identifier );
        }
      } else {
        die ( "couldn't get identifiers from broker" );
      }
    }
    return $listIdentifiers;
  }
  public function listRecords($resumptionToken = null, $metadataPrefix = null, $set = null, $from = null, $until = null) {
    $listRecords = parent::listRecords ( $resumptionToken, $metadataPrefix, $set, $from, $until );
    if (! $listRecords->error ()) {
      if (($response = $this->executeBrokerQuery ( $this->getRecordsListBrokerQuery ( $listRecords->getCursor (), $listRecords->getStepSize (), $listRecords->getMetadataPrefix (), $listRecords->getSet (), $listRecords->getFrom (), $listRecords->getUntil () ) )) != null) {
        if ($resumptionToken == null) {
          $numberResponse = $this->filterRecordsNumber($response) ;          
          $listRecords->setCursor ( 0 );
          $listRecords->setCompleteListSize ( intval ( $numberResponse[self::FIELD_NUMBER] ) );
        }
        $listResponse = $this->filterRecordsList($response);
        foreach ( $listResponse as $recordData ) {
          $record = new \DataProviderObject\Record ();
          $record->setMetadataPrefix ( $listRecords->getMetadataPrefix () );
          $header = new \DataProviderObject\Header ( $this->filterHeader ( $recordData ) );
          $record->setHeader ( $header );
          $record->setMetadata ( new \DataProviderObject\Metadata ( $this->filterMetadata ( $recordData, $listRecords->getMetadataPrefix () ) ) );
          $listRecords->addRecord ( $record );
        }
      } else {
        die ( "couldn't get records from broker" );
      }
    }
    return $listRecords;
  }
  public function getRecord($identifier, $metadataPrefix) {
    $getRecord = parent::getRecord ( $identifier, $metadataPrefix );
    if (($response = $this->executeBrokerQuery ( $this->getRecordBrokerQuery ( $getRecord->getIdentifier (), $getRecord->getMetadataPrefix () ) )) != null) {
      if ($response = $this->filterRecord($response)) {
        $header = new \DataProviderObject\Header ( $this->filterHeader ( $response ) );
        $getRecord->setHeader ( $header );
        $getRecord->setMetadata ( new \DataProviderObject\Metadata ( $this->filterMetadata ( $response, $getRecord->getMetadataPrefix () ) ) );
      } else {
        $getRecord->setError ( \OAIPMH\Server::ERROR_IDDOESNOTEXIST );
      }
    } else {
      die ( "couldn't get record from broker" );
    }
    return $getRecord;
  }
  private function executeBrokerQuery($brokerQuery) {
    if (is_string ( $brokerQuery )) {
      $header = array (
          "Content-Type: application/json",
          "Content-Length: " . strlen ( $brokerQuery ) 
      );
      if ($this->brokerKey !== null) {
        $header [] = "X-Broker-key: " . $this->brokerKey;
      }
      $ch = curl_init ( $this->brokerUrl );
      curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
      curl_setopt ( $ch, CURLOPT_POSTFIELDS, $brokerQuery );
      curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
      $result = curl_exec ( $ch );
      return json_decode ( $result, true );
    } else {
      die ( "incorrect call executeBrokerQuery" );
    }
  }
  protected abstract function getIdentifiyBrokerQuery();
  protected abstract function getSetsNumberBrokerQuery();
  protected abstract function getSetsListBrokerQuery($cursor, $stepSize);
  protected abstract function getIdentifiersListBrokerQuery($cursor, $stepSize, $metadataPrefix, $set, $from, $until);
  protected abstract function getRecordsListBrokerQuery($cursor, $stepSize, $metadataPrefix, $set, $from, $until);
  protected abstract function getRecordBrokerQuery($identifier, $metadataPrefix);
  protected abstract function filterIdentify($identifyData);
  protected abstract function filterSetsNumber($setsNumberData);
  protected abstract function filterSetsList($setsListData);
  protected abstract function filterIdentifiersNumber($identifiersNumberData);
  protected abstract function filterIdentifiersList($identifiersListData);
  protected abstract function filterRecordsNumber($recordsNumberData);
  protected abstract function filterRecordsList($recordsListData);
  protected abstract function filterRecord($record);
  protected abstract function filterHeader($headerData);
  protected abstract function filterMetadata($metadataData, $metadataPrefix);
}