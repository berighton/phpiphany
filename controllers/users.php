<?php
/**
 * User Controller to manage user creations, edits and views
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
 
class users extends controller {

	public function __construct(){
		parent::__construct();
		parent::set_menu('users');
	}

	/**
	 * List the current users
	 *
	 */
	public function index(){
		admin_only();
		global $view;
		$view->title = 'All Users';
		$view->content = "<h1>$view->title</h1><br>\n" .
				"<span class='right' style='margin: -50px 20px'><a href='{$view->assets_dir}users/create' title='New User' class='btn'><i class='icon-file'></i> Create New User</a></span>\n" .
				get_entities(array('type' => 'user'));

		$view->render_page();
		exit();
	}

	/**
	 * Renders a full user view
	 *
	 */
	public function view(){

		// For now, only admins can view other users
		admin_only();

		global $view, $config;

		if (!$config->input->parms) pip_error('Cannot continue! No user ID specified.');
		else $guid = $config->input->parms;
		$guid = is_array($guid)? $guid[0] : $guid;

		$user = orm::get($guid);
		if ($user){
			$view->title = 'User Details: ' . $user->name;
			$view->content = $view->load('entities/full', array('entity' => $user, 'assets_dir' => $view->assets_dir));
			$view->render_page();
		} else pip_error('User GUID "' . $guid . '" does not exist in the database!', '');

		exit();
	}

	public function ajax() {
		global $config;

		// Make sure we do not render the page
		$config->direct_output = true;

		action_gatekeeper(true);

		if (!$config->input->parms) { echo json_encode(array('html' => true, 'error' => 'No parameters given')); exit; }
		else $page = $config->input->parms;

		$page = is_array($page)? $page : array($page);

		// Sanity checking
		$post = $config->input->post;
		if (!$post['fname']) { echo json_encode(array('html' => true, 'error' => 'Empty first name')); exit(); }
		if (!$post['lname']) { echo json_encode(array('html' => true, 'error' => 'Empty last name')); exit(); }
		if (!$post['username']) { echo json_encode(array('html' => true, 'error' => 'Empty username')); exit(); }
		if (!$post['email']) { echo json_encode(array('html' => true, 'error' => 'Empty email address')); exit(); }
		if ($page[0] == 'new' and !$post['password1']) { echo json_encode(array('html' => true, 'error' => 'Empty password')); exit(); }
		if ($post['password1'] and !$post['password2']) { echo json_encode(array('html' => true, 'error' => 'Empty password 2')); exit(); }

		// Input validation
		if (($post['password1'] and $post['password2']) and strcmp($post['password1'], $post['password2']) != 0) { echo json_encode(array('html' => true, 'error' => 'Passwords don\'t match')); exit(); }
		if (!validator::valid_email($post['email'])) { echo json_encode(array('html' => true, 'error' => 'Invalid email address')); exit(); }
		if ($valid = validator::valid_username($post['username']) and $valid !== true) { echo json_encode(array('html' => true, 'error' => $valid)); exit(); }
		if ($post['password1'] and $valid = validator::valid_password($post['password1']) and $valid !== true) { echo json_encode(array('html' => true, 'error' => $valid)); exit(); }
		if ($post['password1'] and $post['password1'] == $post['username']) { echo json_encode(array('html' => true, 'error' => 'Password is invalid')); exit(); }

		global $db;
		// Check for duplicate entries
		if (($page[0] == 'new' and $user = get_user(array('username' => $post['username'])))
			or ($page[0] == 'update' and $user = get_user(array('username' => $post['username'])) and $user->guid != $page[1])) {
				$debug = $config->debug ? $db->show_debug_console(true) : '';
				echo json_encode(array('html' => true, 'error' => 'Username exists', 'debug' => $debug));
				exit();
		} elseif (($page[0] == 'new' and $user = get_user(array('email' => $post['email'])))
			or ($page[0] == 'update' and $user = get_user(array('email' => $post['email'])) and $user->guid != $page[1])) {
				$debug = $config->debug ? $db->show_debug_console(true) : '';
				echo json_encode(array('html' => true, 'error' => 'Email address exists', 'debug' => $debug));
				exit();
		}

		// AJAX page represents user action: 'new', 'edit' or 'delete'
		switch ($page[0]) {
			case 'new':
			case 'update':
				$user = ($page[0] == 'new')? new pip_user() : orm::get($page[1]);
				$user->fname = $post['fname'];
				$user->lname = $post['lname'];
				if ($page[0] == 'new') $user->subtype = $post['type'];
				$user->username = $post['username'];
				$user->email = $post['email'];
				$user->language = $post['language'];
				$user->admin = $post['admin']? $post['admin'] : 'no';
				if ($page[0] == 'new' or $post['password1']){
					$user->password = $user->encrypt_password($post['password1']);
				}
				$action = ($page[0] == 'new')? 'created' : 'updated';
				if ($user->save()) echo json_encode(array('html' => 'User ' . $action, 'error' => ''));
				else {
					$debug = $config->debug ? $db->show_debug_console(true) : '';
					echo json_encode(array('html' => true, 'error' => 'User was not ' . $action, 'debug' => $debug));
				}
				break;
			default:
				echo json_encode(array('html' => true, 'error' => 'Invalid AJAX action'));
				break;
		}
	}

	/**
	 * Generates a view to create user
	 *
	 */
	public function create(){
		// This page is for authorized personnel only (admins)
		//admin_only();

		global $view;

		$view->title = 'Create User';
		html::text(array('name' => 'fname', 'label' => 'first name', 'placeholder' => 'Enter user\'s first name'));
		html::text(array('name' => 'lname', 'label' => 'last name', 'placeholder' => 'Enter user\'s last name'));
		html::combo(array('name' => 'type', 'label' => 'user type', 'options' => get_subtypes('user'), 'size' => 3, 'firstempty' => false));
		html::text(array('name' => 'username', 'placeholder' => 'Enter the desired username'));
		html::text(array('name' => 'email', 'type' => 'email', 'label' => 'email', 'placeholder' => 'Enter user\'s email address'));
		html::text(array('name' => 'password1', 'type' => 'password', 'label' => 'password', 'placeholder' => 'Enter preferred password',
												'help' => 'Password should be min 6 characters, must not be the same as username
												<br>AND must contain letters <u>and</u> at least one number or a special character'));
		html::text(array('name' => 'password2', 'type' => 'password', 'label' => 'password again', 'placeholder' => 'Preferred password one more time'));
		html::text(array('name' => 'language', 'value' => 'en', 'size' => 1, 'maxlength' => 2));
		if (is_admin()) html::combo(array('name' => 'admin', 'label' => 'admin user?', 'options' => array('yes', 'no'), 'size' => 1, 'firstempty' => false, 'selected' => 'no'));

		$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'users/ajax/new', 'name' => 'New User Information'));
		$view->render_page();

		exit();
	}

	/**
	 * Generates a view to update user
	 *
	 */
	public function update(){
		// This page is for owners only (or admin)
		owner_only();

		global $view, $config;
		$guid = '';
		if (!$config->input->parms) pip_error('Update failed! No user ID specified. Cannot continue');
		else $guid = $config->input->parms;
		$guid = is_array($guid)? $guid[0] : $guid;

		// Continue only if this guid exists
		if ($user = orm::get($guid, true)){
			$view->title = 'Update User "' . $user->name . '"';
			html::text(array('name' => 'fname', 'label' => 'first name', 'placeholder' => 'Enter user\'s first name', 'value' => $user->fname));
			html::text(array('name' => 'lname', 'label' => 'last name', 'placeholder' => 'Enter user\'s last name', 'value' => $user->lname));
			html::text(array('name' => 'username', 'placeholder' => 'Enter the desired username', 'value' => $user->username));
			html::text(array('name' => 'email', 'type' => 'email', 'label' => 'email', 'placeholder' => 'Enter user\'s email address', 'value' => $user->email));
			html::text(array('name' => 'password1', 'type' => 'password', 'label' => 'password', 'placeholder' => 'Enter preferred password',
													'help' => 'Enter new user password to change. <br>Otherwise leave blank for no change'));
			html::text(array('name' => 'password2', 'type' => 'password', 'label' => 'password again', 'placeholder' => 'Preferred password one more time'));
			html::text(array('name' => 'language', 'size' => 1, 'maxlength' => 2, 'value' => $user->language));
			html::combo(array('name' => 'admin', 'label' => 'admin user?', 'options' => array('yes', 'no'), 'size' => 1, 'firstempty' => false, 'selected' => $user->admin));

			$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'users/ajax/update/' . $user->guid, 'name' => 'Current User Information',
										'delete' => $view->assets_dir . 'users/delete/' . $user->guid . '?' . generate_token(true)));
			
			$view->render_page();
		} else pip_error('User GUID specified does not exist in the database');

		exit();
	}

	/**
	 * Delete user action
	 *
	 */
	public function delete(){
		// This page is for authorized personnel only (admins)
		admin_only();
		action_gatekeeper();

		global $config;
		$guid = '';
		if (!$config->input->parms) pip_error('Delete failed! No user ID specified. Cannot continue');
		else $guid = $config->input->parms;
		$guid = is_array($guid)? $guid[0] : $guid;

		// Continue only if this guid exists
		if ($user = orm::get($guid)){
			if ($user->delete()) pip_success('User deleted successfully!');
			else pip_error('Unable to delete user. Check system logs');
		} else pip_error('User GUID specified does not exist in the database');

		exit();
	}

}
