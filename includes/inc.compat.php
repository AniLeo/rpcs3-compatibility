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
if (!@include_once(__DIR__."/../classes/class.Compat.php")) throw new Exception("Compat: class.Compat.php is missing. Failed to include class.Compat.php");


// Start: Microtime when page started loading
$start = getTime();

// Profiler
$prof_timing = array();
$prof_names = array();
$prof_desc = "Debug mode: Profiling compat";

// Order queries
$a_order = array(
'' => 'ORDER BY status ASC, game_title ASC',
'1a' => '',
'1d' => '',
'2a' => 'ORDER BY game_title ASC',
'2d' => 'ORDER BY game_title DESC',
'3a' => 'ORDER BY status ASC, game_title ASC',
'3d' => 'ORDER BY status DESC, game_title ASC',
'4a' => 'ORDER BY last_update ASC, game_title ASC',
'4d' => 'ORDER BY last_update DESC, game_title ASC'
);


// Connect to database
prof_flag("Inc: Database Connection");
$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
mysqli_set_charset($db, 'utf8');

// Obtain values from get
prof_flag("Inc: Obtain GET");
$get = validateGet($db);


// Generate query
// 0 => With specified status
// 1 => Without specified status
prof_flag("Inc: Generate Query");
$genquery = generateQuery($get, $db);


// Get game count per status
prof_flag("Inc: Count Games (Search)");
$scount = countGames($db, $genquery[1]);

// Get the total count of entries present in the database (not subjective to search params)
prof_flag("Inc: Count Games (All)");
$games = countGames($db, 'all');


// Pages / CurrentPage
prof_flag("Inc: Count Pages");
$pages = countPages($get, $scount[0][0]);

prof_flag("Inc: Get Current Page");
$currentPage = getCurrentPage($pages);


// Generate the main query
$c_main = "SELECT *
FROM game_list ";
if ($genquery[0] != '') { $c_main .= " WHERE {$genquery[0]} "; }
$c_main .= $a_order[$get['o']]." LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};";


// Run the main query
prof_flag("Inc: Execute Main Query ({$c_main})");
$q_main = mysqli_query($db, $c_main);


// Initials search / Levenshtein search
prof_flag("Inc: Initials + Levenshtein");

// If game search exists and isn't a Game ID (length isn't 9 and chars 4-9 aren't numbers)
if ($get['g'] != '' && strlen($get['g']) >= 2 && ((strlen($get['g'] == 9 && !is_numeric(substr($get['g'], 4, 5)))) || strlen($get['g'] != 9 )) ) {

	// Initials
	$q_initials = mysqli_query($db, "SELECT * FROM initials_cache WHERE initials LIKE '%".mysqli_real_escape_string($db, $get['g'])."%'; ");

	if ($q_initials && mysqli_num_rows($q_initials) > 0) {

		$i = 0;
		// Initialize string
		$partTwo = "";
		while ($row = mysqli_fetch_object($q_initials)) {
			if ($i > 0) { $partTwo .= " OR "; }
			$partTwo .= " game_title = '".mysqli_real_escape_string($db, $row->game_title)."' ";
			$i++;
		}
		$partTwo = " ( {$partTwo} ) ";

		// Recalculate Pages / CurrentPage
		$scount2 = countGames($db, $partTwo);

		// If sum of secondary results with primary is bigger than page limit, don't use them
		if ($scount2[0][0]+$scount[0][0] > $get['r']) {
			$onlyUseMain = true;
		} else {
			$onlyUseMain = false;
			$pages = countPages($get, $scount2[0][0]+$scount[0][0]);
			$currentPage = getCurrentPage($pages);

			// If we're going to use the results, add count of games found here to main count
			// HACK: Check if result isn't numeric to exclude duplicate results
			// TODO: Handle duplicate results properly
			if (!is_numeric($get['g'])) {
				for ($x = 0; $x <= 1; $x++) {
					for ($y = 0; $y <= 5; $y++) {
						$scount[$x][$y] += $scount2[$x][$y];
					}
				}
			}
		}

		$partOne = "SELECT * FROM game_list WHERE ";
		if ($get['s'] != 0) {
			$partOne .= " status = {$get['s']} AND ";
		}

		$q_main2 = mysqli_query($db, " {$partOne} {$partTwo} {$a_order[$get['o']]} LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};");
	}

	// If results not found then apply levenshtein to get the closest result
	$levCheck = mysqli_query($db, "SELECT * FROM game_list WHERE game_title LIKE '%".mysqli_real_escape_string($db, $get['g'])."%'; ");

	if ($levCheck && mysqli_num_rows($levCheck) == 0 && $q_initials && mysqli_num_rows($q_initials) == 0) {
		$l_title = "";
		$l_dist = -1;
		$l_orig = "";

		if ($q_main && mysqli_num_rows($q_main) == 0) {

			// Select all database entries
			$sqlCmd2 = "SELECT * FROM game_list; ";
			$q_lev = mysqli_query($db, $sqlCmd2);

			// Calculate proximity for each database entry
			while($row = mysqli_fetch_object($q_lev)) {
				$lev = levenshtein($get['g'], $row->game_title);

				if ($lev <= $l_dist || $l_dist < 0) {
					$l_title = $row->game_title;
					$l_dist = $lev;
				}
			}

			$genquery = " game_title LIKE '".mysqli_real_escape_string($db, $l_title)."%' ";

			// Re-run the main query
			$sqlCmd = "SELECT *
			FROM game_list
			WHERE {$genquery}
			{$a_order[$get['o']]}
			LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};";
			$q_main = mysqli_query($db, $sqlCmd);

			// Recalculate Pages / CurrentPage
			$scount = countGames($db, $genquery);
			$pages = countPages($get, $scount[0][0]);
			$currentPage = getCurrentPage($pages);

			// Replace faulty search with returned game but keep the original search for display
			$l_orig = $get['g'];
			$get['g'] = $l_title;
		}
	}
}


// Store results
prof_flag("Inc: Store Results");
$a_results = array();

// Stop if too many data is returned on Initials / Levenshtein
$stop = false;

// Fetch all commits => pull requests from builds_windows table
// This is faster than verifying one by one per row
prof_flag("Inc: Store Results - Cache");

// Since this is rather static data, we're caching it to a file
// Saves up a lot of execution time
if (file_exists(__DIR__.'/../cache/a_commits.json')) {
	$a_cache = json_decode(file_get_contents(__DIR__.'/../cache/a_commits.json'), true);
} else {
	// If file isn't present, then just get the contents from the database
	$a_cache = array();

	$q_builds = mysqli_query($db, "SELECT pr,commit FROM builds_windows LEFT JOIN game_list on
	SUBSTR(commit, 1, 7) = SUBSTR(build_commit, 1, 7)
	WHERE build_commit IS NOT NULL
	GROUP BY commit
	ORDER BY merge_datetime DESC;");
	while ($row = mysqli_fetch_object($q_builds)) {
		$a_cache[substr($row->commit, 0, 7)] = array($row->commit, $row->pr);
	}
}

prof_flag("Inc: Store Results - Secondary");
if (isset($q_initials) && $q_initials && mysqli_num_rows($q_initials) > 0 && isset($q_main2) && $q_main2 && mysqli_num_rows($q_main2) > 0 && !$onlyUseMain) {
	storeResults($a_results, $q_main2, $a_cache);
	if (strlen($get['g']) < 2) {
		$stop = true;
	}
}

prof_flag("Inc: Store Results - Main");
if (!$stop && $q_main && mysqli_num_rows($q_main) > 0) {
	storeResults($a_results, $q_main, $a_cache);
}


prof_flag("Inc: Sort Results");
// Temporary array to store sorted results
$a_sorted = array();

// For each status | TODO: Allow reverse order sorting
foreach (range(min(array_keys($a_title))+1, max(array_keys($a_title))) as $i) {
	// Go through our results array
	foreach ($a_results as $key => $value) {
		// When it finds a game on the current status, move it to the temporary array
		// and remove it from the a_results array
		if ($value['status'] == $a_title[$i]) {
			$a_sorted[$key] = $value;
			unset($a_results[$i]);
		}
	}
}

// Copy our new sorted array to a_results
$a_results = $a_sorted;


// Close MySQL connection.
prof_flag("Inc: Close Database Connection");
mysqli_close($db);

prof_flag("--- / ---");


/*****************************************************************************************************************************/

/*******************************
 * General: Combined Search    *
 *   Results per Page          *
 *******************************/
if (in_array($get['r'], $a_pageresults)) {
	$g_pageresults = ($get['r'] == $a_pageresults[$c_pageresults]) ? '' : "r={$get['rID']}&";
}
