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

if (!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once("objects/Profiler.php")) throw new Exception("Compat: objects/Profiler.php is missing. Failed to include objects/Profiler.php");

// Parses the GET data before any other code
$get = validateGet();

// Non-HTML requests: These need to be displayed before any HTML code is loaded or the syntax is broken.

// RSS Feed Request
if (isset($_GET['rss']) && !is_array($_GET['rss'])) {

	if (isset($_GET['b']) && !is_array($_GET['b'])) {

		if (!@include_once("includes/inc.builds.php")) throw new Exception("Compat: inc.builds.php is missing. Failed to include inc.builds.php");
		header('Content-Type: text/xml');
		$Builds = new Builds();
		echo $Builds->getBuildsRSS();
		// No need to load the rest of the page.
		exit();

	} elseif (isset($_GET['h']) && !is_array($_GET['h']) && ($get['m'] == 'c' || $get['m'] == 'n')) {

		// Default to History RSS when parameter is not set
		if (!@include_once("includes/inc.history.php")) throw new Exception("Compat: inc.history.php is missing. Failed to include inc.history.php");
		header('Content-Type: text/xml');
		$History = new History();
		$History->printHistoryRSS();
		// No need to load the rest of the page.
		exit();

	}

}

// JSON API Request
if (isset($_GET['api']) && !is_array($_GET['api'])) {

	// API: v1
	if ($_GET['api'] === "v1") {

		if (isset($_GET['export'])) {
			if (!@include_once('export.php')) throw new Exception("Compat: export.php is missing. Failed to include export.php");
			$results = exportDatabase();
		} else {
			if(!@include_once("includes/inc.compat.php")) throw new Exception("Compat: inc.compat.php is missing. Failed to include inc.compat.php");
			$Compat = new Compat();
			$results = $Compat->APIv1();
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
$start_time = getTime();
Profiler::addData("Index: Start");
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
<title>RPCS3 - Compatibility List</title>
<meta charset=UTF-8>
<meta name="description" content="RPCS3 is an open-source Sony PlayStation 3 emulator and debugger written in C++ for Windows and Linux.">
<meta name="keywords" content="rpcs3, ps3, playstation 3, emulator, nekotekina, compatibility list">
<meta name="author" content="RPCS3">
<meta name="copyright" content="RPCS3">
<?php
if (!@include(__DIR__.'/../../lib/module/sys-meta.php'))
	trigger_error("[Compat] Integration: sys-meta not found", E_USER_WARNING);

if (!@include(__DIR__.'/../../lib/module/sys-css.php')) {
	trigger_error("[Compat] Integration: sys-css not found", E_USER_WARNING);
	echo "<link rel=\"stylesheet\" href=\"compat.css\"/>";
} else {
	echo "<link rel=\"stylesheet\" href=\"/lib/compat/compat.css\"/>";
}

if (!@include(__DIR__.'/../../lib/module/sys-js.php'))
	trigger_error("[Compat] Integration: sys-js not found", E_USER_WARNING);
?>
</head>
<body>
<?php if (!@include(__DIR__.'/../../lib/module/sys-php.php'))
				trigger_error("[Compat] Integration: sys-php not found", E_USER_WARNING); ?>
<div class="page-con-content">
	<div class="header-con-head">
		<div class="header-img-head dynamic-banner">
		</div>
		<div class="header-con-overlay darkmode-header">
		</div>
		<div class="header-con-diffuse">
		</div>
		<div class='header-con-body fade-up-onstart'>
			<div class='header-tx1-body fade-up-onstart'>
				<h1>
				<?php
					if (isset($_GET['h']) && !is_array($_GET['h']))     { echo "History"; }
					elseif (isset($_GET['b']) && !is_array($_GET['b'])) { echo "Builds"; }
					elseif (isset($get['a']))                           { echo "Debug Panel"; }
					elseif (isset($get['l']))                           { echo "PS3 Game library"; }
					else                                                { echo "Compatibility"; }
				?>
				</h1>
			</div>
			<div class='header-tx2-body fade-up-onstart'>
				<p>
					<?php
					if (!$c_maintenance || $get['w']) {
						if (isset($_GET['h']) && !is_array($_GET['h']))     { echo "History of the updates made to the compatibility list"; }
						elseif (isset($_GET['b']) && !is_array($_GET['b'])) { echo "History of RPCS3 Windows builds per merged pull request"; }
						elseif (isset($get['a']))                           { echo "Super cool compatibility list debug control panel"; }
						elseif (isset($get['l']))                           { echo "List of all existing PS3 games known to mankind"; }
						else                                                { echo "There are currently ".countGames(null, 'all')." games listed in our database"; }
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
		if (isset($_GET['h']) && !is_array($_GET['h']))     { include 'pages/history.php'; }
		elseif (isset($_GET['b']) && !is_array($_GET['b'])) { include 'pages/builds.php'; }
		elseif (isset($get['a']))                           { include 'pages/panel.php'; }
		elseif (isset($get['l']))                           { include 'pages/library.php'; }
		else                                                { include 'pages/compat.php'; }
	}
	?>
</div>
<?php if (!@include(__DIR__.'/../../lib/module/ui-main-footer.php'))
				trigger_error("[Compat] Integration: ui-main-footer not found", E_USER_WARNING); ?>
</body>
</html>
