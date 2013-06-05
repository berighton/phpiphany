<?php
/**
 * Authentication Controller used for managing logons and user sessions
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
 
class authenticator extends controller {

	public function __construct(){
		parent::__construct();
	}

	public function index(){
		if (isset($_SESSION['user_guid']) and $_SESSION['user_guid']) {
			forward();
		}

		global $view;

		$view->title = 'Authenticator';
		$view->navbar_login = false;
		$view->custom_css[] = $view->assets_dir . 'css/login.css';
		$view->content = html::login(array('navbar' => false, 'assets_dir' => $view->assets_dir, 'token' => generate_token(false, true)));
		$view->render_page();
		exit();
	}

	/**
	 * Login action controller.
	 * Checks the validity of supplied credentials and sets up a session if authenticated
	 *
	 */
	public function login(){
		action_gatekeeper();
		global $config;
		if (!$config->input->post) pip_error('No parameters given');

		if ($user = get_user(array('username' => $_POST['username']), true) and $user->check_password($_POST['password'])){
			$_SESSION['user_guid'] = $user->guid;
			$_SESSION['user_name'] = $user->name;
			$_SESSION['user_email'] = $user->email;
			$_SESSION['user_admin'] = $user->admin == 'yes'? true : false;
			/*
			 * 	// if remember me checked, set cookie with token and store token on user
			 	if (($persistent)) {
			 		$code = (md5($user->name . $user->username . time() . rand()));
			 		$_SESSION['code'] = $code;
			 		$user->code = md5($code);
			 		setcookie("pipperm", $code, (time()+(86400 * 30)),"/");
			 	}
			 */
			$user->prev_last_login = $user->last_login;
			$user->last_login = 'NOW()';
			$user->save();
			$_SESSION['alert_msg'] = '';
			forward();
		} else {
			pip_error('The username/password combination you entered is INCORRECT. Please try again');
		}
		exit();
	}

	/**
	 * Internal method for system login calls
	 * Usually used when the user was just created and we need to log them in
	 *
	 * @static
	 * @param pip_user $user The user object
	 * @return bool Returns true on successful login, false otherwise
	 */
	static function ilogin(pip_user $user){
		if (!$user) return false;
		// Validate user
		$real_user = orm::get($user->guid);
		if ($user->email == $real_user->email and $user->password == $real_user->password and $user->salt == $real_user->salt){
			$_SESSION['user_guid'] = $user->guid;
			$_SESSION['user_name'] = $user->name;
			$_SESSION['user_email'] = $user->email;
			$_SESSION['user_admin'] = $user->admin == 'yes'? true : false;

			$user->prev_last_login = $user->last_login;
			$user->last_login = 'NOW()';
			$user->save();

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Logout action controller.
	 * Clears the user session
	 *
	 */
	public function logout(){
		action_gatekeeper();
		if (is_loggedin()){
			unset($_SESSION['user_guid']);
			unset($_SESSION['pip_temp_pad']);
			unset($_SESSION['pip_navbar_menu']);
			global $session;
			$session->stop();
		}
		forward();
		exit();
	}
}
