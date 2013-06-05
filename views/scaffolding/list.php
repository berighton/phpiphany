<?php

global $view;


if ($action == 'table') {

	$arl = readable_size($details->Avg_row_length);
	$dl = readable_size($details->Data_length);
	$il = readable_size($details->Index_length);
	$df = readable_size($details->Data_free);
	echo <<<HTML
<div class="page-header">
			<h1>$view->title</h1>
		</div>
		<div class="show-grid">
			<span class="span6"><h3>Table '$table_name'</h3></span>
			<span class="span5"><a href="{$view->assets_dir}scaffolding/table/new/column/$table_name" class="btn btn-success" style="margin-left: 122px" title="Create a new field"><i class="icon-asterisk icon-white"></i> New Column</a>
			<a href="{$view->assets_dir}scaffolding/table/truncate/$table_name" class="btn btn-warning" title="Flush the data inside this table"><i class="icon-trash icon-white"></i> Empty Table</a>
			<a href="{$view->assets_dir}scaffolding/table/delete/$table_name" class="btn btn-danger right" title="Completely delete this table from the database"><i class="icon-remove icon-white"></i> Delete Table</a>
			</span>
			<br><br>
			<div class="content-wrapper">
				<strong>How database sees this table</strong><br><br>
				<table class="table table-striped table-bordered table-condensed">
					<thead>
					<tr>
						<th>Engine</th>
						<th>Version</th>
						<th>Rows</th>
						<th>Avg row length</th>
						<th>Data length</th>
						<th>Index length</th>
						<th>Data free</th>
						<th>Auto increment</th>
						<th>Create time</th>
						<th>Collation</th>
						<th>Comment</th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td>$details->Engine</td>
						<td>$details->Version</td>
						<td>$details->Rows</td>
						<td>$arl</td>
						<td>$dl</td>
						<td>$il</td>
						<td>$df</td>
						<td>$details->Auto_increment</td>
						<td>$details->Create_time</td>
						<td>$details->Collation</td>
						<td>$details->Comment</td>
					</tr>
					</tbody>
				</table>
			</div>
			<br><br>
			<div class="content-wrapper">
				<strong>Column details</strong><br><br>

HTML;
}


$th = '';
foreach ($columns as $name => $val){
	$th .= "				<th>" . ucwords($name) . "</th>\n";
}
$th .= "				<th style='min-width: 75px'>Action</th>\n";

$body = '';
foreach ($data as $tr){
	$body .= "			<tr>\n";
	foreach ($tr as $td){
		$body .= "				<td>$td</td>\n";
	}
	if ($action == 'table') $body .= "				<td><a href='{$view->assets_dir}scaffolding/table/edit/$table_name/{$tr['Field']}'>Edit</a> | <a href='{$view->assets_dir}scaffolding/table/delete/$table_name/{$tr['Field']}'>Delete</a></td>\n";
	else $body .= "				<td><a href='{$view->assets_dir}scaffolding/row/$table_name/edit/{$tr['guid']}'>Edit</a> | <a href='{$view->assets_dir}scaffolding/row/$table_name/delete/{$tr['guid']}'>Delete</a></td>\n";
	$body .= "			</tr>\n";
}
if (!$body) {
	$cols = count($columns) + 1;
	$body = "			<tr><td colspan='$cols' style='text-align: center'><em>Table is empty</em></td></tr>\n";
}

$debug_stats = $debug_stats? "		<script>
			$('query_stats').adopt(new Element('tr', {'html': '$debug_stats'}));
			var q = $('db-mini').get('html');
			var q1 = q.split('\">');
			var q2 = q1[1].split('</a>');
			q = q2[0].split(' / ');
			q2 = q[1];
			q1 = parseInt(q[0]) + 1;
			$('db-mini').set('html', '<a href=\"javascript:db_toggle(\\'console\\')\">' + q1 + ' / ' + q2 + '</a>');
		</script>" : '';

echo <<<HERE

		<table class="table table-striped table-bordered table-condensed">
			<thead>
			<tr>
$th
			</tr>
			</thead>
			<tbody>
$body
			</tbody>
		</table>

$debug_stats

HERE;


if ($action == 'table'){
	echo "\n			</div>\n		</div>\n		<br>\n";
}