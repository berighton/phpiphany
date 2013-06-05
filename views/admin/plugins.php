<?php
/**
 * HTML body of the admin plugin manager
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package admin view
 * @since 1.0
 *
 */


$accordion = '';
if (isset($plugins) and $plugins){
	// Populate the accordion with plugin information
	foreach ($plugins as $plugin){
		$title = ucwords($plugin->name);
		// If this plugin is installed
		if ($plugin->date_installed){
			if ($plugin->active == 'yes') {
				$status = '<span class="plugin-installed">Installed</span><span class="plugin-activated">Activated</span>';
				$active8 = '<a href="?do=deactivate&name=' . $plugin->name . '&' . generate_token(true) . '" title="Deactivate"><i class="icon-star-empty"></i></a>';
			} else {
				$status = '<span class="plugin-installed">Installed</span>';
				$active8 = '<a href="?do=activate&name=' . $plugin->name . '&' . generate_token(true) . '" title="Activate"><i class="icon-star"></i></a>';
			}
			$action = '<a href="?do=edit&name=' . $plugin->name . '" title="Edit"><i class="icon-pencil"></i></a>
											' . $active8 . '
											<a href="?do=uninstall&name=' . $plugin->name . '&' . generate_token(true) . '" title="Uninstall"><i class="icon-remove"></i></a>';
		} else {
			$status = '<span class="plugin-disabled">Disabled</span>';
			$action = '<a href="?do=install&name=' . $plugin->name .'&path=' . $plugin->path . '" class="btn btn-mini btn-success" title="Install"><i class="icon-star icon-white"></i></a>';
		}
		$accordion .=<<<HTML
					<dt><a href="#{$plugin->name}">{$title}{$status}</a></dt>
					<dd id="{$plugin->name}">
						<p>
							$plugin->description
							<table class="display">
								<thead>
									<th class="th_action">Version</th>
									<th class="th_date">Author</th>
									<th class="th_title">Path</th>
									<th class="th_action">Active</th>
									<th class="th_title">Access Group</th>
									<th class="th_date">Date Installed</th>
									<th class="th_action">Actions</th>
								</thead>
								<tbody>
									<tr class="item">
										<td>$plugin->version</td>
										<td>$plugin->author</td>
										<td>$plugin->path</td>
										<td>$plugin->active</td>
										<td>$plugin->group</td>
										<td>$plugin->date_installed</td>
										<td>
											$action
										</td>
									</tr>
								</tbody>
							</table>
						</p>
					</dd>

HTML;
	}
} else {
	global $config;
	$accordion = '<strong><em>No Plugins detected in the plugins folder!</em></strong><br>' . $config->plugins_dir;
}

echo <<<HTML

		<div>
			<div class="title-grid">Plugin Manager</div>
			<div class="content-grid">
				<dl>
$accordion
				</dl>
			</div>
		</div>

		<div class="clear"></div>

HTML;
