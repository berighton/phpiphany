<?php
/**
 * HTML body of the admin wizard - a system installer
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package index page
 * @since 1.0
 *
 */


global $config, $view;

// Simple checks to determine what icon to output
$os = $checks['os']? '<i class="icon-ok"></i>' : '<i class="icon-remove"></i>';
$php = $checks['php']? '<i class="icon-ok"></i>' : '<i class="icon-remove"></i>';
$http = $checks['http']? '<i class="icon-ok"></i>' : '<i class="icon-remove"></i>';
$mysql = $checks['mysql']? '<i class="icon-ok"></i>' : '<i class="icon-remove"></i>';
$settings = $checks['settings']? '<i class="icon-ok"></i>' : '<i class="icon-remove"></i>';

echo <<<HTML

		<div>
			<div class="page-header"><h1>Please take a moment to configure phpiphany on your system</h1></div>


			<span class="install-step">Step 1/3</span>
			<div class="content-wrapper checkmark-big">
				<dl class="install-body">
					<h3>Thank you for choosing phpiphany!</h3><br> This is a very young but powerful (you shall soon discover) framework.
					Although it comes with many functional modules, some parts might not be "polished".
					We are working day and night to eliminate any bugs there might be, as well as to improve the system as a whole.<br><br>
					This installation module is one of the modules that will undergo a heavy overhaul very soon <br>(especially now, since version 1.0 of the framework is out)!<br><br>
					Here, we would check for few basic things and ask you to provide database credentials and create an admin user<br>
					But for now, please make sure that you have installed and configured LAMP (Linux, Apache, MySQL and PHP) in order to continue.
					LAMP is the primary system this framework was tested on and is not guaranteed to work in any other environment (bare with us, we will definitely fix this!)<br><br><br>
					>>> Checking for few basic things...<br><br>
					$os<strong style="margin-left:7px">OS:</strong> {$config->env->os}<br>
					$php<strong style="margin-left:7px">PHP:</strong> {$config->env->php}<br>
					$http<strong style="margin-left:7px">HTTP:</strong> {$config->env->http}<br>
					$mysql<strong style="margin-left:7px">MySQL:</strong> {$config->env->mysql}<br>
					$settings<strong style="margin-left:7px">Config file (settings.php) is writable</strong><br>
				</dl>
			</div>
			<p><div class="clearfix"></div></p>

HTML;

if ($checks['os'] and $checks['php'] and $checks['http'] and $checks['mysql'] and $checks['settings']){
	echo <<<HTML

			<span class="install-step">Step 2/3</span>
			<div class="content-wrapper db-big">
				<dl class="install-body">
					Next, you will need to provide details on how to connect to your database.<br>
					Upon successfully establishing a connection, we will create the schema with system tables.<br>

					<div class="row show-grid">
						<div class="span9">
							<form id="db_form" method="POST" class="form-horizontal">
								<fieldset>
									<legend></legend>
									<div class="control-group">
										<label class="control-label" for="host">Hostname</label>
										<div class="controls">
											<input class="span3" name="host" placeholder="MySQL hostname or IP (eg. localhost)" maxlength="50" type="text">
										</div>
										<label class="control-label" for="user">Username</label>
										<div class="controls">
											<input class="span3" name="user" placeholder="MySQL username to use" maxlength="50" type="text">
										</div>
										<label class="control-label" for="pass">Password</label>
										<div class="controls">
											<input class="span3" name="pass" placeholder="MySQL password (may be blank)" maxlength="50" type="text">
										</div>
										<label class="control-label" for="db">Database</label>
										<div class="controls">
											<input class="span3" name="db" placeholder="MySQL database name (eg. phpiphany)" maxlength="50" type="text" value="phpiphany">
										</div>
										<label class="control-label" for="prefix">Table Prefix</label>
										<div class="controls">
											<input class="span3" name="prefix" placeholder="MySQL database prefix (default: pip_)" maxlength="50" type="text" value="pip_">
										</div>
										
									</div>
									<div id="db_form_div" class="ajax-status hidden"></div>
									<div class="form-actions">
										<button type="submit" id="db_form_submit" class="btn btn-inverse">Save changes</button>
									</div>
								</fieldset>
								$token
							</form>
							
							<script>
								var status1 = $('db_form_div');
	
								$('db_form').addEvent('submit', function(e){
									// Prevent the submit default action
									e.stop();
	
									// Disable the submit button to avoid accidental clicks during an AJAX call
									$('db_form_submit').set('disabled',true);
	
									// Get the div where to output response and populate it with the spinning loading indicator
									status1.set('html', 'Creating schema... <img src="/project1/images/processing.gif" class="right" alt="Loading">').removeClass('hidden').setStyle('background', '#FFFFCC');
	
									// Define effects for the status box to disappear
									var fx = new Fx.Tween(status, {property: 'opacity', duration: 1000, transition: Fx.Transitions.Quart.easeOut});
	
									// Initiate a new AJAX request, expecting JSON to be returned
									var req = new Request.JSON({
										method: 'post',
										url: '{$config->site_url}installer/ajax/db',
										data: this,
										onFailure: function(){
											status1.set('html', '<strong class="red">AJAX submit error</strong>').setStyle('background', '#f2dede');
										},
										onSuccess: function(data){
											if (data.error.length > 0){
												status1.set('html', '<strong class="red">' + data.error + '</strong>').setStyle('background', '#f2dede');
												$('db_form_submit').set('disabled',false);
											} else if (data.html == true){
												status1.set('html', 'Saved!').setStyle('background', '#dff0d8');
												fx.start.pass([1,0], fx).delay(1000);
												$('db_form_submit').set('disabled',false);
											} else if (data.html.length > 1) {
												status1.set('html', data.html).setStyle('background', '#dff0d8');
												$('db_form_submit').set('disabled',true);
												$('user-creation').setStyle('display','');
											}
										}
									}).send();
								});
	
								// Set the status div opacity back to 1 if user clicks submit again
								$('db_form_submit').addEvent('click', function(e){
									if (status1.get('opacity') == 0){
										status1.setStyle('opacity', 1).setStyle('background', '').addClass('hidden');
									}
								});

							</script>
	
						</div>
					</div>
					<br>

				</dl>
			</div>
			<p><div class="clearfix"></div></p>


			<div id="user-creation" style="display:none">
				<span class="install-step">Step 3/3</span>
				<div class="content-wrapper user-big">
					<dl class="install-body">
						You are now ready to create an admin user that has root privileges over your site.<br><br>
						Not only this user can delete other users, objects and groups but has also an ability to view system stats <br>
						such as CPU/HDD/mem usage, access scaffolding interface, plugins, DB migrations, maintenance mode and download manager.<br>

						<div class="row show-grid">
							<div class="span9">
								<form id="user_form" method="POST" class="form-horizontal">
									<fieldset>
										<legend></legend>
										<div class="control-group">
											<label class="control-label" for="fname">First Name</label>
											<div class="controls">
												<input class="span3" type="text" name="fname" placeholder="Enter admin's first name" maxlength="50">
											</div>
											<label class="control-label" for="lname">Last Name</label>
											<div class="controls">
												<input class="span3" type="text" name="lname" placeholder="Enter admin's last name" maxlength="50">
											</div>
											<label class="control-label" for="username">Username</label>
											<div class="controls">
												<input class="span3" type="text" name="username" placeholder="Enter the desired username" maxlength="50">
											</div>
											<label class="control-label" for="email">Email</label>
											<div class="controls">
												<div class="input-prepend"><span class="add-on"><i class="icon-envelope"></i></span>
													<input class="span3 addon" type="text" name="email" placeholder="Site notifications will be sent here" maxlength="50">
												</div>
											</div>
											<label class="control-label" for="password1">Password</label>
											<div class="controls">
												<div class="input-prepend"><span class="add-on"><i class="icon-lock"></i></span>
													<input class="span3 addon" type="password" name="password1" placeholder="Enter preferred password" maxlength="50">
												</div>
												<p class="help-block">Admin password should be very strong: minimum 6 characters, must not be the same as username
														<br>AND must contain letters (upper and lower case) <u>and</u> at least one number or a special character</p>
											</div>
											<label class="control-label" for="password2">Password Again</label>
											<div class="controls">
												<div class="input-prepend"><span class="add-on"><i class="icon-lock"></i></span>
													<input class="span3 addon" type="password" name="password2" placeholder="Preferred password one more time" maxlength="50">
												</div>
											</div>
										</div>
										<div id="user_form_div" class="ajax-status hidden"></div>
										<div class="form-actions">
											<button type="submit" id="user_form_submit" class="btn btn-inverse">Create User</button>
										</div>
									</fieldset>
									$token
								</form>
								<script>
									var status2 = $('user_form_div');
		
									$('user_form').addEvent('submit', function(e){
										// Prevent the submit default action
										e.stop();
		
										// Disable the submit button to avoid accidental clicks during an AJAX call
										$('user_form_submit').set('disabled',true);
		
										// Get the div where to output response and populate it with the spinning loading indicator
										status2.set('html', 'Creating user... <img src="/project1/images/processing.gif" class="right" alt="Loading">').removeClass('hidden').setStyle('background', '#FFFFCC');
		
										// Define effects for the status box to disappear
										var fx = new Fx.Tween(status, {property: 'opacity', duration: 1000, transition: Fx.Transitions.Quart.easeOut});
		
										// Initiate a new AJAX request, expecting JSON to be returned
										var req = new Request.JSON({
											method: 'post',
											url: '{$config->site_url}installer/ajax/user',
											data: this,
											onFailure: function(){
												status2.set('html', '<strong class="red">AJAX submit error</strong>').setStyle('background', '#f2dede');
											},
											onSuccess: function(data){
												if (data.error.length > 0){
													status2.set('html', '<strong class="red">' + data.error + '</strong>').setStyle('background', '#f2dede');
													$('user_form_submit').set('disabled',false);
												} else if (data.html == true){
													status2.set('html', 'Saved!').setStyle('background', '#dff0d8');
													fx.start.pass([1,0], fx).delay(1000);
													$('user_form_submit').set('disabled',false);
												} else if (data.html.length > 1) {
													status2.set('html', data.html).setStyle('background', '#dff0d8');
													$('user_form_submit').set('disabled',true);
													(function(){ window.location = '{$config->site_url}authenticator'; }).delay(2000);
												}
											}
										}).send();
									});
		
									// Set the status div opacity back to 1 if user clicks submit again
									$('user_form_submit').addEvent('click', function(e){
										if (status2.get('opacity') == 0){
											status2.setStyle('opacity', 1).setStyle('background', '').addClass('hidden');
										}
									});

								</script>

							</div>
						</div>
						<br>

					</dl>
				</div>
			</div>
			<p><div class="clearfix"></div></p>

		</div>

		<div class="clear"></div>

HTML;
} else echo '<div class="alert alert-danger centered"><strong>Unfortunately your system does not meet the minimum requirements! Setup cannot continue.</strong></div>';
