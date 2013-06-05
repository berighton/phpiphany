<?php
/**
 * Autoloader class
 *
 * Utilizes spl_autoload functionality to load all system classes
 * by either using set_include_path() method or a simple include()
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package system
 * @since 1.0
 *
 */


class autoloader {
	public static $pip_instance;
	private $locations = array('/controllers/', '/system/classes/');
	public $root;

	/**
	 * Initialize the autoloader class
	 *
	 * @static
	 * @return
	 */
	public static function init() {
		if (self::$pip_instance === NULL) {
			self::$pip_instance = new self();
		}
		return self::$pip_instance;
	}

	/**
	 * Class construct, setup spl_autoloader
	 *
	 */
	private function __construct() {
		// Set the application root directory
		$this->root = dirname(dirname(__FILE__));

		// Nullify any existing autoloads
		spl_autoload_register(null, false);

		// Specify extensions that may be loaded (to increase performance, only one extension is recommended)
		spl_autoload_extensions('.php');

		// if __autoload is active, put it on the spl_autoload stack
		if (is_array(spl_autoload_functions())) {
			if (in_array('__autoload', spl_autoload_functions())) {
				spl_autoload_register('__autoload');
			}
		}

		spl_autoload_register(array($this, 'clean'));
		spl_autoload_register(array($this, 'dirty'));
	}

	/**
	 * The clean method to autoload the class without any includes, works in most cases
	 *
	 * @param $class The class name
	 *
	 */
	private function clean($class) {
		// If this class name uses namespace notation, get just the name
		if (stristr($class, '\\')){
			$class = end(explode('\\', $class));
		}
		foreach ($this->locations as $resource) {
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->root . $resource);
			if (class_exists($class)) spl_autoload($class);
		}
	}

	/**
	 * The dirty method to autoload the class by including the php file containing the class
	 *
	 * @param $class The classname
	 * @return bool Returns true on 100% success or false on if at least one class failed to load
	 *
	 */
	private function dirty($class) {
		$error = false;
		// If this class name uses namespace notation, get just the name
		if (stristr($class, '\\')){
			$class = end(explode('\\', $class));
		}
		foreach ($this->locations as $resource) {
			$path = $this->root . $resource . $class . '.php';
			if (is_readable($path)) {
				include_once($path);
				if (class_exists($class)) spl_autoload($class);
				$error = false;
			} else {
				$error = true;
			}
		}
		if ($error){
			trigger_error("The class '$class' or the file '$path' failed to spl_autoload() ", E_USER_WARNING);
			return false;
		} else return true;
	}

}
