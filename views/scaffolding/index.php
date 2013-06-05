<?php

$table_selector = '';
foreach ($tables as $name){
	$table_selector .= "					<option value='$name'>$name</option>\n";
}
echo <<<HTML
<div class="page-header">
			<h1>$title</h1>
		</div>
		<div class="row show-grid">
			<div class="span8">This page allows you to manage tables of the database configured for this application.<br>
			Please select a table you would like to view, or select 'Insert' to insert a new row to the selected table.</div>
			<div class="span2">
				<select id="table_name">
					<option value="" selected="selected">Select Table</option>
$table_selector
					<option value="Create New Table">Create New Table</option>
				</select>
			</div>
			<div class="span1" style="width: 90px"><a id="insert_btn" class="btn btn-info" href="#"><i class="icon-list-alt icon-white"></i> Insert</a></div>
		</div>
		<br>
		<div id="content" class="content-wrapper hidden"></div>
		<script>
			window.addEvent('domready', function() {
				// Bind select box to populate the content div with results
				$('table_name').addEvent('change', function() {
					var selector = this.get("value"); // get value
					if (selector == 'Create New Table') window.location = '{$assets_dir}scaffolding/table/new';
					else {
						$('content').load('{$assets_dir}scaffolding/row/' + selector + '$token');
						if (selector) $('content').removeClass('hidden');
						else $('content').addClass('hidden');
					}
				});
				// Bind Insert button action
				$('insert_btn').addEvent('click', function() {
					var selector = $('table_name').get("value"); // get value of the select box
					window.location = '{$assets_dir}scaffolding/row/' + selector + '/new';
				});
			});
		</script>
HTML;
