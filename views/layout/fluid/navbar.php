<?php
/**
 * Render the topbar (always on top)
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package fluid view
 * @since 1.0
 *
 * @param object $view Reference to the view object
 * @return string Formatted navbar HTML
 */


function render_navbar(&$view) {

	global $config;
	$login = $search = '';
	if ($view->navbar_login){
		if (isset($_SESSION['user_guid']) and $_SESSION['user_guid']){
			$token = generate_token(true);
			$text = '<div class="login-text">Logged in as <strong>' . $_SESSION['user_name'] . '</strong>';
			$text .= " | <a href=\"{$config->site_url}authenticator/logout?$token\">Logout</a></div>";
		} else {
			$token = generate_token(false, true);
			$text = $view->load('input/login', array('assets_dir' => $view->assets_dir, 'token' => $token));
		}

		$login =<<<HTML

				<ul class="nav pull-right">
					<li class="divider-vertical"></li>
					$text
				</ul>

HTML;
	}
	if ($view->navbar_search){
		$search =<<<HTML

				<form class="navbar-search pull-left" action="{$config->site_url}search/db">
					<input name="q" type="text" class="search-query span3" placeholder="Search">
				</form>
HTML;
		}

	// Get the current menu links
	$links = '';
	$view->navbar_menu = (isset($_SESSION['pip_navbar_menu']) and $_SESSION['pip_navbar_menu'])? $_SESSION['pip_navbar_menu'] : $view->navbar_menu;
	if ($view->navbar_menu === false) $links = '';
	elseif (!$view->navbar_menu or !is_array($view->navbar_menu) or count($view->navbar_menu) < 1) {
		$links =<<<HTML
					<li class="active"><a href="#">Home</a></li>
					<li><a href="#about">About</a></li>
					<li><a href="#contact">Contact</a></li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle">Dropdown <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li><a href="#">Action</a></li>
							<li><a href="#">Another action</a></li>
							<li><a href="#">Something else here <b class="caret-right"></b></a>
								<ul>
									<li><a href="#submenu1">Unlimited levels of</a></li>
									<li><a href="#submenu2">Clickable sub-menus</a></li>
								</ul>
							</li>
							<li><a href="#">Separated link</a></li>
						</ul>
					</li>
HTML;
	} else {
		foreach ($view->navbar_menu as $resource) {
			if (!isset($resource['type']) or $resource['type'] != 'dropdown'){
				$links .= '					<li';
				$links .= $resource['active']? ' class="active">' : '>';
				$title = (isset($resource['title']) and $resource['title'])? ' title="' . $resource['title'] . '"' : '';
				$links .= '<a href="' . $resource['url'] . '"' . $title . '>' . $resource['name'] .'</a></li>';
				$links .= "\n";
			} else {
				$links .= '					<li class="';
				$links .= $resource['active']? 'active dropdown">' : 'dropdown">';
				$links .= "\n						";
				$url = (isset($resource['url']) and $resource['url'])? $resource['url'] : '#';
				$links .= '<a href="' . $url . '" class="dropdown-toggle">' . $resource['name'] . ' <b class="caret"></b></a>';
				$links .= "\n						<ul class=\"dropdown-menu\">\n";
				if (is_array($resource['submenu'])){
					foreach ($resource['submenu'] as $submenu){
						$links .= '							<li';
						$links .= $submenu['active']? ' class="active">' : '>';
						$title = (isset($submenu['title']) and $submenu['title'])? ' title="' . $submenu['title'] . '"' : '';
						$links .= '<a href="' . $submenu['url'] . '"' . $title . '>' . $submenu['name'] .'</a></li>';
						$links .= "\n";
					}
				}
				$links .= "						</ul>\n					</li>\n";
			}
		}
		$links = substr($links, 0, -1);
	}


	$navbar = <<<HTML

<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container-fluid">
			<a class="brand" style="padding: 7px 10px 0 20px" href="$config->site_url"><img src="{$config->site_url}images/logo-small.png" title="$config->site_name home"></a>
			<div class="nav-collapse">
				<ul id="nav" class="nav">
$links
				</ul>
				$search
				$login
			</div><!--/.nav-collapse -->
		</div>
	</div>
</div>

HTML;

	return $navbar;
}
