<?php
/**
 * Dispatch functionality to redirect the request (from a pretty URL) to a proper page
 * If the page is not found, throw a custom 404 error
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


/**
 * @author Paul Brighton <escape@null.net>
 * @since 1.0
 *
 * Usage: $router = new dispatch();
 * If this is indeed an index page that we want to load, check the dispatch fallback
 * if (!$router->fallback){
 *    $router->route();
 * else
 *    // Load the default index page
 *
*/
class dispatch {
	public $controller, $action, $fallback;


	/**
	 * Init the class variables upon object contruct
	 * If no controller was specified, fallback is used to render the index page
	 *
	 */
	public function __construct() {
		if ($controller = get_safe_input('controller')) {
			$this->controller = $controller;
			$this->fallback = false;
		} else {
			$this->controller = 'index';
			$this->fallback = true;
		}
		// First, strip action of any parms it might have
		if ($action = get_safe_input('action')){
			$arr = explode('/', $action);
			if (count($arr) > 1){
				$action = array_shift($arr);
				$parms = array();
				foreach ($arr as $element){
					$parms[] = $element;
				}
			}
			// Now check if controller was supplied
			if (!empty($controller)) {
				$this->action = $action;
				$this->fallback = false;
			}
		}

		remove_magic_quotes();

		// Check to see if we set parms already when we were checking for action
		if (isset($parms)) $parms[] = get_safe_input('parms');
		else $parms = get_safe_input('parms');

		$uri = getenv('REQUEST_URI');
		set_input('uri', $uri);
		if ($parms) set_input('parms', $parms);
		
		// POST attempt to capture form parameters
		if (isset($_POST) and count($_POST) > 2){
			foreach ($_POST as $name => $value){
				// Filter out system variables
				if ($name == 'csrf_ts') set_input($name, $value);
				elseif ($name == 'csrf_token') set_input($name, $value);
				else {
					if ($name != 'controller' and $name != 'action' and $name != 'parms') set_input($name, $value, 'post', true);
				}
			}
		}
		// GET attempt to capture all parms in the URL followed by a question sign ?
		$get_parms = explode("?", $uri);
		if (count($get_parms) > 1){
			array_shift($get_parms);
			$get_parms = explode('&', $get_parms[0]);
			foreach ($get_parms as $get){
				$get = explode('=', $get);
				set_input($get[0], $get[1], 'get', true);
			}
		}
	}

	/**
	 * Routing functionality is accomplished by:
	 * - first checking if a filename was passed in instead of a controller
	 * - checking if the controller class exists in the controllers folder. If so, load it
	 * - if controller class was not found, try to load the controller.php filename in the public folder
	 *
	 * Upon successful controller class include, instantiate the controller object and load the default action
	 *
	 *
	 * @throws error
	 * @return void
	 */
	public function route(){
		$dir = dirname(dirname(__FILE__)) . '/';
		// If a single filename was supplied, try to load it
		if (strstr($this->controller, '.')) {
			$path = $dir . 'public/';
			// Drop the last slash if it exists
			$file = strrchr($this->controller, '/') == '/'? substr($this->controller, 0, -1) : $this->controller;
			if (is_readable($path . $file)) {
				include($path . $file);
			} else {
				include($path . '404.php');
			}
			exit();
		} else {
			// System controllers path
			$sdir = $dir . 'controllers/';
			$spath = $this->controller . '.php';

			// User plugins path
			$pdir = $dir . 'plugins/';
			$ppath = $pdir . $this->controller;
			$pcontrol = $ppath . '/controllers/' . $this->action . '.php';

			require_once($sdir . 'controller.php');
			$class = $sdir . $spath;
			// If this is a system controller
			if (is_readable($class)) {
				// Do not allow calling the main controller
				if ($spath == 'controller.php'){
					throw new error('Invalid controller name');
				} else {
					include $class;
				}
			// Plugins controller (checks access)
			} elseif(is_dir($ppath) and can_access_plugin($this->controller)) {
				if ($this->action){
					// If controller of the plugin was supplied
					if (is_file($pcontrol)) {
						// Shift the system variables to accomodate for the plugin path
						$this->controller = $this->action;
						$parms = get_input('parms');
						// Controller becomes action and the first element inside our parms variable becomes new action
						if (isset($parms)) {
							if (!is_array($parms)) $this->action = $parms;
							else {
								$this->action = array_shift($parms);
								set_input('parms', $parms);
							}
						} else {
							$this->action = '';
						}
						include($pcontrol);
					} else {
						include($dir . '/public/404.php');
						exit();
					}
				// If this is a plugin directory call, try to include the directory index page
				} elseif (is_file($ppath . '/index.php')) {
					include($ppath . '/index.php');
					exit();
				} else {
					include($dir . '/public/404.php');
					exit();
				}
			// File inside 'public' folder
			} else {
				// If a controller class was not found, first check if this is a folder.
				// If so, try to load the action parm first, and then index.php in that directory
				if (is_dir($dir . 'public/' . $this->controller)){
					$path = $dir . 'public/' . $this->controller;
					// Account for subdirectories
					$action = ($this->action != get_safe_input('action'))? get_safe_input('action') : $this->action;
					if (is_file($path . '/' . $action)){
						$file = $path . '/' . $action;
						$mime = mime_content_type($file);
						if (stristr($mime, 'php')) $mime = 'text/html; charset=UTF-8';
						header("Content-Type: " . $mime);
						header("Content-Length: " . filesize($file));
						header("Accept-Ranges: bytes");
						include($file);
						exit();
					// Check if the 'action' is a folder and look for index inside
					} elseif (is_dir($path . '/' . $action)) {
						$path = $path . '/' . $action;
						if (is_file($path . '/index.php')) include($path . '/index.php');
						elseif (is_file($path . '/index.html')) include($path . '/index.html');
						else include($dir . '/public/404.php');
					}
					// Check if the first folder has index.php
					elseif (is_file($path . '/index.php')) include($path . '/index.php');
					// Check if the first folder has index.html
					elseif (is_file($path . '/index.html')) include($path . '/index.html');
					else include($dir . '/public/404.php');

					exit();
				} else {
					// If it is a file, try to load the controller_name.php in the public folder
					if (is_readable($dir . 'public/' . $spath)) {
						include($dir . 'public/' . $spath);
					} else {
						include($dir . '/public/404.php');
					}
					exit();
				}
			}
			$controller = new $this->controller();

			if (is_callable(array($controller, $this->action))) {
				$action = $this->action;
			} else {
				$action = 'index';
			}
			$controller->$action();
		}

	}

}


/* Check for Magic Quotes and remove them */
function strip_slashes_deep($value) {
	$value = is_array($value) ? array_map('strip_slashes_deep', $value) : stripslashes($value);
	return $value;
}

function remove_magic_quotes() {
	if (get_magic_quotes_gpc()) {
		$_GET = strip_slashes_deep($_GET);
		$_POST = strip_slashes_deep($_POST);
		$_COOKIE = strip_slashes_deep($_COOKIE);
	}
}

/** Check register globals and remove them **/
function unregister_globals() {
	if (ini_get('register_globals')) {
		$array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
		foreach ($array as $value) {
			foreach ($GLOBALS[$value] as $key => $var) {
				if ($var === $GLOBALS[$key]) {
					unset($GLOBALS[$key]);
				}
			}
		}
	}
}
