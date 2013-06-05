<?php
/**
 * Main index file which acts as bootstrap to redirect the request to a controller (via route class)
 * And if the required page is indeed this page, shows the contents
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

require (dirname(dirname(__FILE__)) . '/init.php');

// First things first: check if the session is still valid
global $session, $config;
$session->expiration_monitor();

// Since this is our landing page, it is a good idea to start the script execution timer
// which will be visible at the bottom of the page in debug mode
$timer = new timer();

// Initiate our page dispatcher
$router = new dispatch();

// If the system is not installed, launch the installer
if (!$config->db_check(false)){
	$router->fallback = false;
	$router->controller = 'installer';
	$router->action = $router->action == 'ajax'? 'ajax' : 'index';
}

// Determine if we need to route this page further or display the actual content
if (!$router->fallback){
	//echo 'got here ';
	$router->route();
} else {
	// Default landing page HTML

	// Check to see if a user was logged in, redirect to dashboard or admin console
	if (is_loggedin()){
		if (is_admin()) forward('admin');
		//else forward('dashboard');
	}
	// Otherwise render a default index page that is publicly accessible
	global $view;
	$content = 'Default index page is loaded at this point';
	printr($config->input, 'Parameters');
	$view->render_page($content);
	exit;
}

