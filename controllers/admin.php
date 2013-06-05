<?php
/**
 * Admin Controller used for the admin console/dashboard
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
 
class admin extends controller {

	public function __construct(){
		// Restrict access to admins only
		admin_only();

		parent::__construct();

		global $view;
		// Set a custom navbar menu
		$view->load('navbar/admin', array('dir' => $view->assets_dir));
	}

	/**
	 * Admin main page
	 * System stats and widgets are fired off here
	 *
	 */
	public function index(){

		// Render an admin dashboard index page
		global $view, $config;
		$hash = base64_encode($config->env->hostname . $config->site_url . 'admin/ajax/stats?' . generate_token(true));
		$view->use_jquery = true;
		$view->title = 'Admin Control Panel';
		array_push($view->custom_css, 'http://fonts.googleapis.com/css?family=Cuprum', $view->assets_dir . 'css/admin/style.css',
					$view->assets_dir . 'css/admin/jquery-ui-1.8.16.custom.css', $view->assets_dir . 'css/admin/tables.css');
		array_push($view->custom_js, $view->assets_dir . 'js/admin/jquery-ui-1.8.16.custom.min.js',
					$view->assets_dir . 'js/admin/jquery.flot.min.js', $view->assets_dir . 'js/admin/forms.js',
					$view->assets_dir . 'js/admin/chosen.jquery.min.js', $view->assets_dir . 'js/admin/autoresize.jquery.min.js',
					$view->assets_dir . 'js/admin/slidernav-min.js', $view->assets_dir . 'js/admin/functions.php?hash=' . $hash);
		$this->set_menu('main');
		$view->content = $view->load('admin/index');
		$view->alert('Congratulations, you have loaded the admin custom controller', 'success');
		$view->render_page();
		exit();
	}

	/**
	 * Main form action handler
	 *
	 */
	public function ajax() {
		global $config;

		// Make sure we do not render the page
		$config->direct_output = true;

		action_gatekeeper(true);

		if (!$config->input->parms) { echo json_encode(array('html' => true, 'error' => 'No parameters given')); exit; }
		else $page = $config->input->parms;

		$page = is_array($page)? $page : array($page);

		// AJAX page represents admin action: 'stats', 'plugin' or 'wizard'
		switch ($page[0]) {
			case 'stats':
				exec("ps -eo pcpu | sort -s -n | awk '{total += $1} END {print total}'", $cpu);
				exec("ps -eo pmem | sort -s -n | awk '{total += $1} END {print total}'", $ram);
				// We will have to decrease the CPU reading by about 1.5 to account for multicore processors
				echo json_encode(array('cpu' => sprintf("%01.2f", $cpu[0] / 1.5), 'ram' => $ram[0]));
				break;
			case 'plugin':
				switch ($page[1]) {
					case 'new':
						global $db;
						$post = $config->input->post;
						if (!$post['group_guid']) unset($post['group_guid']);
						$post['date_installed'] = 'NOW()';
						$error = $db->insert($config->dbprefix . 'plugins', $post)? '' : 'Error installing plugin!';
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => true, 'error' => $error, 'debug' => $debug));
						break;
					case 'edit':
						if (!$page[2]) { echo json_encode(array('html' => true, 'error' => 'No plugin name given')); exit; }
						global $db;
						$post = $config->input->post;
						if (!$post['group_guid']) unset($post['group_guid']);
						$error = $db->update($config->dbprefix . 'plugins', $post, '`name` = ?', array($page[2]))? '' : 'Error updating plugin!';
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => true, 'error' => $error, 'debug' => $debug));
						break;
				}
				break;
			case 'version':
				// Filter out $ as a special character in the post variables
				$post = $config->input->post;
				$post['upgrade'] = trim(str_replace('$', '\$', $post['upgrade']));
				$post['downgrade'] = trim(str_replace('$', '\$', $post['downgrade']));

				switch ($page[1]) {
					case 'new':
						global $view;

						$class = $config->dbprefix . time();
						// Generate a template with supplied SQL queries embedded inside the class methods
						$template = $view->load('admin/migration', array('template' => true, 'class' => $class, 'upgrade' => $post['upgrade'], 'downgrade' => $post['downgrade']));
						$file = new pip_disk($config->versions_dir, false);
						if ($file->save($class . '.php', $template)) echo json_encode(array('html' => 'Saved successfully', 'error' => ''));
						else echo json_encode(array('html' => true, 'error' => 'Saving file failed'));

						break;
					case 'edit':
						if (!$page[2]) { echo json_encode(array('html' => true, 'error' => 'No version name given')); exit; }

						$path = $config->versions_dir . '/' . $post['name'] . '.php';
						if (is_file($path)){
							$contents = explode('SQL', file_get_contents($path));
							$contents['1'] = "\n\r" . $post['upgrade'] . "\n\r";
							$contents['3'] = "\n\r" . $post['downgrade'] . "\n\r";
							if (file_put_contents($path, implode('SQL', $contents))) echo json_encode(array('html' => 'Saved successfully', 'error' => ''));
							else echo json_encode(array('html' => true, 'error' => 'Saving file failed'));
						} else {
							echo json_encode(array('html' => true, 'error' => 'Version file read error')); exit;
						}
						break;
				}
				break;
			case 'export':
				/**
				 * Extract data from the system and save a specific file type with the ability to download
				 *
				 *
				 * If type or subtype was supplied, fetch pip entities and return only data pertaining to the matched results
				 *
				 * Examples. Let's say you have a group 'guests'. If you input this group's GUID only,
				 * the system will generate EVERYTHING belonging to this group. If you also specify a subtype
				 * ('users' for instance), only users (with their access records) will be exported.
				 * You can also pass in a GUID of a user and for subtype enter files
				 * This will fetch all the files for a particular user
				 *
				 * Another example is if you only specify subtype, let's say you need to extract only users from your system
				 *
				 */

				$post = $config->input->post;

				switch (strtolower($post['format'])) {
					case 'xml':
						// Prepare the folder and generate a file name
						$dir = '/uploads/export/xml/';
						$path = $config->env->root . $dir;
						dir_setup($dir);
						$ext = 'xml';
						$name = get_loggedin_user()->guid . '_' . $_SERVER['REQUEST_TIME'] . '.' . $ext;
						$path .= $name;
						$guid = $post['guid'];
						$result = array();

						if ($type = $post['type']){
							// In case type/subtype is plural, the quick and dirty way to check is to remove last 's'
							$entities = get_entities(array('wheres' => 'e.type = "' . $type . '" or e.subtype = "' . $type .
																'" or e.type like "%' . substr($type, 0, -1) .
																'%" or e.subtype like "%' . substr($type, 0, -1) .
																'%"', 'pagination' => false, 'limit' => 'all'));
							if (!$entities) {
								echo json_encode(array('html' => true, 'error' => 'Invalid type'));
								exit;
							// Filter down further to include the entities related to the GUID of a given type/subtype
							} elseif ($guid){
								$access = get_access($guid);
								$a = (isset($access) and count($access) > 0)? true : false;
								foreach ($entities as $entity){
									if (isset($entity->membership) and in_array($guid, $entity->membership)){
										$result[] = $entity;
									} elseif ($a and in_array($entity->guid, $access)){
										$result[] = $entity;
									}
								}
								$description = 'XML export of GUID "' . $guid . '" for "' . $type . '" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							} else {
								$result = $entities;
								$description = 'XML export of "' . $type . '" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							}
						// If only GUID specified, do an export of everything this user/group/object has or belongs to
						} elseif ($guid){
							$entity = orm::get($post['guid']);
							if (!$entity) {
								echo json_encode(array('html' => true, 'error' => 'Invalid GUID'));
								exit;
							} else {
								$result = $entity;
								$description = 'XML export of GUID "' . $entity->guid . '" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							}
						} else {
							// A full system export: will take exponentially longer time with more entities in the database
							$result = get_entities(array('pagination' => false, 'limit' => 'all'));
							$description = 'Full database XML export was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
						}

						// Convert the array to XML (access will also be added using this function) and save to local storage
						if ($xml = entities2xml($result)){
							$f = new pip_disk($dir, false);
							$f->save($name, $xml);

							// Continue only if file was successfully created
							if (is_file($path)) {
								// Save the file as pip_entity
								$file = new pip_file;
								$file->name = $name;
								$file->description = $description;
								$file->original_name = $_SERVER['REQUEST_TIME'] . '.' . $ext;
								$file->mime_type = $file->get_mime_type($ext);
								$file->extension = $ext;
								$file->size = filesize($path);
								$file->path = $path;
								if ($file->save()) {
									// Generate a download link
									echo json_encode(array('html' => "<a href='{$config->site_url}download/{$file->guid}'><strong>Download</strong></a>", 'error' => ''));
								} else {
									echo json_encode(array('html' => true, 'error' => 'Error saving file'));
									exit;
								}
							} else {
								echo json_encode(array('html' => true, 'error' => 'Error generating file'));
								exit;
							}
						} else {
							echo json_encode(array('html' => true, 'error' => 'XML convert error'));
							exit;
						}
						break;
					case 'csv':
						// Prepare the folder and generate a file name
						$dir = '/uploads/export/csv/';
						$path = $config->env->root . $dir;
						dir_setup($dir);
						$ext = 'csv';
						$name = get_loggedin_user()->guid . '_' . $_SERVER['REQUEST_TIME'] . '.' . $ext;
						$path .= $name;
						if ($type = $post['type']) {
							$type = '(e.type = "' . $type . '" OR e.subtype = "' . $type .
									'" OR e.type LIKE "%' . substr($type, 0, -1) . '%" OR e.subtype LIKE "%' . substr($type, 0, -1) . '%")';
							if ($guid = $post['guid']){
								$guid = " AND e.guid = '$guid' OR (e.owner_guid = '$guid' AND e.type = 'object')";
								$description = 'CSV export for GUID "'. $post['guid'] .'" of type "' . $type . '" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							} else {
								$guid = '';
								$description = 'CSV export of "' . $type . '" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							}
						} else {
							$type = 'e.type IN ("user", "group")';
							if ($guid = $post['guid']){
								$guid = " AND e.guid = '$guid' OR (e.owner_guid = '$guid' AND e.type = 'object')";
								$description = 'CSV export for GUID "'. $post['guid'] .'" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							} else {
								$guid = '';
								$description = 'CSV database export was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							}
						}

						global $db;
						/**
						 * Instead of using get_entities to get the results as pip objects, we will generate the query manually
						 * The entities of interest are users and groups, hence we will export only those two types
						 * if GUID or type/subtype was supplied (and also to have mercy on the DB performance
						 * although even this query might kill a large database, so USE WITH CAUTION
						 */
						// Multi rows for each membership
						$sql = "SELECT e.guid AS 'GUID', e.type AS 'TYPE', e.subtype AS 'SUBTYPE', e.owner_guid AS 'OWNER'," .
								" ifnull(o.group_guid, g.name) AS 'GROUP', u.fname AS 'FNAME', u.lname AS 'LNAME'," .
								" ifnull(f.path, u.email) AS 'IDENTIFIER', m1.group_guid AS 'MEMBEROF', m2.user_guid AS 'MEMBERS'" .
								" FROM {$config->dbprefix}entities e" .
								" LEFT JOIN {$config->dbprefix}users u ON u.guid = e.guid" .
								" LEFT JOIN {$config->dbprefix}groups g ON g.guid = e.guid" .
								" LEFT JOIN {$config->dbprefix}objects o ON o.guid = e.guid" .
								" LEFT JOIN {$config->dbprefix}files f ON f.guid = e.guid" .
								" LEFT JOIN {$config->dbprefix}memberships m1 ON u.guid = m1.user_guid" .
								" LEFT JOIN {$config->dbprefix}memberships m2 ON g.guid = m2.group_guid" .
								" WHERE $type{$guid} AND e.active = 'yes' ORDER BY e.type";
						if ($db->csv($sql, $path)) {
							// Continue only if file was successfully created
							if (is_file($path)) {
								// Save the file as pip_entity
								$file = new pip_file;
								$file->name = $name;
								$file->description = $description;
								$file->original_name = $_SERVER['REQUEST_TIME'] . '.' . $ext;
								$file->mime_type = $file->get_mime_type($ext);
								$file->extension = $ext;
								$file->size = filesize($path);
								$file->path = $path;
								if ($file->save()) {
									// Generate a download link
									echo json_encode(array('html' => "<a href='{$config->site_url}download/{$file->guid}'><strong>Download</strong></a>", 'error' => ''));
								} else {
									echo json_encode(array('html' => true, 'error' => 'Error saving file'));
									exit;
								}
							}
						} else {
							echo json_encode(array('html' => true, 'error' => 'Error making CSV'));
							exit;
						}
						break;
					case 'sqlg':
					case 'sqls':
					case 'sqlsd':
						// Prepare the folder and generate a file name
						$dir = '/uploads/export/sql/';
						$path = $config->env->root . $dir;
						dir_setup($dir);
						$ext = 'sql';
						$name = get_loggedin_user()->guid . '_' . $_SERVER['REQUEST_TIME'] . '.' . $ext;
						$path .= $name;
						$guid = $post['guid'];

						if ($type = $post['type']){
							// In case type/subtype is plural, the quick and dirty way to check is to remove last 's'
							$entities = get_entities(array('wheres' => 'e.type = "' . $type . '" or e.subtype = "' . $type .
																'" or e.type like "%' . substr($type, 0, -1) .
																'%" or e.subtype like "%' . substr($type, 0, -1) .
																'%"', 'pagination' => false, 'limit' => 'all'));
							if (!$entities) {
								echo json_encode(array('html' => true, 'error' => 'Invalid type'));
								exit;
							// Filter down further to include the entities related to the GUID of a given type/subtype
							} elseif ($guid){
								$access = get_access($guid);
								$a = (isset($access) and count($access) > 0)? true : false;
								foreach ($entities as $entity){
									$entity_done = false;
									if (isset($entity->membership) and in_array($guid, $entity->membership)){
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}entities --where=\"guid='$entity->guid'\" --compact", $dump);
										// Only users or groups can belong to another group
										if ($entity->type == 'user'){
											exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}users --where=\"guid='$entity->guid'\" --compact", $dump);
											exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}memberships --where=\"user_guid='$entity->guid'\" --compact", $dump);
											exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"user_guid='$entity->guid'\" --compact", $dump);
										} elseif ($entity->type == 'group'){
											exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}groups --where=\"guid='$entity->guid'\" --compact", $dump);
											exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}memberships --where=\"group_guid='$entity->guid'\" --compact", $dump);
											exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"group_guid='$entity->guid'\" --compact", $dump);
										}
										$entity_done = true;
									} else {
										if ($a and in_array($entity->guid, $access)){
											if (!$entity_done) exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}entities --where=\"guid='$entity->guid'\" --compact", $dump);
											// Only objects and groups can have access only
											if ($entity->type == 'group'){
												exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}groups --where=\"guid='$entity->guid'\" --compact", $dump);
												exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}memberships --where=\"group_guid='$entity->guid'\" --compact", $dump);
												exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"group_guid='$entity->guid'\" --compact", $dump);
											} elseif ($entity->type == 'object'){
												exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}objects --where=\"guid='$entity->guid'\" --compact", $dump);
												exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"object_guid='$entity->guid'\" --compact", $dump);
												if ($entity->subtype == 'file'){
													exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}files --where=\"guid='$entity->guid'\" --compact", $dump);
												}
											}
										}
									}
								}
								$description = 'SQL export of GUID "' . $guid . '" for "' . $type . '" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							} else {
								foreach ($entities as $entity){
									// Array $dump will always be appended to
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}entities --where=\"guid='$entity->guid'\" --compact", $dump);
									// Determine what kind of tables to query for this entity type/subtype
									if ($entity->type == 'user'){
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}users --where=\"guid='$entity->guid'\" --compact", $dump);
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}memberships --where=\"user_guid='$entity->guid'\" --compact", $dump);
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"user_guid='$entity->guid'\" --compact", $dump);
									} elseif ($entity->type == 'group'){
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}groups --where=\"guid='$entity->guid'\" --compact", $dump);
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}memberships --where=\"group_guid='$entity->guid'\" --compact", $dump);
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"group_guid='$entity->guid'\" --compact", $dump);
									} elseif ($entity->type == 'object'){
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}objects --where=\"guid='$entity->guid'\" --compact", $dump);
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"object_guid='$entity->guid'\" --compact", $dump);
										if ($entity->subtype == 'file'){
											exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}files --where=\"guid='$entity->guid'\" --compact", $dump);
										}
									}
								}
								$description = 'SQL export of "' . $type . '" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							}
						// If only GUID specified, do an export of everything this user/group/object has or belongs to
						} elseif ($guid){
							$entity = orm::get($post['guid']);
							if (!$entity) {
								echo json_encode(array('html' => true, 'error' => 'Invalid GUID'));
								exit;
							} else {
								// Array $dump will always be appended to
								exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}entities --where=\"guid='$entity->guid'\" --compact", $dump);
								// Determine what kind of tables to query for this entity type/subtype
								if ($entity->type == 'user'){
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}users --where=\"guid='$entity->guid'\" --compact", $dump);
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}memberships --where=\"user_guid='$entity->guid'\" --compact", $dump);
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"user_guid='$entity->guid'\" --compact", $dump);
								} elseif ($entity->type == 'group'){
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}groups --where=\"guid='$entity->guid'\" --compact", $dump);
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}memberships --where=\"group_guid='$entity->guid'\" --compact", $dump);
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"group_guid='$entity->guid'\" --compact", $dump);
								} elseif ($entity->type == 'object'){
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}objects --where=\"guid='$entity->guid'\" --compact", $dump);
									exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}access --where=\"object_guid='$entity->guid'\" --compact", $dump);
									if ($entity->subtype == 'file'){
										exec("mysqldump -t -u $config->dbuser -p{$config->dbpass} $config->dbname {$config->dbprefix}files --where=\"guid='$entity->guid'\" --compact", $dump);
									}
								}
								$description = 'SQL export of GUID "' . $entity->guid . '" was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
							}
						} else {
							// In order to account for a DB server to be on a separate physical machine, we do not export to a local file
							// but instead to a screen, the output of which we later capture and write using phpiphany's methods and predefined path
							if ($post['format'] == 'sqls') exec("mysqldump -d -u $config->dbuser -p{$config->dbpass} $config->dbname --single-transaction --compact", $dump);
							if ($post['format'] == 'sqlsd') exec("mysqldump -u $config->dbuser -p{$config->dbpass} $config->dbname --single-transaction --compact", $dump);
							$description = 'Full database SQL export was generated by ' . get_loggedin_user()->name . ' (' . getenv('REMOTE_ADDR') . ') on ' . new dater;
						}

						if ($dump){
							// Make it a nice string instead of an array
							$dump = implode("\n", $dump);
							// And write to a path
							$f = new pip_disk($dir, false);
							$f->save($name, $dump);

							// Continue only if file was successfully created
							if (is_file($path)) {
								// Save the file as pip_entity
								$file = new pip_file;
								$file->name = $name;
								$file->description = $description;
								$file->original_name = $_SERVER['REQUEST_TIME'] . '.' . $ext;
								$file->mime_type = $file->get_mime_type($ext);
								$file->extension = $ext;
								$file->size = filesize($path);
								$file->path = $path;
								if ($file->save()) {
									// Generate a download link
									echo json_encode(array('html' => "<a href='{$config->site_url}download/{$file->guid}'><strong>Download</strong></a>", 'error' => ''));
								} else {
									echo json_encode(array('html' => true, 'error' => 'Error saving file'));
									exit;
								}
							} else {
								echo json_encode(array('html' => true, 'error' => 'Error generating file'));
								exit;
							}
						} else {
							echo json_encode(array('html' => true, 'error' => 'Cannot use mysqldump'));
							exit;
						}
						break;
				}
				break;
			case 'import':

				/**
				 * Import method is intended to be launched with $show_status=true parameter in the form.
				 * This means that we will output using ob_flush() instead of JSON
				 *
				 * // Simple code to output sequentially as the server processes the request
				 * 	$time = new timer();
				 * 	echo 'Processing your request...<br>';
				 * 	for ($i=1; $i<=20; $i++){
				 * 		sleep(1);
				 * 		echo "\nItem $i\n<br>";
				 * 		ob_flush();
				 * 		flush();
				 * 	}
				 * 	echo '<br><strong>DONE</strong><br>' . $time->stop();
				 */

				// Some error checking first: we need to have a file uploaded first, and it needs to be a valid pip_file
				if (!isset($_SESSION['pip_file_guid']) or !$_SESSION['pip_file_guid']) {
					json_encode(array('html' => true, 'error' => '<strong class="red">In order to continue, please upload a file first!</strong>'));
					exit;
				}

				if (!($file = orm::get($_SESSION['pip_file_guid']))) {
					json_encode(array('html' => true, 'error' => '<strong class="red">Error with the uploaded file! Cannot link to new path.</strong>'));
					exit;
				}

				// We're all set. Move this temp file to the export folder
				if ($file->handle_uploads()){
					// Get the new file information
					$file = orm::get($file->guid);
					// Read the file
					$contents = trim(file_get_contents($file->path));
					// Clean it up
					$contents = str_replace(array("\r\n", "\n", "\r", "\t"), "", $contents);
					// Parse the file depending on its extension
					switch ($file->extension) {
						case 'csv':
							//here
							break;
						case 'xml':
							// Start the timer
							$xmltimer = new timer();
							// First convert the contents of this XML to an array of objects
							$entities = simplexml_load_string($contents);
							// Check for the validity of the XML file
							if ($entities and isset($entities->entity) and $entities->entity){
								global $db;
								$i = $e = 0;
								// Parsed is the array that will hold the old guids and the newly generated ones
								$parsed = array();
								// Loop through the entities and insert depending on the type/subtype combo
								foreach ($entities->entity as $entity){
									// Skip empty objects (all objects must have a name)
									if ($entity->name and strlen($entity->name) > 0){
										if ($i > 0) echo '<hr>';
										echo truncate('Processing entity: ' . $entity->name, 70) . "<br>Type: $entity->type; Subtype: $entity->subtype<br>";
										//usleep(100000); 0.1 of a second
										ob_flush();
										flush();
										++$i;

										if ($entity->type == 'user') {
											// If the user exists skip this record
											if ($user = get_user(array('username' => $entity->username, 'email' => $entity->email), true)){
												echo '<strong>This user already exists in the system! Skipping...</strong><br>';
												continue;
											} else {
												$import = new pip_user();
												$import->name = $entity->fname . ' ' . $entity->lname;
											}
										} elseif ($entity->type == 'group'){
											// If the group exists skip this record
											$db->query("SELECT `guid` FROM {$config->dbprefix}groups WHERE `name` = '" . $db->escape($entity->name) . "'"  .
													($entity->parent_guid and strlen($entity->parent_guid) == 12? " AND `parent_guid` = '" . $db->escape($entity->parent_guid) . "'" : '') .
													" AND `description` = '" . $db->escape(html_entity_decode($entity->description)) . "' AND `access` = '" .
													($entity->access? $db->escape($entity->access) : 'Private')  . "'");
											if ($row = $db->fetch_obj()){
												echo '<strong>This group already exists in the system! Skipping...</strong><br>';
												continue;
											} else {
												$import = new pip_group();
											}
										} elseif ($entity->subtype == 'file'){
											// If the file exists skip this record
											$db->select('`guid`', $config->dbprefix . 'files', '`filename` = ? AND `size` = ? AND `path` = ?', array($entity->filename, $entity->size, $entity->path));
											if ($row = $db->fetch_obj()){
												echo '<strong>This file already exists in the system! Skipping...</strong><br>';
												continue;
											} else {
												$import = new pip_file();
											}
										} else {
											// If the object exists skip this record
											$db->query("SELECT `guid` FROM {$config->dbprefix}objects WHERE `name` = '" . $db->escape($entity->name) . "'"  .
														" AND `description` = '" . $db->escape($entity->description) . "' AND `access` = '");
											if ($row = $db->fetch_obj()){
												echo '<strong>This object already exists in the system! Skipping...</strong><br>';
												continue;
											} else {
												$import = new pip_object();
											}
										}

										// Populate the import entity with fields from the XML file omitting specific values that we'll deal with later
										foreach ($entity as $k => $v) {
											if ($k != 'guid' and $k != 'members' and $k != 'membership' and $k != 'has_access_to') $import->$k = $v;
										}

										// Create a simple class containing the old entity guid and its relationships
										$old_data = new stdClass();
										$old_data->type = (string) $entity->type;
										$old_data->subtype = (string) $entity->subtype;
										if ($entity->membership and isset($entity->membership->guid) and $g = (array) $entity->membership->guid and count($g) > 0){
											// Set group membership if this is a user
											$old_data->membership = $entity->membership->guid;
										} elseif ($entity->members and isset($entity->members->guid) and $g = (array) $entity->members->guid and count($g) > 0){
											// Set members if this is a group
											$old_data->members = $entity->members->guid;
										} elseif ($entity->has_access_to and isset($entity->has_access_to->guid) and $a = (array) $entity->has_access_to->guid and count($a) > 0){
											// Define access
											$old_data->has_access_to = $a;
										}


										// If creating a new entity with the given fields fails, exit.
										// Otherwise create update the parsed array with the new GUID
										if ($import->save()){
											$old_data->new_guid = $import->guid;
											echo '<strong>New entity successfully created!</strong><br>';
										} else {
											echo '<strong>Creation of a new ' . $import->type . ' <span class="red">failed</span>!';
											echo ($db->fatal_error)? ' Database said: </strong><br>' . $db->fatal_error . '<br>' : ' Unknown error.</strong><br>';
											++$e;
											continue;
										}

										// Add this entity to the parsed array keeping the old guid as the array key
										$parsed[(string)$entity->guid] = $old_data;
									}
								}

								if (count($parsed) > 0){
									// Now deal with memberships and access
									echo '<hr>Recreating entity relationships and access...<br>';
									ob_flush();
									flush();
									foreach ($parsed as $entity){
										if (isset($entity->membership) and $entity->membership){
											// Join this user to the groups they belonged to before
											foreach ($entity->membership as $guid){
												// Check if the old group was included in this import and we have a new GUID for it
												if ((isset($parsed[$guid]) and isset($parsed[$guid]->new_guid) and $new_group_guid = $parsed[$guid]->new_guid) or
																									($pip = orm::get($guid) and $new_group_guid = $pip->guid)) {
													// Join user to this group
													if (!join_group($new_group_guid, $entity->new_guid)){
														++$e;
														echo '<strong>Adding user to a group <span class="red">failed</span>!';
														echo ($db->fatal_error)? ' Database said: </strong><br>' . $db->fatal_error . '<br>' : ' Unknown error.</strong><br>';
														ob_flush();
														flush();
													}
												}
											}
										} elseif (isset($entity->members) and $entity->members){
											// Add members to this group that used to exist before
											foreach ($entity->members as $guid){
												// Check if the old user was included in this import and we have a new GUID for it
												if ((isset($parsed[$guid]) and isset($parsed[$guid]->new_guid) and $new_user_guid = $parsed[$guid]->new_guid) or
																									($pip = orm::get($guid) and $new_user_guid = $pip->guid)) {
													// Join user to this group
													if (!join_group($entity->new_guid, $new_user_guid)){
														++$e;
														echo '<strong>Adding user to a group <span class="red">failed</span>!';
														echo ($db->fatal_error)? ' Database said: </strong><br>' . $db->fatal_error . '<br>' : ' Unknown error.</strong><br>';
														ob_flush();
														flush();
													}
												}
											}
										} elseif (isset($entity->has_access_to) and $entity->has_access_to){
											// Simply recreate access
											foreach ($entity->has_access_to as $guid){
												// Check if the old user was included in this import and we have a new GUID for it
												if ((isset($parsed[$guid]) and isset($parsed[$guid]->new_guid) and $accessee = $parsed[$guid]) or
																		($accessee = orm::get($guid) and $accessee->new_guid = $accessee->guid)) {
													// Create new access rule
													$user_guid = $group_guid = $object_guid = '';
													if ($entity->type == 'user') $user_guid = $entity->new_guid;
													elseif ($entity->type == 'group') $group_guid = $entity->new_guid;
													elseif ($entity->type == 'object') $object_guid = $entity->new_guid;

													if ($accessee->type == 'user') $user_guid = $accessee->new_guid;
													elseif ($accessee->type == 'group') $group_guid = $accessee->new_guid;
													elseif ($accessee->type == 'object') $object_guid = $accessee->new_guid;

													// Insert only if the combination exists
													if (($user_guid and $group_guid) or ($user_guid and $object_guid) or ($object_guid and $group_guid) or ($user_guid and $object_guid and $group_guid)){
														if (!$db->insert($config->dbprefix . 'access', array('user_guid' => $user_guid, 'group_guid' => $group_guid, 'object_guid' => $object_guid))){
															++$e;
															echo '<strong>Adding access rules <span class="red">failed</span>!';
															echo ($db->fatal_error)? ' Database said: </strong><br>' . $db->fatal_error . '<br>' : ' Unknown error.</strong><br>';
															ob_flush();
															flush();
														}
													}
												}
											}
										}
									}
									// Clear cache
									global $cache;
									if ($cache){
										// @TODO need a better solution to flush entire cache structure
										$cache->save('drop', 'group');
										$cache->del('access');
									}
									echo '<strong>Done</strong><br>';
								}
								ob_flush();
								flush();

								echo '<hr><br><h4><strong class="green">XML file imported successfuly!</strong></h4>Execution time: ';
								echo $xmltimer->stop() . '<br>Total entities processed: ' . $i;
								if ($e > 0) echo '<br>Total errors: ' . $e;
								echo '<br><span id="end_of_import"></span>';
								//echo '<script>document.getElementById("end_of_import").scrollIntoView();</script>';

								//echo '<pre>' . print_r($entities, true) . '</pre>';
								ob_flush();
								flush();
								$file->delete();
							} else {
								$file->delete();
								echo '<strong class="red">The XML file you uploaded is not in a recognizable format!</strong><br>' .
										'It looks like it was not generated using internal "export" function.<br>' .
										'<strong>' . $config->site_name . '</strong> is unable to parse the file properly.<br><br>Temp file deleted...';
							}
							break;
						case 'sql':
							// This is the simpliest file import, all we need to do is to invoke a multi_query database call
							global $db;
							if ($db->multi_query($contents, true)){
								echo '<br><h4><strong class="green">SQL file imported successfuly!</strong></h4>Execution time: ' . $db->human_readable_e_notation($db->total_execution_time) . ' ms';
							} else {
								$file->delete();
								echo '<strong class="red">There was a problem processing your file. SQL said:</strong><br><br>'. $db->fatal_error . '<br><br>Temp file deleted...';
							}
							break;
						default:
							echo '<strong class="red">Invalid file extension</strong>';
							break;
					}
				}
				break;
			default:
				echo '<strong class="red">Invalid AJAX action</strong>';
				break;
		}
	}

	/**
	 * Plugins Manager
	 * Facilitates modular approach to third-party/custom extensions
	 *
	 * Index page shows a listing of all the plugins found in the 'plugins' directory
	 * and then checks if any of them are installed by querying the database
	 *
	 * When installing a plugin, group GUID defines access for this plugin to a specific group.
	 * By default it is empty which means everybody have access to it.
	 *
	 */
	public function plugins(){

		global $view, $config, $db;
		$view->use_jquery = false;
		$this->set_menu('plugins');

		// Decide whether or not this is the index page for plugins or a certain action
		if (isset($config->input->get) and $get = $config->input->get and $get['do']){
			switch ($get['do']) {
				case 'install':
				case 'edit':
					// Get all the groups to populate the combo box. If none selected, the plugin will be accessible by anyone
					$groups = get_active_groups(true);
					// Since this swtich/case is serving dual purpose, we populate all field variables from the DB if this is edit, or blank if it is a new creation
					if ($get['do'] == 'edit') {
						if ($plugin = $db->fetch_obj($db->select('*', $config->dbprefix . 'plugins', '`name` = ?', array($get['name'])))){
							$name = $plugin->name;
							$description = $plugin->description;
							$version = $plugin->version;
							$author = $plugin->author;
							$path = $plugin->path;
							$active = $plugin->active;
							$group_guid = $plugin->group_guid;
						} else pip_error('There is no such plugin with name "' . $get['name'] . '"!');
					} else {
						$name = $get['name'];
						$path = $get['path'];
						$description = $version = $author = $active = $group_guid = '';
					}
					$view->title = ucwords($get['do']) . ' Plugin "' . $name . '"';
					html::hidden(array('name' => 'name', 'value' => $name));
					html::hidden(array('name' => 'path', 'value' => $path));
					html::text(array('name' => 'description', 'placeholder' => 'Enter short plugin description', 'maxlength' => 255, 'value' => $description));
					html::text(array('name' => 'version', 'placeholder' => 'Enter the plugin\'s version', 'value' => $version));
					html::text(array('name' => 'author', 'placeholder' => 'Enter the plugin\'s author', 'value' => $author));
					html::combo(array('name' => 'group_guid', 'label' => 'access group', 'options' => $groups, 'selected' => $group_guid,
																'help' => 'If no group selected, this plugin will be accessible to anyone'));
					html::combo(array('name' => 'active', 'label' => 'activate now?', 'options' => array('yes', 'no'), 'size' => 1, 'firstempty' => false, 'selected' => $active));

					// Generate the form depending on the action supplied
					if ($get['do'] == 'edit') {
						$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'admin/ajax/plugin/edit/' . $plugin->name,
										'name' => 'Current plugin information', 'redirect' => $view->assets_dir . 'admin/plugins'));
					} else {
						$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'admin/ajax/plugin/new', 'action' => 'Install',
										'name' => 'New plugin information', 'resubmit' => false, 'redirect' => $view->assets_dir . 'admin/plugins'));
					}
					break;
				case 'activate':
					// Activate a plugin
					action_gatekeeper();
					if ($db->update($config->dbprefix . 'plugins', array('active' => 'yes'), '`name` = ?', array($get['name']))){
						pip_success('Plugin was successfully activated', $config->site_url . 'admin/plugins#' . $get['name']);
					} else {
						pip_error('Unable to activate the plugin. Check the DB logs', $config->site_url . 'admin/plugins#' . $get['name']);
					}
					break;
				case 'deactivate':
					// Deactivate a plugin
					action_gatekeeper();
					if ($db->update($config->dbprefix . 'plugins', array('active' => 'no'), '`name` = ?', array($get['name']))){
						pip_success('Plugin was successfully deactivated', $config->site_url . 'admin/plugins#' . $get['name']);
					} else {
						pip_error('Unable to deactivate the plugin. Check the DB logs', $config->site_url . 'admin/plugins#' . $get['name']);
					}
					break;
				case 'uninstall':
					// Uninstall a plugin
					// Currently works without a confirmation modal window
					action_gatekeeper();
					// Do not continue with uninstalling if this plugin is active
					$plugin = $db->fetch_obj($db->select('*', $config->dbprefix . 'plugins', '`name` = ?', array($get['name'])));
					if ($plugin) {
						if ($plugin->active == 'yes') {
							pip_error('This plugin is currently active and might be used by other users. Please make sure that it is safe to uninstall it, then deactive it to proceed',
									$config->site_url . 'admin/plugins#' . $get['name']);
						} else {
							if ($db->delete($config->dbprefix . 'plugins', '`name` = ?', array($get['name']))) {
								pip_success('Plugin was successfully uninstalled from the system', $config->site_url . 'admin/plugins#' . $get['name']);
							} else {
								pip_error('Unable to uninstall the plugin. Check the DB logs', $config->site_url . 'admin/plugins#' . $get['name']);
							}
						}
					}
					break;
				default:
					pip_error('There is no such action "' . $get['do'] . '" that can be applied to plugins');
					break;
			}
		} else {
			$view->title = 'Plugins Manager';
			array_push($view->custom_css, $view->assets_dir . 'css/admin/style.css', $view->assets_dir . 'css/admin/tables.css');
			// Scan the plugins directory
			$plugins = array();
			if (is_dir($config->plugins_dir)){
				$root = scandir($config->plugins_dir);
				foreach ($root as $value) {
					// Remove hidden files and references to a parent directory
					if ($value[0] === '.' or $value === '..') continue;
					// Get information about this plugin from the database (if any)
					$clean = $db->escape($value);
					//$db->select('*', $config->dbprefix . 'plugins', "`name` = '$clean'");
					$db->query("SELECT p.name, p.description, p.version, p.author, p.path, p.active, g.name as 'group', p.date_installed
					FROM {$config->dbprefix}plugins p LEFT OUTER JOIN {$config->dbprefix}groups g ON p.group_guid = g.guid WHERE p.name = '$clean'");
					$results = $db->fetch_obj();
					if ($results) $plugins[] = $results;
					else {
						$path = "$config->plugins_dir/$value";
						$obj = new stdClass();
						$obj->name = $value;
						$obj->path = $path;
						$obj->version = $obj->author = $obj->active = $obj->group = '';
						$obj->date_installed = false;
						$obj->description = '<em>No description is available about this plugin. <br>Please install it first!</em>';
						$plugins[] = $obj;
					}
				}
			}
			$view->content = $view->load('admin/plugins', array('plugins' => $plugins));
		}
		$view->render_page();
		exit();
	}

	/**
	 * Database versioning (migration) controller
	 * Manages the upgrade and downgrade of DB changes through a set of files located in the corresponding database folder
	 *
	 * Naming convention for the 'version' scripts is dbprefix . timestamp() and will contain sql syntax inside a php class
	 * The class is comprised of upgrade/downgrade static methods which are called within this controller
	 *
	 */
	public function migration(){

		global $view, $config;
		$view->use_jquery = false;
		$this->set_menu('migration');

		$view->title = 'Database Migration Controller';

		$page = isset($config->input->parms)? $config->input->parms : '';
		$page = is_array($page)? $page : array($page);

		// Actions include edit/create, upgrade or downgrade of current version
		switch ($page[0]) {
			case 'new':
			case 'edit':
				// Some error checking
				if ($page[0] == 'edit'){
					$class = $page[1];
					if (!$class) pip_error('No version file specified!');
					$path = $config->versions_dir . '/' . $class . '.php';
					if (!is_file($path)) pip_error('Version name is invalid!');
					include $path;
					if (!class_exists($class)) pip_error('Version script cannot be initialized: class named "' . $class . '" does not exist!');
					if (!is_callable(array($class, 'upgrade')) or !is_callable(array($class, 'downgrade'))) pip_error('Version script does not have "upgrade" or "downgrade" methods!');

					$upgrade = $class::upgrade();
					$downgrade = $class::downgrade();
				} else {
					$class = $upgrade = $downgrade = '';
				}

				html::hidden(array('name' => 'name', 'value' => $class));
				html::longtext(array('name' => 'upgrade', 'placeholder' => 'Enter upgrade SQL syntax here', 'size' => 5, 'value' => $upgrade));
				html::longtext(array('name' => 'downgrade', 'placeholder' => 'Enter downgrade SQL syntax here', 'size' => 5, 'value' => $downgrade));
				if ($page[0] == 'edit') {
					$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'admin/ajax/version/edit/' . $class, 'name' => 'Contents of "' . $class . '" version script', 'redirect' => $view->assets_dir . 'admin/migration'));
				} else {
					$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'admin/ajax/version/new', 'name' => 'Create a new version script', 'redirect' => $view->assets_dir . 'admin/migration'));
				}
				break;
			case 'upgrade':
			case 'downgrade':
				action_gatekeeper();

				// Simple error checking
				$class = $page[1];
				if (!$class) pip_error('No version file specified!');
				$path = $config->versions_dir . '/' . $class . '.php';
				if (!is_file($path)) pip_error('Version name is invalid!');
				include $path;
				if (!class_exists($class, false)) pip_error('Version script cannot be initialized: class named "' . $class . '" does not exist!');
				if (!is_callable(array($class, 'upgrade')) or !is_callable(array($class, 'downgrade'))) pip_error('Version script does not have "upgrade" or "downgrade" methods!');

				// Execute SQL queries as a transaction
				global $db;
				$sql = ($page[0] == 'upgrade')? $class::upgrade(): $class::downgrade();
				$db->transaction_start();
				$db->multi_query($sql);

				if ($db->transaction_complete()) $view->content = '<h1 class="green">Your database was successfully ' . $page[0] . 'd to version "' . $class . '"!</h1>';
				else $view->content = '<h1 class="red">There was an error ' . substr($page[0], 0, -1) . 'ing the database to version "' . $class . '"!</h1>';

				$view->content .= '<br><p><div class="content-wrapper centered"><h4>Please check the error log either by enabling
									the debug mode in the settings and browsing through the transaction queries in the<br>
									database debugger accessible at the <a href="javascript:db_toggle(\'console\')">
									bottom right corner</a>; or reading the database log files located at
									<em>phpiphany_root/tmp/db_stats/</em></h4></div></p>';

				break;
			case 'delete':
				action_gatekeeper();

				// Simple error checking
				$class = $page[1];
				if (!$class) pip_error('No version file specified!');
				$path = $config->versions_dir . '/' . $class . '.php';
				if (!is_file($path)) pip_error('Version name is invalid!');

				// Continue with the delete action
				if (unlink($path)) pip_success('Version "' . $class . '" was successfully deleted!');
				else pip_error('Version "' . $class . '" was NOT deleted! Check file/folder permissions');

				break;
			default: // Render the main page with a list of versions to choose from

				// Set the admin CSS
				array_push($view->custom_css, $view->assets_dir . 'css/admin/style.css', $view->assets_dir . 'css/admin/tables.css');

				// Get all the 'version' scripts located in the model/versions folder depending on the database type configured
				$versions = array();
				if (is_dir($config->versions_dir)) {
					$root = scandir($config->versions_dir);
					foreach ($root as $value) {
						// Remove hidden files and references to a parent directory
						if ($value[0] === '.' or $value === '..') {
							continue;
						}
						$value = explode('.', $value);
						// Get the date from the timestamp part of the file
						$date = new dater(substr($value[0], strlen($config->dbprefix)));
						$versions[$value[0]] = $date;
					}
					// Sort and get the latest version
					natsort($versions);
					end($versions);
					$latest = key($versions);
				} else {
					throw new error('The directory with stored "versioning" scripts cannot be found or cannot be accessed due to permission restrictions!
									<br>Please chmod 755 or create the directory: <em>' . $config->versions_dir . '</em>');
				}
				$view->content = $view->load('admin/migration', array('title' => $view->title, 'link' => $view->assets_dir . 'admin/migration/', 'versions' => $versions, 'latest' => $latest));
				break;
		}
		$view->render_page();
		exit();
	}

	/**
	 * Maintenance mode controller
	 *
	 * Puts the site into maintenance mode by destroying every user session (except admin)
	 * and displaying a maintenance message when trying to access any page
	 *
	 * NOTE: At this stage, current method relies only on cache, so if you want to use it, make sure you have cache configured
	 *
	 */
	public function maintenance(){

		global $view, $config, $db, $cache;
		$view->use_jquery = false;
		$this->set_menu('maintenance');

		$view->title = 'Maintenance Mode Controller';

		$page = isset($config->input->parms)? $config->input->parms : '';
		$page = is_array($page)? $page : array($page);

		if (!$cache) pip_error('Maintenance module requires a valid cache server. Please configure it in the settings file');

		// Actions include edit/create, upgrade or downgrade of current version
		switch ($page[0]) {
			case 'quarantine':
				action_gatekeeper();
				$post = $config->input->post;
				if (!$post['message']) pip_error('Maintenance message is required!');
				$user = get_loggedin_user();
				if ($db->insert($config->dbprefix . 'maintenance',
					array('type' => $post['type'], 'complete_in' => $post['etc'], 'msg' => $post['message'], 'reason' => $post['reason'], 'creator_guid' => $user->guid))){
					$cache->save('maintenance_msg', $post['message']);
					if ($post['etc']) $cache->save('maintenance_etc', (intval($post['etc']) * 60) + time());
					pip_success('Quarantined successfully!');
				} else pip_error('Error putting system in maintenance mode. Check the logs');
			break;
			case 'remove':
				action_gatekeeper();
				if ($db->query('TRUNCATE TABLE `' . $config->dbprefix . 'maintenance`')) {
					$cache->del('maintenance_msg');
					$cache->del('maintenance_etc');
					pip_success('Quarantine removed successfully!');
				} else pip_error('Error removing system maintenance mode. Check the logs');
			break;
		default: // Render the main page

			// Set the admin CSS
			array_push($view->custom_css, $view->assets_dir . 'css/admin/style.css', $view->assets_dir . 'css/admin/tables.css');

			$link = $view->assets_dir . 'admin/maintenance/quarantine';
			$remove = $view->assets_dir . 'admin/maintenance/remove';

			// Check if system is already in maintenance mode. If so, populate the default values and display an option to remove it
			$result = $db->fetch_obj($db->select('*', $config->dbprefix . 'maintenance', '', '', 1));
			if (isset($result) and $result){
				$type = $result->type;
				$reason = $result->reason;
				$msg = $result->msg;
				$complete_in = $result->complete_in;
				$btn = '<a href="' . $remove . '?' . generate_token(true) . '" class="btn btn-large btn-success" style="margin-left: 20px">Remove the system maintenance mode</a>';
			} else $type = $complete_in = $reason = $msg = $btn = '';


			// Estimated time of completion
			$etc = array(5 => '5 minutes', 10 => '10 minutes', 15 => '15 minutes', 20 => '20 minutes', 30 => '30 minutes', 45 => '45 minutes', 60 => '1 hour',
						120 => '2 hours', 180 => '3 hours', 240 => '4 hours', 300 => '5 hours', 360 => '6 hours', 420 => '7 hours', 500 => '8 hours',
						1440 => '1 day', 2880 => '2 days', 4320 => '3 days', 5760 => '4 days', 7200 => '5 days', 8640 => '6 days', 10080 => 'A week');

			// Generate the simple form
			html::combo(array('name' => 'type', 'label' => 'type of maintenance', 'options' => array('Full', 'Readonly'), 'size' => 2, 'firstempty' => false, 'selected' => $type));
			html::combo(array('name' => 'etc', 'label' => 'how long would it take?', 'options' => $etc, 'size' => 2, 'selected' => $complete_in));
			html::longtext(array('name' => 'reason', 'size' => 3, 'rows' => 2, 'tab'=> 8,
								'placeholder' => 'What is the reason of putting the system in maintenance mode? (optional)', 'value' => $reason));
			html::longtext(array('name' => 'message', 'size' => 7, 'rows' => 5, 'tab'=> 8,
								'placeholder' => 'Create a message that users will see when trying to access the site that is in maintenance mode (required)', 'value' => $msg));

			$view->content = $view->load('admin/maintenance', array('title' => $view->title, 'link' => $link, 'btn' => $btn, 'mode' => 'admin', 'content' => html::$form_content));
			break;
		}

		$view->render_page();
		exit();
	}

	/**
	 * Export system data into multiple file formats
	 * Currently only 3 formats are supported: CSV, XML and SQL
	 *
	 * Later, it will be possible to export into OpenDD and other social media formats for integration.
	 * Acknowledging the idea of sharing data with potential clients on the other hand, does not require exporting
	 * ALL data, especially permission/ACL. Therefore, a simple dump of entity's basic information is done with
	 * CSV and XML formats. A full database export dump can be achieved by selecting SQL format (relies on mysqldump)
	 *
	 */
	public function export(){

		global $view;
		$this->set_menu('export');

		$view->title = 'Export System Data';

		// Export formats
		$formats = array('CSV', 'XML', 'sqlg' => 'SQL GUID specific INSERTs', 'sqls' => 'SQL full DB dump: Structure Only', 'sqlsd' => 'SQL full DB dump: Structure + Data');

		// Generate the simple form
		html::combo(array('name' => 'format', 'label' => 'format to export in', 'options' => $formats, 'size' => 2, 'firstempty' => false));
		html::text(array('name' => 'guid', 'label' => 'Entity GUID', 'size' => 2, 'maxlength' => 12, 'placeholder' => 'Specific GUID to export',
							'help' => 'Enter entity GUID you would like to export. <br>If left blank, full system dump will be generated for SQL format' .
									' <br>(for CSV and XML only users and groups will be exported)'));
		html::text(array('name' => 'type', 'label' => 'Entity Type', 'size' => 2, 'maxlength' => 25, 'placeholder' => 'Entity type or subtype',
							'help' => 'You can narrow down the results by specifying a type <br>or a subtype that you want to filter by. Eg. "users" or "files"'));
		$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'admin/ajax/export', 'name' => 'What and how to export?', 'btn' => 'Generate File'));

		$view->render_page();
		exit();
	}

	/**
	 * Import data into the system
	 * Currently only 3 formats are supported: CSV, XML and SQL
	 *
	 */
	public function import(){

		global $view;
		$this->set_menu('import');

		$view->title = 'Import Data Into The System';

		// Generate the simple form
		html::upload(array('label' => 'upload an import file', 'size' => 5, 'filesize' => '50MB', 'path' => 'import', 'ext' => array('csv', 'xml', 'sql'),
							'multi' => true, 'help' => 'Only CSV, XML and SQL files are supported at this time'));

		$view->content = html::form(array('title' => $view->title, 'url' => $view->assets_dir . 'admin/ajax/import', 'name' => 'What to import?',
							'resubmit' => false, 'show_status' => true, 'btn' => 'Process File'));

		$view->render_page();
		exit();
	}

}
