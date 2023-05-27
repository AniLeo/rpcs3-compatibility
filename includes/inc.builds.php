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


// Profiler
Profiler::start_profiler("Profiler: Builds");

// Unreachable during normal usage as it's defined on index
if (!isset($get))
	$get = validateGet();

// Order queries
$a_order = array(
'' => 'ORDER BY merge_datetime DESC',
'1a' => 'ORDER BY pr ASC',
'1d' => 'ORDER BY pr DESC',
'4a' => 'ORDER BY merge_datetime ASC',
'4d' => 'ORDER BY merge_datetime DESC'
);

if (isset($get['o']) && isset($a_order[$get['o']]))
	$order = $a_order[$get['o']];
else
	$order = $a_order[''];

// Connect to database
Profiler::add_data("Inc: Database Connection");
$db = getDatabase();

// Calculate pages and current page
Profiler::add_data("Inc: Count Pages");
$pages = 1;
$q_pages = mysqli_query($db, "SELECT count(*) AS `c` FROM `builds`");
if (!is_bool($q_pages))
{
	$row = mysqli_fetch_object($q_pages);

	if ($row && property_exists($row, "c"))
	{
		$pages = (int) ceil($row->c / $get['r']);
	}
}

Profiler::add_data("Inc: Get Current Page");
$currentPage = getCurrentPage($pages);

// Main query
Profiler::add_data("Inc: Execute Main Query");
$c_builds = "SELECT * FROM `builds` {$order} LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']}; ";
$q_builds = mysqli_query($db, $c_builds);

// Disconnect from database
Profiler::add_data("Inc: Close Database Connection");
mysqli_close($db);

// Check if query succeeded and storing is required, stores messages for error printing
Profiler::add_data("Inc: Check Query Status");
$error = NULL;
$builds = array();

if (is_bool($q_builds))
{
	$error = "Please try again. If this error persists, please contact the RPCS3 team.";
}
elseif (mysqli_num_rows($q_builds) === 0)
{
	$error = "No builds are listed yet.";
}
else
{
	// Store builds in a Build array if there are no errors
	Profiler::add_data("Inc: Store Builds in Array");
	$builds = Build::query_to_builds($q_builds);
}

Profiler::add_data("--- / ---");
