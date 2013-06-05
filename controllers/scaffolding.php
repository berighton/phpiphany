<?php
/**
 * Scaffolding Controller used for database table management
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
 
class scaffolding extends controller {

	public function __construct(){
		parent::__construct();
	}

	public function index(){
		// Render the main page asking user to select or create a table
		global $view, $db;
		$view->title = 'Scaffolding Interface';
		$tables = $db->get_tables();
		if (!$tables or count($tables) < 1) pip_error('There are no tables in the database!');
		else {
			$view->use_jquery = false;
			$view->content = $view->load('scaffolding/index', array('title' => $view->title, 'tables' => $tables, 'assets_dir' => $view->assets_dir, 'token' => '?' . generate_token(true)));
		}
		$view->render_page();
		exit();
	}

	public function ajax() {
		global $config;

		// Make sure we do not render the page
		$config->direct_output = true;

		action_gatekeeper(true);

		global $db;

		if (!$config->input->parms) { echo json_encode(array('html' => true, 'error' => 'No parameters given')); exit; }
		else $page = $config->input->parms;

		// AJAX page is either 'table' or 'row'
		switch ($page[0]) {
			case 'row':
				// Action can be 'new', 'edit' or 'delete'
				switch ($page[1]) {
					case 'new':
						$error = $db->insert($page[2], $config->input->post, true) ? '' : 'Error updating record!';
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => true, 'error' => $error, 'debug' => $debug));
						break;
					case 'edit':
						$error = $db->update($page[2], $config->input->post, 'id = ?', array($page[3])) ? '' : 'Error updating record!';
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => true, 'error' => $error, 'debug' => $debug));
						break;
					case 'delete':
						$error = $db->delete($page[2], 'id = ?', array($page[3])) ? '' : 'Error deleting record!';
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => '<strong>Deleted successfully!</strong>', 'error' => $error, 'debug' => $debug));
						break;
					default:
						echo json_encode(array('html' => true, 'error' => 'Invalid AJAX action'));
						break;
				}
				break;
			case 'table':
				// Action can be 'new', 'truncate' or 'delete' for the table, or 'add', 'edit' or 'detete' for the table column
				switch ($page[1]) {
					case 'new':
						// Is this a new column for a table or a new table entirely?
						if (isset($page[2]) and $page[2] == 'column'){
							$post = $config->input->post;
							$field = new db_field();
							$field->name = $post['name'];
							$field->type = $post['type'];
							$field->size = $post['length'];
							$field->index = strtolower($post['index']);
							$field->index = $field->index == 'index'? true : $field->index;
							$field->null = isset($post['null']) and $post['null'] == 'Y' ? true : false;
							$field->ai = isset($post['ai']) and $post['ai'] == 'Y' ? true : false;
							$error = $db->update_column($page[3], '', $field) ? '' : 'Error creating column!';
						} else {
							// Convert POST parameters into db_field objects
							$fields = array();
							$i = 1;
							$post = $config->input->post;
							$len = explode('_', end(array_keys($post)));
							$len = substr($len[0], 3);
							while ($i <= $len) {
								$field = new db_field();
								$field->name = $post["col{$i}_name"];
								$field->type = $post["col{$i}_type"];
								$field->size = $post["col{$i}_length"];
								$field->index = strtolower($post["col{$i}_index"]);
								$field->null = isset($post["col{$i}_null"]) and $post["col{$i}_null"] == 'Y' ? true : false;
								$field->ai = isset($post["col{$i}_ai"]) and $post["col{$i}_ai"] == 'Y' ? true : false;
								$fields[] = $field;
								++$i;
							}
							$error = $db->create_table($post['tb_name'], $fields) ? '' : 'Error creating table!';
						}
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => true, 'error' => $error, 'debug' => $debug));
						break;
					case 'edit':
						$post = $config->input->post;
						$field = new db_field();
						$field->name = $post['name'];
						$field->type = $post['type'];
						$field->size = $post['length'];
						$field->null = isset($post['null']) and $post['null'] == 'Y' ? true : false;
						$field->ai = isset($post['ai']) and $post['ai'] == 'Y' ? true : false;
						$error = $db->update_column($page[2], $page[3], $field) ? '' : 'Error updating column';
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => true, 'error' => $error, 'debug' => $debug));
						break;
					case 'truncate':
						$error = $db->drop_table($page[2], true) ? '' : 'Error truncating table "' . $page[2] . '"';
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => '<strong>Emptied successfully!</strong>', 'error' => $error, 'debug' => $debug));
						break;
					case 'delete':
						if (isset($page[3])) $error = $db->drop_column($page[2], $page[3]) ? '' : 'Error deleting column';
						else $error = $db->drop_table($page[2]) ? '' : 'Error deleting table!';
						$debug = $config->debug ? $db->show_debug_console(true) : '';
						echo json_encode(array('html' => '<strong>Deleted successfully!</strong>', 'error' => $error, 'debug' => $debug));
						break;
					default:
						echo json_encode(array('html' => true, 'error' => 'Invalid AJAX action'));
						break;
				}
				break;
			default:
				echo json_encode(array('html' => true, 'error' => 'Invalid AJAX action'));
				break;
		}
	}

	/**
	 * Row action controller.
	 * Adds, Edits and Deletes a given row or displays a list of all the rows in a table
	 *
	 */
	public function row(){
		global $config, $view, $db;
		if (!$config->input->parms) pip_error('No parameters given');
		else $page = $config->input->parms;
		$view->use_jquery = false;
		switch($page[1]){
			case 'new':
				$columns = $db->get_table_columns($page[0]);
				//$view->content = printr($columns, 'columns', true);
				$view->title = 'Scaffolding Interface ::: Insert Table Row';
				$view->content = $view->load('scaffolding/row', array('action' => 'new', 'title' => $view->title, 'data' => $columns, 'table' => $page[0], 'breadcrums' => $page[0] . '/new'));
				$view->render_page();
				break;
			case 'edit':
				$view->title = 'Scaffolding Interface ::: Edit Table Row';
				$row = $db->get_data('SELECT * FROM `' . $page[0] . '` WHERE guid = ?', array($page[2]));
				if (!$row or count($row) < 1) {
					pip_error('There is no data for id="' . $page[2] . '" in the database!');
				} else {
					$view->content = $view->load('scaffolding/row', array('action' => 'edit', 'title' => $view->title, 'data' => $row, 'breadcrums' => $page[0] . '/' . $page[2]));
				}
				$view->render_page();
				break;
			case 'delete':
				$view->title = 'Scaffolding Interface ::: Delete Table Row';
				$row = $db->fetch_assoc($db->query('SELECT * FROM `' . $page[0] . '` WHERE id = ?', array($page[2])));
				if (!$row or count($row) < 1) {
					pip_error('There is no data for id="' . $page[2] . '" in the database!');
				} else {
					$view->content = $view->load('scaffolding/row', array('action' => 'delete', 'title' => $view->title, 'id' => $page[2], 'breadcrums' => $page[0] . '/' . $page[2]));
				}
				$view->render_page();
				break;
			// Load table details displaying column names and all the rows inside this table
			default:
				action_gatekeeper();
				// Make sure we do not render the page as this is an .load() call
				$config->direct_output = true;
				$db->query('SELECT * FROM `' . $config->input->parms . '`');
				$debug_stats = $config->debug ? $db->show_debug_console(true) : false;
				$columns = $db->get_columns();
				$data = $db->fetch_assoc_all();
				echo "		<h3>Table '<a href='scaffolding/table/view/{$config->input->parms}'>{$config->input->parms}</a>'</h3><br>\n";
				echo $view->load('scaffolding/list', array('action' => 'row', 'table_name' => $config->input->parms, 'columns' => $columns, 'data' => $data, 'debug_stats' => $debug_stats));
		}
		exit();
	}

	/**
	 * Table action controller.
	 * Creates, Truncates and Deletes a given table as well as edits, adds or drops any columns inside this table
	 *
	 */
	public function table(){
		global $config, $view, $db;
		if (!isset($config->input->parms) or !$config->input->parms) pip_error('No parameters given', $view->assets_dir . 'scaffolding');
		else $page = is_array($config->input->parms)? $config->input->parms : array($config->input->parms);
		$view->use_jquery = false;
		switch($page[0]){
			case 'new':
				$types = $db->datatype('', true);
				// If we want to create a new column inside this table or a new table altogether?
				if ($page[1] == 'column'){
					$view->title = 'Scaffolding Interface ::: Create New Column';
					$view->content = $view->load('scaffolding/table', array('action' => 'new/column', 'name' => $page[2], 'types' => $types, 'breadcrums' => 'Add a new column to table "' . $page[2] . '"'));
				} else {
					$view->title = 'Scaffolding Interface ::: Create New Table';
					$view->content = $view->load('scaffolding/table', array('action' => 'new', 'types' => $types, 'breadcrums' => 'Enter table name and add columns... as many as you want'));
				}
				$view->render_page();
				break;
			case 'edit':
				$types = $db->datatype('', true);
				$view->title = 'Scaffolding Interface ::: Edit Table Column';
				$column = $db->get_table_columns($page[1], $page[2]);
				if (!$column) {
					pip_error('Column name "' . $page[2] . '" does not exist in the in the "' . $page[1] . '" table!');
				} else {
					//$view->content = printr($column[$page[2]], 'col', true);
					$view->content = $view->load('scaffolding/table', array('action' => 'edit', 'types' => $types, 'data' => $column[$page[2]], 'breadcrums' => $page[1] . '/' . $page[2]));
				}
				$view->render_page();
				break;
			case 'truncate':
				$view->title = 'Scaffolding Interface ::: Truncate Table';
				$row = $db->fetch_assoc($db->query("SHOW TABLES LIKE '{$page[1]}'"));
				if (!$row or count($row) < 1) {
					pip_error('Table "' . $page[1] . '" does NOT exist in the database!');
				} else {
					$view->content = $view->load('scaffolding/table', array('action' => 'truncate', 'name' => $page[1]));
				}
				$view->render_page();
				break;
			case 'delete':
				// Is this a column delete or an entire table?
				if (isset($page[2])){
					$view->title = 'Scaffolding Interface ::: Delete Column';
					$column = $db->get_table_columns($page[1], $page[2]);
					if (!$column) {
						pip_error('Column name "' . $page[2] . '" does not exist in the in the "' . $page[1] . '" table!');
					} else {
						$view->content = $view->load('scaffolding/table', array('action' => 'delete', 'name' => $page[1], 'column' => $page[2]));
					}
				} else {
					$view->title = 'Scaffolding Interface ::: Delete Table';
					$row = $db->fetch_assoc($db->query("SHOW TABLES LIKE '{$page[1]}'"));
					if (!$row or count($row) < 1) {
						pip_error('Table "' . $page[1] . '" does NOT exist in the database!');
					} else {
						$view->content = $view->load('scaffolding/table', array('action' => 'delete', 'name' => $page[1]));
					}
				}
				$view->render_page();
				break;
			case 'view':
				$columns = $db->get_table_columns($page[1]);
				if (is_array($columns) and count($columns) > 0){
					$data = $columns;
					$columns = current($columns);
					$details = $db->fetch_obj($db->query("SHOW TABLE STATUS LIKE '{$page[1]}'"));
					$debug_stats = $config->debug ? $db->show_debug_console(true) : false;
					$view->title = 'Scaffolding Interface ::: View Table Details';
					$view->content .= $view->load('scaffolding/list', array('action' => 'table', 'table_name' => $page[1],
													'columns' => $columns, 'data' => $data, 'debug_stats' => $debug_stats, 'details' => $details));
					$view->render_page();
				} else pip_error('This table does not have any columns');
				break;
			// No other action supported. Redirect to the main page
			default:
				forward($view->assets_dir . 'scaffolding');
		}
		exit();
	}

}
