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
if (!@include_once(__DIR__."/../cachers.php")) throw new Exception("Compat: cachers.php is missing. Failed to include cachers.php");
if (!@include_once(__DIR__."/../classes/class.Compat.php")) throw new Exception("Compat: class.Compat.php is missing. Failed to include class.Compat.php");


// Profiler
Profiler::setTitle("Profiler: Compat");

// Unreachable during normal usage as it's defined on index
if (!isset($get))
	$get = validateGet();

// Order queries
$a_order = array(
'' => 'ORDER BY `status` ASC, `game_title` ASC',
'2a' => 'ORDER BY `game_title` ASC',
'2d' => 'ORDER BY `game_title` DESC',
'3a' => 'ORDER BY `status` ASC, `game_title` ASC',
'3d' => 'ORDER BY `status` DESC, `game_title` ASC',
'4a' => 'ORDER BY `last_update` ASC, `game_title` ASC',
'4d' => 'ORDER BY `last_update` DESC, `game_title` ASC'
);

if (isset($get['o']) && isset($a_order[$get['o']]))
	$order = $a_order[$get['o']];
else
	$order = $a_order[''];

// Game array to store games
$games = null;

// Connect to database
Profiler::addData("Inc: Database Connection");
$db = getDatabase();

// Generate query
Profiler::addData("Inc: Generate Query");
$genquery = Compat::generate_query($get, $db);

// Get game count per status
Profiler::addData("Inc: Count Games (Search)");
$scount = countGames($db, $genquery);

// Pages / CurrentPage
Profiler::addData("Inc: Count Pages");
$pages = countPages($get["r"], $scount["network"][0]);

Profiler::addData("Inc: Get Current Page");
$currentPage = getCurrentPage($pages);


// Generate the main query
$limit = $get['r'] * $currentPage - $get['r'];

$c_main = "SELECT * FROM `game_list` ";

// General filters from generate_query
if (!empty($genquery))
{
	$c_main .= " WHERE ({$genquery}) ";
}

// Status filter
if ($get['s'] !== 0)
{
	if (!empty($genquery))
	{
		$c_main .= " AND ";
	}
	else
	{
		$c_main .= " WHERE ";
	}

	$c_main .= " (`status` = {$get['s']}) ";
}

$c_main .= " {$order} LIMIT {$limit}, {$get['r']}; ";


// Run the main query
Profiler::addData("Inc: Execute Main Query ({$c_main})");
$q_main = mysqli_query($db, $c_main);


// Levenshtein search
Profiler::addData("Inc: Levenshtein");

// Levenshtein Search (Get the closest result to the searched input)
// If the main query didn't return anything and game search exists and isn't a Game ID
if ($q_main && mysqli_num_rows($q_main) === 0 && isset($get['g']) && !isGameID($get['g']))
{
	$l_title = "";
	$l_orig = "";
	$l_dist = -1;
	$titles = array();

	// Select all database entries
	$q_lev = mysqli_query($db, "SELECT `game_title`, `alternative_title` FROM `game_list`; ");
	while ($row = mysqli_fetch_object($q_lev))
	{
		$titles[] = $row->game_title;
		if (!is_null($row->alternative_title))
			$titles[] = $row->alternative_title;
	}

	// Calculate proximity for each database entry
	foreach ($titles as $title)
	{
		$lev = levenshtein($get['g'], $title);
		if ($lev <= $l_dist || $l_dist < 0)
		{
			$l_title = $title;
			$l_dist = $lev;
		}
	}

	// Replace faulty search with returned game but keep the original search for display
	$l_orig   = $get['g'];
	$get['g'] = $l_title;

	// Re-run the main query
	$genquery = Compat::generate_query($get, $db);
	$c_main = "SELECT * FROM `game_list` WHERE ({$genquery}) {$order} LIMIT {$limit}, {$get['r']};";
	$q_main = mysqli_query($db, $c_main);

	// Recalculate Pages / CurrentPage
	$scount = countGames($db, $genquery);
	$pages = countPages($get["r"], $scount["network"][0]);
	$currentPage = getCurrentPage($pages);
}

// Check if query succeeded and storing is required, stores messages for error/information printing
Profiler::addData("Inc: Check Search Status");
$error = NULL;

if (!$q_main)
{
	$error = "ERROR_QUERY_FAIL";
}
else if (mysqli_num_rows($q_main) === 0 && isset($get['g']) && isGameID($get['g']))
{
	$error = "ERROR_QUERY_EMPTY";
}
else if ($scount["network"][0] === 0)
{
	$error = "ERROR_STATUS_EMPTY";
}
else if (mysqli_num_rows($q_main) === 0 && isset($l_title) && isset($l_orig) && !empty($l_title) && !empty($l_orig))
{
	$error = "ERROR_QUERY_FAIL_2";
}

// Store results
if ($q_main && mysqli_num_rows($q_main) > 0)
{
	Profiler::addData("Inc: Query to Games");
	$games = Game::query_to_games($q_main);

	Profiler::addData("Inc: Query to Games - Import Wiki");
	Game::import_wiki($games);

	Profiler::addData("Inc: Query to Games - Import Updates");
	Game::import_update_tags($games);
}

// Close MySQL connection.
Profiler::addData("Inc: Close Database Connection");
mysqli_close($db);

Profiler::addData("--- / ---");
