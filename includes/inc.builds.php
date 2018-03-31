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

// Calls for the file that contains the functions needed
if (!@include_once(__DIR__."/../functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/../classes/class.Builds.php")) throw new Exception("Compat: class.Builds.php is missing. Failed to include class.Builds.php");


// Start: Microtime when page started loading
$start = getTime();

// Profiler
$prof_timing = array();
$prof_names = array();
$prof_desc = "Debug mode: Profiling builds";

// Order queries
$a_order = array(
'' => 'ORDER BY merge_datetime DESC',
'1a' => 'ORDER BY pr ASC',
'1d' => 'ORDER BY pr DESC',
'2a' => 'ORDER BY author ASC',
'2d' => 'ORDER BY author DESC',
'3a' => 'ORDER BY merge_datetime ASC',
'3d' => 'ORDER BY merge_datetime DESC',
'4a' => 'ORDER BY merge_datetime ASC',
'4d' => 'ORDER BY merge_datetime DESC'
);

// Override default page results to ID:2 (25) as default is 50
$c_pageresults = 2;

// Obtain values from get
$get = obtainGet();

// Connect to database
prof_flag("Inc: Database Connection");
$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
mysqli_set_charset($db, 'utf8');

// Calculate pages and current page
prof_flag("Inc: Count Pages");
$pages = ceil(mysqli_fetch_object(mysqli_query($db, "SELECT count(*) AS c FROM builds_windows"))->c / $get['r']);
prof_flag("Inc: Get Current Page");
$currentPage = getCurrentPage($pages);

// Main query
prof_flag("Inc: Execute Main Query");
$buildsCommand = "SELECT * FROM builds_windows {$a_order[$get['o']]} LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']}; ";
$buildsQuery = mysqli_query($db, $buildsCommand);

// Disconnect from database
prof_flag("Inc: Close Database Connection");
mysqli_close($db);


// TODO: Cleanup
// TODO: Custom results per page
// TODO: No listing builds with experimental warning 13/14-08/2017 and up + branch only

prof_flag("--- / ---");
