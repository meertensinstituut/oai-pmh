<?php

namespace OAIPMH;

class Server {
  const ERROR_BADVERB = "badVerb";
  const ERROR_BADARGUMENT = "badArgument";
  const ERROR_NOSETHIERARCHY = "noSetHierarchy";
  const ERROR_BADRESUMPTIONTOKEN = "badResumptionToken";
  const ERROR_NORECORDSMATCH = "noRecordsMatch";
  const ERROR_CANNOTDISSEMNINATEFORMAT = "cannotDisseminateFormat";
  const ERROR_IDDOESNOTEXIST = "idDoesNotExist";
  const ARGUMENT_VERB = "verb";
  const ARGUMENT_IDENTIFIER = "identifier";
  const ARGUMENT_METDATAPREFIX = "metadataPrefix";
  const ARGUMENT_RESUMPTIONTOKEN = "resumptionToken";
  const ARGUMENT_SET = "set";
  const ARGUMENT_FROM = "from";
  const ARGUMENT_UNTIL = "until";
  const VERB_IDENTIFY = "Identify";
  const VERB_LISTMETADATAFORMATS = "ListMetadataFormats";
  const VERB_LISTSETS = "ListSets";
  const VERB_LISTIDENTIFIERS = "ListIdentifiers";
  const VERB_LISTRECORDS = "ListRecords";
  const VERB_GETRECORD = "GetRecord";
  private $baseUrl;
  private $arguments = false;
  private $response_dom;
  private $response_oaipmh;
  private $response_oaipmh_request;
  private $dataProvider;
  private $configuration;
  private $timeoutResumption = 1000;
  public function __construct($dataProvider, $configuration) {
    $this->dataProvider = $dataProvider;
    $this->configuration = $configuration;
    // baseUrl
    $port = $_SERVER ["SERVER_PORT"];
    if (isset ( $_SERVER ["HTTPS"] )) {
      $this->baseUrl = "https://" . $_SERVER ["HTTP_HOST"];      
    } else {
      $this->baseUrl = "http://" . $_SERVER ["HTTP_HOST"];      
    }
    $this->baseUrl .= preg_replace ( "/\?(.*?)$/", "", $_SERVER ["REQUEST_URI"] );
    // request
    if ($_SERVER ["REQUEST_METHOD"] === "POST") {
      $this->arguments = $_POST;
    } else if ($_SERVER ["REQUEST_METHOD"] === "GET") {
      $this->arguments = $_GET;
    } else {
      $this->arguments = null;
    }
    $this->processRequest ();
  }
  private function processRequest() {
    $this->init ();
    if (! $this->arguments || ! is_array ( $this->arguments ) || count ( $this->arguments ) == 0) {
      $this->error ( Server::ERROR_BADVERB, "No OAI arguments" );
    } else if (! isset ( $this->arguments [Server::ARGUMENT_VERB] )) {
      $this->error ( Server::ERROR_BADVERB, "No OAI " . Server::ARGUMENT_VERB . " argument" );
    } else if (! is_string ( $this->arguments [Server::ARGUMENT_VERB] )) {
      $this->error ( Server::ERROR_BADVERB, "Illegal OAI " . Server::ARGUMENT_VERB );
    } else if ($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_IDENTIFY) {
      $this->identify ();
    } else if ($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_LISTMETADATAFORMATS) {
      $this->listMetadataFormats ();
    } else if ($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_LISTSETS) {
      $this->listSets ();
    } else if ($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_LISTIDENTIFIERS || $this->arguments [Server::ARGUMENT_VERB] == Server::VERB_LISTRECORDS) {
      $this->listItems ($this->arguments [Server::ARGUMENT_VERB]);
    } else if ($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_GETRECORD) {
      $this->getRecord ();
    } else {
      $this->error ( Server::ERROR_BADVERB, "Unknown OAI " . Server::ARGUMENT_VERB );
    }
    header ( "Content-type: text/xml" );
    print ($this->response_dom->saveXML ()) ;
    exit ();
  }
  private function init() {
    // create xml
    $this->response_dom = new \DOMDocument ( "1.0", "utf-8" );
    //stylesheet
    $xslt = $this->response_dom->createProcessingInstruction("xml-stylesheet", "type=\"text/xsl\" href=\"layout/oai2transform.xsl\"");    
    $this->response_dom->appendChild($xslt);
    //main xml  
    $this->response_oaipmh = $this->response_dom->createElement ( "OAI-PMH" );
    $attribute_xmlns = $this->response_dom->createAttribute ( "xmlns" );
    $attribute_xmlns->appendChild ( $this->response_dom->createTextNode ( "http://www.openarchives.org/OAI/2.0/" ) );
    $this->response_oaipmh->appendChild ( $attribute_xmlns );
    $attribute_xmlns_xsi = $this->response_dom->createAttribute ( "xmlns:xsi" );
    $attribute_xmlns_xsi->appendChild ( $this->response_dom->createTextNode ( "http://www.w3.org/2001/XMLSchema-instance" ) );
    $this->response_oaipmh->appendChild ( $attribute_xmlns_xsi );
    $attribute_xsi_schemaLocation = $this->response_dom->createAttribute ( "xsi:schemaLocation" );
    $attribute_xsi_schemaLocation->appendChild ( $this->response_dom->createTextNode ( "http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd" ) );
    $this->response_oaipmh->appendChild ( $attribute_xsi_schemaLocation );
    $responseDate = $this->response_dom->createElement ( "responseDate" );
    $responseDate->appendChild ( $this->response_dom->createTextNode ( $this->dataProvider->convertTimeStampToDateTime(time(), \OAIPMH\DataProvider::GRANULARITY_DATETIME) ) );
    $this->response_oaipmh->appendChild ( $responseDate );
    $this->response_dom->appendChild ( $this->response_oaipmh );
    $this->response_oaipmh_request = $this->response_dom->createElement ( "request" );
    $this->response_oaipmh_request->appendChild ( $this->response_dom->createTextNode ( $this->baseUrl ) );
    $this->response_oaipmh->appendChild ( $this->response_oaipmh_request );
    $this->response_dom->appendChild ( $this->response_oaipmh );
  }
  private function error($code, $errorText = null) {
    if ($errorText == null) {
      switch ($code) {
        case Server::ERROR_BADARGUMENT :
          $errorText = "The request includes illegal arguments";
          break;
        case Server::ERROR_NOSETHIERARCHY :
          $errorText = "The repository does not support sets";
          break;
        case Server::ERROR_CANNOTDISSEMNINATEFORMAT :
          $errorText = "The value of the metadataPrefix argument is not supported by the repository";
          break;
        case Server::ERROR_NORECORDSMATCH :
          $errorText = "The combination of the values of the from, until, and set arguments results in an empty list";
          break;
        case Server::ERROR_IDDOESNOTEXIST :
          $errorText = "The value of the identifier argument is unknown or illegal in this repository";
          break;
        case Server::ERROR_BADRESUMPTIONTOKEN :
          $errorText = "The value of the resumptionToken argument is invalid or expired";
          break;
        default :
          die ( "missing error text for " . $code );
      }
    }
    $error = $this->response_dom->createElement ( "error" );
    $error->appendChild ( $this->response_dom->createTextNode ( $errorText ) );
    $attribute_code = $this->response_dom->createAttribute ( "code" );
    $attribute_code->appendChild ( $this->response_dom->createTextNode ( $code ) );
    $error->appendChild ( $attribute_code );
    $this->response_oaipmh->appendChild ( $error );
  }
  private function identify() {
    if (count ( $this->arguments ) > 1) {
      $this->error ( Server::ERROR_BADARGUMENT );
      return;
    } else {
      // set verb attribute
      if (! ($dataProviderResponse = $this->dataProvider->identify ()) || ! $dataProviderResponse instanceof \DataProviderObject\Identify) {
        die ( "invalid identify response from dataProvider" );
      } else {
        $this->createRequestAttribute ( Server::ARGUMENT_VERB, $this->arguments [Server::ARGUMENT_VERB] );
      }
      // response
      $response = $this->response_dom->createElement ( $this->arguments [Server::ARGUMENT_VERB] );
      $response->appendChild ( $this->createSimpleItem ( "repositoryName", $dataProviderResponse->getResponseName () ) );
      $response->appendChild ( $this->createSimpleItem ( "baseURL", $this->baseUrl ) );
      $response->appendChild ( $this->createSimpleItem ( "protocolVersion", "2.0" ) );
      foreach ( $dataProviderResponse->getAdminEmail () as $adminEmail ) {
        $response->appendChild ( $this->createSimpleItem ( "adminEmail", $adminEmail ) );
      }
      $response->appendChild ( $this->createSimpleItem ( "earliestDatestamp", $this->dataProvider->convertTimestampToDateTime($dataProviderResponse->getEarliestDatestamp () )) );
      $response->appendChild ( $this->createSimpleItem ( "deletedRecord", $dataProviderResponse->getDeletedRecord () ) );
      $response->appendChild ( $this->createSimpleItem ( "granularity", $dataProviderResponse->getGranularity () ) );
      $this->response_oaipmh->appendChild ( $response );
    }
  }
  private function listMetadataFormats() {
    $validArguments = 1;
    if (($identifier = $this->checkArgument ( Server::ARGUMENT_IDENTIFIER, false )) != null) {
      $validArguments ++;
    }
    if (count ( $this->arguments ) > $validArguments) {
      $this->error ( Server::ERROR_BADARGUMENT );
      return;
    } else {
      // set verb attribute
      if (! ($dataProviderResponse = $this->dataProvider->listMetadataFormats ( $identifier )) || ! $dataProviderResponse instanceof \DataProviderObject\MetadataFormats) {
        die ( "invalid " . $this->arguments [Server::ARGUMENT_VERB] . " response from dataProvider" );
      } else {
        $this->createRequestAttribute ( Server::ARGUMENT_VERB, $this->arguments [Server::ARGUMENT_VERB] );
        $this->createRequestAttribute ( Server::ARGUMENT_IDENTIFIER, $identifier );
      }
      // response
      $response = $this->response_dom->createElement ( $this->arguments [Server::ARGUMENT_VERB] );
      foreach ( $dataProviderResponse->getMetadataFormats () as $metadataFormatItem ) {
        $metadataFormat = $this->response_dom->createElement ( \DataProviderObject\MetadataFormat::METADATAFORMAT );
        $metadataFormat->appendChild ( $this->createSimpleItem ( \DataProviderObject\MetadataFormat::METADATAFORMAT_METADATAPREFIX, $metadataFormatItem->getMetadataPrefix () ) );
        $metadataFormat->appendChild ( $this->createSimpleItem ( \DataProviderObject\MetadataFormat::METADATAFORMAT_SCHEMA, $metadataFormatItem->getSchema () ) );
        $metadataFormat->appendChild ( $this->createSimpleItem ( \DataProviderObject\MetadataFormat::METADATAFORMAT_METADATANAMESPACE, $metadataFormatItem->getMetadataNamespace () ) );
        $response->appendChild ( $metadataFormat );
      }
      $this->response_oaipmh->appendChild ( $response );
    }
  }
  private function listSets() {
    $validArguments = 1;
    if (($resumptionToken = $this->checkArgument ( Server::ARGUMENT_RESUMPTIONTOKEN, false )) != null) {
      $validArguments ++;
    }
    if (count ( $this->arguments ) > $validArguments) {
      $this->error ( Server::ERROR_BADARGUMENT );
      return;
    } else {
      // set verb attribute
      if (! ($dataProviderObject = $this->dataProvider->listSets ( $resumptionToken )) || ! $dataProviderObject instanceof \DataProviderObject\Sets) {
        die ( "invalid " . $this->arguments [Server::ARGUMENT_VERB] . " response from dataProvider" );
      } else {
        $this->createRequestAttribute ( Server::ARGUMENT_VERB, $this->arguments [Server::ARGUMENT_VERB] );
        $this->createRequestAttribute ( Server::ARGUMENT_RESUMPTIONTOKEN, $resumptionToken );
        if ($dataProviderObject->error ()) {
          $this->error ( $dataProviderObject->getErrorCode (), $dataProviderObject->getErrorText () );
        } else if ($dataProviderObject->getCompleteListSize () <= 0) {
          $this->error ( Server::ERROR_NOSETHIERARCHY );
        } else {
          $response = $this->response_dom->createElement ( $this->arguments [Server::ARGUMENT_VERB] );
          foreach ( $dataProviderObject->getSets () as $setItem ) {
            $set = $this->response_dom->createElement ( \DataProviderObject\Set::SET );
            $set->appendChild ( $this->createSimpleItem ( \DataProviderObject\Set::SET_SPEC, $setItem->getSetSpec () ) );
            $set->appendChild ( $this->createSimpleItem ( \DataProviderObject\Set::SET_NAME, $setItem->getSetName () ) );
            $response->appendChild ( $set );
          }
          if ($resumptionToken != null || $dataProviderObject->needResumption ()) {
            $response->appendChild ( $this->createResumptionToken ( $dataProviderObject ) );
          }
          $this->response_oaipmh->appendChild ( $response );
        }
      }
    }
  }
  
  private function listItems() {
    $resumptionToken = null;
    $set = null;
    $metadataPrefix = null;
    $from = null;
    $fromTimestamp = null;
    $fromGranularity = null;
    $until = null;
    $untilTimestamp = null;
    $untilGranularity = null;
    if (($resumptionToken = $this->checkArgument ( Server::ARGUMENT_RESUMPTIONTOKEN, false )) != null) {
      $validArguments = 2;
    } else {
      if (($metadataPrefix = $this->checkArgument ( Server::ARGUMENT_METDATAPREFIX, true )) != null) {
        $validArguments = 2;
        if (($set = $this->checkArgument ( Server::ARGUMENT_SET, false )) != null) {
          $validArguments ++;
        }
        if (($from = $this->checkArgument ( Server::ARGUMENT_FROM, false )) != null) {
          list($fromTimestamp, $fromGranularity) = $this->dataProvider->convertDateTimeToTimeStamp($from, false);
          if(!$fromTimestamp) {
            $this->error(Server::ERROR_BADARGUMENT, "Illegal date/time for \"from\"");
            return;
          }
          $validArguments ++;
        }
        if (($until = $this->checkArgument ( Server::ARGUMENT_UNTIL, false )) != null) {
          list($untilTimestamp, $untilGranularity) = $this->dataProvider->convertDateTimeToTimeStamp($until, true);
          if(!$untilTimestamp) {
            $this->error(Server::ERROR_BADARGUMENT, "Illegal date/time for \"until\"");
            return;
          } else if($fromGranularity!=null && $fromGranularity!==$untilGranularity) {
            $this->error(Server::ERROR_BADARGUMENT, "Granularity mismatch \"from\" and \"until\"");
          }
          $validArguments ++;
        }
      } else {
        return;
      }
    }
    if (count ( $this->arguments ) > $validArguments) {
      $this->error ( Server::ERROR_BADARGUMENT );
      return;
    }
    //check type
    if($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_LISTIDENTIFIERS) {
      if (! ($dataProviderObject = $this->dataProvider->listIdentifiers ( $resumptionToken, $metadataPrefix, $set, $from, $until ))) {
        die ( "invalid " . $this->arguments [Server::ARGUMENT_VERB] . " response from dataProvider" );
      }
    } else if($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_LISTRECORDS) {      
      if (! ($dataProviderObject = $this->dataProvider->listRecords ( $resumptionToken, $metadataPrefix, $set, $fromTimestamp, $untilTimestamp ))) {
        die ( "invalid " . $this->arguments [Server::ARGUMENT_VERB] . " response from dataProvider" );
      }
    } else {
      die ( "invalid " . $this->arguments [Server::ARGUMENT_VERB] . " response from dataProvider" );
    }
    // set request attributes
    $this->createRequestAttribute ( Server::ARGUMENT_VERB, $this->arguments [Server::ARGUMENT_VERB] );
    $this->createRequestAttribute ( Server::ARGUMENT_METDATAPREFIX, $metadataPrefix );
    $this->createRequestAttribute ( Server::ARGUMENT_SET, $set );
    $this->createRequestAttribute ( Server::ARGUMENT_FROM, $from );
    $this->createRequestAttribute ( Server::ARGUMENT_UNTIL, $until );
    $this->createRequestAttribute ( Server::ARGUMENT_RESUMPTIONTOKEN, $resumptionToken );
    // response
    if ($dataProviderObject->error ()) {
      $this->error ( $dataProviderObject->getErrorCode (), $dataProviderObject->getErrorText () );
    } else if (!$dataProviderObject->getCompleteListSize()) {
      $this->error (Server::ERROR_NORECORDSMATCH);
    } else {
      // create response
      $response = $this->response_dom->createElement ( $this->arguments [Server::ARGUMENT_VERB] );
      if($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_LISTIDENTIFIERS) {
        foreach ( $dataProviderObject->getIdentifiers () as $identifierItem ) {
          $response->appendChild ( $this->createHeader($identifierItem->getHeader()) );
        }
      } else if($this->arguments [Server::ARGUMENT_VERB] == Server::VERB_LISTRECORDS) {
        foreach ( $dataProviderObject->getRecords () as $recordItem ) {
          $response->appendChild ( $this->createRecord($recordItem, $this->dataProvider) );
        }
      }  
      // // resumptionToken
      if ($resumptionToken != null || $dataProviderObject->needResumption ()) {
        $response->appendChild ( $this->createResumptionToken ( $dataProviderObject ) );
      }
      $this->response_oaipmh->appendChild ( $response );
    }
  }
  private function getRecord() {
    $validArguments = 1;
    if (($identifier = $this->checkArgument ( Server::ARGUMENT_IDENTIFIER, true )) != null) {
      $validArguments ++;
    } else {
      return;
    }
    if (($metadataPrefix = $this->checkArgument ( Server::ARGUMENT_METDATAPREFIX, true )) != null) {
      $validArguments ++;
    } else {
      return;
    }
    if (count ( $this->arguments ) > $validArguments) {
      $this->error ( Server::ERROR_BADARGUMENT );
      return;
    } else {
      // get data
      if (! ($dataProviderObject = $this->dataProvider->getRecord ( $identifier, $metadataPrefix )) || ! $dataProviderObject instanceof \DataProviderObject\Record) {
        die ( "invalid " . $this->arguments [Server::ARGUMENT_VERB] . " response from dataProvider" );
      } else {
        $this->createRequestAttribute ( Server::ARGUMENT_VERB, $this->arguments [Server::ARGUMENT_VERB] );      
        $this->createRequestAttribute ( Server::ARGUMENT_IDENTIFIER, $identifier );
        $this->createRequestAttribute ( Server::ARGUMENT_METDATAPREFIX, $metadataPrefix );
        if ($dataProviderObject->error ()) {
          $this->error ( $dataProviderObject->getErrorCode (), $dataProviderObject->getErrorText () );
        } else {
          $recordItem = $this->createRecord($dataProviderObject);
          $response = $this->response_dom->createElement ( $this->arguments [Server::ARGUMENT_VERB] );
          $response->appendChild ( $recordItem );          
          $this->response_oaipmh->appendChild ( $response );
        }  
      }          
    }
  }
  private function checkArgument($name, $obligatory) {
    if (isset ( $this->arguments [$name] )) {
      if (! is_string ( $this->arguments [$name] )) {
        $this->error ( Server::ERROR_BADVERB, "Illegal OAI " . $name );
      } else {
        return $this->arguments [$name];
      }
    } else if ($obligatory) {
      $this->error ( Server::ERROR_BADARGUMENT, "No OAI " . $name . " argument" );
    }
    return null;
  }
  private function createRequestAttribute($name, $value) {
    if ($value != null && (is_string ( $value ) || is_numeric ( $value ))) {
      $attribute = $this->response_dom->createAttribute ( $name );
      $attribute->appendChild ( $this->response_dom->createTextNode ( $value ) );
      $this->response_oaipmh_request->appendChild ( $attribute );
    }
  }
  private function createSimpleItem($name, $value) {
    $item = $this->response_dom->createElement ( $name );
    $item->appendChild ( $this->response_dom->createTextNode ( $value ) );
    return $item;
  }
  private function createResumptionToken($dataProviderObject) {
    $response_resumptionToken = $this->response_dom->createElement ( Server::ARGUMENT_RESUMPTIONTOKEN );
    // completeListSize
    $attribute_resumptionTokenCompleteListSize = $this->response_dom->createAttribute ( "completeListSize" );
    $attribute_resumptionTokenCompleteListSize->appendChild ( $this->response_dom->createTextNode ( $dataProviderObject->getCompleteListSize () ) );
    $response_resumptionToken->appendChild ( $attribute_resumptionTokenCompleteListSize );
    // cursor
    $attribute_resumptionTokenCursor = $this->response_dom->createAttribute ( "cursor" );
    $attribute_resumptionTokenCursor->appendChild ( $this->response_dom->createTextNode ( $dataProviderObject->getCursor () ) );
    $response_resumptionToken->appendChild ( $attribute_resumptionTokenCursor );
    // new token
    if ($dataProviderObject->needResumption ()) {
      list ( $newResumptionToken, $newExpirationDate ) = $this->dataProvider->createResumption ( $this->timeoutResumption, $dataProviderObject );
      // expirationDate
      $attribute_resumptionTokenExpirationDate = $this->response_dom->createAttribute ( "expirationDate" );
      $attribute_resumptionTokenExpirationDate->appendChild ( $this->response_dom->createTextNode ( $newExpirationDate ) );
      $response_resumptionToken->appendChild ( $attribute_resumptionTokenExpirationDate );
      // token
      $response_resumptionToken->appendChild ( $this->response_dom->createTextNode ( $newResumptionToken ) );
    }
    return $response_resumptionToken;
  }
  private function createRecord($recordItem) {
    if($recordItem!=null && $recordItem instanceof \DataProviderObject\Record) {
      $record = $this->response_dom->createElement ( \DataProviderObject\Record::RECORD );
      $record->appendChild($this->createHeader ( $recordItem->getHeader()));
      $listMetadataFormats = $this->dataProvider->listMetadataFormats();
      $metadataFormat = $listMetadataFormats->getMetadataFormat($recordItem->getMetadataPrefix());    
      $record->appendChild($metadataFormat->createMetadata($recordItem->getMetadata(), $this->response_dom));
      return $record;
    } else {
      die("incorrect call createRecord");
    }    
  }
  private function createHeader($headerItem) {
    if($headerItem!=null && $headerItem instanceof \DataProviderObject\Header) {
      $header = $this->response_dom->createElement ( \DataProviderObject\Header::HEADER );
      $header->appendChild ( $this->createSimpleItem ( \DataProviderObject\Header::HEADER_IDENTIFIER, $headerItem->getIdentifier () ) );
      $header->appendChild ( $this->createSimpleItem ( \DataProviderObject\Header::HEADER_DATESTAMP, $this->dataProvider->convertTimestampToDateTime($headerItem->getDatestamp ()) ) );
      foreach ( $headerItem->getSetSpecs () as $setSpec ) {
        $header->appendChild ( $this->createSimpleItem ( \DataProviderObject\Header::HEADER_SETSPEC, $setSpec ) );
      }
      return $header;
    } else {
      die("incorrect call createHeader");
    }
  }  
}