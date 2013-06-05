<?php
/**
 * HTML body of the admin DB migration manager
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


// This view serves several purposes
// First if template flag was set, we generate a PHP template of a version script
if (isset($template) and $template){
	echo <<<PHP
<?php

class $class {

	static function upgrade(){
		return <<<SQL

$upgrade

SQL;
	}

	static function downgrade(){
		return <<<SQL

$downgrade

SQL;
	}
}

?>

PHP;

// The default view rendered by a migration controller
} else {
	$list = '';
	if (isset($versions) and $versions){
		// Populate the table with version scripts
		$token = generate_token(true);
		foreach ($versions as $class => $date){
			$list .=<<<HTML

									<tr class="item">
										<td class="amount"><a href="{$link}edit/$class">$class</a></td>
										<td><span class="published">$date</span></td>
										<td><span class="status">
												<a href="{$link}upgrade/$class?$token" title="Upgrade"><i class="icon-upload"></i></a>
												<a href="{$link}downgrade/$class?$token" title="Downgrade"><i class="icon-download"></i></a>
												<a href="{$link}delete/$class?$token" title="Delete this version"><i class="icon-remove"></i></a>
											</span>
										</td>
									</tr>
HTML;
		}
	} else {
		global $config;
		$list = '<strong><em>No version scripts detected in the versioning folder!</em></strong><br>' . $config->versions_dir;
	}

	if (isset($latest) and $latest){
		$latest_btns =<<<HTML
<a href="{$link}upgrade/$latest?$token" class="btn btn-large btn-success"><strong>Upgrade</strong></a>
											<a href="{$link}downgrade/$latest?$token" class="btn btn-large btn-danger"><strong>Downgrade</strong></a>
HTML;
	} else $latest_btns = '';

	echo <<<HTML

		<div>
			<div class="title-grid">$title</div>
			<div class="content-grid">
				<table class="display">
					<thead>
					<tr>
						<th id="vth1">List of available versions</th>
						<th id="vth2">Latest version actions</th>
					</tr>
					</thead>
					<tbody>
					<tr class="item">
						<td>
							<div class="version">
								<a class="btn-inverse btn centered" href="{$link}new">Create New Version</a><br><br>
								<table class="display">
									<thead>
									<tr>
										<th class="th_date">Version</th>
										<th class="th_status">Date</th>
										<th class="th_action">Action</th>
									</tr>
									</thead>
									<tbody>$list
									</tbody>
								</table>
							</div>
						</td>
						<td valign="top">
							<div class="version-action">
								<table class="display">
									<thead>
									<tr>
										<th id="vth3">Apply Now</th>
									</tr>
									</thead>
									<tbody>
									<tr class="item">
										<td>
											$latest_btns
										</td>
									</tr>
									</tbody>
								</table>
							</div>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="clear"></div>

HTML;

}
?>