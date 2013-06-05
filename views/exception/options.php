<?php

echo <<<HTML

<p style="text-align: center; color:orangered; font-size: 14px"><strong>$config->site_name has caught an exception and cannot display this page correctly</strong></p><br />
You can certainly try re-loading this page in a short while (our developers are working very hard to fix these kinds of errors),<br />
or <a href="#" onclick="history.go(-1)">go back</a> to the previous page. You can also email the administrator at: at <a href="mailto:$config->site_email">$config->site_email</a><br /><br />
<span class="label label-warning">Exception</span> caught in <strong>$error_file</strong> on line <strong>$error_line</strong><br /><br />
<div class="alert-actions">
	<button class="btn" onclick="window.location='$config->site_url'">Main Page</button>
</div>

HTML;
