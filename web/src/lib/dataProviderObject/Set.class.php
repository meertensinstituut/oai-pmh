<?php

namespace DataProviderObject;

class Set extends DataProviderObject
{
    private $setSpec;
    private $setName;
    const SET = "set";
    const SET_SPEC = "setSpec";
    const SET_NAME = "setName";

    public function __construct($data)
    {
        if (is_array($data)) {
            if (isset ($data [self::SET_SPEC]) && !is_null($data [self::SET_SPEC]) && (is_string($data [self::SET_SPEC]) || is_numeric($data[self::SET_SPEC]))) {
                $this->setSpec = $data [self::SET_SPEC];
            } else {
                die ("no " . self::SET_SPEC);
            }
            if (isset ($data [self::SET_NAME]) && !is_null($data [self::SET_NAME]) && is_string($data [self::SET_NAME])) {
                $this->setName = $data [self::SET_NAME];
            } else {
                die ("no " . self::SET_NAME);
            }
        } else {
            die ("incorrect call");
        }
    }

    public function getSetSpec()
    {
        return $this->setSpec;
    }

    public function getSetName()
    {
        return $this->setName;
    }
}