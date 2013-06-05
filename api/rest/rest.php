<?php
/**
 * REST Controller web services abstract class
 * Supports GET, POST, PUT and DELETE methods; can output to JSON and XML
 *
 * API classes must extend this class and have _get _post _put or _delete methods
 *
 * Based on REST Controller developed by Phil Sturgeon for CodeIgniter
 * @link https://github.com/philsturgeon/codeigniter-restserver
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package controllers
 * @since 1.0
 *
 */
abstract class rest {
	/**
	 * This defines the rest format (json, xml, html, php, serialized).
	 */
	protected $rest_format = 'json';

	/**
	 * List of allowed HTTP methods
	 *
	 * @var array
	 */
	protected $allowed_http_methods = array('get', 'delete', 'post', 'put');
	
	/**
	 * REST authentication method (basic, digest)
	 * 
	 */
	protected $rest_auth = '';

	/*
	 * Name of the realm displayed at the login prompt
	 */
	protected $rest_realm = 'phpiphany REST authenticator';

	/*
	 * Enable the usage of API keys for this REST resource
	 */
	protected $rest_api_keys = FALSE;
	
	/*
	 * IP whitelist array (set to FALSE to disable whitelist - allow all)
	 */
	protected $rest_ip_whitelist = FALSE;

	/*
	 * Enable REST logs (will write to a default DB table pip_rest_logs)
	 */
	protected $rest_logs = FALSE;

	/*
	 * Set REST limits, or FALSE to disable (will write to a default DB table pip_rest_limits)
	 * When defined, this controller will count the number of uses of each method by an API key each hour.
	 * This is a general rule that can be overridden in the $this->method array in each controller.
	 */
	protected $rest_limit = FALSE;

	/*
	 * REST API key name
	 */
	protected $rest_key_name = 'PAPI_REST';

	/**
	 * General request data and information.
	 * Stores accept, language, body, headers, etc.
	 *
	 * @var object
	 */
	protected $request = NULL;

	/**
	 * Formatter instance
	 */
	protected $format = NULL;

	/**
	 * What is gonna happen in output?
	 *
	 * @var object
	 */
	protected $response = NULL;

	/**
	 * Stores DB, keys, key level, etc
	 *
	 * @var object
	 */
	protected $rest = NULL;

	/**
	 * The arguments for the GET request method
	 *
	 * @var array
	 */
	protected $get_args = array();

	/**
	 * The arguments for the POST request method
	 *
	 * @var array
	 */
	protected $post_args = array();

	/**
	 * The arguments for the PUT request method
	 *
	 * @var array
	 */
	protected $put_args = array();

	/**
	 * The arguments for the DELETE request method
	 *
	 * @var array
	 */
	protected $delete_args = array();

	/**
	 * The arguments from GET, POST, PUT, DELETE request methods combined.
	 *
	 * @var array
	 */
	protected $args = array();

	/**
	 * If the request is allowed based on the API key provided.
	 *
	 * @var boolean
	 */
	protected $allowed = TRUE;

	/**
	 * Determines if output compression is enabled
	 *
	 * @var boolean
	 */
	protected $zlib_oc = FALSE;


	/**
	 * List all supported methods, the first will be the default format
	 *
	 * @var array
	 */
	protected $supported_formats = array(
		'xml' => 'application/xml',
		'json' => 'application/json',
		'jsonp' => 'application/javascript',
		'serialized' => 'application/vnd.php.serialized',
		'php' => 'text/plain',
		'html' => 'text/html',
		'csv' => 'application/csv'
	);

	/**
	 * You can put additional checks (such as authentication or validation) here
	 */
	protected function early_checks(){
		return true;
	}

	/**
	 * Constructor function
	 */
	public function __construct(){
		global $config, $db, $router;

		// Check the compression settings
		$this->zlib_oc = @ini_get('zlib.output_compression');

		// Instantiate the formatter class
		$this->format = new formatter();

		// Create a local request object
		$this->request = new stdClass();

		// Is it over SSL?
		$this->request->ssl = detect_ssl();

		// How is this request being made? POST, DELETE, GET, PUT?
		$this->request->method = $this->detect_method();

		// Create argument container, if nonexistent
		if (!isset($this->{$this->request->method . '_args'})) {
			$this->{$this->request->method . '_args'} = array();
		}

		// Set up our GET variables
		$this->get_args = array_merge($this->get_args, $config->input);

		// Try to find a format for the request (means we have a request body)
		$this->request->format = $this->detect_input_format();

		// Some methods can't have a body
		$this->request->body = NULL;

		$this->{'parse_' . $this->request->method}();

		// Now we know all about our request, let's try and parse the body if it exists
		if ($this->request->format and $this->request->body) {
			$this->request->body = $this->format->factory($this->request->body, $this->request->format)->to_array();
			// Assign payload arguments to proper method container
			$this->{$this->request->method . '_args'} = $this->request->body;
		}

		// Merge both for one mega-args variable
		$this->args = array_merge($this->get_args, $this->put_args, $this->post_args, $this->delete_args, $this->{$this->request->method . '_args'});

		// Which format should the data be returned in?
		$this->response = new stdClass();
		$this->response->format = $this->detect_output_format();

		$this->early_checks();

		// When there is no specific override for the current class/method, use the default auth value set in the config
		if ($this->rest_auth and $this->rest_auth != 'none') {
			if ($this->rest_auth == 'basic') {
				$this->prepare_basic_auth();
			} elseif ($this->rest_auth == 'digest') {
				$this->prepare_digest_auth();
			} elseif ($this->rest_ip_whitelist) {
				$this->check_whitelist_auth();
			}
		}

		$this->rest = new StdClass();

		// Load the DB
		$this->rest->db = $db;

		// If API keys are enabled, check its validity
		if ($this->rest_api_keys) $this->allowed = $this->detect_api_key();

		// Disable the full page renderer, and instead spit out everything on the screen directly
		$config->direct_output = true;

		// Finally route the request to a proper method by launching a remapper
		$this->remap($router->action, $config->input);
	}

	/**
	 * Remap
	 *
	 * This simply maps the action with the HTTP request method to the correct Controller method.
	 *
	 * @param string $action
	 * @param array $arguments The arguments passed to the controller method.
	 */
	public function remap($action, $arguments) {

		$pattern = '/^(.*)\.(' . implode('|', array_keys($this->supported_formats)) . ')$/';
		if (preg_match($pattern, $action, $matches)) {
			$action = $matches[1];
		}

		// If no action, do the index fallback
		$action OR $action = 'index';

		$controller_method = $action . '_' . $this->request->method;

		// Do not allow invalid keys
		if ($this->rest_api_keys AND $this->allowed === FALSE) {
			if ($this->rest_logs) $this->log_request();
			$this->response(array('status' => false, 'error' => 'Invalid API Key.'), 403);
		}

		// Make sure this method exists in our class
		if (!method_exists($this, $controller_method)) {
			// Although this is not strictly allowed, we will permit fallback for class methods with no request methods defined assuming it is _get
			if (!method_exists($this, $action)) {
				$this->response(array('status' => false, 'error'  => 'Unknown method.'), 404);
			} else {
				$controller_method = $action;
				$this->request->method = 'get';
			}
		}

		// Doing key related stuff? Can only do it if they have a key right?
		if ($this->rest_api_keys AND !empty($this->rest->key)) {
			// Check the limit
			if ($this->rest_limit AND !$this->check_limit()) {
				$this->response(array('status' => false, 'error' => 'This API key has reached the hourly limit for this method.'), 401);
			}

			// If no level is set, or it is lower than/equal to the key's level
			$authorized = 0 <= $this->rest->level;

			if ($this->rest_logs) $this->log_request($authorized);

			// They don't have good enough perms
			$authorized OR $this->response(array('status' => false, 'error' => 'This API key does not have enough permissions.'), 401);
		}
		// No key information, just log what's happening then
		else {
			if ($this->rest_logs) $this->log_request($authorized = TRUE);
		}

		// And...... GO!
		$this->$controller_method();
		//$this->execute_method(array($this, $controller_method), $arguments);
	}

	/**
	 * Fire Method
	 *
	 * Fires the designated controller method with the given arguments.
	 *
	 * @param array $class_method The controller method to fire
	 * @param array $args The arguments to pass to the controller method
	 */
	protected function execute_method($class_method, $args) {
		call_user_func_array($class_method, $args);
	}

	/**
	 * Response
	 *
	 * Takes pure data and optionally a status code, then creates the response.
	 *
	 * @param array $data
	 * @param null|int $http_code
	 */
	public function response($data = array(), $http_code = null) {
		global $config;

		// If data is empty and not code provide, error and bail
		if (empty($data) && $http_code === null) {
			$http_code = 404;

			// create the output variable here in the case of $this->response(array());
			$output = NULL;
		}
		// Otherwise (if no data but 200 provided) or some data, carry on camping!
		else {
			// Is compression requested?
			if ($config->compression === TRUE && $this->zlib_oc == FALSE) {
				if (extension_loaded('zlib')) {
					if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) AND strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
						ob_start('ob_gzhandler');
					}
				}
			}

			is_numeric($http_code) OR $http_code = 200;

			// If the format method exists in this class, call and return the output in that format
			if (method_exists($this, 'format_' . $this->response->format)) {
				// Set the correct format header
				header('Content-Type: ' . $this->supported_formats[$this->response->format]);

				$output = $this->{'format_' . $this->response->format}($data);
			}
			// Else if the format method exists in the formatter class, call and return the output in that format
			elseif (method_exists($this->format, 'to_' . $this->response->format)) {
				// Set the correct format header
				header('Content-Type: ' . $this->supported_formats[$this->response->format]);

				$output = $this->format->factory($data)->{'to_' . $this->response->format}();
			}
			// Format not supported, output directly
			else {
				$output = $data;
			}
		}

		header('HTTP/1.1: ' . $http_code);
		header('Status: ' . $http_code);

		// If zlib.output_compression is enabled it will compress the output,
		// but it will not modify the content-length header to compensate for
		// the reduction, causing the browser to hang waiting for more data.
		// We'll just skip content-length in those cases.
		if (!$this->zlib_oc && !$config->compression) {
			header('Content-Length: ' . strlen($output));
		}

		exit($output);
	}

	/*
	 * Detect input format
	 *
	 * Detect which format the HTTP Body is provided in
	 */
	protected function detect_input_format() {
		global $config;
		if ($config->content_type) {
			// Check all formats against the HTTP_ACCEPT header
			foreach ($this->supported_formats as $format => $mime) {
				if (strpos($match = $config->content_type, ';')) {
					$match = current(explode(';', $match));
				}

				if ($match == $mime) {
					return $format;
				}
			}
		}
		return NULL;
	}

	/**
	 * Universal method to get any kind of input submitted to the page
	 *
	 * ***Note: because it checks for every single input "channel", this function is inherently slow
	 *          therefore, if you know the type of input to expect, you should use
	 *          $this->post_args, get_args, put_args  or delete_args (or simply $this->input('post'))
	 *
	 * @param string $format Input method format (get, post, put, delete)
	 * @return array
	 */
	protected function input($format = null){
		if ($format) return $this->{$format . '_args'};
		else {
			global $config;
			$input = array();
			if (isset($this->get_args) and count($this->get_args) > 0) $input = array_merge($input, $this->get_args);
			if (isset($this->post_args) and count($this->post_args) > 0) $input = array_merge($input, $this->post_args);
			if (isset($this->put_args) and count($this->put_args) > 0) $input = array_merge($input, $this->put_args);
			if (isset($this->delete_args) and count($this->delete_args) > 0) $input = array_merge($input, $this->delete_args);
			if (isset($config->input->parms) and count($config->input->parms) > 0) $input = array_merge($input, $config->input->parms);
			if (isset($config->input->get) and count($config->input->get) > 0) $input = array_merge($input, $config->input->get);
			if (isset($config->input->post) and count($config->input->post) > 0) $input = array_merge($input, $config->input->post);
			if ($this->request->format == 'json') $input = array_merge($input, json_decode(file_get_contents('php://input'), true));
			$input = array_unique($input);

			return $input;
		}
	}
	/**
	 * Detect output format
	 *
	 * Detect which format should be used to output the data.
	 *
	 * @return string The output format.
	 */
	protected function detect_output_format() {
		global $config;
		$pattern = '/\.(' . implode('|', array_keys($this->supported_formats)) . ')$/';

		// Check if a file extension is used
		if (preg_match($pattern, $config->uri, $matches)) {
			return $matches[1];
		} elseif ($this->get_args AND !is_array(end($this->get_args)) AND preg_match($pattern, end($this->get_args), $matches)) {
			// The key of the last argument
			$last_key = end(array_keys($this->get_args));

			// Remove the extension from arguments too
			$this->get_args[$last_key] = preg_replace($pattern, '', $this->get_args[$last_key]);
			$this->args[$last_key] = preg_replace($pattern, '', $this->args[$last_key]);

			return $matches[1];
		}

		/*
		// Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
		if ($this->config->item('rest_ignore_http_accept') === FALSE AND $this->input->server('HTTP_ACCEPT')) {
			// Check all formats against the HTTP_ACCEPT header
			foreach (array_keys($this->supported_formats) as $format) {
				// Has this format been requested?
				if (strpos($this->input->server('HTTP_ACCEPT'), $format) !== FALSE) {
					// If not HTML or XML assume its right and send it on its way
					if ($format != 'html' AND $format != 'xml') {

						return $format;
					} // HTML or XML have shown up as a match
					else {
						// If it is truly HTML, it wont want any XML
						if ($format == 'html' AND strpos($this->input->server('HTTP_ACCEPT'), 'xml') === FALSE) {
							return $format;
						} // If it is truly XML, it wont want any HTML
						elseif ($format == 'xml' AND strpos($this->input->server('HTTP_ACCEPT'), 'html') === FALSE) {
							return $format;
						}
					}
				}
			}
		}
		*/

		// A format has been passed as an argument in the URL that is supported
		if (isset($this->get_args['format']) AND array_key_exists($this->get_args['format'], $this->supported_formats)) {
			return $this->get_args['format'];
		}

		// Well, none of that has worked! Return the default format
		return $this->rest_format;
	}

	/**
	 * Detect method
	 *
	 * Detect which HTTP method is being used
	 *
	 * @return string
	 */
	protected function detect_method() {
		global $config;
		$method = strtolower($config->request);

		if ($config->http_method_override) $method = strtolower($config->http_method_override);

		if (in_array($method, $this->allowed_http_methods) && method_exists($this, 'parse_' . $method)) {
			return $method;
		}

		return 'get';
	}

	/**
	 * Detect API Key
	 *
	 * See if the user has provided an API key
	 *
	 * @return boolean
	 */
	protected function detect_api_key() {
		global $config;
		// Get the api key name variable set in the rest config file
		$api_key_variable = $this->rest_key_name;

		// Work out the name of the SERVER entry based on config
		$key_name = 'HTTP_' . strtoupper(str_replace('-', '_', $api_key_variable));

		$this->rest->key = NULL;
		$this->rest->level = NULL;
		$this->rest->user_id = NULL;
		$this->rest->ignore_limits = FALSE;

		// Find the key from server or arguments
		if (($key = isset($this->args[$api_key_variable]) ? $this->args[$api_key_variable] : $_SERVER[$key_name])) {
			if (!($row = $this->rest->db->get_data('SELECT * FROM ' . $config->dbprefix . 'api_keys WHERE key = ?', array($key)))) {
				return FALSE;
			}

			$this->rest->key = $row->key;

			isset($row->user_id) AND $this->rest->user_id = $row->user_id;
			isset($row->level) AND $this->rest->level = $row->level;
			isset($row->ignore_limits) AND $this->rest->ignore_limits = $row->ignore_limits;

			// If "is private key" is enabled, compare the ip address with the list of valid ip addresses stored in the database.
			if (!empty($row->is_private_key)) {
				// Check for a list of valid ip addresses
				if (isset($row->ip_addresses)) {
					// multiple ip addresses must be separated using a comma, explode and loop
					$list_ip_addresses = explode(",", $row->ip_addresses);
					$found_address = FALSE;

					foreach ($list_ip_addresses as $ip_address) {
						if ($config->env->ip == trim($ip_address)) {
							// there is a match, set the the value to true and break out of the loop
							$found_address = TRUE;
							break;
						}
					}

					return $found_address;
				} else {
					// There should be at least one IP address for this private key.
					return FALSE;
				}
			}

			return $row;
		}

		// No key has been sent
		return FALSE;
	}


	/**
	 * Log request
	 *
	 * Record the entry for awesomeness purposes
	 *
	 * @param boolean $authorized
	 * @return object
	 */
	protected function log_request($authorized = FALSE) {
		global $config;
		return $this->rest->db->insert($config->dbprefix . 'rest_logs', array(
					'uri' => $config->uri,
					'method' => $this->request->method,
					'params' => $this->args ? serialize($this->args) : null,
					'api_key' => isset($this->rest->key) ? $this->rest->key : '',
					'ip' => $config->env->ip,
					'time' => function_exists('now') ? now() : time(),
					'authorized' => $authorized
				));
	}

	/**
	 * Limiting requests
	 *
	 * Check if the requests are coming in a tad too fast.
	 *
	 * @return boolean
	 */
	protected function check_limit() {
		global $config;

		// How many times can you get to this method an hour?
		$limit = $this->rest_limit;

		// Get data on a keys usage
		$result = $this->rest->db->get_data('SELECT * FROM ' . $config->dbprefix . 'rest_limits WHERE uri = ? AND api_key = ?', array($config->uri, $this->rest->key));

		// No calls yet, or it's been an hour since they called
		if (!$result OR $result->hour_started < time() - (60 * 60)) {
			// Right, set one up from scratch
			return $this->rest->db->insert($config->dbprefix . 'rest_limits', array(
				'uri' => $config->uri,
				'api_key' => isset($this->rest->key) ? $this->rest->key : '',
				'count' => 1,
				'hour_started' => time()
			));
		}
		// They have called within the hour, so lets update
		else {
			// Your luck is out, you've called too many times!
			if ($result->count >= $limit) return FALSE;
			$this->rest->db->update($config->dbprefix . 'rest_limits', array('count' => 'count + 1'), 'uri = ? AND api_key = ?', array($config->uri, $this->rest->key));
		}

		return TRUE;
	}

	/**
	 * Parse GET
	 */
	protected function parse_get() {
		// Grab proper GET variables
		parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $get);

		// Merge both the URI segments and GET params
		$this->get_args = array_merge($this->get_args, $get);
	}

	/**
	 * Parse POST
	 */
	protected function parse_post() {
		$this->post_args = $_POST;
		$this->request->format and $this->request->body = file_get_contents('php://input');
	}

	/**
	 * Parse PUT
	 */
	protected function parse_put() {
		// It might be an HTTP body
		if ($this->request->format) {
			$this->request->body = file_get_contents('php://input');
		} // If no file type is provided, this is probably just arguments
		else {
			parse_str(file_get_contents('php://input'), $this->put_args);
		}
	}

	/**
	 * Parse DELETE
	 */
	protected function parse_delete() {
		// Set up out DELETE variables (which shouldn't really exist, but sssh!)
		parse_str(file_get_contents('php://input'), $this->delete_args);
	}

	// INPUT FUNCTION --------------------------------------------------------------

	/**
	 * Retrieve a value from the GET request arguments.
	 *
	 * @param string $key The key for the GET request argument to retrieve
	 * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
	 * @return string The GET argument value.
	 */
	public function get($key = NULL, $xss_clean = TRUE) {
		if ($key === NULL) {
			return $this->get_args;
		}

		return array_key_exists($key, $this->get_args) ? $this->xss_clean($this->get_args[$key], $xss_clean) : FALSE;
	}

	/**
	 * Retrieve a value from the POST request arguments.
	 *
	 * @param string $key The key for the POST request argument to retrieve
	 * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
	 * @return string The POST argument value.
	 */
	public function post($key = NULL, $xss_clean = TRUE) {
		if ($key === NULL) {
			return $this->post_args;
		}

		return array_key_exists($key, $this->post_args) ? $this->xss_clean($this->post_args[$key], $xss_clean) : FALSE;
	}

	/**
	 * Retrieve a value from the PUT request arguments.
	 *
	 * @param string $key The key for the PUT request argument to retrieve
	 * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
	 * @return string The PUT argument value.
	 */
	public function put($key = NULL, $xss_clean = TRUE) {
		if ($key === NULL) {
			return $this->put_args;
		}

		return array_key_exists($key, $this->put_args) ? $this->xss_clean($this->put_args[$key], $xss_clean) : FALSE;
	}

	/**
	 * Retrieve a value from the DELETE request arguments.
	 *
	 * @param string $key The key for the DELETE request argument to retrieve
	 * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
	 * @return string The DELETE argument value.
	 */
	public function delete($key = NULL, $xss_clean = TRUE) {
		if ($key === NULL) {
			return $this->delete_args;
		}

		return array_key_exists($key, $this->delete_args) ? $this->xss_clean($this->delete_args[$key], $xss_clean) : FALSE;
	}

	/**
	 * Process to protect from XSS attacks.
	 *
	 * @param string $val The input.
	 * @param boolean $process Do clean or note the input.
	 * @return string
	 */
	protected function xss_clean($val, $process) {
		//return $process ? (preg_replace('/<\/*(j?script|frame|frameset|iframe)(.*)>/i', '', sanitize_string($val))) : $val;
		return $process ? security::xss_clean($val) : $val;
	}


	// SECURITY FUNCTIONS ---------------------------------------------------------

	/**
	 * BASIC authentication
	 */
	protected function prepare_basic_auth() {
		// If whitelist is enabled it has the first chance to kick them out
		if ($this->rest_ip_whitelist) {
			$this->check_whitelist_auth();
		}

		$username = NULL;
		$password = NULL;

		// mod_php
		if ($_SERVER['PHP_AUTH_USER']) {
			$username = $_SERVER['PHP_AUTH_USER'];
			$password = $_SERVER['PHP_AUTH_PW'];
		} // most other servers
		elseif ($_SERVER['HTTP_AUTHENTICATION']) {
			if (strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']), 'basic') === 0) {
				list($username, $password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			}
		}

		if ($user = get_user(array('username' => $username), true) and $user->check_password($password)){
			$_SESSION['user_guid'] = $user->guid;
			$_SESSION['user_name'] = $user->name;
			$_SESSION['user_email'] = $user->email;
		} else {
			$this->show_login();
		}
	}

	/**
	 * DIGEST authentication
	 */
	protected function prepare_digest_auth() {
		// If whitelist is enabled it has the first chance to kick them out
		if ($this->rest_ip_whitelist) {
			$this->check_whitelist_auth();
		}

		$uniqid = uniqid(""); // Empty argument for backward compatibility
		// We need to test which server authentication variable to use
		// because the PHP ISAPI module in IIS acts different from CGI
		if ($_SERVER['PHP_AUTH_DIGEST']) {
			$digest_string = $_SERVER['PHP_AUTH_DIGEST'];
		} elseif ($_SERVER['HTTP_AUTHORIZATION']) {
			$digest_string = $_SERVER['HTTP_AUTHORIZATION'];
		} else {
			$digest_string = "";
		}

		// The $_SESSION['error_prompted'] variable is used to ask the password
		// again if none given or if the user enters wrong auth information.
		if (empty($digest_string)) {
			$this->show_login($uniqid);
		}

		// We need to retrieve authentication information from the $auth_data variable
		preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
		$digest = array_combine($matches[1], $matches[2]);

		if (!array_key_exists('username', $digest) OR !$user = get_user(array('username' => $digest['username']), true)) {
			$this->show_login($uniqid);
		}

		// This is the valid response expected
		$A1 = md5($digest['username'] . ':' . $this->rest_realm . ':' . $user->password);
		$A2 = md5(strtoupper($this->request->method) . ':' . $digest['uri']);
		$valid_response = md5($A1 . ':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' . $A2);

		if ($digest['response'] != $valid_response) {
			header('HTTP/1.0 401 Unauthorized');
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}
	}

	/**
	 * Check if the client's ip is in the 'rest_ip_whitelist' config
	 */
	protected function check_whitelist_auth() {
		if ($this->rest_ip_whitelist) {
			$whitelist = explode(',', $this->rest_ip_whitelist);

			array_push($whitelist, '127.0.0.1', '0.0.0.0');

			foreach ($whitelist AS &$ip) {
				$ip = trim($ip);
			}

			global $config;
			if (!in_array($config->env->ip, $whitelist)) {
				$this->response(array('status' => false, 'error' => 'Not authorized'), 401);
			}
		}
	}

	/**
	 * Displays the login popup window
	 *
	 * @param string $nonce
	 */
	protected function show_login($nonce = '') {
		if ($this->rest_auth == 'basic') {
			header('WWW-Authenticate: Basic realm="' . $this->rest_realm . '"');
		} elseif ($this->rest_auth == 'digest') {
			header('WWW-Authenticate: Digest realm="' . $this->rest_realm . '", qop="auth", nonce="' . $nonce . '", opaque="' . md5($this->rest_realm) . '"');
		}

		$this->response(array('status' => false, 'error'  => 'Not authorized'), 401);
	}
}
