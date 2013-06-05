<?php
/**
 * Controller for dealing with system's installation upon first-time use
 *
 * Checks for valid DB connection, imports the DB schema and creates an admin user
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

class installer extends controller {

	public function __construct(){
		parent::__construct();
	}

	public function index(){
		global $view, $config;

		// Actual checks to determine system compatibility
		$checks = array();
		$checks['os'] = (stripos($config->env->os, 'linux') !== false)? true : false;
		$checks['php'] = (substr($config->env->php, 0, 1) >= 5)? true : false;
		$checks['http'] = (stripos($config->env->http, 'apache') !== false)? true : false;
		$checks['mysql'] = $config->env->mysql? true : false;
		// Check to see if the settings file is writable
		$settings_file = $config->env->root . 'system/settings.php';
		if (!is_writable($settings_file) and !chmod($settings_file, 0666)) {
			$checks['settings'] = false;
		} else {
			$checks['settings'] = true;
		}

		$view->title = 'Installing phpiphany';
		$view->navbar_login = false;
		$view->navbar_search = false;
		$view->navbar_menu = array(array('title' => 'phpiphany needs to be installed on your system. Click here to restart the installation', 'url' => 'install', 'name' => 'Installing ...'));
		$view->content = $view->load('admin/wizard', array('checks' => $checks, 'token' => generate_token(false, true)));
		$view->render_page();
		exit();
	}

	public function ajax(){
		global $config;

		// Make sure we do not render the page
		$config->direct_output = true;

		// Require a valid CSRF token
		action_gatekeeper(true);

		// Get the page name
		if (!$config->input->parms) { echo json_encode(array('html' => true, 'error' => 'No parameters given')); exit; }
		else $page = $config->input->parms;
		$page = is_array($page)? $page : array($page);

		// Get the input
		$post = $config->input->post;

		switch ($page[0]) {
			case 'db':
				if (!$post['host']) { echo json_encode(array('html' => true, 'error' => 'Missing hostname')); exit; }
				elseif (!$post['user']) { echo json_encode(array('html' => true, 'error' => 'Missing username')); exit; }
				elseif (!$post['db']) { echo json_encode(array('html' => true, 'error' => 'Missing db name')); exit; }

				$db = $post['db'];
				$host = $post['host'];
				$user = $post['user'];
				$pass = $post['pass']? $post['pass'] : '';
				$pre = $post['prefix']? $post['prefix'] : '';

				// Check if the connection settings are valid and we are able to connect
				if ($con = mysql_connect($host, $user, $pass)){
					// If the db does not exist, create it
					if (!mysql_select_db($db)) mysql_query("CREATE DATABASE `$db`", $con);
					// Close this temp connection
					mysql_close($con);

					$schema = 'mysql.sql';

					// All good, let's save these settings
					$settings_file = $config->env->root . 'system/settings.php';
					$lines = file($settings_file, FILE_IGNORE_NEW_LINES);
					$lines[48] = "	public \$dbprefix = '$pre';";
					$lines[49] = "	public \$dbuser = '$user';";
					$lines[50] = "	public \$dbpass = '$pass';";
					$lines[51] = "	public \$dbhost = '$host';";
					$lines[52] = "	public \$dbname = '$db';";
					$lines[53] = "	public \$base_schema = '$schema';";
					file_put_contents($settings_file, implode("\n", $lines));

					// In addition, update the object in memory
					$config->dbprefix = $pre;
					$config->dbuser = $user;
					$config->dbpass = $pass;
					$config->dbhost = $host;
					$config->dbname = $db;

					//echo json_encode(array('html' => 'Settings saved', 'error' => ''));

					// Re-instantiate the $db object
					$db = new database();

					// Now, create the schema. Execute SQL queries as a transaction
					$sql = file_get_contents($config->env->root . 'models/schema/' . $schema);
					$sql = $pre == 'pip_'? $sql : str_replace('pip_', $pre, $sql);
					$db->transaction_start();
					$db->multi_query($sql);

					if ($db->transaction_complete()) echo json_encode(array('html' => 'Schema created', 'error' => ''));
					else { echo json_encode(array('html' => true, 'error' => 'Schema not created')); exit; }

				} else { echo json_encode(array('html' => true, 'error' => 'Cannot connect')); exit; }
				break;
			case 'user':
				if (!$post['fname']) { echo json_encode(array('html' => true, 'error' => 'Empty first name')); exit(); }
				if (!$post['lname']) { echo json_encode(array('html' => true, 'error' => 'Empty last name')); exit(); }
				if (!$post['username']) { echo json_encode(array('html' => true, 'error' => 'Empty username')); exit(); }
				if (!$post['email']) { echo json_encode(array('html' => true, 'error' => 'Empty email address')); exit(); }
				if (!$post['password1']) { echo json_encode(array('html' => true, 'error' => 'Empty password')); exit(); }
				if ($post['password1'] and !$post['password2']) { echo json_encode(array('html' => true, 'error' => 'Empty password 2')); exit(); }

				// Input validation
				if (($post['password1'] and $post['password2']) and strcmp($post['password1'], $post['password2']) != 0) { echo json_encode(array('html' => true, 'error' => 'Password mismatch')); exit(); }
				if (!validator::valid_email($post['email'])) { echo json_encode(array('html' => true, 'error' => 'Invalid email address')); exit(); }
				if ($valid = validator::valid_username($post['username']) and $valid !== true) { echo json_encode(array('html' => true, 'error' => $valid)); exit(); }
				if ($post['password1'] and $valid = validator::valid_password($post['password1']) and $valid !== true) { echo json_encode(array('html' => true, 'error' => $valid)); exit(); }
				if ($post['password1'] and $post['password1'] == $post['username']) { echo json_encode(array('html' => true, 'error' => 'Password is invalid')); exit(); }

				$user = new pip_user();
				$user->fname = $post['fname'];
				$user->lname = $post['lname'];
				$user->subtype = 'admin';
				$user->username = $post['username'];
				$user->email = $post['email'];
				$user->language = 'en';
				$user->admin = 'yes';
				$user->password = $user->encrypt_password($post['password1']);
				if ($user->save()) echo json_encode(array('html' => 'Admin created', 'error' => ''));
				else echo json_encode(array('html' => true, 'error' => 'Admin creation error'));

				break;
			default:
				echo json_encode(array('html' => true, 'error' => 'Invalid AJAX action'));
				break;
		}

		exit();
	}
}