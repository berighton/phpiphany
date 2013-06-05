<?php
/**
 * HTML views class with static methods to generate HTML5 elements
 * Each method accepts an array $input which can also be a simple string representing element's name
 * List of input options is defined in the PHPDoc for each method
 *
 * Note that these elements are used for form generation and hence do not return the result
 * Instead it is stored in a local variable $form_content which is later read by a method 'form'
 * The proper way to use this structure is the following:
 *
 * global $view;
 * $view->title = 'My first page';
 * html::text(array('name' => 'first_name', 'id' => 'fname'));
 * html::text(array('name' => 'last_name', 'label' => 'last name', 'placeholder' => 'Enter your last name));
 * html::text(array('type' => 'email', 'name' => 'email', 'placeholder' => 'Enter your email address'));
 * html::text(array('type' => 'password', 'name' => 'password', 'placeholder' => 'Enter your password', 'help' => 'Password should be min 6 characters'));
 * html::combo(array('name' => 'country', 'options' => array('ca' => 'Canada', 'us' => 'United States'), 'size' => 3, 'firstempty' => false));
 * html::text('zip');
 * $view->content = html::form(array('title' => 'Create a new user', 'url' => $view->assets_dir . 'users/ajax/new', 'name' => 'New User Information'));
 * $view->render_page();
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

class html{
	static $form_content = '';

	/*******************************************************************************************************************
	 * Input combo box field
	 *
	 *
	 * $id              Element ID
	 * $name            Element Name
	 * $label           What should the label say that surrounds the element
	 * $size            Define the size of the element by specifying a boostrap class for a grid span (1-12)
	 * $class           CSS class
	 * $js              Assign additional javascript to the element (eg onclick="")
	 * $disabled        Control element disable state (true/false)
	 * $options         The contents of this combo box - the options array
	 * $selected        Define the selected element (by value)
	 * $firstempty      Should the first element in this dropdown be blank? (true/false)
	 * $multi           Is this a regular dropdown box or a multi-select (true/false)
	 * $rows            If it is a multi-select, you can define number of rows (height) it will contain
	 * $help            Additional help message (caption) shown below the input element
	 * $tab             Number of tabs are used for HTML formatting (default 7)
	 *
	 * @static
	 * @param $input
	 */
	static public function combo($input){
		// Check to see if only one element was passed in
		if (!is_array($input)){
			// Here we assume that the only string that was passed in was the name. We set the id to name
			$id = $name = $input;
		} else {
			extract($input);
		}

		$id = (isset($id) and $id)? $id : '';
		$name = (isset($name) and $name)? $name : '';
		$label = (isset($label) and $label)? $label : $name;
		$size = (isset($size) and $size)? 'span' . $size : 'span3';
		$class = (isset($class) and $class)? $class : '';
		$js = (isset($js) and $js)? $js : '';
		$disabled = (isset($disabled) and $disabled)? true : false;
		$options = (isset($options) and $options)? $options : array();
		$selected = (isset($selected) and $selected)? $selected : '';
		$firstempty = (isset($firstempty) and $firstempty === false)? false : true;
		$multi = (isset($multi) and $multi)? true : false;
		if ($multi) {
			$rows = (isset($rows) and $rows) ? $rows : '';
		}
		$help = (isset($help) and $help)? $help : '';
		$tab = (isset($tab) and $tab and is_integer($tab))? $tab : 7;

		// Generate tabs
		$tab_char = '	'; $tabs = '';
		for ($i = 0; $i < $tab; ++$i) {
			$tabs .= $tab_char;
		}

		// Generate the combo selector
		$output = $tabs . $tab_char . '<select class="' . $size;
		$output .= $class? " $class\"" : '"';
		$output .= $id? ' id="' . $id . '"' : '';
		$output .= $name? ' name="' . $name . '"' : '';
		$output .= $multi? ' multiple="multiple"' : '';
		$output .= (isset($rows) and $rows)? ' size="' . $rows . '"' : '';
		$output .= $js? ' ' . $js : '';
		$output .= $disabled? ' disabled="disabled"' : '';
		$output .= '>';

		// Generate combo options
		if ($options){
			$output .= "\n" . $tabs . $tab_char;
			$output .= $firstempty? $tab_char . '<option></option>' . "\n" . $tabs . $tab_char : '';
			// Check if this is an associative array
			$is_assoc = (array_key_exists(0, $options) and $options[0] != '')? false : is_assoc($options);
			foreach ($options as $value => $title){
				$value = $is_assoc? $value : strtolower($title);
				$selected_value = ($selected == $value)? ' selected' : '';
				$output .= $tab_char . "<option value=\"$value\"{$selected_value}>$title</option>";
				$output .= "\n" . $tabs . $tab_char;
			}
		}

		$output .= '</select>';

		if ($help) {
			$output .= "\n" . $tabs . $tab_char . '<p class="help-block">' . $help . '</p>';
		}

		$label4 = $name? $name : $id;
		$output = '<label class="control-label" for="' . $label4 . '">' . ucwords($label) . '</label>' . "
		" . $tabs . "<div class=\"controls\">\n" . $output . "\n" . $tabs . "</div>\n" . $tabs;

		self::$form_content .= $output;
	}

	/*******************************************************************************************************************
	 * View for drag and drop combo box
	 * Currently the module relies on JQuery, but will soon be replaced with an HTML5 equivalent
	 *
	 * The JS library is applied atop of a regular multiselect box
	 * with the ability to drag and drop elements from one column to another as well as search
	 *
	 *
	 * $id              Element ID
	 * $name            Element Name
	 * $label           What should the label say that surrounds the element
	 * $size            Define the size of the element by specifying a boostrap class for a grid span (1-12)
	 * $class           CSS class
	 * $js              Assign additional javascript to the element (eg onclick="")
	 * $disabled        Control element disable state (true/false)
	 * $options         The contents of this combo box - the options array
	 * $selected        Define the selected element (by value)
	 * $help            Additional help message (caption) shown below the input element
	 * $tab             Number of tabs are used for HTML formatting (default 7)
	 *
	 * @static
	 * @param $input
	 */
	static public function dragndrop($input){
		// Check to see if only one element was passed in
		if (!is_array($input)){
			// Here we assume that the only string that was passed in was the name. We set the id to name
			$id = $name = $input;
		} else {
			extract($input);
		}

		$id = (isset($id) and $id)? $id : '';
		$name = (isset($name) and $name)? $name : '';
		$label = (isset($label) and $label)? $label : $name;
		$size = (isset($size) and $size)? 'span' . $size : 'span4';
		$class = (isset($class) and $class)? $class : '';
		$js = (isset($js) and $js)? $js : '';
		$disabled = (isset($disabled) and $disabled)? true : false;
		$options = (isset($options) and $options)? $options : false;
		$selected = (isset($selected) and $selected)? $selected : '';
		$help = (isset($help) and $help)? $help : '';
		$tab = (isset($tab) and $tab and is_integer($tab))? $tab : 7;

		// Generate tabs
		$tab_char = '	'; $tabs = '';
		for ($i = 0; $i < $tab; ++$i) {
			$tabs .= $tab_char;
		}

		// This view requires jquery
		global $view;

		$view->use_jquery = true;
		array_push($view->custom_css, 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.19/themes/flick/jquery-ui.css', $view->assets_dir . 'css/ui.multiselect.css');
		array_push($view->custom_js, 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.19/jquery-ui.min.js', $view->assets_dir . 'js/ui.multiselect.js');

		// Activate the JS
		$output = "{$tabs}{$tab_char}<script type=\"text/javascript\">\n{$tabs}{$tab_char}{$tab_char}$(function(){\$(\".multiselect\").multiselect();});\n{$tabs}{$tab_char}</script>\n";

		// Generate the combo selector
		$output .= $tabs . $tab_char . '<select class="multiselect ' . $size;
		$output .= $class? " $class\"" : '"';
		$output .= $id? ' id="' . $id . '"' : '';
		$output .= $name? ' name="' . $name . '"' : '';
		$output .= $js? ' ' . $js : '';
		$output .= $disabled? ' disabled="disabled"' : '';
		$output .= ' multiple="multiple">';

		// Generate combo options
		if ($options and is_array($options)){
			$output .= "\n" . $tabs . $tab_char;
			foreach ($options as $value => $title){
				$selected_value = ($selected and is_array($selected) and in_array($value, $selected))? ' selected' : '';
				$output .= $tab_char . "<option value=\"$value\"{$selected_value}>$title</option>";
				$output .= "\n" . $tabs . $tab_char;
			}
		}

		$output .= '</select>';

		if ($help) {
			$output .= "\n" . $tabs . $tab_char . '<p class="help-block">' . $help . '</p>';
		}

		$label4 = $name? $name : $id;
		$output = '<label class="control-label" for="' . $label4 . '">' . ucwords($label) . '</label>' . "
		" . $tabs . "<div class=\"controls\">\n" . $output . "\n" . $tabs . "</div>\n" . $tabs;

		self::$form_content .= $output;
	}

	/*******************************************************************************************************************
	 * Hidden input field to compliment the creation of a form
	 *
	 *
	 * $name            Element Name
	 * $value           Set a value for this hidden element
	 * $tab             Number of tabs are used for HTML formatting (default 7)
	 *
	 * @static
	 * @param $input
	 */
	static public function hidden($input){
		// Check to see if only one element was passed in
		if (!is_array($input)){
			$name = $input;
		} else {
			extract($input);
		}

		$name = (isset($name) and $name)? $name : '';
		$value = (isset($value) and $value)? $value : '';
		$tab = (isset($tab) and $tab and is_integer($tab))? $tab : 7;

		// Generate tabs
		$tab_char = '	'; $tabs = '';
		for ($i = 0; $i < $tab; ++$i) {
			$tabs .= $tab_char;
		}

		if ($name){
			// Generate the hidden field
			$output = '<input type="hidden" name="' . $name . '"';
			$output .= $value? ' value="' . $value . '"' : '';
			$output .= ">\n$tabs";
			self::$form_content .= $output;
		}
	}

	/*******************************************************************************************************************
	 * The login form view that generates HTML login input form and returns to the caller
	 *
	 *
	 * $title           What should the heading of the login form say?
	 * $navbar          Is this a navbar element or a full page login?
	 * $token           CSRF token. It has to be generated in a controller rather than inside of a view
	 * $assets_dir      Assets directory which is usually equal to the full site URL path
	 *
	 * @static
	 * @param $input
	 * @return string
	 */
	static public function login($input){
		// Input has to be an array with three important elements: navbar, asset_dir and token
		extract($input);


		$title = (isset($title) and $title)? $title : 'Login Form';
		$navbar = (isset($navbar) and $navbar == false)? false : true;
		$token = (isset($token) and $token)? $token : generate_token(false, true);
		$assets_dir = (isset($assets_dir) and $assets_dir)? $assets_dir : '/';


		// If we are generating a login form for the navbar at the top
		if ($navbar){
			return <<<HTML
		<form action="{$assets_dir}authenticator/login" class="login-form" method="POST">
								<input class="input-small username" type="text" placeholder="Username" name="username">
								<input class="input-small" type="password" placeholder="Password" name="password">
								$token
								<button class="btn" type="submit">Sign in</button>
							</form>
HTML;

		}
		// Else generate a nicely formatted login page
		else {
			return <<<HTML

			<section id="full-page-login-form" class="login-form">
				<form action="{$assets_dir}authenticator/login" method="POST">
					<h1>$title</h1>
					<div>
						<input type="text" placeholder="Username" required="" id="username" name="username">
						<input type="password" placeholder="Password" required="" id="password" name="password">
						$token
					</div>
					<div>
						<input type="submit" value="Log in" class="btn btn-primary" style="float:left; width:150px; margin-left: 10px">
						<a href="{$assets_dir}lost_password">Lost your password</a> |
						<a href="{$assets_dir}users/create">Register</a>
					</div><br><br>
				</form>
			</section><!-- full-page-login-form -->

HTML;

		}
	}

	/*******************************************************************************************************************
	 * Long text (textarea) input field
	 *
	 *
	 * $id              Element ID
	 * $name            Element Name
	 * $label           What should the label say that surrounds the element
	 * $rte             Load a simple HTML textarea or a WYSIWYG TinyMCE rich text editor (RTE)?
	 * $size            Define the size of the element by specifying a boostrap class for a grid span (1-12)
	 * $class           CSS class
	 * $cols            Set number of columns (width)
	 * $rows            Set number of rows (height)
	 * $resize          Allow resizing of this element (true/false)
	 * $js              Assign additional javascript to the element (eg onclick="")
	 * $value           Default value to populate the element with (also used for edits)
	 * $placeholder     Default hint text to show (in grey) that disappears when clicked on
	 * $disabled        Control element disable state (true/false)
	 * $readonly        Make the element read-only (true/false)
	 * $help            Additional help message (caption) shown below the input element
	 * $tab             Number of tabs are used for HTML formatting (default 7)
	 *
	 * @static
	 * @param $input
	 */
	static public function longtext($input){
		// Check to see if only one element was passed in
		if (!is_array($input)){
			// Here we assume that the only string that was passed in was the name. We set the id to name
			$id = $name = $input;
		} else {
			extract($input);
		}

		$id = (isset($id) and $id)? $id : '';
		$name = (isset($name) and $name)? $name : '';
		$label = (isset($label) and $label)? $label : $name;
		$rte = (isset($rte) and $rte)? true : false;
		$size = (isset($size) and $size)? 'span' . $size : 'span3';
		$class = (isset($class) and $class)? $class : '';
		$cols = (isset($cols) and $cols)? $cols : 20;
		$rows = (isset($rows) and $rows)? $rows : 10;
		$resize = (isset($resize) and $resize === false)? ' style="resize: none;"' : '';
		$js = (isset($js) and $js)? $js : '';
		$value = (isset($value) and $value)? $value : '';
		$placeholder = (isset($placeholder) and $placeholder)? $placeholder : '';
		$disabled = (isset($disabled) and $disabled)? true : false;
		$readonly = (isset($readonly) and $readonly)? true : false;
		$help = (isset($help) and $help)? $help : '';
		$tab = (isset($tab) and $tab and is_integer($tab))? $tab : 7;

		// Generate tabs
		$tab_char = '	'; $tabs = '';
		for ($i = 0; $i < $tab; ++$i) {
			$tabs .= $tab_char;
		}

		// Generate the text field
		$output = $tabs . $tab_char . '<textarea class="' . $size;
		$output .= $class? " $class\"" : '"';
		if ($rte and !$id) {
			$id = $name;
		}
		$output .= $id? ' id="' . $id . '"' : '';
		$output .= $name? ' name="' . $name . '"' : '';
		$output .= $placeholder? ' placeholder="' . $placeholder . '"' : '';
		$output .= $js? ' ' . $js : '';
		$output .= ' cols="' . $cols . '"';
		$output .= ' rows="' . $rows . '"';
		$output .= $disabled? ' disabled="disabled"' : '';
		$output .= $readonly? ' readonly="readonly"' : '';
		$output .= $resize . '>';
		$output .= $value? htmlentities($value) : '';
		$output .= '</textarea>';

		if ($help) {
			$output .= "\n" . $tabs . $tab_char . '<p class="help-block">' . $help . '</p>';
		}

		$label4 = $name? $name : $id;

		// Apply the TinyMCE editor if $rte and $id or $name were set
		if ($rte and $label4) {
			global $config;
			$js =<<<JS

									<!-- TinyMCE -->
									<script type="text/javascript" src="{$config->site_url}js/tiny-mce/tiny_mce.js"></script>
									<script type="text/javascript">
										tinyMCE.init({
											// General options
											mode : "exact",
											elements : "$label4",

											theme : "advanced",
											plugins : "autolink,lists,advhr,advimage,advlink,inlinepopups,insertdatetime,preview,print,paste,fullscreen,visualchars,wordcount,advlist",

											// Theme options
											theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,formatselect,fontselect,fontsizeselect,forecolor,backcolor,|,print",
											theme_advanced_buttons2 : "justifyleft,justifycenter,justifyright,justifyfull,|,pastetext,pasteword,|,bullist,numlist,|,link,unlink,image,code,|,insertdate,inserttime,preview,|,charmap,advhr,fullscreen",
											theme_advanced_buttons3 : "",
											theme_advanced_toolbar_location : "top",
											theme_advanced_toolbar_align : "left",
											theme_advanced_statusbar_location : "bottom",
											theme_advanced_resizing : true,

											// Bind a save action on blur
											setup : function(ed) {
												ed.onInit.add(function(ed, evt) {
													tinyMCE.dom.Event.add(tinyMCE.isGecko ? ed.getDoc() : ed.getWin(), 'blur', function(e) {
														tinyMCE.triggerSave();
													});
												});
											},
										});
									</script>
									<!-- /TinyMCE -->
		$tabs
JS;

		} else {
			$js = '';
		}

		$output = '<label class="control-label" for="' . $label4 . '">' . ucwords($label) . '</label>' . "
		" . $tabs . "<div class=\"controls\">\n" . $output . "\n" . $tabs . "</div>\n" . $tabs;

		self::$form_content .= $js . $output;
	}

	/*******************************************************************************************************************
	 * Text input field
	 *
	 *
	 * $id              Element ID
	 * $name            Element Name
	 * $label           What should the label say that surrounds the element
	 * $type            Type of a textbox. Available types are: text, email, password, path, date (default text)
	 * $size            Define the size of the element by specifying a boostrap class for a grid span (1-12)
	 * $class           CSS class
	 * $maxlength       Set number of characters allowed to be inputted
	 * $js              Assign additional javascript to the element (eg onclick="")
	 * $value           Default value to populate the element with (also used for edits)
	 * $placeholder     Default hint text to show (in grey) that disappears when clicked on
	 * $disabled        Control element disable state (true/false)
	 * $readonly        Make the element read-only (true/false)
	 * $lookup          Bind a lookup AJAX call. Used to find duplicates or run a server-side validation (true/false)
	 * $help            Additional help message (caption) shown below the input element
	 * $tab             Number of tabs are used for HTML formatting (default 7)
	 *
	 * @static
	 * @param $input
	 */
	static public function text($input){
		// Check to see if only one element was passed in
		if (!is_array($input)){
			// Here we assume that the only string that was passed in was the name. We set the id to name
			$id = $name = $input;
		} else {
			extract($input);
		}

		$id = (isset($id) and $id)? $id : '';
		$name = (isset($name) and $name)? $name : '';
		$label = (isset($label) and $label)? $label : $name;
		$type = (isset($type) and $type)? $type : 'text';
		$size = (isset($size) and $size)? 'span' . $size : 'span3';
		$class = (isset($class) and $class)? $class : '';
		$maxlength = (isset($maxlength) and $maxlength)? $maxlength : 50;
		$js = (isset($js) and $js)? $js : '';
		$value = (isset($value) and $value)? $value : '';
		$placeholder = (isset($placeholder) and $placeholder)? $placeholder : '';
		$disabled = (isset($disabled) and $disabled)? true : false;
		$readonly = (isset($readonly) and $readonly)? true : false;
		$lookup = (isset($lookup) and $lookup)? $lookup : false;
		$help = (isset($help) and $help)? $help : '';
		$tab = (isset($tab) and $tab and is_integer($tab))? $tab : 9;

		// Name may be omitted if id was specified
		if (!$name) $name = $id;

		// Generate tabs
		$tab_char = '	'; $tabs = '';
		for ($i = 0; $i < $tab; ++$i) {
			$tabs .= $tab_char;
		}

		// Generate the text field
		$output = $tabs . $tab_char . '<input class="' . $size;
		$output .= ($type != 'text')? ' addon' : '';
		$output .= $class? " $class\"" : '"';
		$output .= ($type == 'password')? ' type="password"' : ' type="text"';
		$output .= $id? ' id="' . $id . '"' : '';
		$output .= $name? ' name="' . $name . '"' : '';
		$output .= $placeholder? ' placeholder="' . $placeholder . '"' : '';
		$output .= $value? ' value="' . $value . '"' : '';
		$output .= $js? ' ' . $js : '';
		$output .= $maxlength? ' maxlength="' . $maxlength . '"' : 'maxlength="50"';
		$output .= $disabled? ' disabled="disabled"' : '';
		$output .= $readonly? ' readonly="readonly"' : '';
		if ($lookup) {
			global $view, $config;
			array_push($view->custom_js, $view->assets_dir . "js/search.js");
			$url = "\"{$config->site_url}search/$lookup?\"";
			$lookup_id = "\"lookup_$lookup\"";
			$output .= ' onkeyup=\'search(this.value, ' . $url . ', ' . $lookup_id . '); return false\' autocomplete="off">';
			$output .= "\n" . $tabs . $tab_char . "<span id=$lookup_id class=\"ajax-lookup\"></span>";
		} else {
			$output .= '>';
		}

		// if type is email or password, prepend the input field with an icon
		if ($type == 'email') {
			$output = $tabs . $tab_char . '<div class="input-prepend"><span class="add-on"><i class="icon-envelope"></i></span>' . "\n" . $tab_char . $output . "\n" . $tabs . $tab_char . '</div>';
		} elseif ($type == 'password') {
			$output = $tabs . $tab_char . '<div class="input-prepend"><span class="add-on"><i class="icon-lock"></i></span>' . "\n" . $tab_char . $output . "\n" . $tabs . $tab_char . '</div>';
		} elseif ($type == 'date') {
			$output = $tabs . $tab_char . '<div class="input-prepend"><span class="add-on"><i class="icon-calendar"></i></span>' . "\n" . $tab_char . $output . "\n" . $tabs . $tab_char . '</div>';
		} elseif ($type == 'path') {
			$output = $tabs . $tab_char . '<div class="input-prepend"><span class="add-on"><i class="icon-file"></i></span>' . "\n" . $tab_char . $output . "\n" . $tabs . $tab_char . '</div>';
		}

		if ($help) {
			$output .= "\n" . $tabs . $tab_char . '<p class="help-block">' . $help . '</p>';
		}

		$label4 = $name? $name : $id;
		$output = '<label class="control-label" for="' . $label4 . '">' . ucwords($label) . '</label>' . "\n" .
		$tabs . "<div class=\"controls\">\n" . $output . "\n" . $tabs . "</div>\n" . $tabs;

		self::$form_content .= $output;
	}

	/*******************************************************************************************************************
	 * Upload input field with HTML5 drag and drop functionality
	 *
	 *
	 * $multi           Allow multiple files to be uploaded?
	 * $label           What should the label say that surrounds the element
	 * $path            If an upload path was specified, set it in session, otherwise a tmp folder will be used
	 * $size            Element width (uses same notation of span - 1 through 12), and then mapped to proper HTML sizes
	 * $filesize        Define the max upload file size (uses lazy size such as 10M or 100K)
	 *                  Keep in mind that this limit can easily be circumvented,
	 *                  hence using additional server-side checking is strongly advised
	 * $filetype        Limit the uploaded file to the specified file type:
	 *                   - Audio includes mp3, wav, etc
	 *                   - Video: mp4, wmv, avi, mpg, divx, etc
	 *                   - Image: jpg, png, tiff, gif, etc
	 *                   - Text: txt, csv, css, rtf, html
	 *                   - Document: doc, docx, pdf, xls, odt, ppt, ods, etc
	 *                   - Archive: zip, rar, tar, tgz, gz, bzip, 7z, etc
	 * $ext             To narrow down the filtering even further, a file extension might be specified (as a string or an array of strings)
	 * $dragndrop       Enable HTML5 drag and drop field (true/false)
	 * $js              Assign additional javascript to the element (eg onclick="")
	 * $btn             Specify a name that appears on the upload button
	 * $disabled        Control element disable state (true/false)
	 * $help            Additional help message (caption) shown below the input element
	 *
	 * @static
	 * @param $input
	 */
	static public function upload($input){
		extract($input);

		// id and name of the input field are automatically assigned
		$id = $name = 'fileselect';
		$multi = (isset($multi) and $multi)? true : false;
		$label = (isset($label) and $label)? $label : $name;
		if (isset($path) and $path) {
			$_SESSION['pip_file_owner_type'] = $path;
		}

		$size = (isset($size) and $size)? $size : 3;
		// Since CSS classes do not apply to file select inputs, we do a simple mapping of span size to HTML sizes
		$size = ($size/6*66)-(10-$size)*1.5;

		$filesize = (isset($filesize) and $filesize)? $filesize : '2M';
		ini_set('upload_max_filesize', $filesize);
		ini_set('post_max_size', $filesize);
		// Convert the file size to bytes in order for AJAX uploader to proccess it correctly
		if (!is_numeric($filesize)){
			if ($pos = stripos($filesize, 't') and $pos !== false) {
				$filesize = (int)substr($filesize, 0, $pos) * 1024 * 1024 * 1024 * 1024;
			} elseif ($pos = stripos($filesize, 'g') and $pos !== false) {
				$filesize = (int)substr($filesize, 0, $pos) * 1024 * 1024 * 1024;
			} elseif ($pos = stripos($filesize, 'm') and $pos !== false) {
				$filesize = (int)substr($filesize, 0, $pos) * 1024 * 1024;
			} elseif ($pos = stripos($filesize, 'k') and $pos !== false) {
				$filesize = (int)substr($filesize, 0, $pos) * 1024;
			} elseif ($pos = stripos($filesize, 'b') and $pos !== false) {
				$filesize = (int)substr($filesize, 0, $pos);
			} else {
				$filesize = intval($filesize);
			}
		}
		$filetype = isset($filetype)? strtolower($filetype) : '';
		if (isset($ext)) {
			$ext = is_array($ext) ? strtolower(implode(',', $ext)) : strtolower($ext);
		} else {
			$ext = '';
		}
		$dragndrop = (isset($dragndrop) and $dragndrop == false)? '' : "\n								<div id=\"filedrag\">or drop files here</div>";
		$js = (isset($js) and $js)? $js : '';
		$btn = (isset($btn) and $btn)? $btn : 'Upload File';
		$disabled = (isset($disabled) and $disabled)? true : false;
		$help = (isset($help) and $help)? $help : '';

		// Make some adjustments if multiple files are ought to be uploaded
		$m = '';
		if ($multi){
			$name .= '[]';
			$btn .= 's';
			$m = ' multiple="multiple"';
		}

		// Carry forward some variables to be used in upload error checking
		$_SESSION['pip_file_size'] = $filesize;
		$_SESSION['pip_file_type'] = $filetype;
		$_SESSION['pip_file_ext'] = $ext;

		// Generate the input field
		$output = "<input type=\"file\" id=\"$id\" name=\"$name\" size=\"$size\"$m";
		$output .= $disabled? ' disabled="disabled"' : '';
		$output .= $js? ' ' . $js : '';
		$output .= '>';

		$label4 = $name? $name : $id;
		$label = ucwords($label);

		//<progress class="progress-success bar" min="0" max="100" value="0">0% complete</progress>
		self::$form_content .= <<<HTML

									<input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="$filesize">
									<label class="control-label" for="$label4">$label</label>
									<div class="controls">
										$output{$dragndrop}
										<div id="progress" class="progress hidden">
											<div id="bar" class="bar"></div>
										</div>
										<div id="messages">$help</div>
									</div>

HTML;

		// Include JS framework independent script to handle AJAX uploads as well as pass the current URL where to POST
		global $view, $config, $router;
		$path = $config->site_url . $router->controller . '/upload';
		array_push($view->custom_js, $view->assets_dir . "js/upload.php?m=$multi&amp;s=$filesize&amp;t=$filetype&amp;e=$ext&amp;p=$path");
	}

	/*******************************************************************************************************************
	 * The form view that generates an HTML form binding AJAX action by default
	 *
	 *
	 * $ajax            Is this an AJAX form or standard HTML POST? (true/false)
	 * $title           Title that goes on top of the form in <h1>
	 * $url             Action URL where to POST
	 * $name            Name of the form
	 * $content         The actual content of the form with all the text fields, combo boxes, radio buttons, etc
	 *                  If other methods of this class were used, the content is taken from self::$form_content
	 *                  If, however 'content' was explicitly passed in, overwrite the local variable with it
	 * $btn             Main submit button text
	 * $submit          With this wrapper, you can fully customize the button including CSS class and button type
	 * $cancel          Print a cancel button that redirects to the default controller page or a custom button code
	 * $delete          Enable the delete button for entity deletion (true/false)
	 * $size            Define the size of the form by specifying a boostrap grid class (default 7)
	 * $js              Include additional javascript in the form (must use the same JS library as defined in settings)
	 * $action          What does this form do logically? Be default it outputs "Saved". You can make it say "processed", "submitted", etc
	 * $focus           Focus on a particular element upon form load (requires element id)
	 * $resubmit        Allow form resubmits (multiple times clicking on submit)? (true/false)
	 *                  Sometimes this has to be prevented especially when dealing with payments
	 * $redirect        In case the user needs to be redirected to another page upon successful form submit (accepts url)
	 * $show_status     In most cases, the form result will be returned via AJAX in a form of a simple success/error snippet
	 *                  However if the process you're trying to execute is CPU/DB intensive,
	 *                  you might wanna show the user, the status of a task as it processes
	 *                  This is accomplished by launching a modal window on submit click which shows the server results via ob_flush()
	 *                  See import() method under admin controller to get an idea of how it's done
	 * $help            Additional help message (caption) shown below the input element
	 * $tab             Number of tabs are used for HTML formatting (default 7)
	 *
	 * @static
	 * @param $input
	 * @return string
	 */
	static public function form($input){
		// Form has to have certain mandatory parameters
		extract($input);

		global $view, $router;

		// Get the current token
		$token = generate_token(false, true);
		// Make the form id unique to allow for multiple forms on the same page
		$ts = microtime(true);

		$ajax = (isset($ajax) and $ajax == false)? false : true;
		$title = (isset($title) and $title)? $title : 'Form';
		$url = (isset($url) and $url)? $url : getenv('REQUEST_URI');
		$name = (isset($name) and $name)? "<legend>$name</legend>" : '';
		if (!isset($content) or !$content){
			$content = (self::$form_content)? self::$form_content : '<label class="control-label"><em>No form input specified</em></label>';
		}
		$btn = (isset($btn) and $btn)? $btn : 'Save changes';
		$submit = (isset($submit) and $submit)? $submit : '<button type="submit" id="form_submit" class="btn btn-inverse" >' . $btn . '</button>';
		$cancel = (isset($cancel) and $cancel)? $cancel : '<button id="form_cancel" class="btn">Cancel</button>';
		$delete = (isset($delete) and $delete)? "\n							<a href=\"$delete\" id=\"form_delete\" class=\"btn btn-danger\">Delete</a>" : '';
		$size = (isset($size) and $size)? $size : 'span7';
		$js = (isset($js) and $js)? $js : '';
		$action = (isset($action) and $action)? $action : 'Sav';
		$focus = (isset($focus) and $focus)? $view->use_jquery? "\n\n					$('#$focus').focus();" : "\n\n					$('$focus').focus();" : '';
		if (!isset($resubmit) or $resubmit !== false) {
			$resubmit = "\n									";
			$resubmit .= $view->use_jquery? "$('#form_submit').attr('disabled',false);" : "$('form_submit').set('disabled',false);";
		} else {
			$resubmit = '';
		}
		if (isset($redirect)){
			$redirect = ($redirect === true)? $view->assets_dir . $router->controller : $redirect;
			$sys_redirect = $redirect;
			if ($view->use_jquery){
				$redirect = "\n									setTimeout(function(){ window.location = '$redirect'; }, 2000);";
			} else {
				$redirect = "\n									(function(){ window.location = '$redirect'; }).delay(2000);";
			}
		} else {
			$redirect = '';
			$sys_redirect = $view->assets_dir . $router->controller;
		}

		$ajax_script = false;

		// Load modal window to show status?
		if (isset($show_status) and $show_status === true) {
			// Load the modal CSS and JS
			array_push($view->custom_css, $view->assets_dir . 'css/tinybox.css');
			array_push($view->custom_js, $view->assets_dir . 'js/tinybox.js');
			if ($view->use_jquery){
				$ajax_script =<<<JS

						<script>
							// Show the process updates in a modal window by intercepting the form submit
							$('#form_$ts').submit(function(e) {
								e.preventDefault();
								// Disable the submit button to avoid accidental clicks during an AJAX call
								$('#form_submit').attr('disabled',true);

								TINY.box.show({url:"$url",post:$(this).serialize(),width:500,height:300,opacity:20,topsplit:3});{$resubmit}{$redirect}
							});
						</script>

JS;
			} else {
				$ajax_script =<<<JS

						<script>
							// Show the process updates in a modal window by intercepting the form submit
							$('form_$ts').addEvent('submit', function(e){
								e.stop();
								// Disable the submit button to avoid accidental clicks during an AJAX call
								$('form_submit').set('disabled',true);

								TINY.box.show({url:"$url",post:$(this).toQueryString(),width:500,height:300,opacity:20,topsplit:3});{$resubmit}{$redirect}
							});
						</script>

JS;
			}
		}


		// Even though the default form POST method is AJAX, make sure we can safely fallback to legacy technologies
		if ($ajax){
			// Generate an AJAX bind depending on JS framework used
			if (!$ajax_script and $view->use_jquery){
				$ajax_script =<<<JS

						<script>
							var status = $('#form_div_$ts');

							$('#form_$ts').submit(function(e) {
								// Prevent the submit default action
								e.preventDefault();

								// Disable the submit button to avoid accidental clicks during an AJAX call
								$('#form_submit').attr('disabled',true);

								// Get the div where to output response and populate it with the spinning loading indicator
								status.html('Processing... <img src="{$view->assets_dir}images/processing.gif" class="right" alt="Loading">').removeClass('hidden').css('background', '#FFFFCC');

								// Define effects for the status box to disappear
								//var fx = new Fx.Tween(status, {property: 'opacity', duration: 1000, transition: Fx.Transitions.Quart.easeOut});

								// Initiate a new AJAX request, expecting JSON to be returned
								$.ajax({
									type: 'post',
									url: '$url',
									data: $(this).serialize(),
									dataType: "json",
									error: function(){
										status.html('<strong class="red">AJAX submit error</strong>').css('background', '#f2dede');
									},
									success: function(data){
										if (data.error.length > 0){
											status.html('<strong class="red">' + data.error + '</strong>').css('background', '#f2dede');{$resubmit}
										} else if (data.html == true){
											status.html('{$action}ed!').css('background', '#dff0d8');
											//fx.start.pass([1,0], fx).delay(1000);{$resubmit}{$redirect}
										} else if (data.html.length > 1) {
											status.html(data.html).css('background', '#dff0d8');{$resubmit}{$redirect}
										}

										// Inject debugging information, if any
										if (data.debug && data.debug.length > 0) {
											// Increase the debugger query counter
											var q = $('#db-mini').html();
											var q1 = q.split('\">');
											var q2 = q1[1].split('</a>');
											q = q2[0].split(' / ');
											q2 = q[1];
											q1 = parseInt(q[0]) + 1;
											$('#db-mini').html('<a href="javascript:db_toggle(\'console\')">' + q1 + ' / ' + q2 + '</a>');

											// Since the debug information only shows errors, we need to create the 'errors' tab
											$('#db').find('ul').prepend($('<li />').html('<a href="javascript:db_toggle(\'db-errors\')">errors: <span>1</span></a>'));
											$('#db').append($('<div />').attr('id', 'db-errors').html(data.debug));
										}

									}
								});
							});

							// Set the status div opacity back to 1 if user clicks submit again
							$('#form_submit').click(function(){
								if (status.css('opacity') == 0){
									status.css({'opacity' : '1', 'background' : ''}).addClass('hidden');
								}
							});

							// Bind cancel button to go back to the controller main page
							$('#form_cancel').click(function(e){
								e.preventDefault();
								window.location = '$sys_redirect';
							});{$focus}{$js}

						</script>

JS;
			} elseif (!$ajax_script) {
				$ajax_script =<<<JS

						<script>
							var status = $('form_div_$ts');

							$('form_$ts').addEvent('submit', function(e){
								// Prevent the submit default action
								e.stop();

								// Disable the submit button to avoid accidental clicks during an AJAX call
								$('form_submit').set('disabled',true);

								// Get the div where to output response and populate it with the spinning loading indicator
								status.set('html', 'Processing... <img src="{$view->assets_dir}images/processing.gif" class="right" alt="Loading">').removeClass('hidden').setStyle('background', '#FFFFCC');

								// Define effects for the status box to disappear
								var fx = new Fx.Tween(status, {property: 'opacity', duration: 1000, transition: Fx.Transitions.Quart.easeOut});

								// Initiate a new AJAX request, expecting JSON to be returned
								var req = new Request.JSON({
									method: 'post',
									url: '$url',
									data: this,
									onFailure: function(){
										status.set('html', '<strong class="red">AJAX submit error</strong>').setStyle('background', '#f2dede');
									},
									onSuccess: function(data){
										if (data.error.length > 0){
											status.set('html', '<strong class="red">' + data.error + '</strong>').setStyle('background', '#f2dede');{$resubmit}
										} else if (data.html == true){
											status.set('html', '{$action}ed!').setStyle('background', '#dff0d8');
											fx.start.pass([1,0], fx).delay(1000);{$resubmit}{$redirect}
										} else if (data.html.length > 1) {
											status.set('html', data.html).setStyle('background', '#dff0d8');{$resubmit}{$redirect}
										}

										// Inject debugging information, if any
										if (data.debug && data.debug.length > 0) {
											// Increase the debugger query counter
											var q = $('db-mini').get('html');
											var q1 = q.split('\">');
											var q2 = q1[1].split('</a>');
											q = q2[0].split(' / ');
											q2 = q[1];
											q1 = parseInt(q[0]) + 1;
											$('db-mini').set('html', '<a href="javascript:db_toggle(\'console\')">' + q1 + ' / ' + q2 + '</a>');

											// Since the debug information only shows errors, we need to create the 'errors' tab
											$('db').getElement('ul').grab(new Element('li', {'html': '<a href="javascript:db_toggle(\'db-errors\')">errors: <span>1</span></a>'}), 'top');
											$('db').adopt(new Element('div#db-errors', {'html': data.debug}));
										}
									}
								}).send();
							});

							// Set the status div opacity back to 1 if user clicks submit again
							$('form_submit').addEvent('click', function(e){
								if (status.get('opacity') == 0){
									status.setStyle('opacity', 1).setStyle('background', '').addClass('hidden');
								}
							});

							// Bind cancel button to go back to the controller main page
							$('form_cancel').addEvent('click', function(e){
								e.stop();
								window.location = '$sys_redirect';
							});{$focus}{$js}
						</script>

JS;
			}
			$form_action = '';
		} else {
			$form_action = ' action="' . $url . '"';
			$ajax_script = '';
		}



		return <<<HTML

				<div class="page-header">
					<h1>$title</h1>
				</div>
				<div class="row show-grid">
					<div class="$size content-wrapper">
						<form id="form_$ts" method="POST" class="form-horizontal"$form_action>
							<fieldset>
								$name
								<div class="control-group">
									$content
								</div>
								<div id="form_div_$ts" class="ajax-status hidden"></div>
								<div class="form-actions">
									$submit
									$cancel{$delete}
								</div>
							</fieldset>
							$token
						</form>
						$ajax_script
					</div>
				</div>
				<br>

HTML;

	}
}
