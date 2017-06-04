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

if(!@include_once("config.php")) throw new Exception("Compat: config.php is missing. Failed to include config.php");

// Productcode info: PSDevWiki (http://www.psdevwiki.com/ps3/Productcode)


/**
  * getGameMedia
  *
  * Obtains Game Media by checking Game ID's first character. 
  * Returns Game Media as an image, empty string if Game Media is invalid.
  *
  * @param string $gid GameID: 9 character ID that identifies a game
  * @param bool   $url Whether to return Game Media as a clickable(1) or non-clickable(0) flag
  * @param top    $top Workaround for CSS
  *
  * @return string
  */
function getGameMedia($gid, $url = true, $top = "3px", $extra = '') {
	global $a_media, $get;
	
	$l = substr($gid, 0, 1);
	
	// If it's not a valid / known region then we return an empty string
	if (!array_key_exists($l, $a_media)) {
		return "";
	}
	
	if     ($l == "N")  { $alt = 'Digital'; }           // PSN Retail
	elseif ($l == "B")  { $alt = 'Blu-Ray'; }           // PS3 Blu-Ray
	elseif ($l == "X")  { $alt = 'Blu-Ray + Extras'; }  // PS3 Blu-Ray + Extras
	
	$img = "<img style='top:{$top}' alt=\"{$alt}\" src=\"{$a_media[$l]}\" class='div-compat-fmat'>";
	
	if ($extra != '') {
		$ex = substr($extra, 0, 1);
	} else { $ex = ''; }
	
	// Allow for filter reseting by clicking the flag again
	if ($get['t'] == strtolower($l) && $url) {
		return "<a href=\"?{$ex}\">{$img}</a>";
	}
	
	if ($url) {
		// Returns clickable flag for region (flag) search
		return "<a href=\"?{$extra}t=".strtolower($l)."\">{$img}</a>";
	} else {
		// Returns unclickable flag
		return $img;
	}
}


/**
  * getGameRegion
  *
  * Obtains Game Region by checking Game ID's third character. 
  * Returns Game Region as a clickable or non-clickable flag image,
  *  empty string if Game Region is invalid/unknown.
  * Icon flags from https://www.iconfinder.com/iconsets/famfamfam_flag_icons
  *
  * @param string $gid GameID: 9 character ID that identifies a game
  * @param bool   $url Whether to return Game Region as a clickable(1) or non-clickable(0) flag
  *
  * @return string
  */
function getGameRegion($gid, $url = true, $extra = '') {
	global $a_flags, $get;
	
	$l = substr($gid, 2, 1);
	
	// If it's not a valid / known region then we return an empty string
	if (!array_key_exists($l, $a_flags)) {
		return "";
	}
	
	if ($extra != '') {
		$ex = substr($extra, 0, 1);
	} else { $ex = ''; }
	
	// Allow for filter reseting by clicking the flag again
	if ($get['f'] == strtolower($l) && $url) {
		return "<a href=\"?{$ex}\"><img alt=\"{$l}\" src=\"{$a_flags[$l]}\"></a>";
	}
	
	if ($url) {
		// Returns clickable flag for region (flag) search
		return "<a href=\"?{$extra}f=".strtolower($l)."\"><img alt=\"{$l}\" src=\"{$a_flags[$l]}\"></a>";
	} else {
		// Returns unclickable flag
		return "<img alt=\"{$l}\" src=\"$a_flags[$l]\">";
	}
}


/**
  * getGameType
  *
  * Obtains Game Type by checking Game ID's fourth character. 
  * Returns Game Type as a string, empty if Game Type is invalid/unknown.
  *
  * @param string $gid GameID: 9 character ID that identifies a game
  *
  * @return string
  */
function getGameType($gid) {
	// Physical
	if (substr($gid, 0, 1) == "B" || substr($gid, 0, 1) == "X") {
		if     (substr($gid, 3, 1) == "D")  { return "Demo"; }             // Demo
		elseif (substr($gid, 3, 1) == "M")  { return "Malayan Release"; }  // Malayan Release
		elseif (substr($gid, 3, 1) == "S")  { return "Retail Release"; }   // Retail Release
		// We don't care about the other types as they won't be listed
		else                                { return ""; }
	}
	// Digital
	if (substr($gid, 0, 1) == "N") {
		if     (substr($gid, 3, 1) == "A")  { return "First Party PS3"; }  // First Party PS3 (Demo/Retail)
		elseif (substr($gid, 3, 1) == "B")  { return "Licensed PS3"; }     // Licensed PS3 (Demo/Retail)
		elseif (substr($gid, 3, 1) == "C")  { return "First Party PS2"; }  // First Party PS2 Classic (Demo/Retail)
		elseif (substr($gid, 3, 1) == "D")  { return "Licensed PS2"; }     // Licensed PS2 (Demo/Retail)
		// We don't care about the other types as they won't be listed
		else                                { return ""; }
	}
}


/**
  * getThread
  *
  * Obtains thread URL for a game by adding thread ID to the forum showthread URL prefix
  * Returns provided text wrapped around a hyperlink for the thread
  *
  * @param string $text
  * @param string $tid ThreadID
  *
  * @return string
  */
function getThread($text, $tid) {
	global $c_forum;
	
	// The thread should never be 0. All games MUST have a thread.
	if ($tid != "0") { return "<a href=\"{$c_forum}{$tid}\">{$text}</a>"; } 
	else             { return $text; }
}


/**
  * getCommit
  *
  * Obtains commit URL for a commit by adding commit ID to the github commit URL prefix
  * Returns commit ID wrapped around a hyperlink with BUILD CSS class
  * for the commit or "Unknown" with NOBUILD CSS class if the commit ID is 0 (Unknown)
  *
  * @param string $cid CommitID
  *
  * @return string
  */
function getCommit($cid) {
	global $c_github, $c_unkcommit;
	
	if ($cid == "0") { return "<i>Unknown</i>"; }
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$commitQuery = mysqli_query($db, "SELECT * FROM commit_cache WHERE commit_id = '".mysqli_real_escape_string($db, $cid)."' LIMIT 1; ");
	if (mysqli_num_rows($commitQuery) === 0) {
		// Commit not in cache! Run recacher.
		cacheCommits(false);
	}
	
	$commitQuery = mysqli_query($db, "SELECT * FROM commit_cache WHERE commit_id = '".mysqli_real_escape_string($db, $cid)."' LIMIT 1; ");
	$row = mysqli_fetch_object($commitQuery);	
	
	mysqli_close($db);
	
	if ($row->valid == "1") {
		return "<a href=\"{$c_github}{$cid}\">".mb_substr($cid, 0, 8)."</a>";
	} else {
		return "<i>{$cid}</i>";
	}
}


/**
  * getColoredStatus
  *
  * Obtains colored status using the color from configuration a_colors array
  * Returns status wrapped around a background colored div with STATUS CSS class 
  * or italic "Invalid" if someone messes up inputting statuses.
  *
  * @param string $sn StatusName
  *
  * @return string
  */
function getColoredStatus($sn) {
	global $a_title, $a_color;
	
	foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $i) { 
		if ($sn == $a_title[$i]) { return "<div class='txt-compat-status' style='background: #{$a_color[$i]};'>{$a_title[$i]}</div>"; }
	}
	
	// This should be unreachable unless someone wrongly inputs status in the database
	return "<i>Invalid</i>";
}


/**
  * isValid
  *
  * Checks if string only has allowed characters.
  * Returns true if valid or false if invalid. 
  * Used for the searchbox.
  *
  * @param string $str Some text
  *
  * @return bool
  */
function isValid($str) {
    return !preg_match("/[^A-Za-z0-9.#&~ \/\'-]/", $str);
}


/**
  * isValidCommit
  *
  * Checks if commit exists in master branch by checking HTTP Headers.
  * Returns 1 if valid, 0 if invalid or 2 for internal error.
  *
  * @param string $commit Commit ID
  *
  * @return int (0,1,2)
  */
function isValidCommit($commit) {
	global $c_github;
	
	@file_get_contents($c_github.$commit);

	// HTTP/1.1 404 Not Found - Invalid commit
	if (strpos($http_response_header[0], '404') !== false)     { return 0; } 
	// HTTP/1.1 200 OK - Commit found
	elseif (strpos($http_response_header[0], '200') !== false) { return 1; } 
	
	return 2; // Fallback for other error codes
}


/**
  * highlightBold
  *
  * Returns provided string wrapped in bold html tags
  *
  * @param string $str Some text
  *
  * @return string
  */
function highlightBold($str) {
	return "<b>$str</b>";
}


function obtainGet() {
	global $a_pageresults, $c_pageresults, $a_title, $a_order, $a_flags, $a_histdates, $a_currenthist, $a_admin, $a_media;
	
	// Start new $get array
	$get = array();
	
	// Set default values
	$get['r'] = $a_pageresults[$c_pageresults];
	$get['rID'] = $c_pageresults;
	$get['s'] = 0; // All
	$get['o'] = '';
	$get['c'] = '';
	$get['g'] = "";
	$get['d'] = '';
	$get['f'] = '';
	$get['t'] = '';
	$get['h'] = '';
	$get['h1'] = db_table;
	$get['h2'] = end((array_keys($a_histdates))); 
	$get['m'] = ''; 
	
	// PS3 Games List
	if (isset($_GET['l'])) {
		$get['l'] = $_GET['l'];
	}
	
	// Results per page
	if (isset($_GET['r']) && array_key_exists($_GET['r'], $a_pageresults)) {
		$get['r'] = $a_pageresults[$_GET['r']];
		$get['rID'] = $_GET['r'];
	}
	
	// Status
	if (isset($_GET['s']) && array_key_exists($_GET['s'], $a_title)) {
		$get['s'] = $_GET['s'];
	}
	
	// Order by
	if (isset($_GET['o']) && isset($a_order) && array_key_exists($_GET['o'], $a_order)) {
		$get['o'] = strtolower($_GET['o']);
	}
	
	// Character
	if (isset($_GET['c'])) {
		// If it is a single alphabetic character 
		if (ctype_alpha($_GET['c']) && (strlen($_GET['c']) == 1)) {
			$get['c'] = strtolower($_GET['c']);
		}
		if ($_GET['c'] == '09')  { $get['c'] = '09';  } // Numbers
		if ($_GET['c'] == 'sym') { $get['c'] = 'sym'; } // Symbols
	}
	
	// Searchbox (sf deprecated, use g instead)
	if (isset($_GET['g']) && !empty($_GET['g']) && isValid($_GET['g'])) {
		$get['g'] = $_GET['g'];
	} elseif (isset($_GET['sf']) && !empty($_GET['sf']) && isValid($_GET['sf'])) {
		$get['g'] = $_GET['sf'];
	}
	
	// Date
	if (isset($_GET['d']) && is_numeric($_GET['d']) && strlen($_GET['d']) == 8 && strpos($_GET['d'], '20') === 0) {
		$get['d'] = $_GET['d'];
	}
	
	// Region
	if (isset($_GET['f']) && array_key_exists(strtoupper($_GET['f']), $a_flags)) {
		$get['f'] = strtolower($_GET['f']); 
	}
	
	// Media type
	if (isset($_GET['t']) && array_key_exists(strtoupper($_GET['t']), $a_media)) {
		$get['t'] = strtolower($_GET['t']); 
	}
	
	// History
	if (isset($_GET['h']) && array_key_exists($_GET['h'], $a_histdates)) {
		$keys = array_keys($a_histdates);
		$index = array_search($_GET['h'], $keys);
		
		if ($index >= 0 && $a_currenthist[0] != $_GET['h']) { 
			$get['h1'] = $_GET['h'];
			$get['h2'] = $keys[$index-1]; 
		}
	}
	
	// History mode
	if (isset($_GET['m']) && ($_GET['m'] == "c" || $_GET['m'] == "n")) {
		$get['m'] = strtolower($_GET['m']);
	}
	
	// Admin debug mode
	if (isset($_GET['a']) && isWhitelisted()) {
		$get['a'] = $_GET['a'];
	}
	
	return $get;
}


// Generates query from given GET parameters
function generateQuery($get, $status = true) {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$genquery = '';
	$and = false;
	
	// Force status to be All
	if (!$status) { $get['s'] = 0; }
	
	// QUERYGEN: Status
	if ($get['s'] != 0) { 
		$genquery .= " status = {$get['s']} ";
		$and = true;
	} 
	
	// QUERYGEN: Character
	if ($get['c'] != '') {
		if ($and) { $genquery .= " AND "; }
		if ($get['c'] == '09') {
			$genquery .= " (game_title LIKE '0%' OR game_title LIKE '1%' OR game_title LIKE '2%'
			OR game_title LIKE '3%' OR game_title LIKE '4%' OR game_title LIKE '5%' OR game_title LIKE '6%' OR game_title LIKE '7%'
			OR game_title LIKE '8%' OR game_title LIKE '9%') ";
		} elseif ($get['c'] == 'sym') {
			$genquery .= " (game_title LIKE '.%' OR game_title LIKE '&%') ";
		} else {
			$genquery .= " game_title LIKE '{$get['c']}%' ";
		}
		$and = true;
	}

	// QUERYGEN: Searchbox
	if ($get['g'] != '') {
		if ($and) { $genquery .= " AND "; }
		$s_g = mysqli_real_escape_string($db, $get['g']);
		$genquery .= " (game_title LIKE '%{$s_g}%' OR game_id LIKE '%{$s_g}%') ";
		$and = true;
	}

	// QUERYGEN: Search by region
	if ($get['f'] != '') {
		if ($and) { $genquery .= " AND "; }
		$genquery .= " SUBSTR(game_id, 3, 1) = '{$get['f']}' ";
		$and = true;
	}
	
	// QUERYGEN: Search by media type
	if ($get['t'] != '') {
		if ($and) { $genquery .= " AND "; }
		$genquery .= " SUBSTR(game_id, 1, 1) = '{$get['t']}' ";
		$and = true;
	}

	// QUERYGEN: Search by date
	if ($get['d'] != '') {
		if ($and) { $genquery .= " AND "; }
		$s_d = mysqli_real_escape_string($db, $get['d']);
		$genquery .= " last_edit = '{$s_d}' "; 
	}
	
	mysqli_close($db);
	
	return $genquery;
}


// Select the count of games in each status, subjective to query restrictions
function countGames($query = '', $count = 0) {
	global $a_title, $get;
	
	// Connect to database
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	
	// Failed database connection, return 0 games
	if (!$db) {
		return 0;
	}

	mysqli_set_charset($db, 'utf8');
	
	if ($query == 'all') {
		return mysqli_fetch_object(mysqli_query($db, "SELECT count(*) AS c FROM ".db_table))->c;
	}
	
	foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $s) { 
	
		if ($query == "") {
			// Empty query or general query with order only, all games returned
			$squery[$s] = "SELECT count(*) AS c FROM ".db_table." WHERE status = {$s}";
		} else {
			// Query defined, return count of games with searched parameters
			$squery[$s] = "SELECT count(*) AS c FROM ".db_table." WHERE ({$query}) AND status = {$s}";
		}
		
		$scount[$s] = mysqli_fetch_object(mysqli_query($db, $squery[$s]))->c;
		
		if ($count[0] > 0) {
			$scount[$s] += $count[$s];
		}
		
		// Instead of querying the database once more add all the previous counts to get the total count
		$scount[0] += $scount[$s];
	}
	
	// Close database connection
	mysqli_close($db);
	
	return $scount;
}


// Converts HEX colors to RGB
// Returns array with 0 = Red, 1 = Green, 2 = Blue
function colorHEXtoRGB($color) {
	
	if (strlen($color) == 7) {
		// If it starts by # we remove it and convert anyways
		$color = substr($color, 1);
	} elseif (strlen($color) != 6) {
		// If it's not 7 or 6 characters then it's an invalid color
		return array(0,0,0);
	}
	
	$rgb = array(
		$color[0].$color[1], // Red
		$color[2].$color[3], // Green
		$color[4].$color[5]  // Blue
	);
	
	return array_map('hexdec', $rgb);
}


// Adapted from: http://www.johnciacia.com/2010/01/04/using-php-and-gd-to-add-border-to-text/ to imagestring by me
function imagestringstroketext(&$image, $size, $x, $y, &$textcolor, &$strokecolor, $text, $px) {
    for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
        for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
            $bg = imagestring($image, $size, $c1, $c2, $text, $strokecolor);
   return imagestring($image, $size, $x, $y, $text, $textcolor);
}


function getTime() {
	$t = explode(' ', microtime());
	return $t[1] + $t[0];
}


function getPagesCounter($pages, $currentPage, $extra) {
	global $c_pagelimit;
		
	// IF no results are found then the amount of pages is 0
	// Shows no results found message
	if ($pages == 0) { 
		return 'No results found using the selected search criteria.';
	} 
	// Shows current page and total pages
	else { 
		$s_pagescounter .= "Page {$currentPage} of {$pages} - "; 
	}
	
	// Commonly used code
	$common = "<a href=\"?{$extra}";
	
	// If there's less pages to the left than current limit it loads the excess amount to the right for balance
	if ($c_pagelimit > $currentPage) {
		$a = $c_pagelimit - $currentPage;
		$c_pagelimit = $c_pagelimit + $a + 1;
	}
	
	// If there's less pages to the right than current limit it loads the excess amount to the left for balance
	if ($c_pagelimit > $pages - $currentPage) {
		$a = $c_pagelimit - ($pages - $currentPage);
		$c_pagelimit = $c_pagelimit + $a + 1;
	}
	
	// Loop for each page link and make it properly clickable until there are no more pages left
	for ($i=1; $i<=$pages; $i++) { 
	
		if ( ($i >= $currentPage-$c_pagelimit && $i <= $currentPage) || ($i+$c_pagelimit >= $currentPage && $i <= $currentPage+$c_pagelimit) ) {
			
			// Display number of the page and highlight if current page
			$s_pagescounter .= "{$common}p=$i\">";
			
			if ($i == $currentPage) { if ($i < 10) { $s_pagescounter .= highlightBold("0"); } $s_pagescounter .= highlightBold($i); }
			else { if ($i < 10) { $s_pagescounter .= "0"; } $s_pagescounter .= $i; }
			
			$s_pagescounter .= "</a>&nbsp;&#32;"; 
		
		} 
		// First page
		elseif ($i == 1) {
			$s_pagescounter .= "{$common}p=$i\">01</a>&nbsp;&#32;"; 
			if ($currentPage != $c_pagelimit+2) { $s_pagescounter .= "...&nbsp;&#32;"; }
		}
		// Last page
		elseif ($pages == $i) { $s_pagescounter .= "...&nbsp;&#32;{$common}p=$pages\">$pages</a>&nbsp;&#32;"; }
		
	}
	
	return $s_pagescounter;
	
}


function getTableHeaders($headers, $extra = '') {
	global $get;
	$s_tableheaders .= "<tr>";	
	foreach ($headers as $k => $v) { 
		if     ($v == 0)              { $s_tableheaders .= "<th>{$k}</th>"; }
		elseif ($get['o'] == "{$v}a") { $s_tableheaders .= "<th><a href =\"?{$extra}o={$v}d\">{$k} &nbsp; &#8593;</a></th>"; }
		elseif ($get['o'] == "{$v}d") { $s_tableheaders .= "<th><a href =\"?{$extra}\">{$k} &nbsp; &#8595;</a></th>"; }
		else                          { $s_tableheaders .= "<th><a href =\"?{$extra}o={$v}a\">{$k}</a></th>"; } 
	}
	$s_tableheaders .= "</tr>";
	
	return $s_tableheaders;
}


function getFooter($start) {
	// Finish: Microtime after the page loaded
	$finish = getTime();
	$total_time = round(($finish - $start), 4);
	
	return "<div id=\"compat-author\"><p>
	Compatibility list developed and mantained by <a href='https://github.com/AniLeo' target=\"_blank\">AniLeo</a>
	&nbsp;-&nbsp;
	Page loaded in {$total_time} seconds
	</p></div>";
}


function getMenu($c, $h, $b, $l, $a) {
	$and = false;
	if ($c) {
		$menu .= "<a href='?'>Compatibility List</a>";
		$and = true;
	}
	if ($h) {
		if ($and) { $menu .= " • "; }
		$menu .= "<a href='?h'>Compatibility List History</a>";
		$and = true;
	}
	if ($b) {
		if ($and) { $menu .= " • "; }
		$menu .= "<a href='?b'>RPCS3 Builds History</a>";
		$and = true;
	}
	if ($l) {
		if ($and) { $menu .= " • "; }
		$menu .= "<a href='?l'>PS3 Game Library</a>";
		$and = true;
	}
	if (isWhitelisted() && $a) {
		if ($and) { $menu .= " • "; }
		$menu .= "<a href='?a'>Debug Panel</a>";
	}
	return "<p id='title2'>{$menu}</p>";
}


// Get current page user is on
function getCurrentPage($pages) {
	if (isset($_GET['p'])) {
		$currentPage = intval($_GET['p']);
		if ($currentPage > $pages) { $currentPage = 1; }		
	} else { $currentPage = 1; }
	
	return $currentPage;
}


// Calculate the number of pages according selected status and results per page
function countPages($get, $genquery, $count) {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	// Page calculation according to the user's search
	$pagesCmd = "SELECT count(*) AS c FROM ".db_table;
	if ($genquery != "") {
		$pagesCmd .= " WHERE {$genquery} ";
	}

	$pagesQuery = mysqli_query($db, $pagesCmd);
	$pages = ceil( (mysqli_fetch_object($pagesQuery)->c + $count) / $get['r'] );

	mysqli_close($db);
	
	return $pages;
}


/***********************
 * Status descriptions *
 ***********************/
function getStatusDescriptions($getCount = true) {
	global $a_desc, $a_color, $a_title;
	
	// Get games count per status
	$count = countGames();
	
	foreach (range((min(array_keys($a_desc))+1), max(array_keys($a_desc))) as $i) { 
	
		$s_descontainer .= "<div class='compat-status-main'>
		<div class='compat-status-icon' style='background:#{$a_color[$i]}'></div>
		<div class='compat-status-text'>
		<p style='color:#{$a_color[$i]}'><strong>{$a_title[$i]}";
		if ($getCount) {
			$percentage = round(($count[$i]/$count[0])*100, 2, PHP_ROUND_HALF_UP);
			$s_descontainer .= " ({$percentage}%)";
		}
		$s_descontainer .= ":</strong></p> {$a_desc[$i]}</div>";
		
		if ($getCount) {
			$s_descontainer .= "<div class='compat-status-progress'>
			<progress class='compat-status-progressbar' id='compat-progress{$i}' style=\"color:#{$a_color[$i]}\" max=\"100\" value=\"{$percentage}\"></progress>
			</div>";
		}
		
		$s_descontainer .= "</div>";
	}	
	return $s_descontainer;
}


// Checks if IP is on whitelist
function isWhitelisted() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	// Page calculation according to the user's search
	$ipQuery = mysqli_query($db, "SELECT * FROM ip_whitelist WHERE ip = '".mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR'])."'; ");

	$valid = false;
	
	if (mysqli_num_rows($ipQuery) === 1) {
		$valid = true;
	}
	
	mysqli_close($db);
	
	return $valid;
}


function combinedSearch($r, $s, $c, $g, $f, $t, $d, $o) {
	global $get, $scount, $g_pageresults;
	
	// Combined search: results per page
	if ($r) {$combined .= $g_pageresults;}
	// Combined search: sort by status
	if ($get['s'] != 0 && $s) {$combined .= "s={$get['s']}&";} 
	// Combined search: search by character
	if ($get['c'] != "" && $c) {$combined .= "c={$get['c']}&";}
	// Combined search: searchbox
	if ($get['g'] != "" && $scount[0] > 0 && $g) {$combined .= "g=".urlencode($get['g'])."&";} 
	// Combined search: search by region
	if ($get['f'] != "" && $f) {$combined .= "f={$get['f']}&";} 
	// Combined search: search by media type
	if ($get['t'] != "" && $t) {$combined .= "t={$get['t']}&";} 
	// Combined search: date search
	if ($get['d'] != "" && $d) {$combined .= "d={$get['d']}&";}
	// Combined search: order by
	if ($get['o'] != "" && $o) {$combined .= "o={$get['o']}&";} 
	
	return $combined;
}


function getLatestWindowsBuild() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$query = mysqli_query($db, "SELECT * FROM builds_windows ORDER BY merge_datetime DESC LIMIT 1;");
	$row = mysqli_fetch_object($query);

	mysqli_close($db);
	
	return array($row->appveyor, date_format(date_create($row->merge_datetime), "Y-m-d"));
}


function getLatestLinuxBuild() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$query = mysqli_query($db, "SELECT * FROM builds_linux ORDER BY datetime DESC LIMIT 1;");
	$row = mysqli_fetch_object($query);

	mysqli_close($db);
	
	return array($row->buildname, date_format(date_create($row->datetime), "Y-m-d"));
}
?>
