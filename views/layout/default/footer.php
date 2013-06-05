<?php
/**
 * Render the footer
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package default view
 * @since 1.0
 *
 */


function render_footer(&$view, $debug = null) {

	if (isset($view->custom_js) and is_array($view->custom_js) and count($view->custom_js) > 0) {
		$custom_js = '';
		foreach ($view->custom_js as $file) {
			$custom_js .= "<script src=\"$file\"></script>\n";
		}
	} else {
		$custom_js = '';
	}

	$js_lib = $view->use_jquery? '<script src="' . $view->assets_dir . 'js/alertbox/jquery.js"></script>' : '<script src="' . $view->assets_dir . 'js/alertbox/mootools.js"></script>';

	$js_alert = '';
	if ($alert = $view->alert()) {
		$js_alert .= "<!-- Alerts\n================================================== -->\n\n<script>\n";
		$js_alert .= $alert;
		$js_alert .= "</script>\n";
	}

	if (isset($debug) and $debug){
		$debug = "	<div class=\"debug\">\n		<h1>debug backtrace</h1><br><hr>\n$debug\n	</div>";
	}

	return <<<HTML

{$view->runtime()}
$debug

	<!-- Footer
	================================================== -->

	<footer>
		<!-- <p>Designed and built with all the love in the world <a href="http://www.phpiphany.com/" class="footer" target="_blank">phpiphany</a></p> -->
		<p>$view->copyright</p>
	</footer>

</div><!-- /container -->


<!-- Le javascript
================================================== -->

<!-- Placed at the end of the document so the pages load faster -->
$js_lib
$custom_js

$js_alert


<!-- Analytics
================================================== -->

</body>
</html>

HTML;

}
