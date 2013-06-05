<?php
/**
 * Custom methods to catch all notices, warnings and all fatal errors
 * This file also includes a custom exception class/handler
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

global $config;

if ($config->debug) {
	error_reporting(E_ALL);
} else {
	error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR);
	// Should be changed to 0 (display no errors) in production environment
	//error_reporting(0);
}

// Register our custom handlers
ini_set('display_errors', 0);
//ini_set('html_errors', true);
set_error_handler("error_handler");
set_exception_handler('exception_handler');
register_shutdown_function('last_call');


/**
 * Handles all errors, warnings and notices and prints an error according to the defined views
 * Returns false if this is not a critical error (debugging is handled by a shutdown fucntion)
 *
 * @param int $error_num Error number
 * @param string $error_msg Error message
 * @param string $error_file File where the error occured
 * @param int $error_line Line the file where the error occured
 * @param array $error_context Contain an array of every variable that existed in the scope the error was triggered in (optional)
 * @param bool $exception Is this an exception? Needed since exceptions usually have error code of zero
 * @return mixed Generates HTML with an error
 */
function error_handler($error_num, $error_msg, $error_file, $error_line, $error_context = null, $exception = false){

	global $config, $view;

	$error_num = ($error_num == 666)? 666 : $error_num & error_reporting();
	if ($error_num == 0 and !$exception) return;


	if ($view and $config){
		$style = $view->map_error_styles($error_num);

		// Do not show the message once again if it is a custom text
		if (isset($style['user_died']) and $style['user_died'] == true) $msg = htmlspecialchars_decode('		' . $style['msg']) . ": in <strong>$error_file</strong> script terminator <strong>exit()</strong> or <strong>die()</strong> was called\n";
		else $msg = htmlspecialchars_decode('		' . $style['msg']) . ": <em>$error_msg</em> in <strong>$error_file</strong> on line <strong>$error_line</strong>\n";
		if (function_exists('debug_backtrace')) {
			if ($error_num != E_ERROR) {
				$backtrace = debug_backtrace();
				array_shift($backtrace);
				$msg .= htmlspecialchars_decode($style['table']);
				foreach ($backtrace as $i => $log) {
					$d_class = isset($log['class']) ? $log['class'] : '';
					$d_type = isset($log['type']) ? $log['type'] : '';
					$d_func = isset($log['function']) ? $log['function'] : '';

					$msg .= "			<tr><td style='width: 20px'>[$i]</td><td> in function <strong>{$d_class}{$d_type}{$d_func}</strong>";
					if (isset($log['file'])) $msg .= " in <strong>{$log['file']}</strong>";
					if (isset($log['line'])) $msg .= " on line <strong>{$log['line']}</strong>";
					$msg .= "</td></tr>\n";
				}
				$msg .= "		</table>";
				// Write debugging information into a temp session variable
				if (isset($config->debug) and $config->debug) {
					if (!isset($_SESSION['pip_temp_pad'])) $_SESSION['pip_temp_pad'] = '';
					$_SESSION['pip_temp_pad'] .= $msg;
				}
			}
		}

		// Print out on the screen only the critical errors and exceptions. Others will be visible when debug mode is on
		if ($style['critical']) {
			// Clean the buffer if it had any info
			if (ob_get_length()) ob_end_clean();

			$options = "$msg				<br /><br />
				<div class=\"alert-actions\">
					" . htmlspecialchars_decode($style['buttons']) . "
				</div>";
			$content = $view->render_error('', $options);
			$view->render_page($content);

			// Destroy any alert messages as they are irrelevant in a FATAL "super error"
			if (isset($_SESSION['alert_msg']) and $_SESSION['alert_msg']) $_SESSION['alert_msg'] = false;

			// Because this is a critical error, we do not want the application to go any further
			// At this point we display the contents of an output buffer and quit
			if ($config->debug) echo $view->render_footer();
			echo ob_get_clean();
			ob_get_level() and ob_clean();
			exit();
		} elseif (isset($style['user_died']) and $style['user_died'] == true) {
			if (ob_get_length()) ob_end_clean();
			$content = $view->render_error('<p><strong>' . $error_msg . '</strong></p>');
			$view->render_page($content);
			// Destroy any alert messages as they are irrelevant in a FATAL "super error"
			if (isset($_SESSION['alert_msg']) and $_SESSION['alert_msg']) $_SESSION['alert_msg'] = false;
			if ($config->debug) echo $view->render_footer();
			$_SESSION['pay4_temp_pad'] = '';
		} elseif ($exception and $error_num == 0) {
			// Clean the buffer if it had any info
			ob_end_clean();

			$content = $view->render_exception($error_msg, '', $error_file, $error_line);
			$view->render_page($content);
			exit();
		}
	}
	return;

}

/**
 * Error handler primarily used for capture FATAL errors
 * Because this function is called when the script is fully loaded/parsed,
 * we need to output the internal buffer and clean/erase it
 *
 */
function last_call() {

	global $config, $view;

	// If this is a system error
	if (is_array($e = error_get_last())) {
		$code = isset($e['type']) ? $e['type'] : 0;
		$msg = isset($e['message']) ? $e['message'] : '';
		$file = isset($e['file']) ? $e['file'] : '';
		$line = isset($e['line']) ? $e['line'] : '';
		if ($code > 0) {
			error_handler($code, $msg, $file, $line);
		}
	// Otherwise check if this is a user-terminated exit
	// The only way to determine this is to accept error messages less than 256 characters long
	} elseif (ob_get_level() == 2 and (!isset($_SESSION['pay4_temp_pad']) or !$_SESSION['pay4_temp_pad']) and $msg = ob_get_contents() and strlen($msg) > 0 and strlen($msg) < 256) {
		echo 'here3<br>';
		ob_end_flush();
		error_handler(666, $msg, $_SERVER['SCRIPT_FILENAME'] . '?' . $_SERVER['REDIRECT_QUERY_STRING'], null);
		exit();
	}

	// Draw the debug backtrace console amalgamating php tracer as well as DB debugger
	if ($config->debug and !$config->direct_output) {
		global $db;
		if ($db->connected()) $db->show_debug_console();
		echo $view->render_footer();
	}

	echo ob_get_clean();
	//ob_get_level() and ob_clean();
	//unset($_SESSION['pip_temp_pad']);
	$_SESSION['pip_temp_pad'] = '';

	// Because on each redirect, this function is called (ob_flush), we need to keep the alert messages in session until it is indeed the last page
	// Therefore, when the forward() function is invoked, it sets this flag to 1. Then one extra page render happens, so we increment this flag by one
	// The last page draw should have this value at 2, which indicates that we can show the alert message(s) and flush these session variables
	if (isset($_SESSION['ob_counter'])){
		if ($_SESSION['ob_counter'] == 1) $_SESSION['ob_counter'] = 2;
		elseif ($_SESSION['ob_counter'] == 2) {
			$_SESSION['alert_msg'] = '';
			unset($_SESSION['ob_counter']);
		}
	}

	// Reset back to false
	if ($config->direct_output) $config->direct_output = false;
}


/**
 * Generic error exception that can be thrown by itself
 * throw new error('error message');
 * or with a try-catch block
 * try {
 *    throw new error('error message');
 * } catch (error e) {
 *    echo $e;
 * }
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @package system
 * @subpackage error_handler
 */
final class error extends Exception {
	// Redefine the exception so message isn't optional
	public function __construct($message, $code = 0) {
		// Make sure everything is assigned properly
		parent::__construct($message, $code);
	}

	// Redirect any calls to display this exception to error_handler
	public function __toString() {
		error_handler($this->code, $this->message, $this->file, $this->line, null, true);
	}

}

/**
 * Exception handler
 * Calls 'error' class exception if throw was done via throw new error()
 * Intercepts any calls done via system Exception and forwards to error_handler
 *
 * @param object $e The error object
 */
function exception_handler($e){
	// Redirect all calls to our exception class
	if (!$e instanceof error){
		error_handler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), null, true);
	} else {
		echo $e;
	}
}

