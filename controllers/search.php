<?php
/**
 * Search Controller to find entities inside the database by full or partial string
 * AJAX implementation shows results as the user starts typing
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
 
class search extends controller {

	public function __construct(){
		parent::__construct();
	}

	/**
	 * Default search box
	 *
	 * @param string $q Optional search query
	 */
	public function index($q = ''){
		global $view, $config;
		//array_push($view->custom_css, $view->assets_dir . 'css/search.css');
		$view->title = "Search $config->site_name";
		$view->navbar_search = false;
		$q = $q? '"' . $q . '"' : '""';
		$url = '"' . $config->site_url . 'search/db?"';
		$view->content = "<h1>$view->title</h1><br>\n" . '
			<link type="text/css" rel="stylesheet" href="' . $config->site_url . 'css/search.css">
			<script type="text/javascript" src="' . $config->site_url . 'js/search.js"></script>
			<script>search(' . $q . ', ' . $url . ');</script>

			<form class="search" action="' . $config->site_url . 'search/db" method="get">
				<input id="q" name="q" type="text" autocomplete="off" placeholder="Search for anything: users, groups or objects" ' .
				'onkeyup=\'search(this.value, ' . $url . ')\' value=' . $q . '>
				<input class="btn" type="submit" value="Search">
			</form>

			<div id="show_results"></div>';

		$view->render_page();
		exit();
	}

	/**
	 * The main action for this controller
	 * Relies on the existing function get_entities()
	 *
	 */
	public function db(){
		global $config;

		// Make sure we do not render the page
		if (isset($_GET['ajax'])) $config->direct_output = true;
		else {
			$this->index($_GET['q']);
			exit();
		}

		$min_chars = 3; //minimum number of characters

		// Check for an empty string and display a message.
		if (!isset($_GET['q']) or $_GET['q'] == "") {
			echo  '<div id="counter">Eg. try &acute;john&acute; or &acute;school&acute; without quotes.</div>';
			// Minimum 3 characters condition
		} else {
			if (strlen($_GET['q']) < $min_chars) {
				echo '<div id="counter">Please input at least ' . $min_chars . ' characters</div>';
				// No spaces or special characters in the first few letters
			} else {
				if (preg_replace('/[a-zA-Z0-9]/', '', substr($_GET['q'], 0, $min_chars))) {
					echo '<div id="counter">Please use letters or numbers in first ' . $min_chars . ' characters</div>';
				} else {
					// Very simple call that would find any entity inside the database matching the given criteria
					// If paginate not set to false explicitly, this function will generate pagination automatically
					echo get_entities(array('search' => true, 'public' => true, 'search_query' => $_GET['q'])); //, 'search_by_email' => true));
				}
			}
		}
	}

	/**
	 * Lookup functionality to search for a specific entity by the username and validate username according to validator class
	 * Can be used when creating a user to check if selected username already exists
	 */
	public function username(){
		global $config;

		// Make sure we do not render the page
		if (isset($_GET['ajax'])) $config->direct_output = true;
		else {
			$this->index($_GET['q']);
			exit();
		}

		$min_chars = 6; //minimum number of characters

		// Act only upon non-empty query string
		if (isset($_GET['q']) and $_GET['q']) {
			$username = mysql_real_escape_string($_GET['q']);
			if (strlen($username) >= $min_chars) {
				if ($valid = validator::valid_username($username) and $valid !== true) {
					echo '<strong class="red">' . $valid . '</strong>';
				} else {
					// Do a search for user by its username
					if (get_user(array('username' => $username), true)) {
						echo '<strong class="red">This username exists</strong>';
					} else {
						echo '<strong class="green">This is a valid username</strong>';
					}
				}
			} else {
				echo '<strong class="red">Username too short</strong>';
			}
		}
	}

	/**
	 * Lookup functionality to search for a specific entity by the email and validate email according to validator class
	 * Can be used when creating a user to check if selected email already exists
	 */
	public function email(){
		global $config;

		// Make sure we do not render the page
		if (isset($_GET['ajax'])) $config->direct_output = true;
		else {
			$this->index($_GET['q']);
			exit();
		}

		$min_chars = 8; //minimum number of characters

		// Act only upon non-empty query string
		if (isset($_GET['q']) and $_GET['q']) {
			$email = mysql_real_escape_string($_GET['q']);
			if (strlen($email) >= $min_chars) {
				if (!validator::valid_email($email)) {
					echo '<strong class="red">Email address is invalid</strong>';
				} else {
					// Do a search for user by its email
					if (get_user(array('email' => $email), true)) {
						echo '<strong class="red">This email address exists</strong>';
					} else {
						echo '<strong class="green">This is a valid email address</strong>';
					}
				}
			} else {
				echo '<strong class="red">Email address too short</strong>';
			}
		}
	}

	/**
	 * This controller does not implement AJAX method, so we have to handle the requests by a simple redirect
	 */
	public function ajax() {
		forward();
		exit();
	}

}
