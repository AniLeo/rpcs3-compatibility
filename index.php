<?php
/*
		RPCS3.net Compatibility List (https://github.com/AniLeo/rpcs3-compatibility)
		Copyright (C) 2017 AniLeo
		https://github.com/AniLeo or ani-leo@outlook.com

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License along
		with this program; if not, write to the Free Software Foundation, Inc.,
		51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/


// Non-HTML requests: These need to be displayed before any HTML code is loaded or the syntax is broken.

// RSS Feed Request
if (isset($_GET['rss'])) {

	if (isset($_GET['b'])) {

		if (!@include_once("includes/inc.builds.php")) throw new Exception("Compat: inc.builds.php is missing. Failed to include inc.builds.php");
		header('Content-Type: application/xml');
		echo Builds::getBuildsRSS();

	} else /*if (isset($_GET['h']))*/ {

		// Default to History RSS when parameter is not set
		if (!@include_once("includes/inc.history.php")) throw new Exception("Compat: inc.history.php is missing. Failed to include inc.history.php");
		header('Content-Type: application/xml');
		echo History::getHistoryRSS();

	}

	// No need to load the rest of the page.
	exit();

}

// JSON API Request
if (isset($_GET['api'])) {

	// API: v1
	if ($_GET['api'] == 'v1') {

		if (isset($_GET['export'])) {
			if (!@include_once('export.php')) throw new Exception("Compat: export.php is missing. Failed to include export.php");
			$results = exportDatabase();
		} else {
			if(!@include_once("includes/inc.compat.php")) throw new Exception("Compat: inc.compat.php is missing. Failed to include inc.compat.php");
			$results = Compat::APIv1();
		}

		header('Content-Type: application/json');
		echo json_encode($results, JSON_PRETTY_PRINT);

	}

	// No need to load the rest of the page.
	exit();

}

/**
RPCS3.net Compatibility List by AniLeo
https://github.com/AniLeo
2017.01.22
**/
if (!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__.'/../../lib/module/metadata/head.compat.php')) throw new Exception("Compat: head.compat.php is missing. Failed to include head.compat.php"); ?>
<div class="page-con-content">
	<div class="header-con-head">
		<div class="header-img-head dynamic-banner">
		</div>
		<div class="header-con-overlay darkmode-header">
		</div>
		<div class='header-con-body'>
			<div class='header-tx1-body'>
				<h1>
				<?php
					$get = obtainGet();
					if (isset($_GET['h']))     { echo "HISTORY"; }
					elseif (isset($_GET['b'])) { echo "BUILDS"; }
					elseif (isset($get['a']))  { echo "DEBUG PANEL"; }
					elseif (isset($get['l']))  { echo "PS3 GAME LIBRARY"; }
					else                       { echo "COMPATIBILITY"; }
				?>
				</h1>
			</div>
			<div class='header-tx2-body'>
				<p>
					<?php
					if (!$c_maintenance || $get['w']) {
						if (isset($_GET['h']))     { echo "History of the updates made to the compatibility list"; }
						elseif (isset($_GET['b'])) { echo "History of RPCS3 Windows builds per merged pull request"; }
						elseif (isset($get['a']))  { echo "Super cool compatibility list debug control panel"; }
						elseif (isset($get['l']))  { echo "List of all existing PS3 games known to mankind"; }
						else                       { echo "There are currently ".countGames(null, 'all')." games listed in our database"; }
					} else {
						echo "Compatibility is undergoing maintenance. Please try again in a few minutes.";
					}
					?>

				</p>
			</div>
		</div>
	</div>
	<?php
	if (!$c_maintenance || $get['w']) {
		if (isset($_GET['h']))     { include 'pages/history.php'; }
		elseif (isset($_GET['b'])) { include 'pages/builds.php'; }
		elseif (isset($get['a']))  { include 'pages/panel.php'; }
		elseif (isset($get['l']))  { include 'pages/library.php'; }
		else                       { include 'pages/compat.php'; }
	}
	?>
</div>
<?php if (!@include_once(__DIR__.'/../../lib/module/metadata/footer.compat.php')) throw new Exception("Compat: footer.compat.php is missing. Failed to include footer.compat.php"); ?>
