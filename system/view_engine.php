<?php
/**
 * View Engine - the main page renderer
 * Draws the entire HTML5-valid page by calling amalgamated views
 * Adjust the default views with the included configuration settings
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

$view = view_engine::start();

class view_engine {
	// Declare system variables first
	public $views, $system_msg;
	private $time_created, $runtime;


	// Define the current default view values

	// General settings pertaining to this view
	public $name = '';                          //default: fixed - centered, 1170px width. Alternative is fluid
	public $path = '';                          //default: views/layout/default
	public $css = '';                           //default: public/css/bootstrap.css

	// Navbar settings
	public $navbar_enabled = true;              //default: true
	public $navbar_position = '';               //default: center (options: left, right)
	public $navbar_login = true;                //default: true, shows login form on the navbar
	public $navbar_search = true;               //default: true, shows the search on the navbar
	public $navbar_menu = array();              //default: empty array, specify the links to be displayed at the navbar
	// Example array(array('url' => 'http://www.google.com', 'name' => 'Google', 'active' => true))

	// Left sidebar menu settings
	public $menu_enabled = false;               //default: false for the default view, true for the full view
	public $menu_style = '3';                   //default: menu1 (options: menu2, menu3, menu4)

	// Page specific options
	// <head>
	public $charset = '';                       //default: utf-8
	public $title = '';                         //default: $config->site_name
	public $keywords = '';                      //default: phpiphany
	public $description = '';                   //default: $config->site_desc
	public $author = '';                        //default: $config->site_author
	public $copyright = '';                     //default: $config->copyright
	public $robots = '';                        //default: index, follow
	public $custom_js = array();                //default: none. Accepts array of additional js libraries needed to be loaded
	public $custom_css = array();               //default: none. Accepts array of additional css stylesheets needed to be loaded
	public $assets_dir = '';                    //default: the application root
	public $use_jquery = false;                 //default: false, the default js framework is mootools
	public $js_lib = '';                        //default: mootools, the default js framework CDN

	// <body>
	public $content = '';                       //default: none

	// Styles
	private $style_map = array();


	// Store the single instance of config
	private static $pip_instance = null;



	// Singleton instance monitor
	public static function start() {
		// Re-instantiate self if it was not done so previously
		if (null === self::$pip_instance) {
			self::$pip_instance = new self();
		}
		return self::$pip_instance;
	}

	private function __construct() {
		// Init system variables
		$this->system_init();

		// Get all the possible views in the system and store in $config
		$this->view_finder();

		// Access current error styles
		$this->map_error_styles('init');

		// Finally return the object
		return $this;
	}

	private function system_init(){
		global $config;

		$this->time_created = time();
		$this->system_msg = false;
		$this->views = $this->views? $this->views : new stdClass();
		$this->name = $this->name? $this->name : 'default';
		$this->path = $this->path? $this->path : '/views/layout/' . $this->name;
		$this->path = $config->env->root . $this->path;
		$this->css = $this->css? $this->css : 'bootstrap.css';

		$this->navbar_enabled = isset($this->navbar_enabled)? $this->navbar_enabled : true;
		$this->navbar_position = $this->navbar_position? $this->navbar_position : 'center';
		$this->navbar_login = isset($this->navbar_login)? $this->navbar_login : true;
		$this->navbar_search = isset($this->navbar_search)? $this->navbar_search : true;

		$this->menu_enabled = isset($this->menu_enabled)? $this->menu_enabled : $this->name == 'full'? true : false;
		$this->menu_style = $this->menu_style? preg_replace("/[^0-9]/", '', $this->menu_style) : 1;
		if ($this->menu_enabled === true) {
			$this->menu('init');
		}

		$this->charset = $this->charset? $this->charset : 'utf-8';
		$this->title = $this->title? $this->title : $config->site_name;
		$this->keywords = $this->keywords? $this->keywords : 'phpiphany, php, mvc, framework';
		$this->description = $this->description? $this->description : $config->site_desc;
		$this->author = $this->author? $this->author : $config->site_author;
		$this->copyright = $this->copyright? $this->copyright : $config->copyright;
		$this->robots = $this->robots? $this->robots : 'index, follow';
		$this->assets_dir = $this->assets_dir? $this->assets_dir : $config->site_url;

		// Include database debug console styles and js functionality
		if ($config->debug){
			$this->custom_css[] = $this->assets_dir . 'css/admin/database.css';
			$this->custom_js[] = $this->assets_dir . 'js/admin/database.js';
		}
		/* NOTE: Remember to always add to this array otherwise some items will not be displayed
		 * array_push($view->custom_js, 'js/path/to/your/file.js');
		 * OR
		 * $view->custom_js[] = 'js/path/to/your/file.js';
		*/

	}

	private function view_finder(){
		global $config;
		// Update/create a new array of views inside a global $config file
		if (!isset($this->views->locations) or !$this->views->locations or count($this->views->locations) == 0){
			$this->views->locations = $this->find_views($config->env->root);
		}
	}

	/**
	 * Draws the full page
	 *
	 * @param mixed $content The content (body) to render
	 * @return void
	 */
	public function render_page($content = null) {
		// Set the JS library here instead of system_init in order to capture user use_jquery parm inside the code
		$this->use_jquery = $this->use_jquery? true : false;
		$this->js_lib = $this->use_jquery? '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>' :
		'<script src="http://ajax.googleapis.com/ajax/libs/mootools/1.4.5/mootools-yui-compressed.js"></script>';

		global $config, $cache;

		if (isset($content)) {
			$this->content = $content;
		}
		// If there were any system messages, display them atop of the main content
		if ($this->system_msg) {
			$this->content = $this->system_msg . $this->content;
		}

		// If the system is in maintenance mode, display the message. Otherwise proceed with a regular content population
		// If cache was not enabled, do not bother checking for maintenance mode
		if (isset($cache) and $cache and $msg = $cache->read('maintenance_msg')) {
			// This applies only to regular users
			if (!is_loggedin() or !$_SESSION['user_admin']) {
				$config->debug = false;
				$this->navbar_login = true;
				$this->title = 'Maintenance mode';
				$this->content = '<table><tr><td><img src="/phpiphany/icons/maintenance.png"></td><td><h2 class="red">' . $msg;
				if ($etc = $cache->read('maintenance_etc')) {
					$remaining = $etc - $_SERVER['REQUEST_TIME'];
					$total = '';
					$days_remaining = floor($remaining / 86400);
					$total .= $days_remaining? $days_remaining . ' days and ' : '';
					$hours_remaining = floor(($remaining % 86400) / 3600);
					$total .= $hours_remaining? $hours_remaining . ' hours and ' : '';
					$minutes_remaining = floor(($remaining % 86400) % 3600 / 60);
					$total .= $minutes_remaining? $minutes_remaining . ' minutes and ' : '';
					$total = substr($total, 0, -5);
					$this->content .= '<br><br><span class="green">';
					$this->content .= ($remaining > 0)? 'Expected uptime in ' . $total : 'Site was suppose to be up ' . $total . ' ago';
					$this->content .= '</span>';
				}
				$this->content .= '</h2></td></tr></table>';
			}
		}

		$header = $this->render_header();
		$body = $this->render_body();
		$footer = $config->debug? '' : $this->render_footer();

		//print ($header . $body . $footer);
		//exit;
		// Write to output buffer
		ob_start();
		print ($header . $body . $footer);
	}

	/**
	 * Loads a specific view by reading this object's $view property that should contain all the views defined in the system
	 * @see $this->view_finder();
	 * The view data will be extracted to make local variables.
	 *
	 * @param string $view The view name
	 * @param array $parms The parameters to use in the view (optional)
	 * @throws error
	 * @return string Returns an HTML view or error if view is not found
	 */
	public function load($view, array $parms = null) {
		$view = (string)$view;
		if (!isset($this->views->locations) or !$this->views->locations or count($this->views->locations) == 0) {
			$this->view_finder();
		}
		if (array_key_exists($view, $this->views->locations)) {
			// Import the view variables to a local namespace
			if (isset($parms) and is_array($parms) and count($parms) > 0){
				// If there is a collision, don't overwrite the existing variable. EXTR_OVERWRITE is the default
				extract($parms, EXTR_SKIP);
			}

			// Import the global variables to local namespace
			//global $config;
			//extract($config->input, EXTR_SKIP);

			// Capture the view output
			ob_start();

			try {
				// Load the view within the current scope
				include $this->views->locations[$view];
			} catch (error $e){
				// Delete the output buffer
				ob_end_clean();
				// Re-throw the exception
				throw $e;
			}

			// Get the captured output and close the buffer
			return ob_get_clean();
		} else {
			return $this->error("<em>$view</em> <strong>view does not exist!</strong>", 'error');
		}
	}

	// Sends custom headers
	public function headers(){
		//
	}

	private function render_header() {
		include_once($this->path . '/header.php');
		return render_header($this);
	}

	private function render_body() {
		include_once($this->path . '/body.php');
		return render_body($this);
	}

	// In order to capture all the errors, notices and warnings when parsing is done (page loaded), we will generate the footer last
	// For this, we need to make this method public (called from within error_handler: last_call())
	public function render_footer() {
		include_once($this->path . '/footer.php');
		// Read any debug traces that might have been accumulated in the temp session variable
		global $config;
		if ($config->debug) {
			global $timer;
			$debug = isset($_SESSION['pip_temp_pad'])? $_SESSION['pip_temp_pad'] : '';
			if ($timer) {
				$this->runtime($timer->stop());
			}
		} else {
			$debug = null;
		}
		return render_footer($this, $debug);
	}

	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
	}

	/**
	 * Generates the left sidebar menu
	 * If $new was set, it awaits for the $links array where an empty array creates a break
	 * (first element signifies the text for the break)
	 * System init happens by passing an 'init' into the $new variable
	 *
	 *
	 * Usage:
	 *
	 * // Generate menu for the menu view (works only if menu_enabled is set)
	 * echo $view->menu();
	 *
	 * // Create a new menu for a page
	 * global $view;
	 * $menu_links = array('link1.html' => 'Link 1', array('break'), 'link2.html' => 'Link 2');
	 * $view->menu(true, $menu_links);
	 *
	 * 
	 *
	 * @param bool $new Flag to show/set menu links
	 * @param array $links Array of links if creating a new menu
	 * @return mixed Returns HTML text for the menu <li> or false on error
	 */
	public function menu($new = false, array $links = array()){
		global $config;
		if ($new == 'init'){
			$this->sidebar = array(
				array(),
				$config->site_url => 'Home',
				'test' => 'Test',
				'test1' => 'Test',
				'test4' => 'Test',
				'test5' => 'Test',
				array(),
				'test6' => 'Test',
				'test9' => 'Test',
				'test?page=accordion' => 'Accordion',
				'test10' => 'Test',
				array('Custom Break'),
				'test11' => 'Test',
				'test14' => 'Test',
				'test15' => 'Test'
			);
		} else {
			if ($new){
				// Set the new menu links
				if (is_array($links) and count($links) > 0) {
					$this->sidebar = $links;
				} else {
					return false;
				}
			}
			$menu = '';
			// Generate an HTML output suitable for inserting into the view
			foreach ($this->sidebar as $url => $text) {
				if (is_array($text)) {
					$text = (count($text) < 1)? 'Sidebar' : $text[0];
					$menu .= "					<li class=\"nav-header\">$text</li>\n";
				} else {
					//$menu .= "					<li class=\"active\"><a href=\"2.html#\">Link</a></li>\n";
					$menu .= "					<li><a href=\"$url\">$text</a></li>\n";
				}
			}
			return $menu;
		}
		return true;
	}

	/**
	 * Alert notification system
	 * Injects JS code at the bottom of the page to trigger the alert message
	 *
	 * @param string $msg The text of the message
	 * @param string $type Type of the message: error, warning, notice, success (default notice)
	 * @return mixed Returns the HTML formatted alert snippet if no message text was supplied. Otherwise sets a session variable and returns true
	 */
	public function alert($msg = '', $type = 'notice'){
		// Initialize the alerts session variable
		if (!isset($_SESSION['alert_msg'])) $_SESSION['alert_msg'] = '';

		// Set the error message, making sure this is not a fatal error
		$e = error_get_last();
		if ($msg and (!$e['type'] or $e['type'] > 1)){
			// jQuery
			if ($this->use_jquery) $_SESSION['alert_msg'] .= "	jQuery.alertbox({text: '$msg', type: '$type'});\n";
			// Mootools
			else $_SESSION['alert_msg'] .= "	new alertbox({type: '$type'}).show('$msg','');\n";
			return true;
		} else {
			// If this is a critical error, do not show the popup alert box
			if ($e['type'] and $e['type'] == 1){
				$alert = $_SESSION['alert_msg'] = '';
			} else {
				$alert = $_SESSION['alert_msg'];
			}
			return $alert;
		}
	}

	/**
	 * Sets/prints the page loading time
	 * Injects HTML code at the bottom of the Debug Backtrace div
	 * Depends on the timer() class
	 *
	 * @param mixed $time Script Execution time passed from a timer
	 * @return mixed Returns the HTML formatted time if no time text was supplied. Otherwise sets a private variable
	 */
	public function runtime($time = null){
		// Set the time
		if ($time){
			$this->runtime = "\n	<p class='timer'><small>Script runtime: $time</small></p>";
		} else {
			// Output the current page render time
			return $this->runtime;
		}
	}

	/**
	 * Returns an error message wrapped in this view's error style
	 * The mapping comes from errors.php view defined by error_handler
	 *
	 * @param string $msg The error message text to display
	 * @param string $type The type of message: error | warning | notice | success
	 * @return string Returns an HTML string of the error message wrapped in the proper styling
	 */
	public function error($msg, $type = 'notice'){
		return '<div class="user_error">' . $this->style_map[$type] . ": $msg</div>";
	}

	/**
	 * Scans a given directory for any php files in the 'views' directory
	 * ***This is an expensive function to execute, make sure its results are cached***
	 *
	 * Formatting of a view is done in the following order:
	 * - system views are scanned from a root/views directory and are called view/name
	 * - plugin views are prepended with the plugin name plugin/view/name
	 *
	 * 
	 * @param string $dir The directory to scan
	 * @return array Returns an associative array: [view name] => location
	 */
	private function find_views($dir) {
		global $config;

		// Remove the trailing slash in the directory path
		if (substr($dir, -1, 1) == '/') {
			$dir = substr($dir, 0, -1);
		}
		$result = array();
		if (is_dir($dir)){
			$root = scandir($dir);
			foreach ($root as $value) {
				// Remove hidden files and references to a parent directory
				if ($value[0] === '.' or $value === '..') {
					continue;
				}
				$path = "$dir/$value";
				// Discard any folders that are not related to views
				if ((!stristr($path, 'plugins') and !stristr($path, 'views')) or $value == 'view') {
					continue;
				}
				// Check if this is a real file with .php extension
				if (is_file($path) and substr($value, -4, 4) == '.php' and stristr($path, 'views')) {
					$view = str_replace($config->env->root, '', $path);
					$result[$view] = $path;
					continue;
				}
				$sub_dir = $this->find_views($path);
				if ($sub_dir and count($sub_dir) > 0) {
					foreach ($sub_dir as $value) {
						// Format the view name to have an easily-callable name
						$view = preg_replace('#(/views)|(/plugins/)|(/views/)|(views/)|(' . $config->env->root . ')|(' .
								substr($config->env->root, 0, -1) . ')#', '', substr(str_replace('/views/layout', 'layout', $value), 0, -4));
						$view = preg_replace('#^/#', '', $view);
						$result[$view] = $value;
					}
				}
			}
		}
		return $result ? $result : false;
	}

	/**
	 * Maps the error codes to the defined css styles
	 * Stores the result in this class's variable
	 *
	 * @param int $code The error code to process
	 * @return array|bool Returns $this->style_map array if code was passed as a parameter
	 */
	public function map_error_styles($code) {

		// Populate the style_map if no code was supplied (init called from the constructor)
		if ($code === 'init') {
			$this->style_map = array();
			$this->style_map['critical'] = false;

			if (!defined('E_STRICT')) {
				define('E_STRICT', 2048);
			}
			if (!defined('E_RECOVERABLE_ERROR')) {
				define('E_RECOVERABLE_ERROR', 4096);
			}

			$this->style_map['table'] = '		' . htmlspecialchars('<table class="table table-striped table-bordered table-condensed">') . "\n";
			$this->style_map['buttons'] = htmlspecialchars('

				<div class="btn-toolbar" style="margin-top: 18px;">
					<div class="btn-group">
						<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">Action <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="#"><i class="icon-search"></i> Search</a></li>
							<li><a href="#"><i class="icon-envelope"></i> Email</a></li>
							<li><a href="#"><i class="icon-ok"></i> OK</a></li>
							<li><a href="#"><i class="icon-remove"></i> Delete</a></li>
							<li><a href="#"><i class="icon-off"></i> Off</a></li>
							<li><a href="#"><i class="icon-home"></i> Home</a></li>
							<li><a href="#"><i class="icon-lock"></i> Lock</a></li>
							<li><a href="#"><i class="icon-flag"></i> Flag</a></li>
							<li><a href="#"><i class="icon-book"></i> Book</a></li>
							<li><a href="#"><i class="icon-print"></i> Print</a></li>
							<li><a href="#"><i class="icon-calendar"></i> Calendar</a></li>
						</ul>
					</div><!-- /btn-group -->
					<div class="btn-group">
						<a class="btn btn-primary" href="#"><i class="icon white user"></i> User</a>
						<a class="btn btn-primary dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="#"><i class="icon-pencil"></i> Edit</a></li>
							<li><a href="#"><i class="icon-trash"></i> Delete</a></li>
							<li><a href="#"><i class="icon-ban-circle"></i> Ban</a></li>
							<li><a href="#"><i class="icon-arrow-left"></i> <i class="icon-arrow-right"></i> <i class="icon-arrow-up"></i> <i class="icon-arrow-down"></i><i class="icon-chevron-up"></i> <i class="icon-chevron-down"></i> <i class="icon-plus"></i> <i class="icon-minus"></i></a></li>
							<li class="divider"></li>
							<li><a href="#"><i class="i"></i> Make admin</a></li>
						</ul>
					</div>
				</div>
				<p>
					<a class="btn" href="#"><i class="icon-refresh"></i> Refresh</a>
					<a class="btn btn-success" href="#"><i class="icon-shopping-cart icon-white"></i> Checkout</a>
					<a class="btn btn-danger" href="#"><i class="icon-trash icon-white"></i> Delete</a>
					<a class="btn btn-small" href="#" style="margin-left: 30px"><i class="icon-cog"></i> Settings</a>
					<a class="btn btn-small btn-info" href="#"><i class="icon-info-sign icon-white"></i> More Info</a>
				</p>

			');

			$this->style_map[E_ERROR] = '<span class="label label-important">Fatal Error</span>';
			$this->style_map[E_WARNING] = '<span class="label label-warning">Warning</span>';
			$this->style_map[E_PARSE] = '<span class="label label-important">Parse Error</span>';
			$this->style_map[E_NOTICE] = '<span class="label label-info">Notice</span>';
			$this->style_map[E_CORE_ERROR] = '<span class="label label-important">Core Error</span>';
			$this->style_map[E_CORE_WARNING] = '<span class="label label-warning">Core Warning</span>';
			$this->style_map[E_COMPILE_ERROR] = '<span class="label label-important">Compile Error</span>';
			$this->style_map[E_COMPILE_WARNING] = '<span class="label label-warning">Compile Warning</span>';
			$this->style_map[E_USER_ERROR] = '<span class="label label-warning">User Error</span>';
			$this->style_map[E_USER_WARNING] = '<span class="label label-warning">User Warning</span>';
			$this->style_map[E_USER_NOTICE] = '<span class="label">User Notice</span>';
			$this->style_map[E_STRICT] = '<span class="label label-info">Strict Notice</span>';
			$this->style_map[E_RECOVERABLE_ERROR] = '<span class="label label-info">Recoverable Error</span>';
			$this->style_map[0] = '<span class="label label-warning">Exception</span>';

			$this->style_map['success'] = '<span class="label label-success">Success</span>';
			$this->style_map['notice'] = '<span class="label label-info">Notice</span>';
			$this->style_map['warning'] = '<span class="label label-warning">Warning</span>';
			$this->style_map['error'] = '<span class="label label-important">Error</span>';

		} else {
			if ($code == E_ERROR || $code == E_PARSE || $code == E_CORE_ERROR || $code == E_COMPILE_ERROR) $this->style_map['critical'] = true;
			elseif ($code == 666) $this->style_map['user_died'] = true;

			if (isset($this->style_map)){
				if (array_key_exists($code, $this->style_map)) {
					$this->style_map['msg'] = htmlspecialchars($this->style_map[$code]);
				} else {
					$this->style_map['msg'] = '<span class="label">Unknown error (' . $code . ')</span>';
				}

				return $this->style_map;
			} else {
				return false;
			}
		}

		return true;

	}

	/**
	 * Returns the body HTML for PHP errors
	 *
	 * @param string $error_msg The main error message
	 * @param string $error_options The message to display after. This can be text on how to contact support, or admin; or tips on how to rectify this issue
	 * @return string Returns the HTML snippet
	 */
	public function render_error($error_msg = null, $error_options = '') {

		if (!$error_msg) {
			$error_msg = $this->load('error/critical');
		}

		return $this->load('error/content', array('error_msg' => $error_msg, 'error_options' => $error_options));

	}

	/**
	 * Returns the body HTML for PHP exceptions
	 *
	 * @param string|mixed $error_msg The main error message
	 * @param string $error_options The message to display after. This can be text on how to contact support, or admin; or tips on how to rectify this issue
	 * @param string $error_file The file name where the exception was caught
	 * @param int|mixed $error_line The line number where the exception was caught
	 * @return string Returns the HTML snippet
	 */
	public function render_exception($error_msg = null, $error_options = '', $error_file = '', $error_line = '') {

		if (!$error_msg) {
			$error_msg = 'EXCEPTION';
		} elseif (!stristr($error_msg, 'exception')) {
			$error_msg = 'EXCEPTION! &nbsp; ' . $error_msg;
		}

		if (!$error_options) {
			global $config;
			$error_options = $this->load('exception/options', array('config' => $config, 'error_file' => $error_file, 'error_line' => $error_line));
		}

		return $this->load('exception/content', array('error_msg' => $error_msg, 'error_options' => $error_options));

	}

}

