<?php
/**
 * Row view
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
	switch ($action){
		case 'new':
			$content = '';
			foreach ($data as $field => $value) {
				if ($field != 'id') {
					$content .= '<label class="control-label" for="' . $field . '">' . ucwords($field) . '</label>' . "\n							<div class=\"controls\">\n";
					if (stristr($field, 'email')) {
						$content .= '								<div class = "input-prepend"><span class = "add-on">@</span>
								<input class="span3" style="width: 233px" id="' . $field . '" name="' . $field . '" type="text"></div>';
					} else {
						$content .= '								<input class="span3" id="' . $field . '" name="' . $field . '" type="text">';
					}
					$content .= "\n							</div>\n							";
				}
			}
			$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/row/new/' . $table, 'name' => $breadcrums, 'content' => $content, 'action' => 'Insert'));
			break;
		case 'edit':
			$content = '';
			foreach ($data as $field => $value) {
				if ($field != 'id') {
					$content .= '<label class="control-label" for="' . $field . '">' . ucwords($field) . '</label>' . "\n							<div class=\"controls\">\n";
					if (stristr($field, 'email')) {
						$content .= '								<div class = "input-prepend"><span class = "add-on">@</span>
								<input class="span3" style="width: 233px" id="' . $field . '" name="' . $field . '" type="text" value="' . $value . '"></div>';
					} else {
						$content .= '								<input class="span3" id="' . $field . '" name="' . $field . '" type="text" value="' . $value . '">';
					}
					$content .= "\n							</div>\n							";
				}
			}
			$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/row/edit/' . $breadcrums, 'name' => $breadcrums, 'content' => $content));
			break;
		case 'delete':
			$content = "<h4>Are you sure you want to delete row with id '$id'?</h4><br>This action <u>cannot</u> be undone.";
			$submit = '<button type="submit" id="form_submit" class="btn btn-danger"><i class="icon-remove icon-white"></i> Delete</button>';
			$body = html::form(array('url' => $view->assets_dir . 'scaffolding/ajax/row/delete/' . $breadcrums, 'name' => $breadcrums,
													'content' => $content, 'submit' => $submit, 'size' => 'span4', 'resubmit' => false, 'redirect' => true));
			break;
		default:
			$content = '<span class="span4"><strong class="red centered">View can not be loaded. Controller was supplied with incorrect parameters</strong></span>';
			$body = html::form(array('name' => 'Error', 'content' => $content));
	}
}


echo $body;




