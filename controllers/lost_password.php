<?php
/**
 * Controller to deal with lost password issues
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
 
class lost_password extends controller {

	public function __construct(){
		parent::__construct();
	}

	/**
	 * Main method to render a lost password request form
	 */
	public function index(){
		global $view;

		$view->title = 'Lost Password';
		html::text(array('name' => 'unknown', 'label' => 'username or email', 'help' => 'Please enter your username or email (whichever you remember) and, <br>
						if found in our database, we will send you instructions on how to reset <br>your password to the email address associated with this user account'));

		$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'lost_password/ajax/make_request', 'name' => 'Password retrieval form',
											'btn' => 'Send Password Reset Link', 'resubmit' => false));
		$view->render_page();

		exit();
	}

	public function ajax() {
		global $config;

		// Make sure we do not render the page
		$config->direct_output = true;

		action_gatekeeper(true);
		if (!$post = $config->input->post or !$config->input->parms) {
			echo json_encode(array('html' => true, 'error' => 'Invalid AJAX action'));
			exit;
		} else {
			$page = $config->input->parms;
			$page = is_array($page)? $page : array($page);
		}

		// AJAX action handler (mainly for the reset password link)
		switch ($page[0]) {
			case 'make_request':
				if (!$post['unknown']) { echo json_encode(array('html' => true, 'error' => 'Name/email is required')); exit(); }
				$user = get_user(array('username' => $post['unknown'], 'email' => $post['unknown']), true, 'OR');
				if (!$user) { echo json_encode(array('html' => true, 'error' => 'Invalid username/email')); exit(); }
				else {
					$link = $this->generate_link($user);
					$body = "Dear $user->fname $user->lname,\n\nSomebody from the IP {$config->env->ip} has requested a password change on " . date('Y-m-d \a\t H:i:s');
					$body .= "\nIf it was you, please click on the link below to reset your password:\n$link\n\n";
					$body .= 'Otherwise, please disregard this email and accept our apologies for receiving this unsolicited email.';
					$body .= "\n\n\nThank you, \nphpiphany team";
					$headers = "From: admin@phpiphany.com";
					if (mail($user->email, 'Password reset information', $body, $headers)) {
						echo json_encode(array('html' => 'Reset information sent!', 'error' => ''));
					} else echo json_encode(array('html' => true, 'error' => 'Error sending email'));
					exit();
				}
				break;
			case 'do_reset':
				if (!$guid = $page[1]) { echo json_encode(array('html' => true, 'error' => 'Missing user GUID')); exit(); }
				if (!$user = orm::get($guid)) { echo json_encode(array('html' => true, 'error' => 'Invalid user GUID')); exit(); }
				if (!$post['password1']) { echo json_encode(array('html' => true, 'error' => 'Empty password')); exit(); }
				if ($post['password1'] and !$post['password2']) { echo json_encode(array('html' => true, 'error' => 'Empty password 2')); exit(); }
				if (($post['password1'] and $post['password2']) and strcmp($post['password1'], $post['password2']) != 0) { echo json_encode(array('html' => true, 'error' => 'Passwords don\'t match')); exit(); }
				// Do the reset
				$user->password = $user->encrypt_password($post['password1']);
				if ($user->save()) echo json_encode(array('html' => 'Password updated', 'error' => ''));
				else {
					global $db;
					$debug = $config->debug ? $db->show_debug_console(true) : '';
					echo json_encode(array('html' => true, 'error' => 'Password update error', 'debug' => $debug));
				}
				break;
			default:
				echo json_encode(array('html' => true, 'error' => 'Invalid AJAX action'));
				break;
		}
	}

	/**
	 * Method to generate a password reset link
	 * Simply appends the user guid to the query string (could potentially be a more complicated code injection)
	 *
	 * @param pip_user $user
	 * @return bool|string Returns false on error, link on success
	 */
	private function generate_link($user){
		if (!$user) return false;

		global $config;
		return $config->env->hostname . $config->site_url . 'lost_password/reset/' . $user->guid . '?' . generate_token(true);
	}

	/**
	 * Reset password form generator
	 * Provides an option for users to change their password rather than resetting it to a machine generated one
	 *
	 */
	public function reset(){
		global $config, $view;

		$view->title = 'Reset password form';

		action_gatekeeper();
		if (!$config->input->parms) {
			pip_error('Invalid parameters! Please click on (or copy & paste exactly) the link you received in your email.', '/lost_password');
			exit();
		} else {
			$guid = $config->input->parms;
			$guid = is_array($guid) ? $guid[0] : $guid;
		}

		array_push($view->custom_js, $view->assets_dir . 'js/rndpwd.js');
		html::$form_content = 'You have requested a password reset. On this page you have an option to change your password manually.<br>
									Please select a new password that is hard to guess. A good password would be at least 8 characters long, <br>
									have capitalized and non capitalized alpha-numeric characters as well as special characters like <em>!@#$%^&*()</em><br><br>
									<span style="color:green">For your convenience, you can use this randomly generated password if you want:
									<span id="random_pwd" style="font-weight: bold"></span></span><br><br><br>';
		// Run the random password generator script on DOM ready
		if ($view->use_jquery) $js = "\n\n							$(function() { $('#random_pwd').html(genpwd()); });\n";
		else $js = "\n\n							window.addEvent('domready', function() { $('random_pwd').set('html', genpwd()); });\n";
		html::text(array('name' => 'password1', 'type' => 'password', 'label' => 'password', 'placeholder' => 'Enter preferred password'));
		html::text(array('name' => 'password2', 'type' => 'password', 'label' => 'password again', 'placeholder' => 'Preferred password one more time'));
		$view->content .= html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'lost_password/ajax/do_reset/' . $guid,
									'name' => 'Reset your password using this form', 'btn' => 'Reset My Password', 'js' => $js, 'redirect' => $view->assets_dir . 'authenticator'));

		$view->render_page();
		exit();
	}

}
