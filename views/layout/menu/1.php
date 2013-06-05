<?php

// Side menu style 1

$menu = $this->menu();

echo <<<HTML

			<div id="button" class="well">
				<ul class="nav nav-list">
$menu
				</ul>
			</div>

HTML;


