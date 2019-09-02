<?php

namespace DataProviderObject;

class Records extends Items
{
    private $records;
    private $currentId = 0;

    public function __construct($metadataPrefix, $set = null, $from = null, $until = null)
    {
        parent::__construct($metadataPrefix, $set, $from, $until);
        $this->records = array();
    }

    public function getRecords()
    {
        return $this->records;
    }

    public function addRecord($record)
    {
        if ($record instanceof Record) {
            $this->records [] = $record;
        } else {
            die ("incorrect call addRecord");
        }
    }

    public function setCurrentId($value)
    {
        if ($value !== null && is_numeric($value) && intval($value) >= 0) {
            $this->currentId = intval($value);
        } else {
            die("Cannot set current Id correctly");
        }
    }
    public function getCurrentId() {
        return $this->currentId;
    }
}