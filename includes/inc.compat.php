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


// Start: Microtime when page started loading
$start = getTime();

// Profiler
$prof_timing = array();
$prof_names = array();
$prof_desc = "Debug mode: Profiling compat";

// Order queries
$a_order = array(
'' => 'ORDER BY status ASC, game_title ASC',
'1a' => 'ORDER BY game_id ASC',
'1d' => 'ORDER BY game_id DESC',
'2a' => 'ORDER BY game_title ASC',
'2d' => 'ORDER BY game_title DESC',
'3a' => 'ORDER BY status ASC',
'3d' => 'ORDER BY status DESC',
'4a' => 'ORDER BY last_edit ASC',
'4d' => 'ORDER BY last_edit DESC'
);


// Connect to database
prof_flag("Inc: Database Connection");
$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
mysqli_set_charset($db, 'utf8');

// Obtain values from get
prof_flag("Inc: Obtain GET");
$get = obtainGet($db);


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


// Run the main query 
prof_flag("Inc: Execute Main Query");

$c_main .= "SELECT game_id, game_title, thread_id, status, build_commit, last_edit
FROM ".db_table." ";
if ($genquery[0] != '') { $c_main .= " WHERE {$genquery[0]} "; }
$c_main .= $a_order[$get['o']]." LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};";

$q_main = mysqli_query($db, $c_main);


// Initials search / Levenshtein search
prof_flag("Inc: Initials + Levenshtein");

// If game search exists and isn't a Game ID (length isn't 9 and chars 4-9 aren't numbers)
if ($get['g'] != '' && strlen($get['g'] != 9 && !is_numeric(substr($get['g'], 4, 5)))) {
	
	// Initials
	$q_initials = mysqli_query($db, "SELECT * FROM initials_cache WHERE initials LIKE '%".mysqli_real_escape_string($db, $get['g'])."%'; ");

	if ($q_initials && mysqli_num_rows($q_initials) > 0) {

		$i = 0;
		while ($row = mysqli_fetch_object($q_initials)) {
			if ($i > 0) { $partTwo .= " OR "; }
			$partTwo .= " game_title = '".mysqli_real_escape_string($db, $row->game_title)."' ";
			$i++;
		}
		$partTwo = " ( {$partTwo} ) ";

		// Recalculate Pages / CurrentPage
		$scount2 = countGames($db, $partTwo);
		$pages = countPages($get, $scount2[0][0]+$scount[0][0]);
		$currentPage = getCurrentPage($pages);
		
		if (strlen($get['g']) > 3) {
			$scount = countGames($db, $partTwo, $scount[0]);
		}
		
		$partOne = "SELECT * FROM ".db_table." WHERE ";
		if ($get['s'] != 0) {
			$partOne .= " status = {$get['s']} AND ";
		}
		
		$q_main2 = mysqli_query($db, " {$partOne} {$partTwo} {$a_order[$get['o']]} LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};");	
	}
	
	// If results not found then apply levenshtein to get the closest result
	$levCheck = mysqli_query($db, "SELECT * FROM ".db_table." WHERE game_title LIKE '%".mysqli_real_escape_string($db, $get['g'])."%'; ");
	
	if ($levCheck && mysqli_num_rows($levCheck) == 0 && $q_initials && mysqli_num_rows($q_initials) == 0) {
		$l_title = "";
		$l_dist = -1;
		$l_orig = "";
		
		if ($q_main && mysqli_num_rows($q_main) == 0) {
			
			// Select all database entries
			$sqlCmd2 = "SELECT * FROM ".db_table."; ";
			$q_main2 = mysqli_query($db, $sqlCmd2);
			
			// Calculate proximity for each database entry
			while($row = mysqli_fetch_object($q_main2)) {
				$lev = levenshtein($get['g'], $row->game_title);
				
				if ($lev <= $l_dist || $l_dist < 0) {
					$l_title = $row->game_title;
					$l_dist = $lev;
				}
			}
			
			$genquery = " game_title LIKE '".mysqli_real_escape_string($db, $l_title)."%' ";
			
			// Re-run the main query
			$sqlCmd = "SELECT game_id, game_title, thread_id, status, build_commit, last_edit
			FROM ".db_table."
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

/* 
Small cache for commit -> pr information
Note: Left joining the main query with builds_windows highly increases query time

- Page loaded in 0.0052 seconds (Query: 19570μs)
LEFT JOIN commit_cache AS t2 
ON t1.build_commit = t2.commit_id

- Page loaded in 0.7242 seconds (Query: 7199060μs)
LEFT JOIN builds_windows
ON commit LIKE CONCAT(build_commit, '%') && build_commit != 0

- Page loaded in 0.4061 seconds (Query: 4027700μs)
LEFT JOIN builds_windows
ON SUBSTR(commit,1,7) = SUBSTR(build_commit,1,7) 

- Page loaded in 0.0139 seconds (Query: 15772μs , Store: 97872μs)
Current method
*/
$a_cache = array();

// Stop if too many data is returned on Initials / Levenshtein
$stop = false;


// TODO: Refator cache
if ($q_initials && $q_main2 && mysqli_num_rows($q_initials) == 0 && mysqli_num_rows($q_main2) == 0) {
	while ($row = mysqli_fetch_object($q_main2)) {
		
		// Check if commit has been cached already. If not cache, if yes use cached info.
		$grep = (preg_grep("/^{$row->build_commit}/", array_keys($a_cache)));
		if (count($grep) == 0) {
			$q_builds = mysqli_query($db, "SELECT * FROM builds_windows WHERE commit LIKE '{$row->build_commit}%' LIMIT 1; ");
			
			if (mysqli_num_rows($q_builds) > 0) {
				$buildsRow = mysqli_fetch_object($q_builds);
				$commit = $buildsRow->commit;
				$pr = $buildsRow->pr;
				
				$a_cache[$commit] = $pr;
			} else {
				$commit = $row->build_commit;
				$pr = 0;	
			}
			
		} else {
			$commit = $grep[0];
			$pr = $a_cache[$commit];
			
		}
		
		$a_results[$row->game_id] = array(
		'game_id' => $row->game_id,
		'game_title' => $row->game_title,
		'status' => $row->status,
		'thread_id' => $row->thread_id,
		'last_edit' => $row->last_edit,
		'commit' => $commit,
		'pr' => $pr
		);
		
	}
	if (strlen($get['g']) <= 3) {
		$stop = true;
	}
}

if ($q_main && mysqli_num_rows($q_main) > 0 && !$stop) {
	while ($row = mysqli_fetch_object($q_main)) {
		
		// Check if commit has been cached already. If not cache, if yes use cached info.
		$grep = (preg_grep("/^{$row->build_commit}/", array_keys($a_cache)));
		if (count($grep) == 0) {
			$q_builds = mysqli_query($db, "SELECT * FROM builds_windows WHERE commit LIKE '{$row->build_commit}%' LIMIT 1; ");
			
			if (mysqli_num_rows($q_builds) > 0) {
				$buildsRow = mysqli_fetch_object($q_builds);
				$commit = $buildsRow->commit;
				$pr = $buildsRow->pr;
			
				$a_cache[$commit] = $pr;
			} else {
				$commit = $row->build_commit;
				$pr = 0;	
			}
			
		} else {
			$commit = $grep[0];
			$pr = $a_cache[$commit];
			
		}
		
		$a_results[$row->game_id] = array(
		'game_id' => $row->game_id,
		'game_title' => $row->game_title,
		'status' => $row->status,
		'thread_id' => $row->thread_id,
		'last_edit' => $row->last_edit,
		'commit' => $commit,
		'pr' => $pr
		);
		
	}
}


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
	if ($get['r'] == $a_pageresults[$c_pageresults]) { $g_pageresults = ''; }
	else { $g_pageresults = "r={$get['rID']}&"; }
}


/***********
 * Sort By *
 ***********/
function getSortBy() {
	global $a_title, $a_desc, $scount, $get;

	foreach (range(min(array_keys($a_title)), max(array_keys($a_title))) as $i) { 
		// Displays status description when hovered on
		$s_sortby .= "<a title='$a_desc[$i]' href=\"?"; 
		$s_sortby .= combinedSearch(true, false, true, true, true, true, true, true, false);
		$s_sortby .= "s=$i\">"; 
		
		$temp = "$a_title[$i]&nbsp;({$scount[1][$i]})";
		
		// If the current selected status, highlight with bold
		if ($get['s'] == $i) { $s_sortby .= highlightBold($temp); }
		else { $s_sortby .= $temp; }

		$s_sortby .= "</a>"; 
	}
	return $s_sortby;
}


/********************
 * Results per page *
 ********************/
function getResultsPerPage() {
	global $a_pageresults, $s_pageresults, $get;
	
	foreach (range(min(array_keys($a_pageresults))+1, max(array_keys($a_pageresults))) as $i) { 
		$s_pageresults .= "<a href=\"?"; 
		$s_pageresults .= combinedSearch(false, true, true, true, true, true, true, true);
		$s_pageresults .= "r=$i\">"; 
		
		// If the current selected status, highlight with bold
		if ($get['r'] == $a_pageresults[$i]) { $s_pageresults .= highlightBold($a_pageresults[$i]);} 
		else { $s_pageresults .= $a_pageresults[$i]; }

		$s_pageresults .= "</a>";
		
		// If not the last value then add a separator for the next value
		if ($i < max(array_keys($a_pageresults))) {$s_pageresults .= "&nbsp;•&nbsp;";} 
	}
	return $s_pageresults;
}


/***********************************
 * Clickable URL: Character search *
 **********************************/
function getCharSearch() {
	global $get;

	$a_chars[""] = "All";
	$a_chars["09"] = "0-9";
	foreach (range('a', 'z') as $i) {
		$a_chars[$i] = strtoUpper($i);
	}
	$a_chars["sym"] = "#";
	
	/* Commonly used code: so we don't have to waste lines repeating this */
	$common .= "<td><a href=\"?";
	$common .= combinedSearch(true, true, false, false, true, true, true, false);
	
	foreach ($a_chars as $key => $value) { 
		$s_charsearch .= "{$common}c={$key}\"><div class='compat-search-character'>"; 
		if ($get['c'] == $key) { $s_charsearch .= highlightBold($value); }
		else { $s_charsearch .= $value; }
		$s_charsearch .= "</div></a></td>"; 
	}
	
	return "<tr>{$s_charsearch}</tr>";
}


/*****************
 * Table Headers *
 *****************/
function compat_getTableHeaders() {
	$extra = combinedSearch(true, true, true, true, true, true, true, false);
	
	$headers = array(
		'Game ID' => 1,
		'Game Title' => 2,
		'Status' => 3,
		'Last Test' => 4
	);
	
	return getTableHeaders($headers, $extra);
}


/*****************
 * Table Content *
 *****************/
function getTableContent() {
	global $q_main, $l_title, $l_orig, $a_results;
	
	if ($q_main) {
		if (mysqli_num_rows($q_main) > 0) {
			if ($l_title != "") {
				$s_tablecontent .= "<p class=\"compat-tx1-criteria\">No results found for <i>{$l_orig}</i>. </br> 
				Displaying results for <b><a style=\"color:#06c;\" href=\"?g=".urlencode($l_title)."\">{$l_title}</a></b>.</p>";
			} 
		} else {
			$s_tablecontent .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		}
	}
	
	foreach ($a_results as $key => $value) {
		// prof_flag("Page: Display Table Content: Row - GameID");
		$s = "<td>".getGameRegion($value['game_id'])."&nbsp;&nbsp;".getThread($value['game_id'], $value['thread_id'])."</td>";
		// prof_flag("Page: Display Table Content: Row - Game Title");
		$s .= "<td>".getGameMedia($value['game_id'])."&nbsp;&nbsp;".getThread($value['game_title'], $value['thread_id'])."</td>";
		// prof_flag("Page: Display Table Content: Row - Status");
		$s .= "<td>".getColoredStatus($value['status'])."</td>"; 
		// prof_flag("Page: Display Table Content: Row - Last Updated");
		$s .= "<td><a href=\"?d=".str_replace('-', '', $value['last_edit'])."\">".$value['last_edit']."</a>&nbsp;&nbsp;&nbsp;";

		$s .= $value['pr'] == 0 ? "(<i>Unknown</i>)" : "(<a href='https://github.com/RPCS3/rpcs3/pull/{$value['pr']}'>Pull #{$value['pr']}</a>)";	
		$s .= '</td>';	

		$s_tablecontent .= "<tr>{$s}</tr>";
	}

	return $s_tablecontent;
}


/*****************
 * Pages Counter *
 *****************/
function compat_getPagesCounter() {
	global $pages, $currentPage;
	
	$extra = combinedSearch(true, true, true, true, true, true, true, true);
	
	return getPagesCounter($pages, $currentPage, $extra);
}

/*
return_code
0  - Normal return with results found
1  - Normal return with no results found
2  - Normal return with results found via Levenshtein
-1 - Internal error
-2 - Maintenance
-3 - Illegal search

gameID
  commit
    0 - Unknown / Invalid commit
  status
	Playable/Ingame/Intro/Loadable/Nothing
  date
    yyyy-mm-dd
*/

function APIv1() {
	global $q_main, $c_maintenance, $a_results;
	
	if ($c_maintenance) {
		$results['return_code'] = -2;
		return $results;
	}
	
	if (isset($_GET['g']) && !empty($_GET['g']) && !isValid($_GET['g'])) {
		$results['return_code'] = -3;
		return $results;
	}
	
	// Array to returned, then encoded in JSON
	$results = array();
	$results['return_code'] = 0;
	
	foreach ($a_results as $key => $value) {
		$results['results'][$value['game_id']] = array(
		'title' => $value['game_title'],
		'status' => $value['status'],
		'date' => $value['last_edit'],
		'thread' => intval($value['thread_id']));
		if ($value['commit'] != 0 && $value['pr'] != 0) {
			$results['results'][$value['game_id']]['commit'] = $value['commit'];
		} else {
			$results['results'][$value['game_id']]['commit'] = 0;
		}
	}
	
	if ($q_main) {
		if (mysqli_num_rows($q_main) > 0) {
			if ($l_title != "") {
				$results['return_code'] = 2; // No results found for {$l_orig}. Displaying results for {$l_title}.
			}
		} else {
			$results['return_code'] = 1;
		}
	} else {
		$results['return_code'] = -1;
	}

	return $results;
}

?>