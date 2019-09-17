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

// Order queries
$a_order = array(
'' => 'ORDER BY status ASC, game_title ASC',
'2a' => 'ORDER BY game_title ASC',
'2d' => 'ORDER BY game_title DESC',
'3a' => 'ORDER BY status ASC, game_title ASC',
'3d' => 'ORDER BY status DESC, game_title ASC',
'4a' => 'ORDER BY last_update ASC, game_title ASC',
'4d' => 'ORDER BY last_update DESC, game_title ASC'
);

// Game array to store games
$games = null;

// Connect to database
Profiler::addData("Inc: Database Connection");
$db = getDatabase();

// Generate query
// 0 => With specified status
// 1 => Without specified status
Profiler::addData("Inc: Generate Query");
$genquery = Compat::generateQuery($get, $db);

// Get game count per status
Profiler::addData("Inc: Count Games (Search)");
$scount = countGames($db, $genquery["nostatus"]);

// Pages / CurrentPage
Profiler::addData("Inc: Count Pages");
$pages = countPages($get, $scount["network"][0]);

Profiler::addData("Inc: Get Current Page");
$currentPage = getCurrentPage($pages);


// Generate the main query
$c_main = "SELECT * FROM `game_list` ";
if ($genquery[0] != '') { $c_main .= " WHERE {$genquery[0]} "; }
$c_main .= isset($a_order[$get['o']]) ? $a_order[$get['o']] : $a_order[''];
$c_main .= " LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};";

// Run the main query
Profiler::addData("Inc: Execute Main Query ({$c_main})");
$q_main = mysqli_query($db, $c_main);


// Levenshtein search
Profiler::addData("Inc: Levenshtein");

// Levenshtein Search (Get the closest result to the searched input)
// If the main query didn't return anything and game search exists and isn't a Game ID
if ($q_main && mysqli_num_rows($q_main) === 0 && $get['g'] != '' && !isGameID($get['g'])) {

	$l_title = "";
	$l_orig = "";
	$l_dist = -1;
	$titles = array();

	// Select all database entries
	$q_lev = mysqli_query($db, "SELECT `game_title`, `alternative_title` FROM `game_list`; ");
	while ($row = mysqli_fetch_object($q_lev)) {
		$titles[] = $row->game_title;
		$titles[] = $row->alternative_title;
	}

	// Calculate proximity for each database entry
	foreach ($titles as $title) {
		$lev = levenshtein($get['g'], $title);
		if ($lev <= $l_dist || $l_dist < 0) {
					$l_title = $title;
					$l_dist = $lev;
		}
	}

	$s_title = mysqli_real_escape_string($db, $l_title);

	$genquery = " `game_title` LIKE '%{$s_title}%' OR `alternative_title` LIKE '%{$s_title}%' ";

	// Re-run the main query
	$c_main = "SELECT * FROM `game_list` WHERE {$genquery} {$a_order[$get['o']]}
	LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};";
	$q_main = mysqli_query($db, $c_main);

	// Recalculate Pages / CurrentPage
	$scount = countGames($db, $genquery);
	$pages = countPages($get, $scount["network"][0]);
	$currentPage = getCurrentPage($pages);

	// Replace faulty search with returned game but keep the original search for display
	$l_orig = $get['g'];
	$get['g'] = $l_title;
}


// Check if query succeded and storing is required, stores messages for error/information printing
Profiler::addData("Inc: Check Search Status");
$error = NULL;
$info = NULL;
if (!$q_main) {
	$error = "Please try again. If this error persists, please contact the RPCS3 team.";
} elseif (mysqli_num_rows($q_main) === 0 && isGameID($get['g'])) {
	$error = "The Game ID you just tried to search for isn't registered in our compatibility list yet.";
} elseif ($scount["status"][0] === 0) {
	$error = "No results found for the specified search on the indicated status.";
} elseif (mysqli_num_rows($q_main) > 0 && isset($l_title) && $l_title != "") {
	$info = "No results found for <i>{$l_orig}</i>. </br>
	Displaying results for <b><a style=\"color:#06c;\" href=\"?g=".urlencode($l_title)."\">{$l_title}</a></b>.";
}

// Store results
Profiler::addData("Inc: Store Results");
if ($q_main && mysqli_num_rows($q_main) > 0)
	$games = Game::queryToGames($q_main);

// Close MySQL connection.
Profiler::addData("Inc: Close Database Connection");
mysqli_close($db);

Profiler::addData("--- / ---");
