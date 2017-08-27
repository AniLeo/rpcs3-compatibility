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
  * @param string $gid   GameID, 9 character ID that identifies a game
  * @param bool   $url   Whether to return Game Media as a clickable(1) or non-clickable(0) flag
  * @param class  $class CSS classes for returned image
  * @param extra  $extra Extra params for clickable URL (combined search)
  *
  * @return string
  */
function getGameMedia($gid, $url = true, $class = 'compat-icon-media', $extra = '') {
	global $a_media, $get;
	
	// First letter of Game ID
	$l = substr($gid, 0, 1);
	
	// If it's not a valid / known region then we return an empty string
	if (!array_key_exists($l, $a_media)) {
		return '';
	}
	
	if     ($l == 'N')  { $alt = 'Digital'; }           // PSN Digital
	elseif ($l == 'B')  { $alt = 'Blu-Ray'; }           // PS3 Blu-Ray
	elseif ($l == 'X')  { $alt = 'Blu-Ray + Extras'; }  // PS3 Blu-Ray + Extras
	
	$img = "<img alt=\"{$alt}\" src=\"{$a_media[$l]}\" class=\"{$class}\">";
	
	// Get the page we're on so we can reset back to the correct page
	$ex = $extra != '' ? substr($extra, 0, 1) : '';
	
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
	if ($tid != "0") { return "<a href=\"{$c_forum}{$tid}.html\">{$text}</a>"; } 
	else             { return $text; }
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
    return !preg_match("/[^A-Za-z0-9.#&~; \/\'-]/", $str);
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


function obtainGet($db = null) {
	global $a_pageresults, $c_pageresults, $a_title, $a_order, $a_flags, $a_histdates, $a_currenthist, $a_admin, $a_media;
	
	// Start new $get array
	$get = array();
	
	// rss - Global
	// api - Global
	
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
	
	// Is whitelisted?
	$get['w'] = isWhitelisted($db) ? true : false;
	
	// Admin debug mode
	if (isset($_GET['a']) && $get['w']) {
		$get['a'] = $_GET['a'];
	}
	
	return $get;
}


// Generates query from given GET parameters
function generateQuery($get, $db = null) {
	
	if ($db == null) {
		$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
		mysqli_set_charset($db, 'utf8');
		$close = true;
	} else {
		$close = false;
	}

	$genquery = '';
	$status = '';
	$and = false;
	
	// QUERYGEN: Character
	if ($get['c'] != '') {
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
		$and = true;
	}
		
	// QUERYGEN: Status
	if ($get['s'] != 0) { 
		if ($and) { $status .= " AND "; }
		$status .= " status = {$get['s']} ";
		$and = true;
	}
	
	if ($close) {
		mysqli_close($db);
	}
	
	// 0 => With specified status 
	// 1 => Without specified status
	return array($genquery.$status, $genquery);
}


// Select the count of games in each status, subjective to query restrictions
function countGames($db = null, $query, $count = 0) {
	global $get, $a_title;
	
	if ($db == null) {
		$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
		mysqli_set_charset($db, 'utf8');
		$close = true;
	} else {
		$close = false;
	}
	
	// Failed database connection, return 0 games
	if (!$db) {
		return 0;
	}
	
	if ($query == 'all') {
		return mysqli_fetch_object(mysqli_query($db, "SELECT count(*) AS c FROM ".db_table))->c;
	}
	
	if ($query == '') {
		// Empty query or general query with order only, all games returned
		$gen = "SELECT status, count(*) AS c FROM ".db_table." GROUP BY status;";
	} else {
		// Query defined, return count of games with searched parameters
		$gen = "SELECT status, count(*) AS c FROM ".db_table." WHERE ({$query}) GROUP BY status;";
	}

	$q_gen = mysqli_query($db, $gen);

	// Zero-fill the array keys that are going to be used
	foreach (range( min(array_keys($a_title)), max(array_keys($a_title)) ) as $s) { 
		$scount[0][$s] = 0;
		$scount[1][$s] = 0;
	}

	while ($row = mysqli_fetch_object($q_gen)) {
		
		// Get Status ID
		$id = array_search($row->status, $a_title);
		
		// For count with specified status, include only results for specified status
		if ($id == $get['s']) {
			$scount[0][$id] = $row->c;
			$scount[0][0] = $row->c;
		}
		
		// Add count from status to the array
		$scount[1][$id] = $row->c;
		
		// Add extra counts if existing
		if ($count[0] > 0) {
			$scount[1][$id] += $count[$id];
		}
	
		// Instead of querying the database once more add all the previous counts to get the total count
		$scount[1][0] += $scount[1][$id];
	}
	
	// If no status is specified, fill status-specified array normally
	if ($get['s'] == 0) {
		$scount[0] = $scount[1];
	}
	
	if ($close) {
		mysqli_close($db);
	}
	
	return $scount;
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
	global $prof_timing, $prof_names, $prof_desc, $c_profiler, $get;
	
	// Finish: Microtime after the page loaded
	$finish = getTime();
	$total_time = round(($finish - $start), 4);
	
	$s = "<p>Compatibility list developed and mantained by 
	<a href='https://github.com/AniLeo' target=\"_blank\">AniLeo</a>
	&nbsp;-&nbsp;
	Page loaded in {$total_time} seconds</p>";
	if ($get['w'] && $c_profiler && !empty($prof_desc)) {
		$s .= "<p style='line-height:20px; padding-bottom:15px;'><b>{$prof_desc}</b><br>".prof_print()."</p>";
	}
	return "<div id=\"compat-author\">{$s}</div>";
}


function getMenu($c, $h, $b, $l, $a) {
	global $get;
	
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
	if ($get['w'] && $a) {
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
function countPages($get, $count) {
	return ceil($count / $get['r']);
}


/*****************
 * Status Module *
 *****************/
function generateStatusModule($getCount = true) {
	global $a_desc, $a_color, $a_title;
	
	// Get games count per status
	$count = countGames()[0];
	
	// Pretty output for readibility
	foreach (range((min(array_keys($a_desc))+1), max(array_keys($a_desc))) as $i) { 
	
		$output .= "<div class='compat-status-main'>\n";
		$output .= "<div class='compat-status-icon' style='background:#{$a_color[$i]}'></div>\n";
		$output .= "<div class='compat-status-text'>\n";
		$output .= "<p style='color:#{$a_color[$i]}'><strong>{$a_title[$i]}";
		
		if ($getCount) {
			$percentage = round(($count[$i]/$count[0])*100, 2, PHP_ROUND_HALF_UP);
			$output .= " ({$percentage}%)";
		}

		$output .= ":</strong></p>&nbsp;&nbsp;{$a_desc[$i]}\n</div>\n";

		if ($getCount) {
			$output .= "<div class='compat-status-progress'>\n";
			$output .= "<progress class='compat-status-progressbar' id='compat-progress{$i}' style=\"color:#{$a_color[$i]}\" max=\"100\" value=\"{$percentage}\"></progress>\n";
			$output .= "</div>\n";
		}
		
		$output .= "</div>\n";
	}
	
	return "<div class='compat-con-container'>\n{$output}</div>";
}


// Checks if IP is on whitelist
function isWhitelisted($db = null) {
	global $c_cloudflare;
	
	if ($db == null) {
		$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
		mysqli_set_charset($db, 'utf8');
		$close = true;
	} else {
		$close = false;
	}
	
	if ($c_cloudflare) {
		$ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
	} else {
		$ip = $_SERVER["REMOTE_ADDR"];
	}
	
	// Page calculation according to the user's search
	$ipQuery = mysqli_query($db, "SELECT * FROM ip_whitelist WHERE ip = '".mysqli_real_escape_string($db, $ip)."' LIMIT 1; ");
	
	if ($close) {
		mysqli_close($db);
	}
	
	return mysqli_num_rows($ipQuery) === 1 ? true : false;
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


// Based on https://stackoverflow.com/a/399357 
function getPageTitle($fp) {
    if (!$fp) { 
		return '';
	}

	$res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
    if (!$res) { return ''; }
 
    // Clean up title: remove EOL's and excessive whitespace.
    $title = preg_replace('/\s+/', ' ', $title_matches[1]);
    $title = trim($title);
    return $title;
}


// Based on https://stackoverflow.com/a/29022400
function prof_flag($str) {
    global $prof_timing, $prof_names;
    $prof_timing[] = microtime(true) * 10000000;
    $prof_names[] = $str;
}


// Based on https://stackoverflow.com/a/29022400
function prof_print() {
    global $prof_timing, $prof_names;
    $size = count($prof_timing);
	
    for ($i=0;$i<$size - 1; $i++) {
        $s .= sprintf("%05dμs&nbsp;-&nbsp;{$prof_names[$i]}<br>", $prof_timing[$i+1]-$prof_timing[$i]);
    }
	
	return $s;
}


function getDateDiff($datetime) {
	$diff = time() - strtotime($datetime);
	$days = floor($diff / 86400);	
		
	if ($days == 0) {
		$hours = floor($diff / 3600);	
		if ($hours == 0) { 
			$minutes = floor($diff / 60);
			if ($minutes == 1) { 
				$diff = "{$minutes} minute ago";
			} else {
				$diff = "{$minutes} minutes ago";
			}
		} elseif ($hours == 1) { 
			$diff = "{$hours} hour ago";
		} else {
			$diff = "{$hours} hours ago";
		}
	} elseif ($days == 1) { 
		$diff = "{$days} day ago"; 
	} else { 
		$diff = "{$days} days ago"; 
	}
	
	return $diff;
}

?>