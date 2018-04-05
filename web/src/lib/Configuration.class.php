<?php

namespace OAIPMH;

class Configuration {
  
  private $config;
  private $filename;
  
  public function __construct($file) {
    $this->filename = $file;
    if (file_exists ( $file ) && is_readable ( $file )) {
      $this->load ( $file );
    } else {
      die("no configuration found");
    }
  }  
  private function load($file) {
    $old = array ();
    $old = get_defined_vars ();
    include ($file);
    $new = get_defined_vars ();
    $this->config = array ();
    $this->configTimestamp = filemtime ( $file );
    foreach ( $new as $key => $value ) {
      if (! isset ( $old [$key] )) {
        $this->config [$key] = $value;
      }
    }
  }
  
  public function variableSet($name) {
    return isset($this->config[$name]);
  }
  
  public function get($name) {
    if(isset($this->config[$name])) {
      return $this->config[$name];
    } else {
      return null;
    }
  }
}

?>