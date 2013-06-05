<?php
/**
 * Main init file that calls all the system classes, methods and an application settings file
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package main
 * @since 1.0
 *
 */


require(dirname(__FILE__) . '/system/functions.php');
require(dirname(__FILE__) . '/system/settings.php');
require(dirname(__FILE__) . '/system/view_engine.php');
require(dirname(__FILE__) . '/system/error_handler.php');
require(dirname(__FILE__) . '/system/dispatch.php');
require(dirname(__FILE__) . '/system/autoloader.php');



// Include all system files
//foreach (glob(dirname(__FILE__) . "/system/*.php") as $file) require($file);

// Autoload all classes and controllers
autoloader::init();

require(dirname(__FILE__) . '/system/cache.php');
require(dirname(__FILE__) . '/system/session.php');

// Include a database class that instantiates $db object
require(dirname(__FILE__) . '/models/database.php');

// Include the disk writing class
require(dirname(__FILE__) . '/system/classes/storage/disk.php');