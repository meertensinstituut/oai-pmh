<?php
// autoload classes
const CLASS_EXTENSION = ".class.php";
function autoLoader($class) {
  if (preg_match ( "/^OAIPMH\\\\([^\\\\]+)$/", $class, $match )) {
    if (file_exists ( "lib/" . $match [1] . CLASS_EXTENSION )) {
      require_once ("lib/" . $match [1] . CLASS_EXTENSION);
    } else {
      die ( "class " . $class . " not found" );
    }
  } else if (preg_match ( "/^DataProviderModule\\\\([^\\\\]+)$/", $class, $match )) {
    if (file_exists ( "lib/dataProviderModule/" . $match [1] . CLASS_EXTENSION )) {
      require_once ("lib/dataProviderModule/" . $match [1] . CLASS_EXTENSION);
    } else {
      die ( "module " . $class . " not found (DataProviderModule)" );
    }
  } else if (preg_match ( "/^DataProviderObject\\\\([^\\\\]+)$/", $class, $match )) {
    if (file_exists ( "lib/dataProviderObject/" . $match [1] . CLASS_EXTENSION )) {
      require_once ("lib/dataProviderObject/" . $match [1] . CLASS_EXTENSION);
    } else {
      die ( "module " . $class . " not found (DataProviderObject)" );
    }
  } 
}
spl_autoload_register ( "autoLoader" );
// read configuration
$config = new \OAIPMH\Configuration ( "config/config.inc.php" );
$cacheDirectory = "cache";
//create server
$dataProviderName = "\\DataProviderModule\\".$config->get("dataProvider");
$dataProvider = new $dataProviderName($cacheDirectory, $config);
$server = new \OAIPMH\Server ( $dataProvider, $config);
?>