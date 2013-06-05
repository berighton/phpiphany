<?php

/**
 * Commonly used functions across all modules
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
 * Sanitise the input (SQL injection, XSS).
 *
 * @TODO: utilize the security class
 * @param string $param The GET variable name
 * @param string $default The default value if the above param is empty
 * @param bool $filter_script Filter potentially harmful script tags and HTML attributes (default true).
 * @return mixed The sanitised variable (int/float or string)
 */
function get_safe_input($param, $default = null, $filter_script = true) {
	$input = get_input($param, $default);
	if ($filter_script) {
		return (preg_replace('/<\/*(j?script|frame|frameset|iframe)(.*)>/i', '', sanitize_string($input)));
	} else {
		// sanitise_string is a wrapper of mysql_real_escape_string
		return sanitize_string($input);
	}
}

/**
 * Get some input from variables passed on the GET or POST line.
 *
 *
 * @param $variable string The variable we want to return.
 * @param $default mixed A default value for the variable if it is not found.
 * @return object with all the parameters
 */
function get_input($variable, $default = '') {
	global $config;
	if (isset($config->input->$variable)) {
		return $config->input->$variable;
	}

	if (isset($_REQUEST[$variable])) {
		if (is_array($_REQUEST[$variable])) {
			$var = $_REQUEST[$variable];
		} else {
			$var = trim($_REQUEST[$variable]);
		}
		return $var;
	}

	// As a last resort, try to read the variable in session
	if (isset($_SESSION[$variable])) {
		return $_SESSION[$variable];
	}

	// Nothing was found, return default
	return $default;
}

/**
 * Sanitize a string for database use
 *
 * @param string $string The string to sanitize
 * @return string Sanitized string
 */
function sanitize_string($string) {
	return mysql_real_escape_string($string);
}

/**
 * Sets an input value that may later be retrieved by get_input
 *
 * Note: this function does not handle nested arrays (ex: form input of param[m][n])
 *
 * @param string $variable The name of the variable
 * @param string $value The value of the variable
 * @param string $category The name of the category for this input (usually used for POST variables)
 * @param bool $safe To sanitize the input or not
 */
function set_input($variable, $value, $category = '', $safe = false) {
	global $config;
	if (!isset($config->input)) {
		$config->input = new stdClass();
	}
	if ($category){
		if (!isset($config->input->$category)) {
			$config->input->$category = array();
		}
		$config->input->{$category}[$variable] = $safe? sanitize_string($value) : $value;
	} else {
		$config->input->$variable = $safe ? sanitize_string($value) : $value;
	}
}

/**
 * Shortens long strings by adding '...' at the end
 *
 * @param mixed $string The string to truncate
 * @param mixed $limit Character cutoff limit
 * @return mixed Returns the truncated string
 */
function truncate($string, $limit = 32) {
	if (strlen($string) > $limit) {
		return substr($string, 0, $limit - 10) . '...' . substr($string, -7);
	} else {
		return $string;
	}
}

/**
 * Get a unique array of entities. Works similar to array_unique but recursive
 *
 * @param array $array The array that needs to be distinct
 * @return array Returns an array with unique elements
 */
function unique_entities($array) {
	$result = array_map("unserialize", array_unique(array_map("serialize", $array)));
	foreach ($result as $key => $value) {
		if (is_array($value)) {
			$result[$key] = unique_entities($value);
		}
	}
	return $result;
}

/**
 * A custom function for php built-in function usort
 * Sorts an array of entities (announcements) by the date/time (desc) it was created.
 * Usage: usort($events, 'sort_array_of_entities');
 *
 * @param object $a The first element of an array
 * @param object $b The second element of an array
 * @return boolean Returns a sorting condition for the usort function
 */
function sort_array_of_entities($a, $b) {
	if (($a->time_created == $b->time_created) or ($a->time_updated == $b->time_updated)) {
		return 0;
	}
	return (($a->time_created < $b->time_created) or ($a->time_updated < $b->time_updated)) ? 1 : -1;
}

/**
 * Wrapper function to register error and redirect to the previous (or user defined) page
 * Simply prints out the error message if the request was done via API
 *
 * @param string $text The error message text. Can be custom or elgg_echo dictionary entry
 * @param string $redirect_link
 * @param bool $admin_only Determines if the redirect request came from admin_only() function
 * @return none Redirects by sending new headers
 */
function pip_error($text, $redirect_link = '', $admin_only = false) {
	global $view;
	$view->alert($text, 'error');

	$_SESSION['last_url'] = $admin_only? '' : $_SERVER['REQUEST_URI'];
	if ($redirect_link) {
		forward($redirect_link);
	} else {
		forward($_SESSION['last_url']);
	}
	exit();
}

/**
 * Wrap function to register green success message and redirect to the previous (or user defined) page
 *
 * @param string $text The error message text. Can be custom or elgg_echo dictionary entry
 * @param string $redirect_link
 * @return void Redirects by sending new headers
 */
function pip_success($text, $redirect_link = '') {
	global $view;
	$view->alert($text, 'success');

	$_SESSION['last_url'] = $_SERVER['REQUEST_URI'];
	if ($redirect_link) {
		forward($redirect_link);
	} else {
		forward($_SESSION['last_url']);
	}
	exit();
}

/**
 * Redirect to a specified URL
 *
 * @param mixed $url (optional)
 */
function forward($url = null){
	//echo "<br>URL: $url";
	//echo "<br>request: " . $_SERVER['REQUEST_URI'];
	//echo "<br>referrer: " . $_SERVER['HTTP_REFERER'];
	//echo "<br>last_url: " . $_SESSION['last_url'];
	//echo "<br>alert msg " . print_r($_POST);
	global $config;
	// Disallow self redirect to avoid infinite loops
	if ($url == $_SERVER['REQUEST_URI']) {
		// But first try to load the default controller/action if referer is not available
		global $router;
		if (isset($_SERVER['HTTP_REFERER']) and $_SERVER['HTTP_REFERER']) $full_address = $_SERVER['HTTP_REFERER'];
		else $full_address = $config->site_url;
		if ($router->controller){
			$tmp_url = $config->site_url . $router->controller;
			if ($router->action) $tmp_url .= '/' . $router->action;
			/*
			if (!$full_address){
				$full_address = (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on')? 'https://' : 'http://';
				$full_address .= $_SERVER['HTTP_HOST'] . $tmp_url;
			}
			$headers = get_headers($full_address, 1);
			// Make sure this page loads fine and is not a redirect
			//if ($headers[0] == 'HTTP/1.1 200 OK' or $headers[0] == 'HTTP/1.1 302 Found' or $headers[1] == 'HTTP/1.1 200 OK') {
			if (isset($headers['Location']) and $headers['Location']) {
				$url = $headers['Location'];
			} else $url = $full_address;
			*/

			// If the controller/action still equal to request_uri, try redirecting to the referer
			$url = ($tmp_url == $_SERVER['REQUEST_URI'])? $full_address : $tmp_url;
			// Last attempt to prevent infinite loop by redirecting to main page if all hell broke loose
			if (isset($_SESSION['redirect_alert_msg']) and strpos($_SESSION['redirect_alert_msg'], 'The username/password combination you entered is INCORRECT') !== false){
				// Since we have an attempt to login, reset any other session variables in order to do so
				if (isset($_SESSION['user_guid']) and $_SESSION['user_guid']) unset($_SESSION['user_guid']);
				$url = $config->site_url . 'authenticator';
			} elseif ($_SERVER['REQUEST_URI'] == $_SESSION['last_url'] and isset($_SERVER['HTTP_REFERER']) and
				(strpos($_SERVER['HTTP_REFERER'], 'authenticator') !== false or strpos($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']) !== false)){
				$url = $config->site_url;
			}
		} else {
			$url = $full_address ? $full_address : false;
		}
		//echo "<br>Final URL: $url";
	}
	if (!$url){
		// Redirect to last url only if it was set, is not equal to the current url (redirect) and the request already passed authentication
		if (isset($_SESSION['last_url']) and $_SESSION['last_url'] and $_SESSION['last_url'] != $_SERVER['REQUEST_URI'] and
						isset($_SERVER['HTTP_REFERER']) and strpos($_SERVER['HTTP_REFERER'], 'authenticator') !== false){
			$url = $_SESSION['last_url'];
		// Authenticator is asking for a legit page, we need to redirect to referrrer rather than last_url
		} elseif (strpos($_SERVER['REQUEST_URI'], 'authenticator') !== false and isset($_SERVER['HTTP_REFERER']) and $_SESSION['last_url'] != $_SERVER['HTTP_REFERER']) {
			// First try to avoid 500 errors and redirect to front page if referer is the same as the request uri
			if (strpos($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']) !== false){
				$url = $config->site_url;
			} else {
				$url = (isset($_SERVER['HTTP_REFERER']) and $_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : $config->site_url;
			}
		} else {
			$url = $config->site_url;
		}
	}

	//echo "<br>Final URL: $url<br>";;
	//exit;

	// Add site url bit if it was not set for internal redirects
	if ($url[0] == '/' and strpos($url, $config->site_url) === false) $url = $config->site_url . substr($url, 1);

	// Flag the alerts flush (see error_handler::last_call() for usage)
	$_SESSION['ob_counter'] = 1;

	// Do the redirect
	if (headers_sent()){
		// This hack will work if headers were already sent by pushing the redirect logic manually
		echo '<html><head><meta http-equiv="refresh" content="0;url=' . $url . '"/></head><body>Redirecting...</body></html>';
	} else header('Location: ' . $url);

	die();
}

/**
 * Detects whether SSL is being used or not
 *
 * @return bool
 */
function detect_ssl() {
	return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on");
}

/**
 * Checks to see if headers can be sent and if any error has been output to the browser
 *
 * @return bool true if headers haven't been sent and no errors will break the output, false if unsafe
 */
function can_send_headers() {
	if (headers_sent()) {
		// output has been flushed and headers have already been sent
		return false;
	} else {
		if (strlen((string)ob_get_contents()) > 0) {
			// headers haven't been sent, but there is data in the buffer that will break our output data
			return false;
		}
	}

	return true;
}

/**
 * Gets the current access list either from cache or by making a new DB call
 * Keep in mind that this potentially might be a very expensive function, hence configuring cache is strongly recommended
 *
 * @param string $guid Entity guid for which to get access list (optional)
 * @param boolean $clear_cache If set to true, the access array stored in cache would be overwritten with new data (optional)
 * @return array Returns the associative array of guid=>access combination
 */
function get_access($guid = '', $clear_cache = false){
	global $cache;
	if ($cache and $cached = $cache->read('access') and !$clear_cache) {
		// If GUID was specified, return the cached entry
		if ($guid){
			if (isset($cached->$guid) and $cached->$guid) {
				return $cached->$guid;
			} // If this GUID is not in cache, try querying the database
			else {
				return get_access($guid, true);
			}
		} else {
			return $cached;
		}
	} else {
		global $db, $config;
		$db->query("SELECT * FROM {$config->dbprefix}access");
		$results = $db->fetch_assoc_all();
		if ($results) {
			$entities = new stdClass();
			// @TODO: either make sure that GUID always starts with a letter or make this an array
			foreach ($results as $result){
				// Check if user can access a group. If so, add this relationship to users AND groups arrays
				if ($result['user_guid'] and $result['group_guid']){
					$entities->{$result['user_guid']}[] = $result['group_guid'];
					$entities->{$result['group_guid']}[] = $result['user_guid'];
				}
				// Check if user can access an object. If so, add this relationship to users AND objects arrays
				elseif ($result['user_guid'] and $result['object_guid']) {
					$entities->{$result['user_guid']}[] = $result['object_guid'];
					$entities->{$result['object_guid']}[] = $result['user_guid'];
				}
				// Check if user can access an object. If so, add this relationship to users AND objects arrays
				elseif ($result['group_guid'] and $result['object_guid']) {
					$entities->{$result['group_guid']}[] = $result['object_guid'];
					$entities->{$result['object_guid']}[] = $result['group_guid'];
				}
			}
			$cache->save('access', $entities);
			return $guid? $entities->$guid : $entities;
		} else {
			return false;
		}
	}
}

/**
 * Simple check to see if the current logged in user has access to a specific entity
 *
 * @param string $guid The entity in question
 * @return bool Returns true if user has access, false otherwise
 */
function can_access($guid){
	if ($guid){
		$access = get_access($guid);
		$user = get_loggedin_user();
		return (in_array($user->guid, $access) or $user->admin)? true : false;
	}
	return false;
}

/**
 * Joins a user to a specific group, adding the necessary access permissions automagically
 * Note: this function does NOT clear cache as it is expected to be done in the parent method calling it
 *
 * @param string $group_guid The GUID of a group where to join the user to
 * @param string $user_guid The GUID of a user that needs to become member of the group above
 * @return bool Returns true on success, false on error
 */
function join_group($group_guid, $user_guid){
	global $db, $config;
	if ($db->insert($config->dbprefix . 'memberships', array('user_guid' => $user_guid, 'group_guid' => $group_guid)) and
		$db->insert($config->dbprefix . 'access', array('user_guid' => $user_guid, 'group_guid' => $group_guid))){
		// Update cache entries for this group and user
		orm::get($group_guid, true);
		orm::get($user_guid, true);
		return true;
	} else {
		return false;
	}
}

/**
 * Revokes membership of a specific user from a specified group removing access permissions as well
 * Note: this function does NOT clear cache as it is expected to be done in the parent method calling it
 *
 * @param string $group_guid The GUID of a group where to unlink the user
 * @param string $user_guid The GUID of a user that needs to be removed from the group above
 * @return bool Returns true on success, false on error
 */
function leave_group($group_guid, $user_guid){
	global $db, $config;
	if ($db->delete($config->dbprefix . 'memberships', 'user_guid = ? AND group_guid = ?', array($user_guid, $group_guid)) and
		$db->delete($config->dbprefix . 'access', 'user_guid = ? AND group_guid = ?', array($user_guid, $group_guid))){
		// Update cache entries for this group and user
		orm::get($group_guid, true);
		orm::get($user_guid, true);
		return true;
	} else {
		return false;
	}
}

/**
 * Quick debugging method to return the contents of an array or an object in a nicely formatted way using print_r
 *
 * @param mixed $entity An array or an object to be disected
 * @param string $var_name Name of the $entity
 * @param bool $output Set true to return the value and false (default) to print right away
 * @param bool $print2view Since the $view handles all the page draws, we want to print the output first thing on the page. If false, will do a simple echo
 * @return string <pre>print_r($entity)</pre>
 */
function printr($entity, $var_name = null, $output = false, $print2view = true) {
	if (!$var_name) {
		$var_name = print_var_name($entity);
		if (!$var_name) {
			$var_name = 'Our variable';
		} else {
			$var_name = "<em>$var_name</em> variable";
		}
	}
	$return = "<b>$var_name:</b> <pre>" . print_r($entity, true) . "</pre><br />";

	if ($output) {
		return $return;
	} elseif ($print2view) {
		global $view;
		$view->system_msg .= $return;
	} else {
		echo $return;
	}
	return true;
}

/**
 * Gets the variable name by looping through all the variables defined at the runtime (expensive)
 *
 * @param  $var The variable to get the name for
 * @return bool|string The variable name or false
 */
function print_var_name($var) {
	foreach ($GLOBALS as $var_name => $value) {
		if ($value === $var) {
			return $var_name;
		}
	}
	return false;
}


/**
 * Search for a value (or key) in an object. Works similarly to the native in_array function
 *
 * @param  mixed $needle What to search for
 * @param  object $haystack Where to search. Can be native stdClass or our SD_Entity class object
 * @param  boolean $is_key If set to true, searches by key and returns value instead of searching by value and returning key
 * @return mixed Returns the result if found, or false otherwise
 */
function in_object($needle, $haystack, $is_key = false) {
	if (is_object($haystack)) {
		foreach ($haystack as $key => $item) {
			// Search by key instead of value
			if ($is_key) {
				if ($needle == $key) {
					return $item;
				}
			} else {
				if ($needle == $item) {
					return $key;
				}
			}
		}
		// Nothing found
		return false;
	}
	return false;
}

/**
 * Function to convert the size (in bytes) to a human readable format
 *
 * @param integer $size The size of a file represented in bytes
 * @return string Returns the size with appended B, KB, MB, GB or TB
 */
function readable_size($size) {
	$i = 0;
	$iec = array("B", "KB", "MB", "GB", "TB");
	while (($size / 1024) > 1) {
		$size = $size / 1024;
		++$i;
	}
	return round($size, 1) . $iec[$i];
}

/**
 * Determines if the OS is 64 or 32bit by checking the inval cutoff point
 *
 * @return bool|string
 */
function is_64bit() {
	$int = "9223372036854775807";
	$int = intval($int);
	if ($int == 9223372036854775807) {
		/* 64bit */
		return true;
	} elseif ($int == 2147483647) {
		/* 32bit */
		return false;
	} else {
		/* error */
		return "error";
	}
}

/**
 * Checks if an array is an associative array
 *
 * @param array $array The array to check
 * @return boolean Returns true or false if it is an associative array
 */
function is_assoc($array) {
	return (is_array($array) && array_keys($array) !== range(0, count($array) - 1));
	//return (is_array($array) && (0 !== count(array_diff_key($array, array_keys(array_keys($array)))) || count($array) == 0));
}

/**
 * Generates an ajax_response for a message with status 400
 *
 * @param string $message The message
 * @return string Returns the result of ajax_response
 */
function ajax_error($message) {
	$params = array('message' => $message);
	return ajax_response($params, 400);
}

/**
 * Pass an associative array of params to return to webpage as json_encode
 *
 * @param array $params The array of parameters
 * @param integer $status HTTP status code (default 200)
 * @throws Exception if invalid argument was passed in
 * @return string json encoded parameters
 */
function ajax_response($params, $status = 200) {
	if ($params['status']) {
		throw new Exception("Invalid argument 'status' passed in as parameter to ajax_response");
	}
	$response = array('response' => array('status' => $status));
	foreach ($params as $key => $value) {
		$response['response'][$key] = $value;
	}
	echo json_encode($response);
	exit;
}

/**
 * Simple function to create a writeable directory in a hierarchical manner
 * By default, the folder is created within the phpiphany root and descends down to the child dir if specified
 *
 * Eg.
 * dir_setup('mydir/tmp/group/php');
 * This will create a parent 'mydir' folder and all the child folders underneath if allowed by OS permissions
 *
 * @param string $name The folder name or a path
 * @param bool $pip_dir Whether or not this is a phpiphany folder or any folder in the system
 * @return bool T/F
 */
function dir_setup($name, $pip_dir = true){
	if ($name){
		global $config;
		// Break down a multi folder structure
		$dirs = explode('/', $name);
		// Set the root directory to create a full path
		$path = '';
		if ($pip_dir){
			// Some system writes such as db query logs (stats) are written on object destruct where $config is not available
			if (isset($config) and isset($config->env) and isset($config->env->root)) {
				$path = $config->env->root . '/';
			} else {
				// Hence we use the DB object to get a full log dir path
				global $db;
				$path = $db->path . '/';
			}
		}
		foreach ($dirs as $dir){
			if ($dir){
				// Directories should always be appended with /
				if (substr($dir, -1) != '/') {
					$dir .= '/';
				}
				$path .= $dir;
				// If the directory does not exists, create it
				if (!is_dir($path)) {
					// The mode on this directory is affected by your current umask. It will end up having (<mkdir-mode> and (not <umask>)).
					// If you want to create one that is publicly readable, it has to be done like this
					$oldumask = umask(0);
					mkdir($path, 0777); // or even 01777 so you get the sticky bit set
					umask($oldumask);
				}
				// If the directory is not writeable, try to change permissions on it
				if (!is_writable($path) and !chmod($path, 0777)) {
					// If this is an API/AJAx call, output a simple text, otherwise throw a regular pip exception
					if ($config->direct_output) {
						die('Failed to chmod() the "' . $path . '" directory! Please do so manually.');
					} else {
						throw new error('Failed to <em>chmod()</em> the "' . $path . '" directory! Please do so manually.');
					}
				}
			}
		}
		return $path;
	}
	return false;
}

/**
 * Converts the special characters for a clean export
 * Works similar to htmlentities(), but more suitable for XML
 *
 * @param string $str The unexacped string
 * @return string The 'clean' string
 */
function cleanxml($str) {
	$strout = null;

	for ($i = 0; $i < strlen($str); $i++) {
		$ord = ord($str[$i]);

		if (($ord > 0 && $ord < 32) || ($ord >= 127)) {
			$strout .= "&amp;#{$ord};";
		} else {
			switch ($str[$i]) {
				case '<':
					$strout .= '&lt;';
					break;
				case '>':
					$strout .= '&gt;';
					break;
				case '&':
					$strout .= '&amp;';
					break;
				case '"':
					$strout .= '&quot;';
					break;
				default:
					$strout .= $str[$i];
			}
		}
	}

	return $strout;
}

/**
 * A quick function to convert an associative (multi-dimensional) array into an object of stdClass
 * For a one-dimension array this can be achieved with type casting like so: $arr = (object) $arr;
 *
 * @param array $array The array to convert
 * @return stdClass object or false if nothing was passed in
 */
function array2object($array) {
	if (!is_array($array)) {
		return $array;
	}
	$object = new stdClass();
	if (is_array($array) && count($array) > 0) {
		foreach ($array as $name => $value) {
			$name = strtolower(trim($name));
			if (!empty($name)) {
				$object->$name = array2object($value);
			}
		}
		return $object;
	} else {
		return FALSE;
	}
}

/**
 * Converts a single entity or an array of entities into XML format
 *
 * @param array|object $entities Array of pip entities
 * @return bool|string Returns XML string on success, false on error
 */
function entities2xml($entities){
	$xml = false;
	if (is_array($entities) and count($entities) > 0){
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$xml .= "<!-- This XML was generated by " . get_loggedin_user()->name . " on " . new dater . " -->\n";
		$xml .= "<entities>\n";
		foreach ($entities as $entity){
			if ($entity instanceof pip_entity){
				$xml .= "	<entity>\n";
				foreach ($entity as $key => $value){
					// Traverse inside an array or object
					if ((is_array($value) or is_object($value)) and count($value) > 0){
						$multi_value = "\n";
						foreach ($value as $v){
							//$k = is_integer($k)? "guid$k" : $k;
							//$multi_value .= "			<$k>" . cleanxml($v) . "</$k>\n";
							$multi_value .= "			<guid>$v</guid>\n";
						}
						$multi_value .= "		";
					} else {
						$multi_value = cleanxml($value);
					}
					$xml .= "		<$key>$multi_value</$key>\n";
				}
				// Add access for this entity
				if ($access = get_access($entity->guid)){
					$xml .= "		<access>\n";
					foreach ($access as $v){
						$xml .= "			<guid>$v</guid>\n";
					}
					$xml .= "		</access>\n";
				}
				$xml .= "	</entity>\n";
			} else {
				$xml .= "	<error>Not a valid pip_entity!</error>";
			}
		}
		$xml .= "</entities>\n";
	// Entity is by itself (not an array), but it must be a pip object
	} else {
		if ($entities instanceof pip_entity){
			$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
			$xml .= "<!-- This XML was generated by " . get_loggedin_user()->name . " on " . new dater . " -->";
			$xml .= "<entity>\n";
			foreach ($entities as $key => $value){
				// Traverse inside an array or object
				if ((is_array($value) or is_object($value)) and count($value) > 0){
					$multi_value = "\n";
					foreach ($value as $k => $v){
						$k = is_integer($k)? "guid$k" : $k;
						$multi_value .= "		<$k>" . cleanxml($v) . "</$k>\n";
					}
					$multi_value .= "	";
				} else {
					$multi_value = cleanxml($value);
				}
				$xml .= "	<$key>$multi_value</$key>\n";
			}
			// Add access for this entity
			if ($access = get_access($entities->guid)){
				$xml .= "		<access>\n";
				foreach ($access as $v){
					$xml .= "			<guid>$v</guid>\n";
				}
				$xml .= "		</access>\n";
			}
			$xml .= "</entity>\n";
		}
	}
	return $xml;
}

/**
 * Makes an artificial POST request
 *
 * @param string $url The fully-qualified URL of the server where to make the request
 * @param array $data POST data
 * @param string $referer Where are we coming from (optional)
 * @return array Returns a status array as well as server response
 */
function post_request($url, $data, $referer = '') {

	// Convert the data array into URL Parameters like a=b&foo=bar etc.
	$data = http_build_query($data);

	// parse the given URL
	$url = parse_url($url);

	if ($url['scheme'] != 'http') {
		die('Error: Only HTTP request are supported !');
	}

	// extract host and path:
	$host = $url['host'];
	$path = $url['path'];

	// open a socket connection on port 80 - timeout: 30 sec
	$fp = fsockopen($host, 80, $errno, $errstr, 30);

	if ($fp) {

		// send the request headers:
		fputs($fp, "POST $path HTTP/1.1\r\n");
		fputs($fp, "Host: $host\r\n");

		if ($referer != '') {
			fputs($fp, "Referer: $referer\r\n");
		}

		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: " . strlen($data) . "\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);

		$result = '';
		while (!feof($fp)) {
			// receive the results of the request
			$result .= fgets($fp, 128);
		}
	} else {
		return array('status' => 'err', 'error' => "$errstr ($errno)");
	}

	// close the socket connection:
	fclose($fp);

	// split the result header from the content
	$result = explode("\r\n\r\n", $result, 2);

	$header = isset($result[0]) ? $result[0] : '';
	$content = isset($result[1]) ? $result[1] : '';

	// return as structured array:
	return array('status' => 'ok', 'header' => $header, 'content' => $content);
}


/**
 * Generate CSRF security token
 *
 * @param boolean $GET If set to true, will return token as the GET query parameter suitable for inserting as URL
 * @param boolean $html If set to true, will return token in a form of an HTML block
 * @return mixed Returns either the HTML formatted text, the string in a form of GET parameters or an array with token and ts variables
 */
function generate_token($GET = false, $html = false) {
	global $config;

	// Current timestamp
	$ts = $_SERVER['REQUEST_TIME'];

	// Server hash salt (secret key)
	$salt = "<{$config->site_name}> _MD_ ProductionS: " . date('Y', $ts);
	$hash = md5($ts . $config->site_desc . $salt);
	
	// Generate the tokens
	$token = sha1(hash_hmac('gost', $config->env->hostname . $config->env->ip, $hash));

	// Set/Update the current token in session
	$_SESSION['csrf_token'] = $token;
	$_SESSION['csrf_ts'] = $ts;

	// Return results
	if ($GET) return "csrf_token=$token&csrf_ts=$ts";
	if ($html) return "<input type=\"hidden\" name=\"csrf_token\" value=\"$token\"><input type=\"hidden\" name=\"csrf_ts\" value=\"$ts\">";
	else return array($token, $ts);
}

/**
 * Validates the CSRF action token
 *
 * @param mixed $api If set (API object), outputs the error in the format configured by the API response (JSON, XML, etc - not phpiphany formatted HTML)
 * @param bool $force_new Determines whether or not to force fetching token and ts from $_REQUEST, or simply rely on $config values
 * @return mixed true if valid, renders an error page if something went wrong
 */
function action_gatekeeper($api = false, $force_new = false) {
	global $config;
	$error = false;

	if ($force_new){
		//$sent_ts = get_safe_input('csrf_ts');
		//$sent_token = get_safe_input('csrf_token');
		$sent_ts = $config->input->csrf_ts;
		$sent_token = $config->input->csrf_token;
	} else {
		$sent_ts = isset($config->input->csrf_ts)? $config->input->csrf_ts : get_safe_input('csrf_ts');
		$sent_token = isset($config->input->csrf_token)? $config->input->csrf_token : get_safe_input('csrf_token');
	}

	if (!$sent_ts or !$sent_token){
		$error = 'This page requires extra layer of security. No validation token supplied.';
	}

	$valid_ts = $_SERVER['REQUEST_TIME'];

	// Server hash salt (secret key)
	$salt = "<{$config->site_name}> _MD_ ProductionS: " . date('Y', (int)$sent_ts);
	$hash = md5($sent_ts . $config->site_desc . $salt);

	// Generate the tokens
	$valid_token = sha1(hash_hmac('gost', $config->env->hostname . $config->env->ip, $hash));

	// Check if token is still valid by calculating 10 minutes time span
	if ($sent_ts < $valid_ts - 60 * 10 * 60) {
		$error = 'Security token for this page has expired. Please refresh the page or click on the link you used to get to this page.';
	}

	// Compare tokens
	if ($valid_token != $sent_token) {
		$error = 'Invalid token supplied. Validity of this page has been compromised!';
	}

	if ($error){
		if ($api) {
			if ($api === true) echo $error;
			else $api->response(array('status' => false, 'error' => $error), 403);
		}
		else pip_error($error, '/');
		exit();
	} else {
		return true;
	}

}


/**
 * Simple function to check if user is logged in by verifying whether or not user's session is set
 *
 * @return bool
 */
function is_loggedin(){
	if (isset($_SESSION['user_guid']) and $_SESSION['user_guid']) return true;
	else return false;
}

/**
 * Returns currently logged in user as an instance of pip_entity
 *
 * @return bool|object|pip_entity
 */
function get_loggedin_user(){
	if (is_loggedin()){
		return orm::get($_SESSION['user_guid']);
	} else {
		return false;
	}
}

/**
 * Return the current logged in user by id.
 *
 * @see get_loggedin_user()
 * @return int
 */
function get_loggedin_userid() {
	$user = get_loggedin_user();
	if ($user) return $user->guid;

	return 0;
}

/**
 * Tries to find a user by given criteria. The more parameters specified, the narrower the search
 * Since this function is not anticipated to be heavily used, the results are not cached
 *
 * Usage:
 * if ($user = get_user(array('fname' => 'Paul', 'lname' => 'B'))) echo 'User "' . $user->name . '" found!';
 *
 * @param array $options Array of known information to retrieve a user by. Acceptable parameters: 'guid', 'email', 'username', 'code', 'fname', 'lname'
 * @param bool $strict Define how loose the search has to be. If set to false (default), the query will execute with LIKE '%%'. Otherwise an exact match
 * @param string $gate The logical gate defining how the search has to be conducted (default 'AND'), alternative: OR
 * @return mixed Returns a pip_user object if user found, otherwise false
 */
function get_user(array $options, $strict = false, $gate = 'AND'){
	if (count($options) < 1) return false;
	$guid = $email = $username = $code = $fname = $lname = '';
	$gate = strtoupper($gate);
	if ($gate != 'AND' and $gate != 'OR') $gate = 'AND';
	foreach ($options as $criteria => $value) {
		$value = mysql_real_escape_string($value);
		if ($criteria == 'guid' and $value) $guid = $strict ? "`guid` = '$value' $gate " : "`guid` LIKE '%$value%' $gate ";
		elseif ($criteria == 'email' and $value) $email = $strict ? "`email` = '$value' $gate " : "`email` LIKE '%$value%' $gate ";
		elseif ($criteria == 'username' and $value) $username = $strict ? "`username` = '$value' $gate " : "`username` LIKE '%$value%' $gate ";
		elseif ($criteria == 'code' and $value) $code = $strict ? "`code` = '$value' $gate " : "`code` LIKE '%$value%' $gate ";
		elseif ($criteria == 'fname' and $value) $fname = $strict ? "`fname` = '$value' $gate " : "`fname` LIKE '%$value%' $gate ";
		elseif ($criteria == 'lname' and $value) $lname = $strict ? "`lname` = '$value' $gate " : "`lname` LIKE '%$value%' $gate ";
	}
	if ($guid or $email or $username or $code or $fname or $lname){
		global $db, $config;
		// Run the query removing the trailing 'AND'
		$db->query(substr("SELECT `guid` FROM {$config->dbprefix}users WHERE {$guid}{$email}{$username}{$code}{$fname}{$lname}", 0, -4));
		$result = $db->fetch_assoc();
		if ($result['guid']) return orm::get($result['guid']);
		else return false;
	} else return false;
}

/**
 * Simple function to get a file object based on a filename which should be unique
 *
 * @param string $name The filename to lookup
 * @return bool|object Returns pip_file if entity found, false otherwise
 */
function get_file($name){
	if ($name){
		global $db, $config;
		// Run the query searching for a filename
		$db->query("SELECT `guid` FROM {$config->dbprefix}files WHERE `filename` = ?", array($name));
		$result = $db->fetch_assoc();
		if ($result['guid']){
			return orm::get($result['guid']);
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * A function that returns a maximum of $limit users who have done something within the last $seconds seconds.
 *
 * @param int $seconds Number of seconds (default 600 = 10min)
 * @param int $limit Limit, default 10.
 * @param int $offset Offset, defualt 0.
 * @return array of pip_users
 */
function find_active_users($seconds = 600, $limit = 10, $offset = 0) {
	global $config, $db;

	$seconds = (int)$seconds;
	$limit = (int)$limit;
	$offset = (int)$offset;
	$time = time() - $seconds;

	return $db->get_data("SELECT distinct `guid` FROM {$config->dbprefix}users WHERE last_action >= {$time} ORDER BY last_action DESC LIMIT {$offset},{$limit}");
}

/**
 * Simple check if the user is logged in
 * Forwards to the main page if this condition is not met
 *
 * @return bool
 */
function loggedin_only(){
	if (!is_loggedin()){
		global $config;
		pip_error('You must be logged in to access this page or your session timed out!', $config->site_url . 'authenticator');
	}
	return false;
}

/**
 * Simple check if the user is logged in AND is admin
 * Forwards to the main page if those conditions are not met
 *
 * @return bool
 */
function admin_only(){
	if (is_loggedin()){
		if ($_SESSION['user_admin']) return true;
		else pip_error('This page is accessible only to the administrator. You seem to lack elevated privileges!', '', true);
	} else {
		global $config;
		pip_error('You must be logged in to access this page or your session timed out!', $config->site_url . 'authenticator');
	}
	return false;
}

/**
 * Another simple check to make sure only owner has access to a given page
 * Parameters for that page are fetched either from the POST array or URL query string (GET)
 *
 * @return bool
 */
function owner_only(){
	global $config;
	if ($config->input->post['id']) {
		$guid = $config->input->post['id'];
	} elseif ($config->input->post['guid']) {
		$guid = $config->input->post['guid'];
	} else {
		$guid = $config->input->parms;
	}
	$guid = is_array($guid)? $guid[0] : $guid;
	$user = get_loggedin_user();
	if ($user->guid == $guid or $user->admin) return true;
	else {
		pip_error('You are unauthorized to view this page based on your credentials');
		return false;
	}
}

/**
 * Simple check to see if the current logged in user is admin
 *
 * @return bool
 */
function is_admin(){
	$user = get_loggedin_user();
	return (isset($user) and $user and $user->admin)? true : false;
}

/**
 * Gets all the active groups a logged in user has access to
 *
 * @param bool $combo_box If set to true, will return the result as an associative array ready to be plugged in to a combo box view (optional)
 * @return array Returns an array of group_guid => group_name for combo box, or and array of objects
 */
function get_active_groups($combo_box = false) {
	global $config, $db;
	$user = get_loggedin_user();
	$groups = $db->get_data("SELECT g.* FROM {$config->dbprefix}groups g, {$config->dbprefix}entities e, {$config->dbprefix}memberships m
							WHERE g.guid = e.guid AND e.active = 'yes' AND m.user_guid = '$user->guid' AND m.group_guid = g.guid");
	if ($groups) {
		$combo = array();
		$groups = is_array($groups)? $groups : array($groups);
		// Apply the access controls
		$access = get_access($user->guid);
		foreach ($groups as $key => $group) {
			if (!in_array($group->guid, $access)){
				unset($groups[$key]);
				continue;
			}
			if ($combo_box) {
				$combo[$group->guid] = $group->name;
			}
		}
		return $combo_box? $combo : $groups;
	} else {
		return false;
	}
}

/**
 * Checks if the current logged in user can access the specified plugin
 * The permission is set in the plugins table if the plugin is installed
 * If no group_guid was specified in the table, everybody is granted access
 *
 * @param string $name The name of the plugin to check
 * @return bool True on permission match or no group_guid or false otherwise
 */
function can_access_plugin($name){
	$user = get_loggedin_user();
	global $db, $config;
	// Perhaps this should be cached?!
	$plugin = $db->fetch_obj($db->select('`group_guid`', $config->dbprefix . 'plugins', '`name` = ?', array($name)));

	if ($plugin) {
		if ($plugin->group_guid and is_loggedin()){
			return (in_array($plugin->group_guid, $user->membership))? true : false;
		} else {
			return true;
		}
	// If no plugin found in the database, it means it is not installed, and therefore access denied
	} else {
		return false;
	}
}

/**
 * Gets all the subtypes registered in the system
 *
 * @param string $type The default type for which to get all the subtypes
 * @param array $exclude If we need to exclude specific set of subtypes that we do not want to show
 * @return array Returns an array of subtypes
 */
function get_subtypes($type = '', array $exclude = array()){
	global $db, $config;
	$type = $type? '`type` = "' . sanitize_string($type) . '"' : '';
	$db->select('`subtype`', $config->dbprefix . 'entity_subtypes', $type);
	$result = $db->fetch_assoc_all();
	if ($result){
		$subtypes = array();
		foreach ($result as $subtype){
			if (stripos($subtype['subtype'], 'admin') !== false or in_array($subtype['subtype'], $exclude)) {
				continue;
			} else {
				$subtypes[$subtype['subtype']] = ucwords(str_replace('_', ' ', $subtype['subtype']));
			}
		}
		return $subtypes;
	}
	return false;
}

/**
 * Clears cache by unsetting the $cache variable and re-initializing it again
 *
 * @TODO needs to be rewritten
 *
 */
function clear_cache(){
	global $cache;
	$cache->del($cache->get_keys());
}

/**
 * Normalize the singular keys in an options array to the plural keys.
 *
 * @param $options
 * @param $singulars
 * @return array
 */
function normalize_plural_options_array($options, $singulars) {
	foreach ($singulars as $singular) {
		$plural = $singular . 's';
		if (isset($options[$singular])) {
			if (isset($options[$plural])) {
				if (is_array($options[$plural])) {
					$options[$plural][] = $options[$singular];
				} else {
					$options[$plural] = array($options[$plural], $options[$singular]);
				}
			} else {
				$options[$plural] = array($options[$singular]);
			}
		}
		unset($options[$singular]);
	}
	return $options;
}

/**
 * Get the full URL of the current page.
 *
 * @param bool $drop_query_string If set to true, will omit any GET parameters
 * @return string The URL
 */
function full_url($drop_query_string = false) {
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
	$port = ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443") ? "" : (":".$_SERVER["SERVER_PORT"]);

	$quotes = array('\'', '"');
	$encoded = array('%27', '%22');

	$url = $protocol . "://" . $_SERVER['SERVER_NAME'] . $port;
	if ($drop_query_string){
		$parms = explode('?', $_SERVER['REQUEST_URI']);
		$url .= str_replace($quotes, $encoded, $parms[0]);
	} else {
		$url .= str_replace($quotes, $encoded, $_SERVER['REQUEST_URI']);
	}

	return $url;
}

/**
 * Gets and displays all entities. Additional parameters are accepted to further filter down the list
 *
 * Plural arguments can be written as singular if only specifying a single element.
 * (e.g., 'type' => 'object' vs 'types' => array('object'))
 *
 *
 * @param array $options Array in format:
 *
 * types => NULL|STR entity type (SQL: type IN ('type1', 'type2') Joined with subtypes by AND...see below)
 * subtypes => NULL|STR entity subtype (SQL: subtype IN ('subtype1', 'subtype2))
 *
 * *************************************************************************************************************
 * ** As per the phpiphany database schema design, there are only 3 system entity types: user, group, object; **
 * ** and 5 subtypes: admin, generic, super, plugin and widget. But of course, it is expected that            **
 * ** a system built on phpiphany will have more types/subtypes tailored for specific business needs          **
 * *************************************************************************************************************
 *
 * owner_guids => NULL|INT entity guid
 * group_guids => NULL|INT group_guid where this entity belongs (checks relationship table)
 *
 * created_time_lower => NULL|INT Created time lower boundary in epoch time
 * created_time_upper => NULL|INT Created time upper boundary in epoch time
 * updated_time_lower => NULL|INT Modified time lower boundary in epoch time
 * updated_time_upper => NULL|INT Modified time upper boundary in epoch time
 *
 * order_by => NULL (time_created desc)|STR SQL ORDER BY clause
 * group_by => NULL|STR SQL GROUP BY clause
 * limit => NULL INT SQL LIMIT clause used to limit the amount of entities fetched
 * offset => NULL (0)|INT SQL OFFSET clause (treated as page number if pagination is used;
 *           otherwise as a DB offset: can be used to control the raw retrieval of entities e.g. API)
 * paginate => TRUE Whether or not to return the results in paginated format (@see pagination class)
 * fullview => FALSE Whether or not to return the HTML results of entities in full view, or short, few-lines-long items
 * count => TRUE|FALSE return a count instead of entities
 * selects => array() Additional SELECT clauses to AND together
 * wheres => array() Additional WHERE clauses to AND together
 * joins => array() Additional JOINs
 *
 * public => FALSE Use this option with caution: if set to true, bypasses all the security controls.
 *           Useful for public entities which anybody can view without being logged in
 * search => FALSE If set to true, will try to find an entity of any type by joining 3 tables: users, groups and objects
 * search_by_email => FALSE If search was set to true, toggle this parameters to enable searching of users by email
 *           With this parm enabled, resulting set will be larger as you can have multiple users with common email parts
 * search_query => NULL The actual search query if search flag was set
 *
 * cache => TRUE Set this to false to invalidate cache (not read from cache but instead make a db call)
 * view => FALSE Set this to a custom view that would render a list of entities using a non-standard view template
 *
 *
 * As part of optimization technique, after rendering a first set of results (set with $limit), this function
 * caches the next consecutive result fetch so that next time the same set of entities will be pulled from cache
 *
 * @return mixed Returns either an array of fetched entities, or an HTML formatted result with pagination
 *
 */
function get_entities(array $options = array()){
	global $config, $cache, $db;

	$defaults = array(
		'types'					=>	NULL,
		'subtypes'				=>	NULL,

		'owner_guids'			=>	NULL,
		'group_guids'			=>	NULL,

		'created_time_lower'	=>	NULL,
		'created_time_upper'	=>	NULL,
		'updated_time_lower'	=>	NULL,
		'updated_time_upper'	=>	NULL,

		'order_by' 				=>	'e.created DESC',
		'group_by'				=>	NULL,
		'limit'					=>	5,
		'offset'				=>	0,
		'pagination'			=>	TRUE,
		'fullview'				=>	FALSE,
		'count'					=>	FALSE,
		'selects'				=>	array(),
		'wheres'				=>	array(),
		'joins'					=>	array(),

		'public'				=>	FALSE,
		'search'				=>	FALSE,
		'search_by_email'		=>	FALSE,
		'search_query'			=>	NULL,

		'cache'					=>	TRUE,
		'view'					=>	FALSE,
	);

	// Get the limit and offset values from the URI (if set)
	if (isset($config->input->get) and $input = $config->input->get){
		if (isset($input['page']) and $input['page']) $options['offset'] = intval($input['page']);
		if (isset($input['limit']) and $input['limit']) $options['limit'] = $input['limit']; // not int val because it can be 'all'
	}

	$options = array_merge($defaults, $options);

	$singulars = array('type', 'subtype', 'owner_guid', 'group_guid');
	$options = normalize_plural_options_array($options, $singulars);

	// If we want to search for an entity, generate a query manually
	if ($options['search'] === true){
		$q = mysql_real_escape_string($options['search_query']);
		$email = $options['search_by_email'] === true? " OR u.username LIKE '%$q%' OR u.email LIKE '%$q%'" : '';
		$query = "SELECT DISTINCT e.guid FROM {$config->dbprefix}entities e, {$config->dbprefix}users u, {$config->dbprefix}groups g, {$config->dbprefix}objects o WHERE ";
		$query .= "(e.guid = u.guid AND (u.fname LIKE '%$q%' OR u.lname LIKE '%$q%'{$email})) OR ";
		$query .= "(e.guid = g.guid AND (g.name LIKE '%$q%' OR g.description LIKE '%$q%')) OR ";
		$query .= "(e.guid = o.guid AND (o.name LIKE '%$q%' OR o.description LIKE '%$q%')) AND ";
	} else {
		// evaluate WHERE clauses
		if (!is_array($options['wheres'])) {
			$options['wheres'] = array($options['wheres']);
		}

		$wheres = $options['wheres'];
		if ($options['types']) $wheres[] = '(e.type IN ("' . implode('", "', $options['types']) . '"))';
		if ($options['subtypes']) $wheres[] = '(e.subtype IN ("' . implode('", "', $options['subtypes']) . '"))';
		if ($options['owner_guids']) $wheres[] = '(e.owner_guid IN ("' . implode('", "', $options['owner_guids']) . '"))';
		if ($options['group_guids']) $wheres[] = '(g.guid IN ("' . implode('", "', $options['group_guids']) . '"))';
		if ($options['created_time_lower'] and is_int($options['created_time_lower'])) $wheres[] = '(e.created >= ' . $options['created_time_lower'] . ')';
		if ($options['created_time_upper'] and is_int($options['created_time_upper'])) $wheres[] = '(e.created <= ' . $options['created_time_upper'] . ')';
		if ($options['updated_time_lower'] and is_int($options['updated_time_lower'])) $wheres[] = '(e.updated >= ' . $options['updated_time_lower'] . ')';
		if ($options['updated_time_upper'] and is_int($options['updated_time_upper'])) $wheres[] = '(e.updated <= ' . $options['updated_time_upper'] . ')';


		// remove identical where clauses
		$wheres = array_unique($wheres);

		// see if any functions failed
		// remove empty strings on successful functions
		foreach ($wheres as $i => $where) {
			if ($where === FALSE) {
				return FALSE;
			} elseif (empty($where)) {
				unset($wheres[$i]);
			}
		}

		// evaluate JOIN clauses
		if (!is_array($options['joins'])) {
			$options['joins'] = array($options['joins']);
		}

		// remove identical join clauses and empty strings
		$joins = array_unique($options['joins']);
		foreach ($joins as $i => $join) {
			if ($join === FALSE) {
				return FALSE;
			} elseif (empty($join)) {
				unset($joins[$i]);
			} else {
				$joins[$i] = ', ' . $join;
			}
		}

		// evalutate SELECTs
		if ($options['selects']) {
			$selects = '';
			foreach ($options['selects'] as $select) {
				$selects = ", $select";
			}
		} else {
			$selects = '';
		}

		$query = "SELECT DISTINCT e.guid{$selects} FROM {$config->dbprefix}entities e ";

		// add joins
		foreach ($joins as $j) {
			$query .= " $j ";
		}

		// add wheres
		$query .= 'WHERE';

		foreach ($wheres as $w) {
			$query .= " $w AND ";
		}

	}
	// add access controls (if any)
	$user = get_loggedin_user();
	// admin user can see anything
	if ($user or !$user->admin) {
		// @TODO: Fine-tune this
		$query .= "(NOT EXISTS (SELECT user_guid FROM {$config->dbprefix}access WHERE user_guid = '{$user->guid}') OR EXISTS ";
		$query .= "(SELECT * FROM {$config->dbprefix}access a, pip_entities e WHERE user_guid = '{$user->guid}' AND (a.group_guid = e.guid OR a.object_guid = e.guid)))";
	} elseif (($user and $user->admin) or $options['public'] === true) {
		// Remove the last 'AND'
		$query = substr($query, 0, -5);
	} else {
		global $view;
		return $view->error('You are not logged in. Cannot get entities!', 'error');
	}

	// Get the total either from cache or make a call to the db
	if (isset($cache)){
		// Check if other actions have triggered clearing of cache (or "no cache" flag was set manually)
		if ($options['cache'] === false) {
			$clear = true;
		} else {
			$clear = (($options['types'] and in_array($cache->read('drop'), $options['types'])) or ($options['subtypes'] and in_array($cache->read('drop'), $options['subtypes']))) ? true : false;
		}
		//$clear = true;
		// If so, clear the current cache key for this query and clear the 'drop' action
		if ($clear){
			$cache->del(md5($query . '_total'));
			$cache->del('drop');
		}
		// Continue populating the total
		if (!($total = $cache->read(md5($query . '_total')))) {
			// Replace the regular SELECT with COUNT
			$total = (int)$db->fetch_obj($db->query(strtr($query, array("SELECT DISTINCT e.guid" => 'SELECT COUNT(DISTINCT e.guid) as total'))))->total;
			$cache->save(md5($query . '_total'), $total);
		}
	} else {
		$total = (int)$db->fetch_obj($db->query(strtr($query, array("SELECT DISTINCT e.guid" => 'SELECT COUNT(DISTINCT e.guid) as total'))))->total;
	}

	// Return the total if count is all we're after
	if ($options['count']) return $total;
	else {

		if ($options['group_by'] = sanitize_string($options['group_by'])) $query .= " GROUP BY {$options['group_by']}";
		if ($options['order_by'] = sanitize_string($options['order_by'])) $query .= " ORDER BY {$options['order_by']}";

		// By default, limit the number of returned entities to 10.
		if ($options['limit'] and strtolower($options['limit']) != 'all') {
			$limit = abs((int)$options['limit']);
			$offset = abs((int)$options['offset']);

			// Some error checking on offset
			if ($offset > 0) --$offset;
			if ($offset > $total) $offset -= $limit;
			if ($offset < 0) $offset = 0;
			// Finally if we are viewing any page other than the first, adjust the offset to show the next set of results
			if ($offset != 0) $offset *= $limit;

			$query .= " LIMIT $offset, $limit";
		}

		// Get the result from cache rather than from making a new db call
		if (isset($cache)) {
			if (!$clear) $cache->del(md5($query));
			if (!($entities = $cache->read(md5($query)))) {
				$entities = $db->get_pip_entities($query);
				$cache->save(md5($query), $entities);
			}
			// If cache is not available, query the database
		} else {
			$entities = $db->get_pip_entities($query);
		}
		// Return the resulted array if the requester does not want the paginated HTML view
		if (!$options['pagination']) return $entities;
		else {
			// Otherwise, paginate the array and return a formatted HTML code
			$p = new pagination(($options['offset'] == 0 ? 1 : $options['offset']), $options['limit'], $total);
			return $p->render($entities, $options['view']) . '		<br>'; // . $query;
		}
	}
}




// $shell = new COM("WScript.Shell") or die("Requires Windows Scripting Host");
// $time_bias = -($shell->RegRead("HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\TimeZoneInformation\\Bias"))/60;

