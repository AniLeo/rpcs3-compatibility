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
$scount = countGames($db, $genquery[1]);

// Pages / CurrentPage
Profiler::addData("Inc: Count Pages");
$pages = countPages($get, $scount[0][0]);

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


// Initials search / Levenshtein search
Profiler::addData("Inc: Initials + Levenshtein");

// If game search exists and isn't a Game ID (length isn't 9 and chars 4-9 aren't numbers)
if ($get['g'] != '' && strlen($get['g']) >= 2 && !isGameID($get['g'])) {

	$s_title = mysqli_real_escape_string($db, $get['g']);

	// Initials Search
	$q_initials = mysqli_query($db, "SELECT * FROM `initials_cache` WHERE
		`initials` LIKE '%{$s_title}' && `game_title` NOT LIKE '%{$s_title}%'; ");

	if ($q_initials && mysqli_num_rows($q_initials) > 0) {

		$c_title = "";
		for ($i = 0; $row = mysqli_fetch_object($q_initials); $i++) {
			if ($i > 0)
				$c_title .= " OR ";

			$s_title = mysqli_real_escape_string($db, $row->game_title);
			$c_title .= " game_title = '{$s_title}' OR alternative_title = '{$s_title}' ";
		}
		$c_title = " ( {$c_title} ) ";

		// Recalculate Pages / CurrentPage
		$scount2 = countGames($db, $c_title);

		// If sum of secondary results with primary is bigger than page limit, don't use them
		if ($scount2[0][0]+$scount[0][0] > $get['r']) {
			$onlyUseMain = true;
		} else {
			$onlyUseMain = false;

			// If we're going to use the results, add count of games found here to main count
			for ($x = 0; $x <= 1; $x++)
				for ($y = 0; $y <= 5; $y++)
					$scount[$x][$y] += $scount2[$x][$y];

			$pages = countPages($get, $scount[0][0]);
			$currentPage = getCurrentPage($pages);
		}

		$c_status = ($get['s'] != 0) ? " status = {$get['s']} AND " : "";
		$q_main2 = mysqli_query($db, " SELECT * FROM `game_list` WHERE {$c_status} {$c_title} {$a_order[$get['o']]} LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};");
	}

	// Levenshtein Search
	// If there are no results from previous searches then apply levenshtein to get the closest result
	if ($q_main && mysqli_num_rows($q_main) == 0 && $q_initials && mysqli_num_rows($q_initials) == 0) {
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

		$genquery = " `game_title` LIKE '{$s_title}%' OR `alternative_title` LIKE '{$s_title}%' ";

		// Re-run the main query
		$c_main = "SELECT * FROM `game_list` WHERE {$genquery} {$a_order[$get['o']]}
		LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};";
		$q_main = mysqli_query($db, $c_main);

		// Recalculate Pages / CurrentPage
		$scount = countGames($db, $genquery);
		$pages = countPages($get, $scount[0][0]);
		$currentPage = getCurrentPage($pages);

		// Replace faulty search with returned game but keep the original search for display
		$l_orig = $get['g'];
		$get['g'] = $l_title;
	}
}


// Check if query succeded and storing is required, stores messages for error/information printing
Profiler::addData("Inc: Check Search Status");
$error = NULL;
$info = NULL;
if (!$q_main) {
	$error = "Please try again. If this error persists, please contact the RPCS3 team.";
} elseif (mysqli_num_rows($q_main) == 0 && isGameID($get['g'])) {
	$error = "The Game ID you just tried to search for isn't registered in our compatibility list yet.";
} elseif ($scount[0][0] == 0) {
	$error = "No results found for the specified search on the indicated status.";
} elseif (mysqli_num_rows($q_main) > 0 && isset($l_title) && $l_title != "") {
	$info = "No results found for <i>{$l_orig}</i>. </br>
	Displaying results for <b><a style=\"color:#06c;\" href=\"?g=".urlencode($l_title)."\">{$l_title}</a></b>.";
}

// Store results
Profiler::addData("Inc: Store Results");

// If secondary results exist, sorting is needed
$needsSorting = false;
// Stop if too many data is returned on Initials / Levenshtein
$stop = false;

Profiler::addData("Inc: Store Results - Secondary");
if (isset($q_initials) && $q_initials && mysqli_num_rows($q_initials) > 0 && isset($q_main2) && $q_main2 && mysqli_num_rows($q_main2) > 0 && !$onlyUseMain) {
	$needsSorting = true;

	$games = Game::queryToGames($q_main2);

	if (strlen($get['g']) < 2)
		$stop = true;
}

Profiler::addData("Inc: Store Results - Main");
if (!$stop && $q_main && mysqli_num_rows($q_main) > 0) {
	// If secondary search exists
	if ($needsSorting)
		$games = array_merge($games, Game::queryToGames($q_main));
	else
		$games = Game::queryToGames($q_main);
}

Profiler::addData("Inc: Sort Results");
if ($needsSorting)
	if ($get['o'] == '')
		Game::sort($games, '3', 'a');
	else
		Game::sort($games, substr($get['o'], 0, 1), substr($get['o'], 1, 1));


// Close MySQL connection.
Profiler::addData("Inc: Close Database Connection");
mysqli_close($db);

Profiler::addData("--- / ---");
