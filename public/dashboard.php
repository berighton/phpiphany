<?php
/**
 * Dashboard page. Renders the content for authenticated users, otherwise redirects to an index page
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package public
 * @since 1.0
 *
 */


// Determine if we need to route this page further or display the actual content
if (!$router->fallback){
	$router->route();
} else {
	// Default landing page HTML
	global $view, $config;
	$content = 'Default index page is supposed to load at this point';
	$content .= '<br /><strong>Paramaters: </strong><br /><pre>' . print_r($config->input, true) . '</pre>';
	$view->render_page($content);
}

