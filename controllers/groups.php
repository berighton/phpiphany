<?php
/**
 * Group Controller to manage group creation, edits and views
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
 
class groups extends controller {

	public function __construct(){
		parent::__construct();
	}

	/**
	 * List the current groups
	 *
	 */
	public function index(){
		loggedin_only();
		$user = get_loggedin_user();

		global $view;
		$view->title = $user->admin? 'All Groups' : 'Your Groups';
		$view->content = "<h1>$view->title</h1><br>\n" .
				"<span class='right' style='margin: -50px 20px'><a href='{$view->assets_dir}groups/create' title='New Group' class='btn'><i class='icon-file'></i> Create New Group</a></span>\n" .
				get_entities(array('type' => 'group', 'limit' => 1));

		$view->render_page();
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
		if (!$post['name']) { echo json_encode(array('html' => true, 'error' => 'Empty group name')); exit(); }

		// AJAX page represents group action: 'new', 'edit' or 'delete'
		switch ($page[0]) {
			case 'new':
			case 'update':
				$group = ($page[0] == 'new')? new pip_group() : orm::get($page[1]);
				$group->name = $post['name'];
				$group->description = $post['description'];
				$group->access = $post['access'];
				$group->members = $post['members'];

				$action = ($page[0] == 'new')? 'created' : 'updated';
				if ($group->save()) echo json_encode(array('html' => 'Group ' . $action, 'error' => ''));
				else {
					global $db;
					$debug = $config->debug ? $db->show_debug_console(true) : '';
					echo json_encode(array('html' => true, 'error' => 'Group was not ' . $action, 'debug' => $debug));
				}
				break;
			default:
				echo json_encode(array('html' => true, 'error' => 'Invalid AJAX action'));
				break;
		}
	}

	/**
	 * Renders a full group view
	 *
	 */
	public function view(){

		global $view, $config;

		if (!$config->input->parms) pip_error('Cannot continue! No group ID specified.');
		else $guid = $config->input->parms;
		$guid = is_array($guid)? $guid[0] : $guid;

		$group = orm::get($guid);
		if ($group){
			// Continue only if the logged in user has access to this group or the group is public
			if (can_access($guid) or $group->access == 'public'){
				$view->title = 'Group Details: ' . $group->name;
				$view->content = $view->load('entities/full', array('entity' => $group, 'assets_dir' => $view->assets_dir));
				$view->render_page();
			} else pip_error('You do not have permission to view this group');
		} else pip_error('Group GUID "' . $guid . '" does not exist in the database!', '');

		exit();
	}

	/**
	 * Generates a view to create a group
	 *
	 */
	public function create(){

		global $view;

		$view->title = 'Create a Group';

		html::text(array('name' => 'name', 'label' => 'group name', 'placeholder' => 'Enter group\'s name'));
		html::combo(array('name' => 'type', 'label' => 'group type', 'options' => get_subtypes('group'), 'size' => 3, 'firstempty' => false));
		html::longtext(array('name' => 'description', 'label' => 'short description', 'placeholder' => 'Enter group\'s description', 'rte' => true));
		// If admin is logged in, show the members dropdown
		if (is_admin()) {
			// get the list of all the users in the system
			$users = get_entities(array('type' => 'user', 'pagination' => false));
			if ($users and is_array($users) and count($users) > 0){
				$options = array();
				foreach ($users as $user){
					if ($user->guid != get_loggedin_userid()) $options[$user->guid] = $user->name;
				}
				html::dragndrop(array('name' => 'members[]', 'label' => 'Members', 'options' => $options,
									'help' => 'If none selected, <strong>nobody</strong> will have access to this group expect you'));
			}
		}
		html::combo(array('name' => 'access', 'label' => 'group access', 'options' => array('Private', 'Public'), 'size' => 2, 'firstempty' => false, 'selected' => 'Private'));
		html::upload(array('label' => 'upload an avatar', 'size' => 5, 'filesize' => '2MB', 'path' => 'avatars/group', 'filetype' => 'image', 'multi' => false));

		$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'groups/ajax/new', 'name' => 'New Group Information'));
		$view->render_page();

		exit();
	}

	/**
	 * Generates a view to update the group
	 *
	 */
	public function update(){

		global $view, $config;

		if (!$config->input->parms) pip_error('Update failed! No group ID specified. Cannot continue');
		else $guid = $config->input->parms;
		$guid = is_array($guid)? $guid[0] : $guid;

		// Edits can be done only by a logged in user
		loggedin_only();

		$group = orm::get($guid);
		if ($group){
			// Continue only if the logged in user has access to this group
			if (can_access($guid)){
				$view->title = 'Update a Group';

				html::text(array('name' => 'name', 'label' => 'group name', 'placeholder' => 'Enter group\'s name', 'value' => $group->name));
				html::longtext(array('name' => 'description', 'label' => 'short description', 'placeholder' => 'Enter group\'s description', 'rte' => false, 'value' => $group->description));

				// If admin is logged in, show the members dropdown
				if (is_admin()) {
					// get the list of all the users in the system
					$users = get_entities(array('type' => 'user', 'pagination' => false));
					if ($users and is_array($users) and count($users) > 0){
						$options = array();
						foreach ($users as $user){
							if ($user->guid != get_loggedin_userid()) $options[$user->guid] = $user->name;
						}
						html::dragndrop(array('name' => 'members[]', 'label' => 'Members', 'options' => $options, 'selected' => $group->members,
												'help' => 'If none selected, <strong>nobody</strong> will have access to this group expect you'));
					}
				}
				html::combo(array('name' => 'access', 'label' => 'group access', 'options' => array('Private', 'Public'), 'size' => 2, 'firstempty' => false, 'selected' => $group->access));
				html::upload(array('label' => 'upload an avatar', 'size' => 5, 'filesize' => '2MB', 'path' => 'avatars/group', 'filetype' => 'image', 'multi' => false));

				$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'groups/ajax/update/' . $group->guid, 'name' => 'Current Group Information',
											'delete' => $view->assets_dir . 'groups/delete/' . $group->guid . '?' . generate_token(true)));
				$view->render_page();
			} else pip_error('You do not have permission to edit this group');
		} else pip_error('Group GUID "' . $guid . '" does not exist in the database!', '');

		exit();
	}

	/**
	 * Delete group action
	 *
	 */
	public function delete(){
		// This page is for authorized personnel only (admins)
		admin_only();
		action_gatekeeper();

		global $config;
		$guid = '';
		if (!$config->input->parms) pip_error('Delete failed! No group ID specified. Cannot continue');
		else $guid = $config->input->parms;
		$guid = is_array($guid)? $guid[0] : $guid;

		// Continue only if this guid exists
		if ($group = orm::get($guid)){
			if ($group->delete()) pip_success('Group deleted successfully!');
			else pip_error('Unable to delete group. Check system logs');
		} else pip_error('Group GUID specified does not exist in the database');

		exit();
	}

}
