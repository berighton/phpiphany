<?php
/**
 * Table view
 * Used along with scaffolding controller
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package views/scaffolding
 * @since 1.0
 *
 */

if (!isset($action) and !$action) $body = '<span class="span4"><strong class="red centered">View can not be loaded due to insufficient parameters</strong></span>';
else {
	global $view;
	// Populate the generic form for new table AND new column views
	if (strpos($action, 'new') !== false or strpos($action, 'edit') !== false){
		$c_name = $c_type = $c_len = $c_index = $c_index_msg = $c_null = $c_ai = null;
		switch($action){
			case 'new/column':
				$col_heading = 'New Column';
				$prefix = '';
				break;
			// Populate the form with column values
			case 'edit':
				$col_heading = 'Edit Column';
				$prefix = '';
				$c_name = ' value="' . $data['Field'] .'"';
				// If the type has length associated with it
				if (strpbrk($data['Type'], '(')){
					$c_type = explode('(', substr($data['Type'], 0, -1));
					$c_len = ' value="' . $c_type['1'] .'"';
					$c_type = strtoupper(trim($c_type['0']));
				} else {
					$c_len = '';
					$c_type = strtoupper(trim($data['Type']));
				}
				$c_null = $data['Null'] == 'YES'? ' checked="yes"' : '';
				$c_ai = $data['Extra'] == 'auto_increment'? ' checked="yes"' : '';
				$c_index = ' disabled="disabled"';
				$c_index_msg = "\n									<p class='help-block'>You cannot edit index on existing column</p>";
				break;
			default:
				$col_heading = 'Column 1';
				$prefix = 'col1_';
		}
		$options = '';
		foreach ($types as $col_group => $column_type) {
			if (is_array($column_type)) {
				$options .= '										<optgroup label="' . htmlspecialchars($col_group) . "\">\n";
				foreach ($column_type as $col_group_type) {
					if ($col_group_type == $c_type) $options .= "											<option value='$col_group_type' selected='selected'>$col_group_type</option>\n";
					else $options .= "											<option value='$col_group_type'$c_type>$col_group_type</option>\n";
				}
				$options .= "										</optgroup>\n";
				continue;
			}

			if ($column_type == $c_type) $options .= "										<option value='$column_type' selected='selected'>$column_type</option>\n";
			else $options .= "										<option value='$column_type'>$column_type</option>\n";
		}
		$column = '<label class="control-label"><strong class="btn-large">' . $col_heading . '</strong></label><div class="clear"></div>
								<label class="control-label" for="' . $prefix . 'name">Name</label>
								<div class="controls">
									<input class="input-large" id="' . $prefix . 'name" name="' . $prefix . 'name" type="text"' . $c_name . '>
								</div>
								<label class="control-label" for="' . $prefix . 'type">Type</label>
								<div class="controls">
									<select class="input-large" id="' . $prefix . 'type" name="' . $prefix . 'type">
										<option value="" selected="selected">Select Type</option>
' . $options . '
									</select>
								</div>
								<label class="control-label" for="' . $prefix . 'length">Length</label>
								<div class="controls">
									<input class="input-large" id="' . $prefix . 'length" name="' . $prefix . 'length" type="text"' . $c_len . '>
								</div>
								<label class="control-label" for="' . $prefix . 'index">Index</label>
								<div class="controls">
									<select class="input-large" id="' . $prefix . 'index" name="' . $prefix . 'index"' . $c_index . '>
										<option value="" selected="selected">---------------</option>
										<option value="PRIMARY">PRIMARY</option>
										<option value="UNIQUE">UNIQUE</option>
										<option value="INDEX">INDEX</option>
										<option value="TEXT">FULLTEXT</option>
									</select>' . $c_index_msg . '
								</div>
								<label class="control-label" for="' . $prefix . 'null">Allow Null</label>
								<div class="controls">
									<input type="checkbox" name="' . $prefix . 'null" value="Y"' . $c_null . '>
									<div class="clear"></div>
								</div>
								<label class="control-label" for="' . $prefix . 'ai">Autoincrement</label>
								<div class="controls">
									<input type="checkbox" name="' . $prefix . 'ai" value="Y"' . $c_ai . '>
									<div class="clear"></div>
								</div>' . "\n";
	}

	// Continue with the regular action redirect
	switch ($action){
		// Generate fields for the new table
		case 'new':
			$content = '<label class="control-label" for="Table Name">Table Name</label>
							<div class="controls">
								<input class="input-large" id="tb_name" name="tb_name" type="text">
							</div><p>
							<label class="control-label"></label>
							<div class="controls">
								<a id="add_column" class="btn btn-success" href="#"><i class="icon-list-alt icon-white"></i> Add Column</a>
								<a id="del_column" class="btn btn-danger" href="#"><i class="icon-remove icon-white"></i> Delete Column</a>
							</div></p>
						</div>
						<div id="columns" class="control-group">
							<div id="col1" class="control-group">
								' . "\n";
			$content .= $column . "							</div>\n";
			$js = "\n
					// Table Add and Remove buttons
					var i = 1;
					$('add_column').addEvent('click', function(e){
						var col = $('col' + i).clone();
						var reg = new RegExp(i,'g');
						++i;
						col = col.set('id', 'col' + i).set('html', col.get('html').replace(reg, i));
						$('columns').adopt(col);
					});
					$('del_column').addEvent('click', function(e){
						if (i > 1) {
							$('col' + i).dispose();
							--i;
						}
					});";
			$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/table/new', 'name' => $breadcrums, 'content' => $content, 'action' => 'Insert', 'focus' => 'tb_name', 'js' => $js));
			break;
		// Same fields as above, but for one column only
		case 'new/column':
			$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/table/new/column/' . $name, 'name' => $breadcrums, 'content' => $column, 'action' => 'Insert'));
			break;
		case 'edit':
			$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/table/edit/' . $breadcrums, 'name' => $breadcrums, 'content' => $column));
			break;
		case 'truncate':
			$content = "<h4>Are you sure you want to empty table '$name'?</h4><br>By pressing 'Empty Table' you aknowledge that the contents of this table will be discarded, but columns will be intact.<br>This action <u>cannot</u> be undone. <br>Are you sure?";
			$submit = '<button type="submit" id="form_submit" class="btn btn-warning"><i class="icon-trash icon-white"></i> Empty Table</button>';
			$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/table/truncate/' . $name,
													'content' => $content, 'submit' => $submit, 'size' => 'span4', 'resubmit' => false, 'redirect' => true));
			break;
		case 'delete':
			// Is this a column delete or entire table?
			if (isset($column) and $column) {
				$content = "<h4>Are you sure you want to delete column <br>'$column' from table '$name'?</h4><br>This action <u>cannot</u> be undone.";
				$submit = '<button type="submit" id="form_submit" class="btn btn-danger"><i class="icon-remove icon-white"></i> Delete Table</button>';
				$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/table/delete/' . $name . '/' . $column,
													'content' => $content, 'submit' => $submit, 'size' => 'span4', 'resubmit' => false, 'redirect' => true));
			} else {
				$content = "<h4>Are you sure you want to delete table '$name'?</h4><br>By pressing 'Delete Table' you aknowledge that the entire table will be deleted from the database including all of its rows, indexes and privileges. This action <u>cannot</u> be undone. <br>Are you sure?";
				$submit = '<button type="submit" id="form_submit" class="btn btn-danger"><i class="icon-remove icon-white"></i> Delete Table</button>';
				$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/table/delete/' . $name,
													'content' => $content, 'submit' => $submit, 'size' => 'span4', 'resubmit' => false, 'redirect' => true));
			}
			break;
		case 'delete/column':
			$content = "<h4>Are you sure you want to delete table '$name'?</h4><br>By pressing 'Delete Table' you aknowledge that the entire table will be deleted from the database including all of its rows, indexes and privileges. This action <u>cannot</u> be undone. <br>Are you sure?";
			$submit = '<button type="submit" id="form_submit" class="btn btn-danger"><i class="icon-remove icon-white"></i> Delete Table</button>';
			$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/table/delete/' . $name,
													'content' => $content, 'submit' => $submit, 'size' => 'span4', 'resubmit' => false, 'redirect' => true));
			break;
		default:
			$content = '<span class="span4"><strong class="red centered">View can not be loaded. Controller was supplied with incorrect parameters</strong></span>';
			$body = html::form(array('name' => 'Error', 'content' => $content));
	}
}


echo $body;


