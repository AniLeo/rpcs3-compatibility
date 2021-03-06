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

// Check if we're running PHP 7.4 or above
if (phpversion()[0] < 7 || ((int) phpversion()[0] === 7 && phpversion()[2] < 4))
{
	trigger_error("[COMPAT] Initialization: Incompatible PHP version. This application requires PHP 7.4+", E_USER_ERROR);
	die();
}

// Parses the GET data before any other code
$get = validateGet();

// Non-HTML requests: These need to be displayed before any HTML code is loaded or the syntax is broken.

// RSS Feed Request
if (isset($get['rss']))
{
	if (isset($get['b']))
	{
		if (!@include_once("includes/inc.builds.php")) throw new Exception("Compat: inc.builds.php is missing. Failed to include inc.builds.php");
		header('Content-Type: text/xml');
		$Builds = new Builds();
		echo $Builds->getBuildsRSS();

		// No need to load the rest of the page.
		exit();
	}
	elseif (isset($get['h']) && isset($get['m']) && ($get['m'] === 'c' || $get['m'] === 'n'))
	{
		if (!@include_once("includes/inc.history.php")) throw new Exception("Compat: inc.history.php is missing. Failed to include inc.history.php");
		header('Content-Type: text/xml');
		$History = new History();
		$History->printHistoryRSS();

		// No need to load the rest of the page.
		exit();
	}
}

// JSON API Request
if (isset($get['api']))
{
	// API: v1
	if ($get['api'] === "v1")
	{
		if (isset($_GET['export']))
		{
			if (!@include_once('export.php')) throw new Exception("Compat: export.php is missing. Failed to include export.php");
			$results = exportDatabase();
		}
		elseif (isset($_GET['patch']))
		{
			if (!@include_once('patch.php')) throw new Exception("Compat: patch.php is missing. Failed to include patch.php");
			$results = exportGamePatches();
		}
		else
		{
			if (!@include_once("includes/inc.compat.php")) throw new Exception("Compat: inc.compat.php is missing. Failed to include inc.compat.php");
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
$start_time = microtime(true);
Profiler::addData("Index: Start");
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
<title>RPCS3 - Compatibility List</title>
<meta charset=UTF-8>
<meta name="description" content="RPCS3 is a multi-platform open-source Sony PlayStation 3 emulator and debugger written in C++ for Windows, Linux and BSD.">
<meta name="keywords" content="rpcs3, playstation, playstation 3, ps3, emulator, debugger, windows, linux, bsd, open source, nekotekina, kd11, compatibility list">
<meta name="author" content="RPCS3">
<meta name="copyright" content="RPCS3">
<?php
if (!@include(__DIR__.'/../../lib/module/sys-meta.php'))
	trigger_error("[COMPAT] Integration: sys-meta not found", E_USER_WARNING);

if (!@include(__DIR__.'/../../lib/module/sys-css.php'))
{
	trigger_error("[COMPAT] Integration: sys-css not found", E_USER_WARNING);
	echo "<link rel=\"stylesheet\" href=\"compat.css\"/>";
}
else
{
	echo "<link rel=\"stylesheet\" href=\"/lib/compat/compat.css\"/>";
}

if (!@include(__DIR__.'/../../lib/module/sys-js.php'))
	trigger_error("[COMPAT] Integration: sys-js not found", E_USER_WARNING);
?>
</head>
<body>
<?php if (!@include(__DIR__.'/../../lib/module/sys-php.php'))
				trigger_error("[COMPAT] Integration: sys-php not found", E_USER_WARNING); ?>
<div class="page-con-content">
	<div class="banner-con-container darkmode-header">
		<div id="object-particles">
		</div>
		<div class="wavebar-con-container">
			<div class="wavebar-con-wrap">
				<div class="wavebar-svg-object">
				</div>
				<div class="wavebar-svg-object">
				</div>
			</div>
		</div>
		<div class='banner-con-title fade-up-onstart'>
			<div class='banner-tx1-title fade-up-onstart pulsate'>
				<h1>
				<?php
					if     (isset($get['h'])) { echo "History"; }
					elseif (isset($get['b'])) { echo "Builds"; }
					elseif (isset($get['a'])) { echo "Debug Panel"; }
					else                      { echo "Compatibility"; }
				?>
				</h1>
			</div>
			<div class='banner-tx2-title fade-up-onstart'>
				<p>
					<?php
					if ((isset($c_maintenance) && !$c_maintenance) || $get['w'] != NULL) {
						if     (isset($get['h'])) { echo "History of the updates made to the compatibility list"; }
						elseif (isset($get['b'])) { echo "History of RPCS3 builds per merged pull request"; }
						elseif (isset($get['a'])) { echo "Super cool compatibility list debug control panel"; }
						else                      { echo "There are currently ".count_games_all()." games listed in our database"; }
					} else {
						echo "Compatibility is undergoing maintenance. Please try again in a few minutes.";
					}
					?>
				</p>
			</div>
		</div>
	</div>
	<?php
	if ((isset($c_maintenance) && !$c_maintenance) || $get['w'] != NULL)
	{
		if     (isset($get['h'])) { include 'pages/history.php'; }
		elseif (isset($get['b'])) { include 'pages/builds.php'; }
		elseif (isset($get['a'])) { include 'pages/panel.php'; }
		else                      { include 'pages/compat.php'; }
	}
	?>
</div>
<?php if (!@include(__DIR__.'/../../lib/module/ui-main-footer.php'))
				trigger_error("[COMPAT] Integration: ui-main-footer not found", E_USER_WARNING); ?>
</body>
</html>
