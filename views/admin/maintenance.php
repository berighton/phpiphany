<?php
/**
 * HTML body of the maintenance mode view
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


// This view has two modes: 'admin' where admin gets to toggle the maintenance mode; and 'user' where users see the admin-defined maintenance message
if (isset($mode) and $mode == 'admin'){
	$token = generate_token(false, true);
	global $view;

	echo <<<HTML

		<div>
			<div class="title-grid">$title</div>
			<div class="content-grid">
				<table class="display">
					<tbody>
					<tr class="item">
						<td>
							<div class="content-wrapper" style="margin-right: 10px; margin-bottom: 15px">
								The purpose of this controller is to put system in maintenance mode if an urgent bug made its way onto a production server<br>
								or a release of the new code is being applied and the server needs to go offline for some time. <br><br>
								<strong>It is recommended to use this method as a last resort, as none of the users will be able to use the site</strong><br>
								Current logged-in users will be kicked out (session terminated) and will see the maintenance message upon trying to access any page
							</div>
							<form action="$link" method="post">
								<p>Please fill out the following information</p>
								$content
								<br><input type="submit" class="btn btn-large btn-danger" value="Put the system in maintenance mode">$btn
								$token
							</form>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="clear"></div>

HTML;

// Render a nicely formatted message for users who are trying to access the site while it is in maintenance mode
} else {
	echo <<<HTML

	<h1 class="red">Site is unavailable dudes!</h1>

HTML;

}