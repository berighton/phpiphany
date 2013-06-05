<?php
/**
 * phpiphany global configuration file. 
 * Define variables and system settings here.
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package config
 * @since 1.0
 * 
 */


// Locale: set the default time zone
date_default_timezone_set('America/New_York');
ini_set('date.timezone', 'America/New_York');


/**
 * Usage: $config = config::init();
 * or simply make it global like so: global $config
 * Changing scope would work on every page because we instantiate and store the $config object below
 *
*/

$config = config::init();

class config {
	/* Declare system variables first */
	public $time_created;
	public $env;
	public $uri, $request, $content_type, $http_method_override;

	/* Declare and initialize application-specific variables */
	public $site_name = 'phpiphany';
	public $site_desc = 'phpiphany is poetic; powerful, innovative, playful; pleasure in programming';
	public $site_author = 'Paul Brighton';
	// This HAS to end with a slash, otherwise full paths would not work
	public $site_url = '/project1/';
	public $site_email = 'admin@phpiphany.com';
	public $copyright = '&copy; Copyrighted by _MD_ ProductionS ';
	public $version = '1.0';

	/* Database settings */
	public $dbtype = 'mysql';
	public $dbprefix = 'pip_';
	public $dbuser = '';
	public $dbpass = '';
	public $dbhost = '';
	public $dbname = '';
	public $base_schema = '';
	public $autoconnect = true;

	/* Plugins */
	public $plugins_dir = 'plugins';

	/* Migrations */
	public $versions_dir = 'versioning';

	/* File MIME types icon folder */
	public $mime_icons = 'default';

	/* Caching */
	public $cache = true;

	/* Compression */
	public $compression = false;

	/* Define API (webservices) mechanism */
	public $api = 'rest';

	/* If the API will reside on a separate server (or vhost) accessible through a subdomain. E.g. api.phpiphany.com */
	public $api_subdomain = false;

	/* Default caching mechanism is memcache
	   Available options are: memcache, memcached, apc, redis, phpredis, shared_memory and local */
	public $cache_type = 'redis';
	// Specify an array of cache servers with array of options
	public $cache_server = array(
								/*
								array('host' => 'localhost',
									'port' => '', // Specify the port on which to access the cache server if it differs from the default install. Leave blank otherwise
									'persistent' => true,
									'weight' => 1,
									'timeout' => 1,
									'retry_interval' => 15),
								*/
							);


	// Print all the warnings and notices appended to the bottom of the page as well as database queries debugger
	public $debug = false;

	/*
	 * Sessions
	 *
	 * Specify whether or not the session expiration should happen exactly after a timeout limit has been reached
	 * or if session should remain valid for the time until user is still online
	 *
	 * Available modes are: loose or strict
	 * Default: loose
	 *
	 */
	public $session_mode = 'loose';


	// Some pages might require a direct output to the screen (AJAX reponse for example)
	public $direct_output = false;

	// Store the single instance of config
	private static $pip_instance = null;

	// Singleton instance monitor
	public static function init() {
		// Re-instantiate self if it was not done so previously
		if (null === self::$pip_instance) {
			self::$pip_instance = new self();
		}

		return self::$pip_instance;
	}

	private function __construct() {
		// Init system variables
		$this->system_init();

		// Finally return the object
		return $this;
	}

	/**
	 * System init
	 * Sets system variables
	 *
	 * @return void
	 */
	private function system_init(){
		$this->time_created = $_SERVER['REQUEST_TIME'];
		$this->site_email = $this->site_email? $this->site_email : getenv('SERVER_ADMIN');
		$this->copyright .=  date('Y', $_SERVER['REQUEST_TIME']);
		$this->uri = getenv('REQUEST_URI');
		$this->request = getenv('REQUEST_METHOD');
		$this->content_type = getenv('CONTENT_TYPE');
		$this->http_method_override = getenv('HTTP_X_HTTP_METHOD_OVERRIDE');

		if ($this->compression) ini_set("zlib.output_compression", 4096);

		// Get the current working environment and versions if defined in the config
		$this->env = new stdClass();
		// Get the current port
		$this->env->hostname = (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		// Get the hostname of this server ('server_name' is for apache env variable and 'hostname' for nginx)
		$this->env->hostname .= getenv('SERVER_NAME') ? strtolower(getenv('SERVER_NAME')) : strtolower(getenv('HOSTNAME'));
		$this->env->ip = getenv('REMOTE_ADDR');
		$this->env->root = getenv('DOCUMENT_ROOT') . $this->site_url;
		$this->env->php = phpversion();
		$this->env->os = php_uname('s');
		// Get HTTP server
		$http = explode('Server', preg_replace("#\n|\r|<(/)?address>#", '', getenv('SERVER_SIGNATURE')));
		$this->env->http = $http[0];
		// Get mysql version
		$output = shell_exec('mysql -V');
		preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);
		$this->env->mysql = $version[0];
		$this->env->dir = __DIR__;
		$this->env->file = __FILE__;

		$this->plugins_dir = $this->env->root . $this->plugins_dir;
		$this->versions_dir = $this->env->root . "models/$this->versions_dir/$this->dbtype";
		$this->mime_icons = $this->mime_icons? $this->mime_icons : '/default';
		$this->mime_icons = 'icons/mime/' . $this->mime_icons;

		// Load corresponding API classes
		if (isset($this->api) and strtolower($this->api) == 'soap') {
			include dirname(dirname(__FILE__)) . '/api/soap/soap.php';
		} else {
			include dirname(dirname(__FILE__)) . '/api/rest/rest.php';
		}
	}

	/**
	 * Checks if all the parameters were set in order to establish a database connection
	 * Usually called from the database class construct
	 *
	 * @throws error and halts the script execution if errors found
	 * @param bool $display controls whether to throw an error to the screen or simply return false
	 * @return bool Returns true if all required parms are present
	 */
	public function db_check($display = true){
		$error = false;
		if (!$this->dbtype)         $error = 'DB TYPE';
		elseif (!$this->dbhost)     $error = 'DB HOSTNAME';
		elseif (!$this->dbuser)     $error = 'DB USERNAME';
		elseif (!$this->dbpass)     $error = 'DB PASSWORD';
		elseif (!$this->dbname)     $error = 'DB NAME';

		if ($error) {
			if ($display and $this->base_schema) throw new error($error . ' is not set! In order to connect to a database, you need to configure these settings in the config object.');
			else return false;
		}
		else return true;
	}

	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
	}
}

// Initialize the session variable that will be used for temporary storage (output buffer mostly)
$_SESSION['pip_temp_pad'] = '';
