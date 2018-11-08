<?php

namespace DataProviderModule;

use OAIPMH\DataProviderMysql;
use DataProviderObject\MetadataFormatDC;
use DataProviderObject\MetadataFormatIsebel;

class Verhalenbank extends \OAIPMH\DataProviderMysql
{
    const SPLIT_CHARACTER1 = "|-|-|-|-|-|";
    const SPLIT_CHARACTER2 = "|*|*|*|*|*|";
    const PREFIX_HEADER = "header.";
    const PREFIX_METADATA = "metadata.";


    public function listMetadataFormats($identifier = null)
    {
        $metadataFormats = parent::listMetadataFormats($identifier);
        $metadataFormats->addMetadataFormat(new \DataProviderObject\MetadataFormatDC ());
        $metadataFormats->addMetadataFormat(new \DataProviderObject\MetadataFormatIsebel ());
        return $metadataFormats;
    }

    protected function getIdentifySql()
    {
        list ($binds, $conditions) = $this->createItemsConditions();
        $sql = "SELECT 
              UNIX_TIMESTAMP(MIN(`created`)) AS :fieldEarliestDatestamp 
              FROM `omeka_items`
              WHERE (" . implode(") AND (", $conditions) . ")
              ";
        $binds[] = array(":fieldEarliestDatestamp", self::FIELD_EARLIESTDATESTAMP);
        return array(
            $sql,
            $binds
        );
    }

    protected function getSetsNumberSql()
    {
        list ($binds, $conditions) = $this->createItemsConditions();
        $sql = "SELECT
              COUNT(DISTINCT(`omeka_collections`.`id`)) AS :fieldNumber
              FROM `omeka_collections`
              INNER JOIN `omeka_items` 
              ON `omeka_collections`.`id` = `omeka_items`.`collection_id`
              WHERE (" . implode(") AND (", $conditions) . ")
              ORDER BY `omeka_collections`.`id`
              ";
        $binds[] = array(":fieldNumber", self::FIELD_NUMBER);
        return array(
            $sql,
            $binds
        );
    }

    protected function getSetsListSql($cursor, $stepSize)
    {
        list ($binds, $conditions) = $this->createItemsConditions();
        $sql = "SELECT
                `omeka_collections`.`id` AS `setSpec`,
                `omeka_element_texts`.`text` AS `setName`,
                `omeka_element_texts`.`text` AS `setDescription`
              FROM `omeka_collections`
              INNER JOIN `omeka_items` 
              ON `omeka_collections`.`id` = `omeka_items`.`collection_id`
              LEFT JOIN `omeka_element_texts`
              ON `omeka_collections`.`id` = `omeka_element_texts`.`record_id`
              AND `omeka_element_texts`.`record_type` = 'Collection'
              AND `omeka_element_texts`.`element_id` = 50
              WHERE (" . implode(") AND (", $conditions) . ")
              GROUP BY `omeka_collections`.`id`
              ORDER BY `omeka_collections`.`id`
              LIMIT " . intval($cursor) . "," . intval($stepSize);
        return array(
            $sql,
            $binds
        );
    }

    protected function getIdentifiersNumberSql($metadataPrefix, $set, $from, $until)
    {
        list ($binds, $conditions) = $this->createItemsConditions($set, $from, $until);
        $sql = "SELECT
                COUNT(*) AS :fieldNumber
              FROM `omeka_items`
              LEFT JOIN `omeka_collections`
              ON `omeka_items`.`collection_id` = `omeka_collections`.`id`
              WHERE (" . implode(") AND (", $conditions) . ")";
        $binds[] = array(":fieldNumber", self::FIELD_NUMBER);
        return array(
            $sql,
            $binds
        );
    }

    protected function getIdentifiersListSql($cursor, $stepSize, $metadataPrefix, $set, $from, $until)
    {
        list ($binds, $conditions) = $this->createItemsConditions($set, $from, $until);
        $sql = "SELECT
                `omeka_items`.`id` AS `" . self::PREFIX_HEADER . "identifier`,
                UNIX_TIMESTAMP(`omeka_items`.`modified`) AS `" . self::PREFIX_HEADER . "datestamp`,
                `omeka_items`.`collection_id` AS `" . self::PREFIX_HEADER . "setSpec`
              FROM `omeka_items`
              LEFT JOIN `omeka_collections`
              ON `omeka_items`.`collection_id` = `omeka_collections`.`id`
              WHERE (" . implode(") AND (", $conditions) . ")
              ORDER BY `omeka_items`.`id`
              LIMIT " . intval($cursor) . "," . intval($stepSize) . "
              ";
        return array(
            $sql,
            $binds
        );
    }

    protected function getRecordsNumberSql($metadataPrefix, $set, $from, $until)
    {
        return $this->getIdentifiersNumberSql($metadataPrefix, $set, $from, $until);
    }

    protected function getRecordsListSql($cursor, $stepSize, $metadataPrefix, $set, $from, $until)
    {
        list ($binds, $conditions) = $this->createItemsConditions($set, $from, $until);
        if ($metadataPrefix == MetadataFormatDC::METADATAPREFIX) {
            $sql = "SELECT
                  `omeka_items`.`id` AS `" . self::PREFIX_HEADER . "identifier`,
                  UNIX_TIMESTAMP(`omeka_items`.`modified`) AS `" . self::PREFIX_HEADER . "datestamp`,
                  `omeka_items`.`collection_id` AS `" . self::PREFIX_HEADER . "setSpec`,
                  GROUP_CONCAT(DISTINCT(`dc_contributor`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "contributor`,
                  GROUP_CONCAT(DISTINCT(`dc_coverage`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "coverage`,
                  GROUP_CONCAT(DISTINCT(`dc_creator`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "creator`,
                  GROUP_CONCAT(DISTINCT(`dc_date`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "date`,
                  GROUP_CONCAT(DISTINCT(`dc_description`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "description`,
                  GROUP_CONCAT(DISTINCT(`dc_format`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "format`,
                  GROUP_CONCAT(DISTINCT(`dc_identifier`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "identifier`,
                  GROUP_CONCAT(DISTINCT(`dc_language`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "language`,
                  GROUP_CONCAT(DISTINCT(`dc_publisher`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "publisher`,
                  GROUP_CONCAT(DISTINCT(`dc_relation`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "relation`,
                  GROUP_CONCAT(DISTINCT(`dc_rights`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "rights`,
                  GROUP_CONCAT(DISTINCT(`dc_source`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "source`,
                  GROUP_CONCAT(DISTINCT(`dc_subject`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "subject`,
                  GROUP_CONCAT(DISTINCT(`dc_title`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "title`,
                  GROUP_CONCAT(DISTINCT(`dc_type`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "type`
                FROM `omeka_items`
                LEFT JOIN `omeka_collections`
                ON `omeka_items`.`collection_id` = `omeka_collections`.`id`
                LEFT JOIN `omeka_element_texts` AS `dc_contributor`
                ON `dc_contributor`.`record_type` = 'Item' AND `dc_contributor`.`record_id` = `omeka_items`.`id` AND `dc_contributor`.`element_id` = 37   
                LEFT JOIN `omeka_element_texts` AS `dc_coverage`
                ON `dc_coverage`.`record_type` = 'Item' AND `dc_coverage`.`record_id` = `omeka_items`.`id` AND `dc_coverage`.`element_id` = 38   
                LEFT JOIN `omeka_element_texts` AS `dc_creator`
                ON `dc_creator`.`record_type` = 'Item' AND `dc_creator`.`record_id` = `omeka_items`.`id` AND `dc_creator`.`element_id` = 39   
                LEFT JOIN `omeka_element_texts` AS `dc_date`
                ON `dc_date`.`record_type` = 'Item' AND `dc_date`.`record_id` = `omeka_items`.`id` AND `dc_date`.`element_id` = 40   
                LEFT JOIN `omeka_element_texts` AS `dc_description`
                ON `dc_description`.`record_type` = 'Item' AND `dc_description`.`record_id` = `omeka_items`.`id` AND `dc_description`.`element_id` = 41   
                LEFT JOIN `omeka_element_texts` AS `dc_format`
                ON `dc_format`.`record_type` = 'Item' AND `dc_format`.`record_id` = `omeka_items`.`id` AND `dc_format`.`element_id` = 42   
                LEFT JOIN `omeka_element_texts` AS `dc_identifier`
                ON `dc_identifier`.`record_type` = 'Item' AND `dc_identifier`.`record_id` = `omeka_items`.`id` AND `dc_identifier`.`element_id` = 43   
                LEFT JOIN `omeka_element_texts` AS `dc_language`
                ON `dc_language`.`record_type` = 'Item' AND `dc_language`.`record_id` = `omeka_items`.`id` AND `dc_language`.`element_id` = 44   
                LEFT JOIN `omeka_element_texts` AS `dc_publisher`
                ON `dc_publisher`.`record_type` = 'Item' AND `dc_publisher`.`record_id` = `omeka_items`.`id` AND `dc_publisher`.`element_id` = 45   
                LEFT JOIN `omeka_element_texts` AS `dc_relation`
                ON `dc_relation`.`record_type` = 'Item' AND `dc_relation`.`record_id` = `omeka_items`.`id` AND `dc_relation`.`element_id` = 46   
                LEFT JOIN `omeka_element_texts` AS `dc_rights`
                ON `dc_rights`.`record_type` = 'Item' AND `dc_rights`.`record_id` = `omeka_items`.`id` AND `dc_rights`.`element_id` = 47   
                LEFT JOIN `omeka_element_texts` AS `dc_source`
                ON `dc_source`.`record_type` = 'Item' AND `dc_source`.`record_id` = `omeka_items`.`id` AND `dc_source`.`element_id` = 48   
                LEFT JOIN `omeka_element_texts` AS `dc_subject`
                ON `dc_subject`.`record_type` = 'Item' AND `dc_subject`.`record_id` = `omeka_items`.`id` AND `dc_subject`.`element_id` = 49   
                LEFT JOIN `omeka_element_texts` AS `dc_title`
                ON `dc_title`.`record_type` = 'Item' AND `dc_title`.`record_id` = `omeka_items`.`id` AND `dc_title`.`element_id` = 50   
                LEFT JOIN `omeka_element_texts` AS `dc_type`
                ON `dc_type`.`record_type` = 'Item' AND `dc_type`.`record_id` = `omeka_items`.`id` AND `dc_type`.`element_id` = 51   
                WHERE (" . implode(") AND (", $conditions) . ")
                GROUP BY `omeka_items`.`id`    
                ORDER BY `omeka_items`.`id`
                LIMIT " . intval($cursor) . "," . intval($stepSize) . "
                ;";
        } else if ($metadataPrefix == MetadataFormatIsebel::METADATAPREFIX) {
            $sql = "SET SESSION group_concat_max_len=" . self::CONCAT_LENGTH . "; SELECT                  
                  `omeka_items`.`id` AS `" . self::PREFIX_HEADER . "identifier`,
                  UNIX_TIMESTAMP(`omeka_items`.`modified`) AS `" . self::PREFIX_HEADER . "datestamp`,
                  `omeka_items`.`collection_id` AS `" . self::PREFIX_HEADER . "setSpec`,
                  'story' AS `" . self::PREFIX_METADATA . "type`,
                  `isebel_type`.`text` AS `" . self::PREFIX_METADATA . "subgenre`,
                  `omeka_items`.`id` AS `" . self::PREFIX_METADATA . "id`,                     
                  CONCAT('http://www.verhalenbank.nl/items/show/', `omeka_items`.`id`) AS `" . self::PREFIX_METADATA . "url`,
                  GROUP_CONCAT(DISTINCT(`isebel_identifier`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "identifier`,
                  GROUP_CONCAT(DISTINCT(`isebel_text`.`text` SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "text`,
                  GROUP_CONCAT(DISTINCT(`isebel_date`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "date`,
                  GROUP_CONCAT(DISTINCT(CONCAT(`isebel_location`.`id`,'" . self::SPLIT_CHARACTER2 . "',`isebel_location`.`locality`,'" . self::SPLIT_CHARACTER2 . "',`isebel_location`.`latitude`,'" . self::SPLIT_CHARACTER2 . "',`isebel_location`.`longitude`)) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "location`,
                  GROUP_CONCAT(DISTINCT(`isebel_narrator`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "narrator`, 
                  GROUP_CONCAT(DISTINCT(`isebel_keyword`.`name`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "keyword`                   
                FROM `omeka_items`
                LEFT JOIN `omeka_collections`
                ON `omeka_items`.`collection_id` = `omeka_collections`.`id`
                LEFT JOIN `omeka_element_texts` AS `isebel_identifier`
                ON `isebel_identifier`.`record_type` = 'Item' AND `isebel_identifier`.`record_id` = `omeka_items`.`id` AND `isebel_identifier`.`element_id` = 43
                LEFT JOIN `omeka_element_texts` AS `isebel_text`
                ON `isebel_text`.`record_type` = 'Item' AND `isebel_text`.`record_id` = `omeka_items`.`id` AND `isebel_text`.`element_id` = 1
                LEFT JOIN `omeka_element_texts` AS `isebel_type`
                ON `isebel_type`.`record_type` = 'Item' AND `isebel_type`.`record_id` = `omeka_items`.`id` AND `isebel_type`.`element_id` = 58
                LEFT JOIN `omeka_element_texts` AS `isebel_date`
                ON `isebel_date`.`record_type` = 'Item' AND `isebel_date`.`record_id` = `omeka_items`.`id` AND `isebel_date`.`element_id` = 40
                LEFT JOIN `omeka_locations` AS `isebel_location`
                ON `isebel_location`.`item_id` = `omeka_items`.`id`
                LEFT JOIN `omeka_element_texts` AS `isebel_narrator`
                ON `isebel_narrator`.`record_type` = 'Item' AND `isebel_narrator`.`record_id` = `omeka_items`.`id` AND `isebel_narrator`.`element_id` = 39
                LEFT JOIN `omeka_records_tags` AS `omeka_isebel_keyword`
                ON `omeka_isebel_keyword`.`record_type` = 'Item' AND `omeka_isebel_keyword`.`record_id` = `omeka_items`.`id`
                LEFT JOIN `omeka_tags` AS `isebel_keyword`
                ON `isebel_keyword`.id = `omeka_isebel_keyword`.`tag_id`
                WHERE (" . implode(") AND (", $conditions) . ")      
                GROUP BY `omeka_items`.`id`    
                ORDER BY `omeka_items`.`id`
                LIMIT " . intval($cursor) . "," . intval($stepSize) . "
                ";
//             die($sql);
        } else {
            die("unknown metadataPrefix");
        }
        return array(
            $sql,
            $binds
        );
    }

    protected function getRecordSql($identifier, $metadataPrefix)
    {
        $binds = array(
            array(
                ":identifier",
                $identifier
            )
        );
        if ($metadataPrefix == MetadataFormatDC::METADATAPREFIX) {
            $sql = "SELECT
                  `omeka_items`.`id` AS `" . self::PREFIX_HEADER . "identifier`,
                  UNIX_TIMESTAMP(`omeka_items`.`modified`) AS `" . self::PREFIX_HEADER . "datestamp`,
                  `omeka_items`.`collection_id` AS `" . self::PREFIX_HEADER . "setSpec`,
                  GROUP_CONCAT(DISTINCT(`dc_contributor`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "contributor`,
                  GROUP_CONCAT(DISTINCT(`dc_coverage`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "coverage`,
                  GROUP_CONCAT(DISTINCT(`dc_creator`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "creator`,
                  GROUP_CONCAT(DISTINCT(`dc_date`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "date`,
                  GROUP_CONCAT(DISTINCT(`dc_description`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "description`,
                  GROUP_CONCAT(DISTINCT(`dc_format`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "format`,
                  GROUP_CONCAT(DISTINCT(`dc_identifier`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "identifier`,
                  GROUP_CONCAT(DISTINCT(`dc_language`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "language`,
                  GROUP_CONCAT(DISTINCT(`dc_publisher`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "publisher`,
                  GROUP_CONCAT(DISTINCT(`dc_relation`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "relation`,
                  GROUP_CONCAT(DISTINCT(`dc_rights`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "rights`,
                  GROUP_CONCAT(DISTINCT(`dc_source`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "source`,
                  GROUP_CONCAT(DISTINCT(`dc_subject`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "subject`,
                  GROUP_CONCAT(DISTINCT(`dc_title`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "title`,
                  GROUP_CONCAT(DISTINCT(`dc_type`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "type`
                FROM `omeka_items`
                LEFT JOIN `omeka_collections`
                ON `omeka_items`.`collection_id` = `omeka_collections`.`id`
                LEFT JOIN `omeka_element_texts` AS `dc_contributor`
                ON `dc_contributor`.`record_type` = 'Item' AND `dc_contributor`.`record_id` = `omeka_items`.`id` AND `dc_contributor`.`element_id` = 37   
                LEFT JOIN `omeka_element_texts` AS `dc_coverage`
                ON `dc_coverage`.`record_type` = 'Item' AND `dc_coverage`.`record_id` = `omeka_items`.`id` AND `dc_coverage`.`element_id` = 38   
                LEFT JOIN `omeka_element_texts` AS `dc_creator`
                ON `dc_creator`.`record_type` = 'Item' AND `dc_creator`.`record_id` = `omeka_items`.`id` AND `dc_creator`.`element_id` = 39   
                LEFT JOIN `omeka_element_texts` AS `dc_date`
                ON `dc_date`.`record_type` = 'Item' AND `dc_date`.`record_id` = `omeka_items`.`id` AND `dc_date`.`element_id` = 40   
                LEFT JOIN `omeka_element_texts` AS `dc_description`
                ON `dc_description`.`record_type` = 'Item' AND `dc_description`.`record_id` = `omeka_items`.`id` AND `dc_description`.`element_id` = 41   
                LEFT JOIN `omeka_element_texts` AS `dc_format`
                ON `dc_format`.`record_type` = 'Item' AND `dc_format`.`record_id` = `omeka_items`.`id` AND `dc_format`.`element_id` = 42   
                LEFT JOIN `omeka_element_texts` AS `dc_identifier`
                ON `dc_identifier`.`record_type` = 'Item' AND `dc_identifier`.`record_id` = `omeka_items`.`id` AND `dc_identifier`.`element_id` = 43   
                LEFT JOIN `omeka_element_texts` AS `dc_language`
                ON `dc_language`.`record_type` = 'Item' AND `dc_language`.`record_id` = `omeka_items`.`id` AND `dc_language`.`element_id` = 44   
                LEFT JOIN `omeka_element_texts` AS `dc_publisher`
                ON `dc_publisher`.`record_type` = 'Item' AND `dc_publisher`.`record_id` = `omeka_items`.`id` AND `dc_publisher`.`element_id` = 45   
                LEFT JOIN `omeka_element_texts` AS `dc_relation`
                ON `dc_relation`.`record_type` = 'Item' AND `dc_relation`.`record_id` = `omeka_items`.`id` AND `dc_relation`.`element_id` = 46   
                LEFT JOIN `omeka_element_texts` AS `dc_rights`
                ON `dc_rights`.`record_type` = 'Item' AND `dc_rights`.`record_id` = `omeka_items`.`id` AND `dc_rights`.`element_id` = 47   
                LEFT JOIN `omeka_element_texts` AS `dc_source`
                ON `dc_source`.`record_type` = 'Item' AND `dc_source`.`record_id` = `omeka_items`.`id` AND `dc_source`.`element_id` = 48   
                LEFT JOIN `omeka_element_texts` AS `dc_subject`
                ON `dc_subject`.`record_type` = 'Item' AND `dc_subject`.`record_id` = `omeka_items`.`id` AND `dc_subject`.`element_id` = 49   
                LEFT JOIN `omeka_element_texts` AS `dc_title`
                ON `dc_title`.`record_type` = 'Item' AND `dc_title`.`record_id` = `omeka_items`.`id` AND `dc_title`.`element_id` = 50   
                LEFT JOIN `omeka_element_texts` AS `dc_type`
                ON `dc_type`.`record_type` = 'Item' AND `dc_type`.`record_id` = `omeka_items`.`id` AND `dc_type`.`element_id` = 51   
                WHERE `omeka_items`.`public`
                AND NOT `omeka_items`.`featured`
                AND `omeka_items`.`id` = :identifier
                GROUP BY `omeka_items`.`id`
                ";
        } else if ($metadataPrefix == MetadataFormatIsebel::METADATAPREFIX) {
            $sql = "SELECT
                  `omeka_items`.`id` AS `" . self::PREFIX_HEADER . "identifier`,
                  UNIX_TIMESTAMP(`omeka_items`.`modified`) AS `" . self::PREFIX_HEADER . "datestamp`,
                  `omeka_items`.`collection_id` AS `" . self::PREFIX_HEADER . "setSpec`,
                  'story' AS `" . self::PREFIX_METADATA . "type`,
                  `omeka_items`.`id` AS `" . self::PREFIX_METADATA . "id`,                     
                  CONCAT('http://www.verhalenbank.nl/items/show/', `omeka_items`.`id`) AS `" . self::PREFIX_METADATA . "url`,
                  GROUP_CONCAT(DISTINCT(`isebel_identifier`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "identifier`,
                  GROUP_CONCAT(DISTINCT(`isebel_text`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "text`,
                  GROUP_CONCAT(DISTINCT(CONCAT(`isebel_location`.`id`,'" . self::SPLIT_CHARACTER2 . "',`isebel_location`.`locality`,'" . self::SPLIT_CHARACTER2 . "',`isebel_location`.`latitude`,'" . self::SPLIT_CHARACTER2 . "',`isebel_location`.`longitude`)) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "location`,
                  GROUP_CONCAT(DISTINCT(`isebel_narrator`.`text`) SEPARATOR '" . self::SPLIT_CHARACTER1 . "') AS `" . self::PREFIX_METADATA . "narrator`
                FROM `omeka_items`
                LEFT JOIN `omeka_collections`
                ON `omeka_items`.`collection_id` = `omeka_collections`.`id`
                LEFT JOIN `omeka_element_texts` AS `isebel_identifier`
                ON `isebel_identifier`.`record_type` = 'Item' AND `isebel_identifier`.`record_id` = `omeka_items`.`id` AND `isebel_identifier`.`element_id` = 43
                LEFT JOIN `omeka_element_texts` AS `isebel_text`
                ON `isebel_text`.`record_type` = 'Item' AND `isebel_text`.`record_id` = `omeka_items`.`id` AND `isebel_text`.`element_id` = 1
                LEFT JOIN `omeka_locations` AS `isebel_location`
                ON `isebel_location`.`item_id` = `omeka_items`.`id`
                LEFT JOIN `omeka_element_texts` AS `isebel_narrator`
                ON `isebel_narrator`.`record_type` = 'Item' AND `isebel_narrator`.`record_id` = `omeka_items`.`id` AND `isebel_narrator`.`element_id` = 39
                WHERE `omeka_items`.`public`
                AND NOT `omeka_items`.`featured`
                AND `omeka_items`.`id` = :identifier
                GROUP BY `omeka_items`.`id`                 
                ;";
//            die($sql);
        } else {
            die("unknown metadataPrefix");
        }
        return array(
            $sql,
            $binds
        );
    }

    protected function filterIdentify($identifyData)
    {
        return $identifyData;
    }

    protected function filterSetsNumber($setsNumberData)
    {
        return $setsNumberData;
    }

    protected function filterSetsList($setsListData)
    {
        return $setsListData;
    }

    protected function filterIdentifiersNumber($identifiersNumberData)
    {
        return $identifiersNumberData;
    }

    protected function filterIdentifiersList($identifiersListData)
    {
        return $identifiersListData;
    }

    protected function filterRecordsNumber($recordsNumberData)
    {
        return $recordsNumberData;
    }

    protected function filterRecordsList($recordsListData)
    {
        return $recordsListData;
    }

    protected function filterRecord($recordData)
    {
        return $recordData;
    }

    protected function filterHeader($headerData)
    {
        $filteredData = array();
        foreach ($headerData as $key => $value) {
            if (preg_match("/^" . preg_quote(self::PREFIX_HEADER) . "(.*?)$/", $key, $match)) {
                $filteredData [$match [1]] = $value;
            }
        }
        return $filteredData;
    }

    protected function filterMetadata($metadataData, $metadataPrefix)
    {
        $filteredData = array();
        foreach ($metadataData as $key => $value) {
            if (preg_match("/^" . preg_quote(self::PREFIX_METADATA) . "(.*?)$/", $key, $match)) {
                if ($value != null) {
                    if (strpos($value, self::SPLIT_CHARACTER1) !== false) {
                        $items = explode(self::SPLIT_CHARACTER1, $value);
                        $filteredData [$match [1]] = array();
                        foreach ($items AS $item) {
                            if (strpos($item, self::SPLIT_CHARACTER2) !== false) {
                                $filteredData [$match [1]][] = explode(self::SPLIT_CHARACTER2, $item);
                            } else {
                                $filteredData [$match [1]][] = $item;
                            }
                        }
                    } else {
                        if (strpos($value, self::SPLIT_CHARACTER2) !== false) {
                            $filteredData [$match [1]] = explode(self::SPLIT_CHARACTER2, $value);
                        } else {
                            $filteredData [$match [1]] = $value;
                        }
                    }
                } else {
                    $filteredData [$match [1]] = $value;
                }
            }
        }
        return $filteredData;
    }

    private function createItemsConditions($set = null, $from = null, $until = null)
    {
        $binds = array();
        $conditions = array();
        $conditions [] = "`omeka_items`.`collection_id` in ( '1' )";
        $conditions [] = "`omeka_items`.`public`";
        $conditions [] = "NOT `omeka_items`.`featured`";
        if ($set !== null) {
            $conditions [] = "`omeka_items`.`collection_id` = :set";
            $binds [] = array(
                ":set",
                $set
            );
        }
        if ($from !== null) {
            $conditions [] = "`omeka_items`.`modified` >= :from";
            $binds [] = array(
                ":from",
                date("Y-m-d H:i:s", $from)
            );
        }
        if ($until !== null) {
            $conditions [] = "`omeka_items`.`modified` <= :until";
            $binds [] = array(
                ":until",
                date("Y-m-d H:i:s", $until)
            );
        }
        return array(
            $binds,
            $conditions
        );
    }
}