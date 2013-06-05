<?php
/**
 * HTML body of the admin panel view
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


// Get the current disk space/usage
exec("df -H | grep -e 'root' -e 'sd' -e 'hd' | awk '{print $1,$2,$3,$5,$6}'", $df);
$hdd = array();
$total_space = $total_used = 0;
foreach ($df as $stat) {
	$stat = explode(' ', $stat);
	$stat[1] .= 'B';
	$stat[2] .= 'B';
	$hdd[] = array('dev' => $stat[0], 'mount' => $stat[4], 'total' => $stat[1], 'used' => $stat[2], 'percent' => $stat[3]);
	$total_space += stristr($stat[1], 'mb') ? intval((int)$stat[1] / 1024) : (int)$stat[1];
	$total_used += stristr($stat[2], 'mb') ? intval((int)$stat[2] / 1024) : (int)$stat[2];
}
$total_percent = intval($total_used / $total_space * 100);

global $view;

echo <<<HTML

		<!--Statistic-->
		<div class="grid-1">
			<div class="title-grid">Server Stats</div>
			<div class="content-grid">
				<div class="reports">
					<div class="report">
						<p class="report-title">Disk Space</p>
						<p class="report-figures">{$total_used}GB / {$total_space}GB</p>

						<div class="clear"></div>
						<div class="headway">
							<div class="advance" style="width:{$total_percent}%"></div>
						</div>
					</div>

HTML;

$i = 0;
foreach ($hdd as $one) {
	$last = ($i % 2 != 0) ? 'report-last' : '';
	echo <<<HTML

					<div class="report-2 $last">
						<p class="report-title">{$one['mount']}</p>
						<p class="report-figures">{$one['used']}</p>
						<div class="clear"></div>
						<div class="headway"><div class="advance" style="width:{$one['percent']};"></div></div>
					</div>

HTML;
	$i++;
}


echo <<<HTML

				</div>
				<span id="hw-stats"><span id="ram-text">RAM: <span id="ram"></span>%</span><span id="cpu-text">CPU: <span id="cpu"></span>%</span></span>

				<div class="cpu-live graph"></div>
				<span id="cpu-loading"><img src="/phpiphany/images/loading.gif" alt="Loading"/></span>

				<div class="ram-live graph"></div>
				<span id="ram-loading"><img src="/phpiphany/images/loading.gif" alt="Loading"/></span>

				<div class="clear"></div>

			</div>
		</div>
		<!--Statistic end-->


		<!--Visitor graph -->
		<div class="grid-1">
			<div class="title-grid">Visitor Graph</div>
			<div class="content-grid">
				<div class="bars" style="width:690px;height:200px;"></div>
			<div class="clear"></div>
			</div>
		</div>
		<!--Visitor graph end-->

		<!--Users-->
		<div class="grid-2">
			<div class="title-grid">Users</div>
			<div class="content-grid">
				<div class="users-list">
					<div class="user">
						<div class="user-avatar"><img src="{$view->assets_dir}images/admin/avatar_message_1.png" alt=""></div>
						<div class="user-info">
							<div class="user-name">John Doe</div>

							<div class="user-email">youremail@domain.com</div>
							<div class="user-link"><a href="#">Account Settings</a> | <a href="#">Delete</a></div>
						</div>
						<div class="clear"></div>
					</div>
					<div class="user">

						<div class="user-avatar"><img src="{$view->assets_dir}images/admin/avatar_message_1.png" alt=""></div>
						<div class="user-info">
							<div class="user-name">John Doe</div>
							<div class="user-email">youremail@domain.com</div>
							<div class="user-link"><a href="#">Account Settings</a> | <a href="#">Delete</a></div>
						</div>

						<div class="clear"></div>
					</div>
					<div class="user">
						<div class="user-avatar"><img src="{$view->assets_dir}images/admin/avatar_message_1.png" alt=""></div>
						<div class="user-info">
							<div class="user-name">John Doe</div>
							<div class="user-email">youremail@domain.com</div>
							<div class="user-link"><a href="#">Account Settings</a> | <a href="#">Delete</a></div>

						</div>
						<div class="clear"></div>
					</div>


					<div class="clear"></div>
				</div>
			</div>
		</div>
		<!--Users end-->

		<!--Website statistic-->
		<div class="grid-2 last right">
			<div class="title-grid">Website statistic</div>
			<div class="content-grid">

				<table class="display">
					<thead>
					<tr>
						<th class="th_date">Amount</th>

						<th class="th_status">Description</th>
					</tr>
					</thead>
					<tbody>
					<tr class="item">
						<td class="amount"><a href="#">1,056</a></td>
						<td><span class="published">Views</span></td>

					</tr>
					<tr class="item">
						<td class="amount"><a href="#">2,587</a></td>
						<td><span class="published">Pageviews</span></td>
					</tr>
					<tr class="item">
						<td class="amount"><a href="#">91%</a></td>

						<td><span class="published">Bounce Rate</span></td>
					</tr>
					<tr class="item">
						<td class="amount"><a href="#">01:32</a></td>
						<td><span class="published">Avg. Time on Site</span></td>
					</tr>
					<tr class="item">

						<td class="amount"><a href="#">63%</a></td>
						<td><span class="published">New Visits</span></td>
					</tr>
					<tr class="item">
						<td class="amount"><a href="#">37%</a></td>
						<td><span class="published">Returning Visitor</span></td>
					</tr>

					<tr class="item">
						<td class="amount"><a href="#">164</a></td>
						<td><span class="published">Ticket</span></td>
					</tr>
					</tbody>
				</table>


			</div>

		</div>
		<!--Website statistic end-->


		<!--Pop-up notifications end-->
		<div class="grid-2" style="float:right">
			<div class="title-grid">Admin actions</div>
			<div class="content-grid">
				<p>
					<a class="btn" href="#"><i class="icon-refresh"></i> Refresh</a>
					<a class="btn btn-success" href="#"><i class="icon-shopping-cart icon-white"></i> Checkout</a>
					<a class="btn btn-danger" href="#"><i class="icon-trash icon-white"></i> Delete</a><br /><br />
					<a class="btn btn-small" href="#" style="margin-left: 30px"><i class="icon-cog"></i> Settings</a>
					<a class="btn btn-small btn-info" href="#"><i class="icon-info-sign icon-white"></i> More Info</a>
				</p>
			</div>

		</div>
		<!--Pop-up notifications end-->


		<div class="clear"></div>

		<!--Tabs-->
		<div class="grid-1 tab" id="tabs">
			<div class="title-grid tabs">
				<ul class="tabNavigation">
					<li><a href="#tabs-1">tab 1</a></li>
					<li><a href="#tabs-2">tab 2</a></li>

					<li><a href="#tabs-3">tab 3</a></li>
				</ul>
			</div>
			<div class="content-grid">
				<div id="tabs-1">
					<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut
						laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation
						ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.</p>
				</div>
				<div id="tabs-2">

					<p><strong>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt
						ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation
						ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.</strong></p>
				</div>
				<div id="tabs-3">
					<p><em> Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut
						laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation
						ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.</em></p>
				</div>

			</div>
		</div>

		<!--Tabs end-->


		<!--Articles-->
		<div class="grid-1">
			<div class="title-grid">Articles</div>
			<div class="content-grid">
				<div class="articles">
					<div class="article">
						<p class="identifier">1</p>

						<div class="article-content">
							<p class="article-title"><a href="#">Loading bar</a></p>
							<ul class="article-action">
								<li><a href="#" class="article-edit tip-top" title="Edit">edit</a></li>
								<li><a href="#" class="article-delete tip-top" title="Delete">delete</a></li>
							</ul>
							<p class="article-description">I worked on the little loading bar a little bit today. Also bumped up
								the text contrast a little bit. Merry Christmas y'all. You should also probably follow...</p>

							<p class="article-author">Posted: <a href="#">Erik Deiner</a></p>
						</div>
					</div>

					<div class="article">
						<p class="identifier">2</p>

						<div class="article-content">

							<p class="article-title"><a href="#">Loading bar</a></p>
							<ul class="article-action">
								<li><a href="#" class="article-edit tip-top" title="Edit">edit</a></li>
								<li><a href="#" class="article-delete tip-top" title="Delete">delete</a></li>
							</ul>
							<p class="article-description">I worked on the little loading bar a little bit today. Also bumped up
								the text contrast a little bit. Merry Christmas y'all. You should also probably follow...</p>

							<p class="article-author">Posted: <a href="#">Erik Deiner</a></p>
						</div>
					</div>

					<div class="article">
						<p class="identifier">3</p>

						<div class="article-content">

							<p class="article-title"><a href="#">Loading bar</a></p>
							<ul class="article-action">
								<li><a href="#" class="article-edit tip-top" title="Edit">edit</a></li>
								<li><a href="#" class="article-delete tip-top" title="Delete">delete</a></li>
							</ul>
							<p class="article-description">I worked on the little loading bar a little bit today. Also bumped up
								the text contrast a little bit. Merry Christmas y'all. You should also probably follow...</p>

							<p class="article-author">Posted: <a href="#">Erik Deiner</a></p>
						</div>
					</div>

					<div class="article">
						<p class="identifier">4</p>

						<div class="article-content">

							<p class="article-title"><a href="#">Loading bar</a></p>
							<ul class="article-action">
								<li><a href="#" class="article-edit tip-top" title="Edit">edit</a></li>
								<li><a href="#" class="article-delete tip-top" title="Delete">delete</a></li>
							</ul>
							<p class="article-description">I worked on the little loading bar a little bit today. Also bumped up
								the text contrast a little bit. Merry Christmas y'all. You should also probably follow...</p>

							<p class="article-author">Posted: <a href="#">Erik Deiner</a></p>
						</div>
					</div>


				</div>
				<div class="clear"></div>
			</div>
		</div><!--Articles end-->

		<div class="clear"></div>

HTML;



