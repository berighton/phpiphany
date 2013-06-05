<?php

// Side menu style 4 (sticky left: suitable for default view style)

$menu = $this->menu();

echo <<<HTML

			<nav>
				<ul>
					<li class="nav-header">Sidebar</li>
$menu
				</ul>
			</nav>

HTML;


