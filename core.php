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

// Initialize variables with the default values
// Results per page [Default: 50 (from config)]
$r = $a_pageresults[$c_pageresults];
$rid = $c_pageresults;
// Display status (0-All; 1-Playable; 2-Ingame; 3-Intro; 4-Loadable; 5-Nothing) [Default: 0]
$s = 0;
// Character searched by [Default: none]
$c = "";
// Order by [Default: none]
$o = "";
// Search box content [Default: none]
$sf = "";
// Search by region [Default: none]
$f = "";
// Search by date [Default: none]
$d = "";

// Order queries
$a_order = array(
'1a' => 'ORDER BY game_id ASC',
'1d' => 'ORDER BY game_id DESC',
'2a' => 'ORDER BY game_title ASC',
'2d' => 'ORDER BY game_title DESC',
'3a' => 'ORDER BY status ASC',
'3d' => 'ORDER BY status DESC',
'4a' => 'ORDER BY last_edit ASC',
'4d' => 'ORDER BY last_edit DESC'
);

/**************************
 * Obtain values from GET *
 **************************/

// Get requested 'r' parameter and convert it to the amount results per page to display
if (isset($_GET['r']) && array_key_exists($_GET['r'], $a_pageresults)) {
	$r = $a_pageresults[$_GET['r']];
	$rid = $_GET['r'];
	// If 'r' isn't any of the above values or not provided it will remain as default [50]
}

// Get requested 's' parameter and convert it to the status ID
if (isset($_GET['s']) && array_key_exists($_GET['s'], $a_title)) {
	$s = $_GET['s'];
	// If 's' isn't any of the above values or not provided it will remain as default [0]
}

// Order by
if (isset($_GET['o']) && array_key_exists($_GET['o'], $a_order)) {
	$o = strtolower($_GET['o']);
}

// Search by character: Get character
if (isset($_GET['c'])) {
	// For each letter between a to z: Check if one is selected
	foreach (range('a', 'z') as $char) {
		// strToLower is there in case someone decides to manually write the URL and use UpperCase chars
		if ($_GET['c'] == strtolower($char)) { $c = strtolower($char); }
	}
	if ($_GET['c'] == "09")  { $c = "09";  } // Numbers
	if ($_GET['c'] == "sym") { $c = "sym"; } // Symbols
}

// Search box: Get provided input
if (isset($_GET['sf']) && !empty($_GET['sf']) && isValid($_GET['sf'])) {
	$sf = $_GET['sf'];
}

// Search by region
if (isset($_GET['f'])) {
	if ($_GET['f'] == "a" || $_GET['f'] == "h" || $_GET['f'] == "e" || $_GET['f'] == "u" || $_GET['f'] == "j") { $f = strtolower($_GET['f']); }
}

// Search by date, simple checks for valid values
if (isset($_GET['d']) && is_numeric($_GET['d']) && strlen($_GET['d']) == 8 && strpos($_GET['d'], '20') === 0) {
	$d = $_GET['d'];
}

// History
if (isset($_GET['h'])) {
	$h1 = "rpcs3"; $h2 = "2017_03";  // current
} else { $h1 = "rpcs3"; $h2 = "2017_03"; } // current

/***
 Database Queries
***/

// Query generation, activate!
$genquery = " WHERE ";

// QUERYGEN: Status
if ($s > min(array_keys($a_title))) { $genquery .= " status = $s "; } 

// QUERYGEN: Character
if ($c != "") {
	if ($c == '09') {
		if ($s > min(array_keys($a_title))) { $genquery .= " AND "; }
		$genquery = $genquery . " (game_title LIKE '0%' OR game_title LIKE '1%' OR game_title LIKE '2%'
		OR game_title LIKE '3%' OR game_title LIKE '4%' OR game_title LIKE '5%' OR game_title LIKE '6%' OR game_title LIKE '7%'
		OR game_title LIKE '8%' OR game_title LIKE '9%') ";
	} elseif ($c == 'sym') {
		if ($s > min(array_keys($a_title))) { $genquery .= " AND "; }
		$genquery = $genquery . " (game_title LIKE '.%' OR game_title LIKE '&%') "; // TODO: Add more symbols when they show up
	} else {
		if ($s > min(array_keys($a_title))) { $genquery .= " AND "; }
		$genquery = $genquery . " game_title LIKE '".$c."%' ";
	}
}

// QUERYGEN: Searchbox
if ($sf != "") {
	if ($s > min(array_keys($a_title)) && $c == "") { $genquery .= " AND "; }
	if ($c != "") { $genquery .= " AND "; }
	$ssf = mysqli_real_escape_string($db, $sf);
	$genquery .= " (game_title LIKE '%".$ssf."%' OR game_id LIKE '%".$ssf."%') ";
}

// QUERYGEN: Search by region
if ($f != "") {
	if ($s > min(array_keys($a_title)) && $c == "") { $genquery .= " AND "; }
	if ($c != "" || $sf != "") { $genquery .= " AND "; }
	$genquery .= " SUBSTR(game_id, 3, 1) = '".$f."' ";
}

// QUERYGEN: Search by date
if ($d != "") {
	if ($s > min(array_keys($a_title)) && $c == "" && $f != "") { $genquery .= " AND "; }
	if ($c != "" || $sf != "" || $f != "") { $genquery .= " AND "; }
	$sd = mysqli_real_escape_string($db, $d);
	$genquery .= " last_edit = '$sd' "; 
}

// QUERYGEN: Order
if ($genquery == " WHERE ") { $genquery = " "; }
if ($o == "") {
	$genquery .= " ORDER BY status ASC, game_title ASC ";
} else {
	$genquery .= " ".$a_order[$o]." ";
}

if ($genquery == " WHERE ") { $genquery = " "; }
// Query generation, end.


// Select the count of games in each status
$scquery = array();
foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $sc) { 
	if ($sf != "") {
		$ssf = mysqli_real_escape_string($db, $sf);
		$scquery[$sc] = "SELECT count(*) AS c FROM ".db_table." WHERE (game_title LIKE '%$ssf%' OR game_id LIKE '%$ssf%') AND status = $sc";
	} else {
		$scquery[$sc] = "SELECT count(*) AS c FROM ".db_table." WHERE status = $sc";
	}
}

$scount = array();
foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $sc) { 
	if ($c != "" && $c != "09" && $c != "sym") {
		$scquery[$sc] .= " AND game_title LIKE '$c%'";
	}
	if ($c == "09") {
		$scquery[$sc] .= " AND (game_title LIKE '0%' OR game_title LIKE '1%' OR game_title LIKE '2%'
		OR game_title LIKE '3%' OR game_title LIKE '4%' OR game_title LIKE '5%' OR game_title LIKE '6%' OR game_title LIKE '7%'
		OR game_title LIKE '8%' OR game_title LIKE '9%') ";
	}
	if ($c == "sym") {
		$scquery[$sc] .= " AND (game_title LIKE '.%' OR game_title LIKE '&%') ";
	}
	$scount[$sc] = mysqli_fetch_object(mysqli_query($db, $scquery[$sc]))->c;
}

// Get the total count of entries present in the database (not subjective to search params)
$games = mysqli_fetch_object(mysqli_query($db, "SELECT count(*) AS c FROM ".db_table))->c;

// Instead of querying the database once more add all the previous counts to get the total count (subjective to search params)
foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $sc) {
	$scount[0] += $scount[$sc];
}


// Page calculation according to the user's search
$pagesCmd = "SELECT count(*) AS c FROM ".db_table." $genquery ;";
$pagesQry = mysqli_query($db, $pagesCmd);
$pages = ceil(mysqli_fetch_object($pagesQry)->c / $r);


// Get current page user is on
// And calculate the number of pages according selected status and results per page
if (isset($_GET['p'])) {
	$currentPage = intval($_GET['p']);
	if ($currentPage > $pages) { $currentPage = 1; }		
} else { $currentPage = 1; }


// Run the main query 
$sqlCmd = "SELECT game_id, game_title, build_commit, thread_id, status, last_edit
			FROM ".db_table." "
			.$genquery.
			"LIMIT ".($r*$currentPage-$r).", $r;";
$sqlQry = mysqli_query($db, $sqlCmd);


// If results not found then apply levenshtein to get the closest result
$l_title = "";
$l_dist = -1;
$sfo = "";
if ($sqlQry && mysqli_num_rows($sqlQry) == 0) {
	$sqlCmd2 = "SELECT * FROM ".db_table."; ";
	$sqlQry2 = mysqli_query($db, $sqlCmd2);
	
	while($row = mysqli_fetch_object($sqlQry2)) {
		$lev = levenshtein($sf, $row->game_title);
		
		if ($lev <= $l_dist || $l_dist < 0) {
			$l_title = $row->game_title;
			$l_dist = $lev;
		}
	}
	
	if ($l_title != "") {
		$sqlCmd = "SELECT game_id, game_title, build_commit, thread_id, status, last_edit
				FROM ".db_table." WHERE game_title LIKE '%{$l_title}%' 
				LIMIT ".($r*$currentPage-$r).", $r;";
		$sqlQry = mysqli_query($db, $sqlCmd);
		
		// Recalculate pages
		$pagesQry = mysqli_query($db, "SELECT count(*) AS c FROM ".db_table." WHERE game_title LIKE '%{$l_title}%' ;");
		$pages = ceil(mysqli_fetch_object($pagesQry)->c / $r);
		if (isset($_GET['p'])) {
			$currentPage = intval($_GET['p']);
			if ($currentPage > $pages) { $currentPage = 1; }		
		} else { $currentPage = 1; }
		
		$sfo = $sf;
		$sf = $l_title;
	}
}

// Close MySQL connection. If user is search
mysqli_close($db);

/*****************************************************************************************************************************/


/*******************************
 * General: Combined Search    *
 *   Results per Page          *
 *******************************/
if (in_array($r, $a_pageresults)) {
	if ($r == $a_pageresults[$c_pageresults]) { $g_pageresults = ''; }
	else { $g_pageresults = "r=$rid&"; }
}


/***********
 * Sort By *
 ***********/
function getSortBy() {
	global $a_title, $a_desc, $g_pageresults, $scount, $c, $s, $sf;

	foreach (range(min(array_keys($a_title)), max(array_keys($a_title))) as $i) { 
		// Displays status description when hovered on
		$s_sortby .= "<a title='$a_desc[$i]' href=\"?"; 
		
		// Combined search: results per page
		$s_sortby .= $g_pageresults;
		// Combined search: search by character
		if ($c != "") {$s_sortby .= "c=$c&";}
		// Combined search: searchbox
		if ($sf != "" && $scount[0] > 0)	{$s_sortby .= "sf=".urlencode($sf)."&";} 
		
		$s_sortby .= "s=$i\">"; 
		
		$temp = "$a_title[$i]&nbsp;($scount[$i])";
		
		// If the current selected status, highlight with bold
		if ($s == $i) { $s_sortby .= highlightBold($temp); }
		else { $s_sortby .= $temp; }

		$s_sortby .= "</a>"; 
	}
	return $s_sortby;
}


/********************
 * Results per page *
 ********************/
function getResultsPerPage() {
	global $a_pageresults, $s, $c, $sf, $s_pageresults, $scount, $r, $a_title;
	
	foreach (range(min(array_keys($a_pageresults)), max(array_keys($a_pageresults))) as $i) { 
		$s_pageresults .= "<a href=\"?"; 
		
		// Combined search: sort by status
		if ($s > min(array_keys($a_title))) {$s_pageresults .= "s=$s&";} 
		// Combined search: search by character
		if ($c != "") {$s_pageresults .= "c=$c&";} 
		// Combined search: searchbox
		if ($sf != "" && $scount[0] > 0) {$s_pageresults .= "sf=".urlencode($sf)."&";} 
		
		$s_pageresults .= "r=$i\">"; 
		
		// If the current selected status, highlight with bold
		if ($r == $a_pageresults[$i]) { $s_pageresults .= highlightBold($a_pageresults[$i]);} 
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
		$s_descontainer .= '<div id="compat-con-status">
								<div id="compat-ico-status" style="background:#'.$a_color[$i].'"></div>
								<div id="compat-tx1-status"><strong>'.$a_title[$i].':</strong> '.$a_desc[$i].'</div>
							</div>';
	}	
	return $s_descontainer;
}


/***********************************
 * Clickable URL: Character search *
 **********************************/
function getCharSearch() {
	global $g_pageresults, $s, $c, $a_css, $a_title;
	
	/* Commonly used code: so we don't have to waste lines repeating this */
	$common .= "<td><a href=\"?";

	// Combined search: results per page
	$common .= $g_pageresults;
	// Combined search: search by status
	if ($s > min(array_keys($a_title))) {$common .= "s=$s&";} 
	
	
	/* ALL */
	$s_charsearch .= $common;
	$s_charsearch .= "c=\"><div id=\"{$a_css["CHARACTER_SEARCH"]}\">"; 
	if ($c == "") { $s_charsearch .= highlightBold("All"); }
	else { $s_charsearch .= "All"; }
	$s_charsearch .= "</div></a></td>"; 

	/* A-Z */
	foreach (range('a', 'z') as $i) { 
		$s_charsearch .= $common;
		$s_charsearch .= "c=$i\"><div id=\"{$a_css["CHARACTER_SEARCH"]}\">";
		if ($c == $i) { $s_charsearch .= highlightBold(strToUpper($i)); }
		else { $s_charsearch .= strToUpper($i); }
		$s_charsearch .= "</div></a></td>"; 
	} 

	/* Numbers */
	$s_charsearch .= $common;
	$s_charsearch .= "c=09\"><div id=\"{$a_css["CHARACTER_SEARCH"]}\">"; 
	if ($c == "09") { $s_charsearch .= highlightBold("0-9"); }
	else { $s_charsearch .= "0-9"; }
	$s_charsearch .= "</div></a></td>"; 
	
	
	/* Symbols */
	$s_charsearch .= $common;
	$s_charsearch .= "c=sym\"><div id=\"{$a_css["CHARACTER_SEARCH"]}\">"; 
	if ($c == "sym") { $s_charsearch .= highlightBold("#"); }
	else { $s_charsearch .= "#"; }
	$s_charsearch .= "</div></a></td>";
	
	return $s_charsearch;
}


/*****************
 * Table Headers *
 *****************/
function getTableHeaders() {
	global $s, $c, $g_pageresults, $sf, $scount, $o, $a_title;
	
	/* Commonly used code: so we don't have to waste lines repeating this */
	$common .= "<th><a href =\"?";

	// Order support: Sort by status
	if ($s > min(array_keys($a_title))) {$common .= "s=$s&";} 
	// Order support: Results per page
	$common .= $g_pageresults;
	// Order support: Search by character
	if ($c != "") {$common .= "c=$c&";} 
	// Order support: Searchbox
	if ($sf != "" && $scount[0] > 0) {$common .= "sf=".urlencode($sf)."&";} 
	
	
	/* Game ID */
	$s_tableheaders .= $common;
	// Order by: Game ID (ASC, DESC)
	if ($o == "1a") { $s_tableheaders .= "o=1d\">Game ID &nbsp; &#8593;</a></th>"; }
	elseif ($o == "1d") { $s_tableheaders .= "\">Game ID &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=1a\">Game ID</a></th>"; } 

	/* Game Title */
	$s_tableheaders .= $common;
	// Order by: Game Title (ASC, DESC)
	if ($o == "2a") { $s_tableheaders .= "o=2d\">Game Title &nbsp; &#8593;</a></th>"; }
	elseif ($o == "2d") { $s_tableheaders .= "\">Game Title &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=2a\">Game Title</a></th>"; }

	/* Build Used */
	$s_tableheaders .= "<th>Build Used</th>";

	/* Status */
	$s_tableheaders .= $common;
	// Order by: Status (ASC, DESC)
	if ($o == "3a") { $s_tableheaders .= "o=3d\">Status &nbsp; &#8593;</a></th>"; }
	elseif ($o == "3d") { $s_tableheaders .= "\">Status &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=3a\">Status</a></th>"; }

	/* Last Updated */
	$s_tableheaders .= $common;
	// Order by: Last Updated (ASC, DESC)
	if ($o == "4a") { $s_tableheaders .= "o=4d\">Last Updated &nbsp; &#8593;</a></th>"; }
	elseif ($o == "4d") { $s_tableheaders .= "\">Last Updated &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=4a\">Last Updated</a></th>"; }
	
	return $s_tableheaders;
}


/*****************
 * Table Content *
 *****************/
function getTableContent() {
	global $sqlQry, $l_title, $sfo;
	
	if ($sqlQry) {
		if (mysqli_num_rows($sqlQry) > 0) {
			if ($l_title != "") {
				$s_tablecontent .= "<p class=\"compat-tx1-criteria\">No results found for <i>{$sfo}</i>. </br> Displaying results for <b><a style=\"color:#06c;\" href=\"?sf=".urlencode($l_title)."\">{$l_title}</a></b>.</p>";
			}
			while($row = mysqli_fetch_object($sqlQry)) {
				$s_tablecontent .= "<tr>
				<td>".getGameRegion($row->game_id)."&nbsp;&nbsp;".getThread($row->game_id, $row->thread_id)."</td>
				<td>".getGameMedia($row->game_id)."&nbsp;&nbsp;".getThread($row->game_title, $row->thread_id)."</td>
				<td>".getCommit($row->build_commit)."</td>
				<td>".getColoredStatus($row->status)."</td>
				<td><a href=\"?d=".str_replace('-', '', $row->last_edit)."\">".$row->last_edit."</a></td>
				</tr>";
			}	
		} 
	} else {
		// Query generator fail error
		$s_tablecontent .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
	}
	return $s_tablecontent;
}


/*****************
 * Pages Counter *
 *****************/
function getPagesCounter() {
	global $pages, $sf, $currentPage, $s, $c, $o, $g_pageresults, $f, $a_title;
	
	// IF no results are found then the amount of pages is 0
	// Shows no results found message
	if ($pages == 0) { 
		if ($sf != "") { 
		// $s_pagescounter .= "Results for '$sf' Game ID or Game Title not found."; 
		}
		else { $s_pagescounter .= 'No results found using the selected search criteria.'; }
	} 
	// ELSE it shows current page and total pages
	else { $s_pagescounter .= 'Page '.$currentPage.' of '.$pages.' - '; }
			
	// Loop for each page link and make it properly clickable until there are no more pages left
	for ($i=1; $i<=$pages; $i++) { 
		$s_pagescounter .= "<a href=\"?";
		
		// Page support: Sort by status
		if ($s > min(array_keys($a_title))) {$s_pagescounter .= "s=$s&";} 
		// Page support: Results per page
		$s_pagescounter .= $g_pageresults;
		// Page support: Search by character
		if ($c != "") {$s_pagescounter .= "c=$c&";} 
		// Page support: Search by region
		if ($f != "") {$s_pagescounter .= "f=$f&";} 
		// Page support: Order by
		if ($o != "") {$s_pagescounter .= "o=$o&";} 
		
		// Display number of the page
		$s_pagescounter .= "p=$i\">";
		if ($i == $currentPage) { if ($i < 10) { $s_pagescounter .= highlightBold("0"); } $s_pagescounter .= highlightBold($i); }
		else { if ($i < 10) { $s_pagescounter .= "0"; } $s_pagescounter .= $i; }
		
		$s_pagescounter .= "</a>&nbsp;&#32;"; 
	}
	
	return $s_pagescounter;
}

// Compatibility History: Pulls information from backup and compares with current database
function getHistory(){
	global $h1, $h2; 
	
	// Establish MySQL connection to be used for history
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$theQuery = mysqli_query($db, 
	"SELECT t1.game_id AS gid, t1.game_title AS title, t1.thread_id AS tid, t1.status AS new_status, t2.status AS old_status, t1.last_edit AS new_date, t2.last_edit AS old_date
	FROM {$h1} AS t1
	LEFT JOIN {$h2} AS t2
	ON t1.game_id=t2.game_id
	ORDER BY new_status ASC, -old_status DESC, title ASC; ");
	
	if (!$theQuery || mysqli_num_rows($theQuery) == 0) { 
		echo "An error has occoured."; 
	} else {
		echo "<tr>
		<th>Game ID</th>
		<th>Game Title</th>
		<th>New Status</th>
		<th>New Date</th>
		<th>Old Status</th>
		<th>Old Date</th>
		</tr>";
		
		while($row = mysqli_fetch_object($theQuery)) {
			
			if ($row->old_status != $row->new_status) {
				echo "<tr>
				<td>".getGameRegion($row->gid)."&nbsp;&nbsp;".getThread($row->gid, $row->tid)."</td>
				<td>".getGameMedia($row->gid)."&nbsp;&nbsp;".getThread($row->title, $row->tid)."</td>
				<td>".getColoredStatus($row->new_status)."</td>
				<td>{$row->new_date}</td>";
				
				if ($row->old_status !== NULL) {
					echo "<td>".getColoredStatus($row->old_status)."</td>
					<td>{$row->old_date}</td>";
				} else {
					echo "<td><i>None</i></td>
					<td><i>None</i></td>";
				}
				echo "</tr>";	
			}
		}
	}
	
	// Close MySQL connection again since it won't be required
	mysql_close($db);
}

/*****************************************************************************************************************************/

// End of time calculations
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);

?>
