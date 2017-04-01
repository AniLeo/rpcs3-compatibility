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

// Calls for the file that contains the config variables
if (!@include("lib/compat/config.php")) throw new Exception("Compat: config is missing. Failed to include config.php");
// Calls for the file that contains the functions needed
if (!@include("lib/compat/functions.php")) throw new Exception("Compat: functions is missing. Failed to include functions.php");

// Turns off notice/error reporting for regular users
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Start of time calculations
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

// TODO: Some new additions need refactoring
// TODO: Multiple search for date, region

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

$a_histdates = array(
'2017_02' => 'March 1st, 2017',
'2017_03' => 'March 30th, 2017'
);

$currenthist = '2017_04';

/**************************
 * Obtain values from GET *
 **************************/

$get = obtainGet();

/***
 Database Queries
***/

$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
mysqli_set_charset($db, 'utf8');

// Generate query
$genquery = generateQuery($db, $get, true);

// Select the count of games in each status
function countGames() {
	global $a_title, $db, $get;
	
	foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $s) { 
		
		if (generateQuery($db, $get, false) == "") {
			// Empty query or general query with order only, all games returned
			$squery[$s] = "SELECT count(*) AS c FROM ".db_table." WHERE status = {$s}";
		} else {
			// Query defined, return count of games with searched parameters
			$squery[$s] = "SELECT count(*) AS c FROM ".db_table." WHERE (".generateQuery($db, $get, false).") AND status = {$s}";
		}
		
		$scount[$s] = mysqli_fetch_object(mysqli_query($db, $squery[$s]))->c;
		
		// Instead of querying the database once more add all the previous counts to get the total count (subjective to search params)
		$scount[0] += $scount[$s];
	}
	
	return $scount;
}

$scount = countGames();

// Get the total count of entries present in the database (not subjective to search params)
$games = mysqli_fetch_object(mysqli_query($db, "SELECT count(*) AS c FROM ".db_table))->c;


// Page calculation according to the user's search
$pagesCmd = "SELECT count(*) AS c FROM ".db_table;
if ($genquery != "") {
	$pagesCmd .= " WHERE {$genquery} ";
}
$pagesQry = mysqli_query($db, $pagesCmd);
$pages = ceil(mysqli_fetch_object($pagesQry)->c / $get['r']);


// Get current page user is on
// And calculate the number of pages according selected status and results per page
if (isset($_GET['p'])) {
	$currentPage = intval($_GET['p']);
	if ($currentPage > $pages) { $currentPage = 1; }		
} else { $currentPage = 1; }


// Run the main query 
$sqlCmd .= "SELECT game_id, game_title, build_commit, thread_id, status, last_edit FROM ".db_table." ";
if ($genquery != "") {
	$sqlCmd .= " WHERE {$genquery} ";
}
$sqlCmd .= $a_order[$get['o']]." LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};";
$sqlQry = mysqli_query($db, $sqlCmd);


// Abbreviation search
if ($get['g'] != "") {
	$abbreviationQuery = mysqli_query($db, "SELECT * FROM initials_cache WHERE initials LIKE '%".mysqli_real_escape_string($db, $get['g'])."%'; ");

	if ($abbreviationQuery && mysqli_num_rows($abbreviationQuery) > 0) {
		$partOne = "SELECT * FROM ".db_table." WHERE ";
		$i = 0;
		
		while($row = mysqli_fetch_object($abbreviationQuery)) {
			if ($i > 0) { $partTwo .= " OR "; }
			$partTwo .= " game_title = '".mysqli_real_escape_string($db, $row->game_title)."' ";
			$i++;
		}
		
		// Recalculate pages if abbreviation searched is smaller than 3 characters because other results aren't being displayed
		if (strlen($get['g'] < 3)) {
			// Recalculate pages
			$pagesQry = mysqli_query($db, "SELECT count(*) AS c FROM ".db_table." WHERE {$partTwo}; ");
			$pages = ceil(mysqli_fetch_object($pagesQry)->c / $get['r']);
			// Get current page
			if (isset($_GET['p'])) {
				$currentPage = intval($_GET['p']);
				if ($currentPage > $pages) { $currentPage = 1; }		
			} else { $currentPage = 1; }
			
			// Recount games
			foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $s) { 
				// Query defined, return count of games with searched parameters
				$squery[$s] = "SELECT count(*) AS c FROM ".db_table." WHERE ({$partTwo}) AND status = {$s}";
				
				$scount[$s] = mysqli_fetch_object(mysqli_query($db, $squery[$s]))->c;
				
				// Instead of querying the database once more add all the previous counts to get the total count (subjective to search params)
				$scount[0] += $scount[$s];
			}
		}
		
		$sqlQry2 = mysqli_query($db, "{$partOne} {$partTwo} LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};");	
	}

	// If results not found then apply levenshtein to get the closest result
	// TODO: Refactor
	$levCheck = mysqli_query($db, "SELECT * FROM ".db_table." WHERE game_title LIKE '%".mysqli_real_escape_string($db, $get['g'])."%'; ");

	if ($levCheck && mysqli_num_rows($levCheck) === 0 && $abbreviationQuery && mysqli_num_rows($abbreviationQuery) === 0) {
		$l_title = "";
		$l_dist = -1;
		$sfo = "";
		if ($sqlQry && mysqli_num_rows($sqlQry) == 0) {
			$sqlCmd2 = "SELECT * FROM ".db_table."; ";
			$sqlQry2 = mysqli_query($db, $sqlCmd2);
			
			while($row = mysqli_fetch_object($sqlQry2)) {
				$lev = levenshtein($get['g'], $row->game_title);
				
				if ($lev <= $l_dist || $l_dist < 0) {
					$l_title = $row->game_title;
					$l_dist = $lev;
				}
			}
			
			if ($l_title != "") {
				$sqlCmd = "SELECT game_id, game_title, build_commit, thread_id, status, last_edit
						FROM ".db_table." WHERE game_title = '".mysqli_real_escape_string($db, $l_title)."' 
						LIMIT ".($get['r']*$currentPage-$get['r']).", {$get['r']};";
				$sqlQry = mysqli_query($db, $sqlCmd);
				
				// Recalculate pages
				$pagesQry = mysqli_query($db, "SELECT count(*) AS c FROM ".db_table." WHERE game_title = '".mysqli_real_escape_string($db, $l_title)."' ;");
				$pages = ceil(mysqli_fetch_object($pagesQry)->c / $get['r']);
				if (isset($_GET['p'])) {
					$currentPage = intval($_GET['p']);
					if ($currentPage > $pages) { $currentPage = 1; }		
				} else { $currentPage = 1; }
				
				$sfo = $get['g'];
				$get['g'] = $l_title;
				
				// Recount games
				foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $s) { 
					// Query defined, return count of games with searched parameters
					$squery[$s] = "SELECT count(*) AS c FROM ".db_table." WHERE game_title = '".mysqli_real_escape_string($db, $l_title)."' AND status = {$s}";
					
					$scount[$s] = mysqli_fetch_object(mysqli_query($db, $squery[$s]))->c;
					
					// Instead of querying the database once more add all the previous counts to get the total count (subjective to search params)
					$scount[0] += $scount[$s];
				}
			}
		}
	}
}

// Close MySQL connection. If user is search
mysqli_close($db);

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
	global $a_title, $a_desc, $g_pageresults, $scount, $get;

	foreach (range(min(array_keys($a_title)), max(array_keys($a_title))) as $i) { 
		// Displays status description when hovered on
		$s_sortby .= "<a title='$a_desc[$i]' href=\"?"; 
		
		// Combined search: results per page
		$s_sortby .= $g_pageresults;
		// Combined search: search by character
		if ($get['c'] != "") {$s_sortby .= "c={$get['c']}&";}
		// Combined search: searchbox
		if ($get['g'] != "" && $scount[0] > 0)	{$s_sortby .= "g=".urlencode($get['g'])."&";} 
		
		$s_sortby .= "s=$i\">"; 
		
		$temp = "$a_title[$i]&nbsp;($scount[$i])";
		
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
	global $a_pageresults, $s_pageresults, $scount, $a_title, $get;
	
	foreach (range(min(array_keys($a_pageresults)), max(array_keys($a_pageresults))) as $i) { 
		$s_pageresults .= "<a href=\"?"; 
		
		// Combined search: sort by status
		if ($get['s'] > min(array_keys($a_title))) {$s_pageresults .= "s={$get['s']}&";} 
		// Combined search: search by character
		if ($get['c'] != "") {$s_pageresults .= "c={$get['c']}&";} 
		// Combined search: searchbox
		if ($get['g'] != "" && $scount[0] > 0) {$s_pageresults .= "g=".urlencode($get['g'])."&";} 
		
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


/***********************
 * Status descriptions *
 ***********************/
function getStatusDescriptions() {
	global $a_desc, $a_color, $a_title;
	
	foreach (range((min(array_keys($a_desc))+1), max(array_keys($a_desc))) as $i) { 
		$s_descontainer .= "<div id=\"compat-con-status\">
								<div id=\"compat-ico-status\" style=\"background:#{$a_color[$i]}\"></div>
								<div id=\"compat-tx1-status\"><strong>{$a_title[$i]}:</strong> {$a_desc[$i]}</div>
							</div>";
	}	
	return $s_descontainer;
}


/***********************************
 * Clickable URL: Character search *
 **********************************/
function getCharSearch() {
	global $g_pageresults, $a_css, $a_title, $get;
	
	/* Commonly used code: so we don't have to waste lines repeating this */
	$common .= "<td><a href=\"?";

	// Combined search: results per page
	$common .= $g_pageresults;
	// Combined search: search by status
	if ($get['s'] > min(array_keys($a_title))) {$common .= "s={$get['s']}&";} 
	
	
	/* ALL */
	$s_charsearch .= $common;
	$s_charsearch .= "c=\"><div id=\"{$a_css["CHARACTER_SEARCH"]}\">"; 
	if ($get['c'] == "") { $s_charsearch .= highlightBold("All"); }
	else { $s_charsearch .= "All"; }
	$s_charsearch .= "</div></a></td>"; 

	/* A-Z */
	foreach (range('a', 'z') as $i) { 
		$s_charsearch .= $common;
		$s_charsearch .= "c=$i\"><div id=\"{$a_css["CHARACTER_SEARCH"]}\">";
		if ($get['c'] == $i) { $s_charsearch .= highlightBold(strToUpper($i)); }
		else { $s_charsearch .= strToUpper($i); }
		$s_charsearch .= "</div></a></td>"; 
	} 

	/* Numbers */
	$s_charsearch .= $common;
	$s_charsearch .= "c=09\"><div id=\"{$a_css["CHARACTER_SEARCH"]}\">"; 
	if ($get['c'] == "09") { $s_charsearch .= highlightBold("0-9"); }
	else { $s_charsearch .= "0-9"; }
	$s_charsearch .= "</div></a></td>"; 
	
	
	/* Symbols */
	$s_charsearch .= $common;
	$s_charsearch .= "c=sym\"><div id=\"{$a_css["CHARACTER_SEARCH"]}\">"; 
	if ($get['c'] == "sym") { $s_charsearch .= highlightBold("#"); }
	else { $s_charsearch .= "#"; }
	$s_charsearch .= "</div></a></td>";
	
	return $s_charsearch;
}


/*****************
 * Table Headers *
 *****************/
function getTableHeaders() {
	global $g_pageresults, $scount, $a_title, $get;
	
	$s_tableheaders .= "<tr>";
	
	/* Commonly used code: so we don't have to waste lines repeating this */
	$common .= "<th><a href =\"?";

	// Order support: Sort by status
	if ($get['s'] > min(array_keys($a_title))) {$common .= "s={$get['s']}&";} 
	// Order support: Results per page
	$common .= $g_pageresults;
	// Order support: Search by character
	if ($get['c'] != "") {$common .= "c={$get['c']}&";} 
	// Order support: Searchbox
	if ($get['g'] != "" && $scount[0] > 0) {$common .= "g=".urlencode($get['g'])."&";} 
	
	
	/* Game ID */
	$s_tableheaders .= $common;
	// Order by: Game ID (ASC, DESC)
	if ($get['o'] == "1a") { $s_tableheaders .= "o=1d\">Game ID &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "1d") { $s_tableheaders .= "\">Game ID &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=1a\">Game ID</a></th>"; } 

	/* Game Title */
	$s_tableheaders .= $common;
	// Order by: Game Title (ASC, DESC)
	if ($get['o'] == "2a") { $s_tableheaders .= "o=2d\">Game Title &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "2d") { $s_tableheaders .= "\">Game Title &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=2a\">Game Title</a></th>"; }

	/* Build Used */
	$s_tableheaders .= "<th>Build Used</th>";

	/* Status */
	$s_tableheaders .= $common;
	// Order by: Status (ASC, DESC)
	if ($get['o'] == "3a") { $s_tableheaders .= "o=3d\">Status &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "3d") { $s_tableheaders .= "\">Status &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=3a\">Status</a></th>"; }

	/* Last Updated */
	$s_tableheaders .= $common;
	// Order by: Last Updated (ASC, DESC)
	if ($get['o'] == "4a") { $s_tableheaders .= "o=4d\">Last Updated &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "4d") { $s_tableheaders .= "\">Last Updated &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=4a\">Last Updated</a></th>"; }
	
	$s_tableheaders .= "</tr>";
	
	return $s_tableheaders;
}


/*****************
 * Table Content *
 *****************/
function getTableContent() {
	global $sqlQry, $sqlQry2, $abbreviationQuery, $l_title, $sfo, $get;
	
	if ($abbreviationQuery && mysqli_num_rows($abbreviationQuery) > 0 && $sqlQry2 && mysqli_num_rows($sqlQry2) > 0) {
		while($row = mysqli_fetch_object($sqlQry2)) {
			$s_tablecontent .= getTableContentRow($row);
		}
		// If used abbreviation is smaller than 3 characters then don't return normal results as they're probably a lot and unrelated
		if (strlen($get['g']) < 3) {
			return $s_tablecontent;
		}
	}
	
	if ($sqlQry) {
		if (mysqli_num_rows($sqlQry) > 0) {
			if ($l_title != "") {
				$s_tablecontent .= "<p class=\"compat-tx1-criteria\">No results found for <i>{$sfo}</i>. </br> 
				Displaying results for <b><a style=\"color:#06c;\" href=\"?g=".urlencode($l_title)."\">{$l_title}</a></b>.</p>";
			}
			while($row = mysqli_fetch_object($sqlQry)) {
				$s_tablecontent .= getTableContentRow($row);
			}
		}
	} else {
		// Query generator fail error
		$s_tablecontent .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
	}
	return $s_tablecontent;
}


/**********************
 * Table Content: Row *
 **********************/
function getTableContentRow($row) {
	return "<tr>
	<td>".getGameRegion($row->game_id, true)."&nbsp;&nbsp;".getThread($row->game_id, $row->thread_id)."</td>
	<td>".getGameMedia($row->game_id)."&nbsp;&nbsp;".getThread($row->game_title, $row->thread_id)."</td>
	<td>".getCommit($row->build_commit)."</td>
	<td>".getColoredStatus($row->status)."</td>
	<td><a href=\"?d=".str_replace('-', '', $row->last_edit)."\">".$row->last_edit."</a></td>
	</tr>";	
}


/*****************
 * Pages Counter *
 *****************/
function getPagesCounter() {
	global $pages, $currentPage, $g_pageresults, $a_title, $get;
	
	// IF no results are found then the amount of pages is 0
	// Shows no results found message
	if ($pages == 0) { 
		$s_pagescounter .= 'No results found using the selected search criteria.';
	} 
	// Shows current page and total pages
	else { 
		$s_pagescounter .= "Page {$currentPage} of {$pages} - "; 
	}
	
	// Commonly used code
	$common = "<a href=\"?";
	
	// Page support: Sort by status
	if ($get['s'] > min(array_keys($a_title))) {$common .= "s={$get['s']}&";} 
	// Page support: Results per page
	$common .= $g_pageresults;
	// Page support: Search by character
	if ($get['c'] != "") {$common .= "c={$get['c']}&";} 
	// Page support: Search by region
	if ($get['f'] != "") {$common .= "f={$get['f']}&";} 
	// Page support: Date search
	if ($get['f'] != "") {$common .= "d={$get['d']}&";} 
	// Page support: Order by
	if ($get['o'] != "") {$common .= "o={$get['o']}&";} 
	
	
	// Loop for each page link and make it properly clickable until there are no more pages left
	for ($i=1; $i<=$pages; $i++) { 
	
		if ( ($i >= $currentPage-7 && $i <= $currentPage) || ($i+7 >= $currentPage && $i <= $currentPage+7) ) {
			
			$s_pagescounter .= $common;
			
			// Display number of the page and highlight if current page
			$s_pagescounter .= "p=$i\">";
			if ($i == $currentPage) { if ($i < 10) { $s_pagescounter .= highlightBold("0"); } $s_pagescounter .= highlightBold($i); }
			else { if ($i < 10) { $s_pagescounter .= "0"; } $s_pagescounter .= $i; }
			
			$s_pagescounter .= "</a>&nbsp;&#32;"; 
		
		} elseif ($i == 1) {
			// First page
			$s_pagescounter .= $common;
			$s_pagescounter .= "p=$i-1\">01</a>&nbsp;&#32;...&nbsp;&#32;"; 
		} elseif ($pages == $i) {
			// Last page
			$s_pagescounter .= "...&nbsp;&#32;";
			$s_pagescounter .= $common;
			$s_pagescounter .= "p=$pages\">$pages</a>&nbsp;&#32;"; 
		}
		
	}
	
	return $s_pagescounter;
}

function getHistoryOptions() {
	global $get, $a_histdates;
	
	$s_historyoptions .= "<p>You're now watching the updates that altered a game's status for RPCS3's Compatibility List since {$a_histdates[$get['h2']]}.</p>";

	$m1 = "<a href=\"?h=2017_03\">March 2017</a>";
	$m2 = "<a href=\"?h\">April 2017</a>";
	
	$s_historyoptions .= "</br><p style=\"font-size:13px\">
	<strong>Month:&nbsp;</strong>";
	
	if ($get['h2'] == "2017_02") { 
		$s_historyoptions .= highlightBold($m1);
	} else {
		$s_historyoptions .= $m1;
	}
	
	$s_historyoptions .= "&nbsp;&#8226;&nbsp";
	
	if ($get['h1'] == db_table) { 
		$s_historyoptions .= highlightBold($m2);
	} else {
		$s_historyoptions .= $m2;
	}
	
	$s_historyoptions .= "</br>";
	
	$o1 = "<a href=\"?h\">Show all entries</a>";
	$o2 = "<a href=\"?h&m=c\">Show only previously existent entries</a>";
	$o3 = "<a href=\"?h&m=n\">Show only new entries</a>";
	
	if ($get['h1'] != "") { 
		$s_historyoptions .= highlightBold($o1);
	} else {
		$s_historyoptions .= $o1;
	}
	$s_historyoptions .= " <a href=\"?h&rss\">(RSS)</a>&nbsp;&#8226;&nbsp;";
	
	if ($get['h1'] != "" && $get['m'] == "c") { 
		$s_historyoptions .= highlightBold($o2);
	} else {
		$s_historyoptions .= $o2;
	}
	$s_historyoptions .= " <a href=\"?h&m=c&rss\">(RSS)</a>&nbsp;&#8226;&nbsp;";
	
	if ($get['h1'] != "" && $get['m'] == "n") { 
		$s_historyoptions .= highlightBold($o3);
	} else {
		$s_historyoptions .= $o3;
	}
	$s_historyoptions .= " <a href=\"?h&m=n&rss\">(RSS)</a>&nbsp;&#8226;&nbsp;";
	
	$s_historyoptions .= "<a href=\"?\">Back to Compatibility List</a>";
	
	$s_historyoptions .= "</p>";
	
	return $s_historyoptions;
}

// Compatibility History: Pulls information from backup and compares with current database
function getHistory(){
	global $get; 
	
	// Establish MySQL connection to be used for history
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	if ($get['m'] == "c" || $get['m'] == "") {
		$cQuery = mysqli_query($db, 
		"SELECT t1.game_id AS gid, t1.game_title AS title, t1.thread_id AS tid, t1.status AS new_status, t2.status AS old_status, t1.last_edit AS new_date, t2.last_edit AS old_date
		FROM {$get['h1']} AS t1
		LEFT JOIN {$get['h2']} AS t2
		ON t1.game_id=t2.game_id
		WHERE t1.status != t2.status 
		ORDER BY new_status ASC, -old_status DESC, title ASC; ");
	
		if (!$cQuery) { 
			echo "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		} elseif (mysqli_num_rows($cQuery) == 0) {
			echo "<p class=\"compat-tx1-criteria\">No results found for the selected criteria.</p>";
		} else {
			echo "
			<table class='compat-con-container'><tr>
			<th>Game ID</th>
			<th>Game Title</th>
			<th>New Status</th>
			<th>New Date</th>
			<th>Old Status</th>
			<th>Old Date</th>
			</tr>";
			
			while($row = mysqli_fetch_object($cQuery)) {
				echo "<tr>
				<td>".getGameRegion($row->gid, false)."&nbsp;&nbsp;".getThread($row->gid, $row->tid)."</td>
				<td>".getGameMedia($row->gid)."&nbsp;&nbsp;".getThread($row->title, $row->tid)."</td>
				<td>".getColoredStatus($row->new_status)."</td>
				<td>{$row->new_date}</td>
				<td>".getColoredStatus($row->old_status)."</td>
				<td>{$row->old_date}</td>
				</tr>";	
			}
			echo "</table></br>";
		}
	}
	
	if ($get['m'] == "n" || $get['m'] == "") {
		$nQuery = mysqli_query($db, 
		"SELECT t1.game_id AS gid, t1.game_title AS title, t1.thread_id AS tid, t1.status AS new_status, t2.status AS old_status, t1.last_edit AS new_date, t2.last_edit AS old_date
		FROM {$get['h1']} AS t1
		LEFT JOIN {$get['h2']} AS t2
		ON t1.game_id=t2.game_id
		WHERE t2.status IS NULL
		ORDER BY new_status ASC, -old_status DESC, title ASC; ");
		
		if (!$nQuery) { 
			echo "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		} elseif (mysqli_num_rows($nQuery) == 0) {
			echo "<p class=\"compat-tx1-criteria\">No results found for the selected criteria.</p>";
		} else {
			echo "</br>
			<p class=\"compat-tx1-criteria\"><strong>Newly reported games</strong></p>
			<table class='compat-con-container'><tr>
			<th>Game ID</th>
			<th>Game Title</th>
			<th>Status</th>
			<th>Date</th>
			</tr>";
			
			while($row = mysqli_fetch_object($nQuery)) {
				echo "<tr>
				<td>".getGameRegion($row->gid, false)."&nbsp;&nbsp;".getThread($row->gid, $row->tid)."</td>
				<td>".getGameMedia($row->gid)."&nbsp;&nbsp;".getThread($row->title, $row->tid)."</td>
				<td>".getColoredStatus($row->new_status)."</td>
				<td>{$row->new_date}</td>
				</tr>";	
			}
			echo "</table></br>";
		}
	}
	
	// Close MySQL connection again since it won't be required
	mysqli_close($db);
}


function getHistoryRSS(){
	global $c_forum, $get;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$rssCmd = "
	SELECT t1.game_id AS gid, t1.game_title AS title, t1.thread_id AS tid, t1.status AS new_status, t2.status AS old_status, t1.last_edit AS new_date, t2.last_edit AS old_date
	FROM {$get['h1']} AS t1
	LEFT JOIN {$get['h2']} AS t2
	ON t1.game_id=t2.game_id ";
	if ($get['m'] == "c") {
		$rssCmd .= " WHERE t1.status != t2.status ";
	} elseif ($get['m'] == "n") {
		$rssCmd .= " WHERE t2.status IS NULL ";
	} else {
		$rssCmd .= " WHERE t1.status != t2.status OR t2.status IS NULL ";
	}
	$rssCmd .= "ORDER BY new_date DESC, new_status ASC, -old_status DESC, title ASC; ";
	
	$rssQuery = mysqli_query($db, $rssCmd);
	
	if (!$rssQuery) {
		return "An error occoured. Please try again. If the issue persists contact RPCS3 team.";
	}

    $rssfeed = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
				<rss version=\"2.0\">
				<channel>
				<title>RPCS3 Compatibility List History's RSS feed</title>
				<link>https://rpcs3.net/compatibility?h</link>
				<description>For more information about RPCS3 visit https://rpcs3.net</description>
				<language>en-uk</language>";
 
    while($row = mysqli_fetch_object($rssQuery)) {
 
        $rssfeed .= "<item>
					<title><![CDATA[{$row->gid} - {$row->title}]]></title>
					<link>{$c_forum}{$row->tid}</link>";
		
		if ($row->old_status !== NULL) {
			$rssfeed .= "<description>Updated from {$row->old_status} ({$row->old_date}) to {$row->new_status} ({$row->new_date})</description>";
		} else {
			$rssfeed .= "<description>New entry for {$row->new_status} ({$row->new_date})</description>";
		}
        
		$rssfeed .= "<pubDate>{$row->new_date}</pubDate>
					</item>";
    }
 
    $rssfeed .= "</channel>
				</rss>";
				
	// Close MySQL connection again since it won't be required
	mysqli_close($db);
	
    return $rssfeed;
}

/*****************************************************************************************************************************/

// End of time calculations
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);

?>
