<?php

namespace OAIPMH;

abstract class DataProvider {
  private $databaseFilename;
  private $database;
  private $configuration;
  private $granularity;
  const TYPE_SETS = "sets";
  const TYPE_IDENTIFIERS = "identifiers";
  const TYPE_RECORDS = "records";
  const GRANULARITY_DATE = "YYYY-MM-DD";
  const GRANULARITY_DATETIME = "YYYY-MM-DDThh:mm:ssZ";
  public function __construct($cacheDirectory, $configuration) {
    $this->configuration = $configuration;
    // datetime granularity
    if ($this->configuration->variableSet ( "granularity" )) {
      $this->granularity = $this->getGranularity ( $this->configuration->get ( "granularity" ) );
    } else {
      $this->granularity = self::GRANULARITY_DATETIME;
    }
    // database connection for resumptionToken
    $this->databaseFilename = $cacheDirectory . "/" . get_class ( $this ) . ".sqlite";
    if (! file_exists ( $cacheDirectory ) || ! is_dir ( $cacheDirectory )) {
      die ( "directory " . $cacheDirectory . " doesn't exist" );
    } else if (! is_writable ( $cacheDirectory )) {
      die ( "directory " . $cacheDirectory . " not writeable" );
    } else if (file_exists ( $this->databaseFilename ) && ! is_writable ( $this->databaseFilename )) {
      die ( "problem accessing " . $this->databaseFilename );
    } else {
      // always reconstruct database after 1 day unchanged
      if (file_exists ( $this->databaseFilename ) && ($modificationTime = filemtime ( $this->databaseFilename )) && (time () - $modificationTime) > 86400) {
        unlink ( $this->databaseFilename );
      }
      $skipInit = file_exists ( $this->databaseFilename );
      $this->database = new \PDO ( "sqlite:" . $this->databaseFilename );
      $this->database->setAttribute ( \PDO::ATTR_TIMEOUT, 5000 );
      if (! $skipInit) {
        $this->initDatabase ();
      }
    }
  }
  private function initDatabase() {
    $sql = "CREATE TABLE IF NOT EXISTS \"resumptionToken\" (
          \"id\" INTEGER PRIMARY KEY ASC,
          \"token\" TEXT NOT NULL,
          \"type\" TEXT NOT NULL,
          \"cursor\" INTEGER,
          \"completeListSize\" INTEGER,
          \"metadataPrefix\" TEXT,
          \"set\" TEXT,
          \"from\" TEXT,
          \"until\" TEXT,
          \"expires\" TEXT NOT NULL);";
    $query = $this->database->prepare ( $sql );
    $query->execute ();
    unset ( $query );
  }
  public function identify() {
    $identify = new \DataProviderObject\Identify ( $this->getGranularity () );
    if ($this->configuration->variableSet ( "identifyResponseName" )) {
      $identify->setResponseName ( $this->configuration->get ( "identifyResponseName" ) );
    }
    if ($this->configuration->variableSet ( "identifyAdminEmail" )) {
      $identify->setAdminEmail ( $this->configuration->get ( "identifyAdminEmail" ) );
    }
    return $identify;
  }
  public function listMetadataFormats($identifier = null) {
    return new \DataProviderObject\MetadataFormats ( $identifier );
  }
  public function listSets($resumptionToken = null) {
    $sets = new \DataProviderObject\Sets ();
    if ($resumptionToken != null) {
      if ($data = $this->getResumption ( $resumptionToken, self::TYPE_SETS )) {
        $sets->setCursor ( intval ( $data ["cursor"] ) );
        $sets->setCompleteListSize ( intval ( $data ["completeListSize"] ) );
      } else {
        $sets->setError ( Server::ERROR_BADRESUMPTIONTOKEN );
      }
    }
    return $sets;
  }
  public function listIdentifiers($resumptionToken = null, $metadataPrefix = null, $set = null, $from = null, $until = null) {
    if ($resumptionToken != null) {
      if ($data = $this->getResumption ( $resumptionToken, self::TYPE_IDENTIFIERS )) {
        $identifiers = new \DataProviderObject\Identifiers ( $data [Server::ARGUMENT_METDATAPREFIX], $data [Server::ARGUMENT_SET], $data [Server::ARGUMENT_FROM], $data [Server::ARGUMENT_UNTIL] );
        $identifiers->setCursor ( intval ( $data ["cursor"] ) );
        $identifiers->setCompleteListSize ( intval ( $data ["completeListSize"] ) );
      } else {
        $identifiers = new \DataProviderObject\Identifiers ( $metadataPrefix, $set, $from, $until );
        $identifiers->setError ( Server::ERROR_BADRESUMPTIONTOKEN );
      }
    } else {
      $identifiers = new \DataProviderObject\Identifiers ( $metadataPrefix, $set, $from, $until );
      $listMetadataFormats = $this->listMetadataFormats ();
      if (! $listMetadataFormats->metadataPrefixAvailable ( $metadataPrefix )) {
        $identifiers->setError ( Server::ERROR_CANNOTDISSEMNINATEFORMAT );
      }
    }
    return $identifiers;
  }
  public function listRecords($resumptionToken = null, $metadataPrefix = null, $set = null, $from = null, $until = null) {
    if ($resumptionToken != null) {
      if ($data = $this->getResumption ( $resumptionToken, self::TYPE_RECORDS )) {
        $records = new \DataProviderObject\Records ( $data [Server::ARGUMENT_METDATAPREFIX], $data [Server::ARGUMENT_SET], $data [Server::ARGUMENT_FROM], $data [Server::ARGUMENT_UNTIL] );
        $records->setCursor ( intval ( $data ["cursor"] ) );
        $records->setCompleteListSize ( intval ( $data ["completeListSize"] ) );
      } else {
        $records = new \DataProviderObject\Records ( $metadataPrefix, $set, $from, $until );
        $records->setError ( Server::ERROR_BADRESUMPTIONTOKEN );
      }
    } else {
      $records = new \DataProviderObject\Records ( $metadataPrefix, $set, $from, $until );
      $listMetadataFormats = $this->listMetadataFormats ();
      if (! $listMetadataFormats->metadataPrefixAvailable ( $metadataPrefix )) {
        $records->setError ( Server::ERROR_CANNOTDISSEMNINATEFORMAT );
      }
    }
    return $records;
  }
  public function getRecord($identifier, $metadataPrefix) {
    $record = new \DataProviderObject\Record ( $identifier, $metadataPrefix );
    $record->setIdentifier ( $identifier );
    $listMetadataFormats = $this->listMetadataFormats ();
    if (! $listMetadataFormats->metadataPrefixAvailable ( $metadataPrefix )) {
      $record->setError ( Server::ERROR_CANNOTDISSEMNINATEFORMAT );
    } else {
      $record->setMetadataPrefix ( $metadataPrefix);
    }  
    return $record;
  }
  public function getResumption($token, $type) {
    $sql = "SELECT * FROM \"resumptionToken\"
    WHERE token IS :token AND type IS :type AND expires >= datetime(:now, 'unixepoch')";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":token", $token );
    $query->bindValue ( ":type", $type );
    $query->bindValue ( ":now", time () );
    if ($query->execute ()) {
      $result = $query->fetch ( \PDO::FETCH_ASSOC );
      unset ( $query );
      return $result;
    } else {
      return null;
    }
  }
  private function createResumptionToken($timeout, $type, $cursor, $completeListSize, $metadataPrefix, $set, $from, $until) {
    // create token
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $charactersLength = strlen ( $characters );
    $resumptionToken = "";
    for($i = 0; $i < 32; $i ++) {
      $resumptionToken .= $characters [rand ( 0, $charactersLength - 1 )];
    }
    $expiration = time () + $timeout;
    // store in database
    $this->clearResumption ();
    $sql = "INSERT OR IGNORE INTO \"resumptionToken\"
    (\"token\", \"type\", \"cursor\", \"completeListSize\", \"metadataPrefix\", \"set\", \"from\", \"until\", \"expires\")
    VALUES (:token, :type, :cursor, :completeListSize, :metadataPrefix, :set, :from, :until, datetime(:expires, 'unixepoch'))";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":token", $resumptionToken );
    $query->bindValue ( ":type", $type );
    $query->bindValue ( ":cursor", $cursor );
    $query->bindValue ( ":completeListSize", $completeListSize );
    $query->bindValue ( ":metadataPrefix", $metadataPrefix );
    $query->bindValue ( ":set", $set );
    $query->bindValue ( ":from", $from );
    $query->bindValue ( ":until", $until );
    $query->bindValue ( ":expires", $expiration );
    $query->execute ();
    unset ( $query );
    // return
    return array (
        $resumptionToken,
        $this->convertTimeStampToDateTime ( time (), self::GRANULARITY_DATETIME ) 
    );
  }
  public function createResumption($timeout, $dataProviderObject) {
    if ($dataProviderObject instanceof \DataProviderObject\Sets) {
      return $this->createResumptionToken ( $timeout, self::TYPE_SETS, $dataProviderObject->getCursor () + $dataProviderObject->getStepSize (), $dataProviderObject->getCompleteListSize (), null, null, null, null );
    } else if ($dataProviderObject instanceof \DataProviderObject\Identifiers) {
      return $this->createResumptionToken ( $timeout, self::TYPE_IDENTIFIERS, $dataProviderObject->getCursor () + $dataProviderObject->getStepSize (), $dataProviderObject->getCompleteListSize (), $dataProviderObject->getMetadataPrefix (), $dataProviderObject->getSet (), $dataProviderObject->getFrom (), $dataProviderObject->getUntil () );
    } else if ($dataProviderObject instanceof \DataProviderObject\Records) {
      return $this->createResumptionToken ( $timeout, self::TYPE_RECORDS, $dataProviderObject->getCursor () + $dataProviderObject->getStepSize (), $dataProviderObject->getCompleteListSize (), $dataProviderObject->getMetadataPrefix (), $dataProviderObject->getSet (), $dataProviderObject->getFrom (), $dataProviderObject->getUntil () );
    } else {
      die ( "incorrect call createResumption" );
    }
  }
  public function clearResumption() {
    $sql = "DELETE FROM \"resumptionToken\" WHERE expires < datetime(:now, 'unixepoch');";
    $query = $this->database->prepare ( $sql );
    $query->bindValue ( ":now", time () );
    $query->execute ();
    unset ( $query );
  }
  public function convertDateTimeToTimeStamp($dateTime, $until=false, $granularity = null) {
    if ($granularity == null) {
      $granularity = $this->granularity;
    } else if (! is_string ( $granularity )) {
      die ( "incorrect provided granularity in convertTimeStampToDateTime" );
    }
    if (is_string ( $dateTime )) {
      if ($granularity == self::GRANULARITY_DATETIME) {
        if (preg_match ( "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}Z$/", $dateTime )) {
          $timeStamp = strtotime ( $dateTime );
          return array (
              $timeStamp,
              self::GRANULARITY_DATETIME 
          );
        }
      }
      if ($granularity == self::GRANULARITY_DATETIME || $granularity == self::GRANULARITY_DATE) {
        if (preg_match ( "/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/", $dateTime )) {
          $timeStamp = strtotime ( $dateTime );
          if($until) {
            $timeStamp+=((24*60*60)-1);
          }
          return array (
              $timeStamp,
              self::GRANULARITY_DATE 
          );
        }
      }
    }
    return array (
        null,
        null 
    );
  }
  public function convertTimeStampToDateTime($timeStamp, $granularity = null) {
    if ($granularity == null) {
      $granularity = $this->granularity;
    } else if (! is_string ( $granularity )) {
      die ( "incorrect provided granularity in convertTimeStampToDateTime" );
    }
    if (is_numeric ( $timeStamp )) {
      if ($granularity == self::GRANULARITY_DATETIME) {
        return gmdate ( "Y-m-d\TH:i:s\Z", $timeStamp );
      } else if ($granularity == self::GRANULARITY_DATE) {
        return gmdate ( "Y-m-d", $timeStamp );
      } else {
        die ( "incorrect granularity" );
      }
    } else {
      die ( "incorrect call convertTimeStampToDateTime" );
    }
  }
  protected function getGranularity($granularity = null) {
    if ($granularity == null) {
      return $this->granularity;
    } else if (is_string ( $granularity ) && ($granularity == self::GRANULARITY_DATE || $granularity == self::GRANULARITY_DATETIME)) {
      return $granularity;
    } else {
      die ( "incorrect granularity provided" );
    }
  }
}