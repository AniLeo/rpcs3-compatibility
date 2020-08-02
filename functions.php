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
	* getDatabase
	*
	* Establishes a database connection and sets utf8mb4 charset
	*
	* @return object Connection to MySQL Server
	*/
function getDatabase() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	if (!$db)
		trigger_error("[Compat] Database: Connection could not be established", E_USER_ERROR);
	mysqli_set_charset($db, 'utf8mb4');
	return $db;
}


/**
	* getGameMediaIcon
	*
	* Obtains Game Media by checking Game ID's first character.
	* Returns Game Media as an icon image, empty string if Game Media is invalid.
	*
	* @param string $gid   GameID, 9 character ID that identifies a game
	* @param bool   $url   Whether to return Game Media as a clickable(1) or non-clickable(0) flag
	* @param string $extra Extra params for clickable URL (combined search)
	*
	* @return string
	*/
function getGameMediaIcon($gid, $url = true, $extra = '') {
	global $a_media, $get;

	// First letter of Game ID
	$l = substr($gid, 0, 1);

	// If it's not a valid / known region then we return an empty string
	if (!array_key_exists($l, $a_media)) {
		return '';
	}

	$img = "<img title=\"{$a_media[$l]['name']}\" alt=\"{$a_media[$l]['name']}\" src=\"{$a_media[$l]['icon']}\" class=\"compat-icon-media\">";

	// Get the module we're on so we can reset back to the correct module
	$ex = $extra != '' ? substr($extra, 0, 1) : '';

	if ($url) {
		// Allow for filter resetting by clicking the icon again
		if ($get['t'] == strtolower($l)) {
			return "<a href=\"?{$ex}\">{$img}</a>";
		}
		// Returns clickable icon for type (media) search
		return "<a href=\"?{$extra}t=".strtolower($l)."\">{$img}</a>";
	}

	// Returns unclickable icon
	return $img;
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
	* @param string $extra
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

	// Get the module we're on so we can reset back to the correct module
	$ex = $extra != '' ? substr($extra, 0, 1) : '';

	// Allow for filter resetting by clicking the flag again
	if ($get['f'] == strtolower($l) && $url) {
		return "<a href=\"?{$ex}\"><img class=\"compat-icon-flag\" title=\"{$gid}\" alt=\"{$l}\" src=\"{$a_flags[$l]}\"></a>";
	}

	if ($url) {
		// Returns clickable flag for region (flag) search
		return "<a href=\"?{$extra}f=".strtolower($l)."\"><img class=\"compat-icon-flag\" title=\"{$gid}\" alt=\"{$l}\" src=\"{$a_flags[$l]}\"></a>";
	} else {
		// Returns unclickable flag
		return "<img class=\"compat-icon-flag\" title=\"{$gid}\" alt=\"{$l}\" src=\"$a_flags[$l]\">";
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
		if (substr($gid, 3, 1) == "D")  { return "Demo"; }             // Demo
		if (substr($gid, 3, 1) == "M")  { return "Malayan Release"; }  // Malayan Release
		if (substr($gid, 3, 1) == "S")  { return "Retail Release"; }   // Retail Release
	}
	// Digital
	if (substr($gid, 0, 1) == "N") {
		if (substr($gid, 3, 1) == "A")  { return "First Party PS3"; }  // First Party PS3 (Demo/Retail)
		if (substr($gid, 3, 1) == "B")  { return "Licensed PS3"; }     // Licensed PS3 (Demo/Retail)
		if (substr($gid, 3, 1) == "C")  { return "First Party PS2"; }  // First Party PS2 Classic (Demo/Retail)
		if (substr($gid, 3, 1) == "D")  { return "Licensed PS2"; }     // Licensed PS2 (Demo/Retail)
	}

		// We don't care about the other types as they won't be listed
		return "";
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
	if ($tid != "0") { return "<a href=\"{$c_forum}/thread-{$tid}.html\">{$text}</a>"; }
	else             { return $text; }
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
	return !preg_match("/[^A-Za-z0-9.#&~;:\* \/\'-]/", $str);
}


/**
	* highlightText
	*
	* Returns provided string with increased size and font-weight
	*
	* @param string $str 	Some text
	* @param bool		$cond	Condition to be met for text to be highlighted
	*
	* @return string
	*/
function highlightText($str, $cond = true) {
	return $cond ? "<span class=\"highlightedText\">{$str}</span>" : $str;
}


function validateGet($db = null) {
	global $a_pageresults, $c_pageresults, $a_status, $a_order, $a_flags, $a_histdates, $a_currenthist, $a_media;

	// Start new $get array
	$get = array();

	// TODO: Parse those here
	// rss - Global
	// api - Global

	// Set default values
	$get['r'] = $c_pageresults;
	$get['s'] = 0; // All
	$get['o'] = "";
	$get['c'] = '';
	$get['g'] = "";
	$get['d'] = "";
	$get['f'] = '';
	$get['t'] = '';
	$get['h'] = $a_currenthist[0];
	$get['m'] = '';

	// PS3 Games List
	if (isset($_GET['l']) && !is_array($_GET['l'])) {
		$get['l'] = $_GET['l'];
	}

	// Results per page
	if (isset($_GET['r']) && !is_array($_GET['r']) && in_array($_GET['r'], $a_pageresults)) {
		$get['r'] = (int) $_GET['r'];
	}

	// Status
	if (isset($_GET['s']) && !is_array($_GET['s']) && ((int) $_GET['s'] === 0 || array_key_exists($_GET['s'], $a_status))) {
		$get['s'] = (int) $_GET['s'];
	}

	// Order by
	if (isset($_GET['o']) && !is_array($_GET['o']) && strlen($_GET['o']) == 2 && is_numeric(substr($_GET['o'], 0, 1)) && (substr($_GET['o'], 1, 1) == 'a' || substr($_GET['o'], 1, 1) == 'd')) {
		$get['o'] = $_GET['o'];
	}

	// Character
	if (isset($_GET['c']) && !is_array($_GET['c'])) {
		// If it is a single alphabetic character
		if (ctype_alpha($_GET['c']) && (strlen($_GET['c']) == 1)) {
			$get['c'] = strtolower($_GET['c']);
		}
		if ($_GET['c'] == '09')  { $get['c'] = '09';  } // Numbers
		if ($_GET['c'] == 'sym') { $get['c'] = 'sym'; } // Symbols
	}

	// Searchbox (sf deprecated, use g instead)
	if (!isset($_GET['g']) && !is_array($_GET['sf']) && isset($_GET['sf'])) {
		$_GET['g'] = $_GET['sf'];
	}
	if (isset($_GET['g']) && !is_array($_GET['g']) && !empty($_GET['g']) && mb_strlen($_GET['g']) <= 128 && isValid($_GET['g'])) {
		$get['g'] = $_GET['g'];
		// Trim all unnecessary double spaces
		while (strpos($get['g'], "  ") !== false)
			$get['g'] = str_replace("  ", " ", $get['g']);
	}

	// Date
	if (isset($_GET['d']) && !is_array($_GET['d']) && is_numeric($_GET['d']) && strlen($_GET['d']) === 8 && strpos($_GET['d'], '20') === 0) {
		$get['d'] = $_GET['d'];
	}

	// Media type
	if (isset($_GET['t']) && !is_array($_GET['t']) && array_key_exists(strtoupper($_GET['t']), $a_media)) {
		$get['t'] = strtolower($_GET['t']);
	}

	// Region
	if (isset($_GET['f']) && !is_array($_GET['f']) && array_key_exists(strtoupper($_GET['f']), $a_flags)) {
		$get['f'] = strtolower($_GET['f']);
	}

	// History
	if (isset($_GET['h']) && !is_array($_GET['h']) && array_key_exists($_GET['h'], $a_histdates)) {
		$get['h'] = $_GET['h'];
	}

	// History mode
	if (isset($_GET['m']) && !is_array($_GET['m']) && ($_GET['m'] == 'c' || $_GET['m'] == 'n')) {
		$get['m'] = strtolower($_GET['m']);
	}

	// Get debug permissions, if any
	$get['w'] = getDebugPermissions($db);

	// Enable error reporting for admins
	if ($get['w'] != NULL) {
		error_reporting(E_ALL);
		ini_set('display_errors', 1);

		// Admin debug mode
		if (isset($_GET['a']) && !is_array($_GET['a']) && array_search("debug.view", $get['w']) !== false) {
			$get['a'] = $_GET['a'];
		}
	}

	return $get;
}


// Select the count of games in each status, subjective to query restrictions
function countGames($db = null, $query = "") {
	global $get, $a_status;

	if ($db == null) {
		$db = getDatabase();
		if (is_null($db))
			return 0; // If there's no database connection, return 0 games
		$close = true;
	} else {
		$close = false;
	}

	// Total game count (without network games)
	if ($query === "all") {
		$q_unique = mysqli_query($db, "SELECT count(*) AS `c` FROM `game_list` WHERE `network` = 0 OR (`network` = 1 && `status` <= 2); ");
		if (!$q_unique)
			return 0;
		return (int) mysqli_fetch_object($q_unique)->c;
	}

	$and = $query === "" ? "" : " AND ({$query}) ";

	// Without network only games
	$q_gen1 = mysqli_query($db, "SELECT `status`+0 AS `statusID`, count(*) AS `c` FROM `game_list`
	WHERE (`network` = 0 OR (`network` = 1 && `status` <= 2)) {$and} GROUP BY `status`;");
	// With network only games
	$q_gen2 = mysqli_query($db, "SELECT `status`+0 AS `statusID`, count(*) AS `c` FROM `game_list`
	WHERE (`network` = 0 OR `network` = 1) {$and} GROUP BY `status`;");

	if ($close)
		mysqli_close($db);

	if (!$q_gen1 || !$q_gen2)
		return 0;

	// Zero-fill the array keys that are going to be used
	$scount["status"][0]   = 0;
	$scount["nostatus"][0] = 0;
	$scount["network"][0]  = 0; // Derivative of status mode but with network games
	foreach ($a_status as $id => $status) {
		$scount["status"][$id]   = 0;
		$scount["nostatus"][$id] = 0;
		$scount["network"][$id]  = 0;
	}

	while ($row1 = mysqli_fetch_object($q_gen1)) {
		$sid   = (int) $row1->statusID;
		$count = (int) $row1->c;

		$scount["nostatus"][$sid] =  $count;
		$scount["nostatus"][0]    += $count;

		// For count with specified status, include only results for the specified status
		// If there is no specified status, replicate nostatus mode
		if ($get['s'] === 0 || $sid === $get['s']) {
			$scount["status"][$sid]  =  $count;
			$scount["status"][0]     += $count;
		}
	}

	while ($row2 = mysqli_fetch_object($q_gen2)) {
		$sid   = (int) $row2->statusID;
		$count = (int) $row2->c;

		if ($get['s'] === 0 || $sid === $get['s']) {
			$scount["network"][$sid] =  $count;
			$scount["network"][0]    += $count;
		}
	}

	return $scount;
}


function getPagesCounter($pages, $currentPage, $extra) {
	global $c_pagelimit;

	// Initialize string
	$s_pagescounter = "";

	// IF no results are found then the amount of pages is 0
	// Returns no results found message
	if ($pages == 0)
		return "No results found using the selected search criteria.";

	// Shows current page and total pages
	$s_pagescounter .= "Page {$currentPage} of {$pages} - ";

	// If there's less pages to the left than current limit it loads the excess amount to the right for balance
	if ($c_pagelimit > $currentPage)
		$c_pagelimit += $c_pagelimit - $currentPage + 1;

	// If there's less pages to the right than current limit it loads the excess amount to the left for balance
	if ($c_pagelimit > $pages - $currentPage)
		$c_pagelimit += $c_pagelimit - ($pages - $currentPage) + 1;

	// Loop for each page link and make it properly clickable until there are no more pages left
	for ($i = 1; $i <= $pages; $i++) {

		if ( ($i >= $currentPage-$c_pagelimit && $i <= $currentPage) || ($i+$c_pagelimit >= $currentPage && $i <= $currentPage+$c_pagelimit) ) {

			$s_pagescounter .= "<a href=\"?{$extra}p=$i\">";

			// Add zero padding if it is a single digit number
			$p = ($i < 10) ? "0{$i}" : "{$i}";

			// Highlights the page if it's the one we're currently in
			$s_pagescounter .= highlightText($p, $i == $currentPage);

			$s_pagescounter .= "</a>&nbsp;&#32;";

		}
		// First page
		elseif ($i == 1) {
			$s_pagescounter .= "<a href=\"?{$extra}p=$i\">01</a>&nbsp;&#32;";
			if ($currentPage != $c_pagelimit+2) { $s_pagescounter .= "...&nbsp;&#32;"; }
		}
		// Last page
		elseif ($pages == $i) {
			$s_pagescounter .= "...&nbsp;&#32;<a href=\"?{$extra}p=$pages\">$pages</a>&nbsp;&#32;";
		}

	}

	return $s_pagescounter;

}


function getTableHeaders($headers, $extra = '') {
	global $get;

	// Initialize string
	$s_tableheaders = "";

	foreach ($headers as $i => $header) {
		if     ($header['sort'] === 0)              { $s_tableheaders .= "<div class=\"{$header['class']}\">{$header['name']}</div>"; }
		elseif ($get['o'] === "{$header['sort']}a") { $s_tableheaders .= "<div class=\"{$header['class']}\"><a href =\"?{$extra}o={$header['sort']}d\">{$header['name']} &nbsp; &#8593;</a></div>"; }
		elseif ($get['o'] === "{$header['sort']}d") { $s_tableheaders .= "<div class=\"{$header['class']}\"><a href =\"?{$extra}\">{$header['name']} &nbsp; &#8595;</a></div>"; }
		else                                        { $s_tableheaders .= "<div class=\"{$header['class']}\"><a href =\"?{$extra}o={$header['sort']}a\">{$header['name']}</a></div>"; }
	}

	return "<div class=\"compat-table-header\">{$s_tableheaders}</div>";
}


function getFooter() {
	global $c_maintenance, $get, $start_time;

	// Total time in miliseconds
	$total_time = round((microtime(true) - $start_time)*1000,2);

	$s = "Compatibility list developed and maintained by
	<a href='https://github.com/AniLeo' target=\"_blank\">AniLeo</a>
	&nbsp;-&nbsp;
	Page loaded in {$total_time}ms";
	$s = "<div class=\"compat-footer\"><p>{$s}</p></div>";

	// Debug output
	if ($get['w'] != NULL) {
		$s .= "<div class=\"compat-profiler\">";
		// Maintenance mode information
		$s .= "<p>Maintenance mode: ";
		$s .= $c_maintenance ? "<span class=\"color-green\"><b>ON</b></span>" : "<span class=\"color-red\"><b>OFF</b></span>";
		$s .= "</p>";

		// Profiler information
		$s .= Profiler::getDataHTML();
		$s .= "</div>";
	}

	return $s;
}


// File path where the menu was called from
function getMenu($file) {
	global $get;

	$file = basename($file, '.php');

	$menu = "";

	if ($file != "compat") 	{ $menu .= "<a href='?'>Compatibility List</a>"; }
	if ($file != "history") { $menu .= "<a href='?h'>Compatibility List History</a>"; }
	if ($file != "builds") 	{ $menu .= "<a href='?b'>RPCS3 Builds History</a>";	}
	if ($file != "library") { $menu .= "<a href='?l'>PS3 Game Library</a>"; }
	if ($get['w'] != NULL && $file != "panel") { $menu .= "<a href='?a'>Debug Panel</a>"; }

	return "<div class=\"compat-menu\">{$menu}</div>";
}


// Get current page user is on
function getCurrentPage($pages) {
	if (isset($_GET['p']) && !is_array($_GET['p'])) {
		$currentPage = (int) $_GET['p'];
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
	global $a_status;

	// Get games count per status
	$count = countGames()["status"];

	// Initialize string
	$output = "";

	// Pretty output for readability
	foreach ($a_status as $id => $status) {

		$output .= "<div class='compat-status-main'>\n";
		$output .= "<div class='compat-status-icon' style='background:#{$status['color']}'></div>\n";
		$output .= "<div class='compat-status-text'>\n";
		$output .= "<p style='color:#{$status['color']}'><strong>{$status['name']}";

		if ($getCount) {
			$percentage = round(($count[$id]/$count[0])*100, 2, PHP_ROUND_HALF_UP);
			$output .= " ({$percentage}%)";
		}

		$output .= ":</strong></p>&nbsp;&nbsp;{$status['desc']}\n</div>\n";

		if ($getCount) {
			$output .= "<div class='compat-status-progress'>\n";
			$output .= "<progress class='compat-status-progressbar' id='compat-progress{$id}' style=\"color:#{$status['color']}\" max=\"100\" value=\"{$percentage}\"></progress>\n";
			$output .= "</div>\n";
		}

		$output .= "</div>\n";
	}

	return "<div class='compat-status-container'>\n{$output}</div>";
}


// Checks if user has debug permissions
// TODO: Login system
function getDebugPermissions($db = null) {
	if (!isset($_COOKIE["debug"]) || !is_string($_COOKIE["debug"]) || !ctype_alnum($_COOKIE["debug"])) {
		return null;
	}

	if ($db == null) {
		$db = getDatabase();
		if ($db == null) {
			return null; // If there's no database connection, just assume user isn't whitelisted
		}
		$close = true;
	} else {
		$close = false;
	}

	$q_debug = mysqli_query($db, "SELECT * FROM `debug_whitelist` WHERE `token` = '".mysqli_real_escape_string($db, $_COOKIE["debug"])."' LIMIT 1; ");

	if (is_null($q_debug) || mysqli_num_rows($q_debug) === 0)
		return null;

	$row = mysqli_fetch_object($q_debug);
	$permissons = array();

	if (strpos($row->permissions, ',') === false) {
		$permissions[0] = $row->permissions;
	} else {
		$permissions = explode(',', $row->permissions);
	}

	if ($close)
		mysqli_close($db);

	if (is_null($permissions) || !is_array($permissions))
		return null;

	return $permissions;
}


function combinedSearch($r, $s, $c, $g, $f, $t, $d, $o) {
	global $get, $scount, $c_pageresults;

	// Initialize string
	$combined = "";

	// Combined search: results per page
	if ($get['r'] != $c_pageresults && $r) {$combined .= "r={$get['r']}&";}
	// Combined search: sort by status
	if ($get['s'] != 0 && $s) {$combined .= "s={$get['s']}&";}
	// Combined search: search by character
	if ($get['c'] != "" && $c) {$combined .= "c={$get['c']}&";}
	// Combined search: searchbox
	if ($get['g'] != "" && $scount["status"] > 0 && $g) {$combined .= "g=".urlencode($get['g'])."&";}
	// Combined search: search by media type
	if ($get['t'] != "" && $t) {$combined .= "t={$get['t']}&";}
	// Combined search: search by region
	if ($get['f'] != "" && $f) {$combined .= "f={$get['f']}&";}
	// Combined search: date search
	if ($get['d'] != "" && $d) {$combined .= "d={$get['d']}&";}
	// Combined search: order by
	if ($get['o'] != "" && $o) {$combined .= "o={$get['o']}&";}

	return $combined;
}


function getDateDiff($datetime) {
	$diff = time() - strtotime($datetime);
	$days = floor($diff / 86400);

	if ($days == 0) {
		$hours = floor($diff / 3600);
		if ($hours == 0) {
			$minutes = floor($diff / 60);
			$diff = $minutes == 1 ? "{$minutes} minute" : "{$minutes} minutes";
		} else {
			$diff = $hours == 1   ? "{$hours} hour" : "{$hours} hours";
		}
	} else {
		$diff = $days == 1      ? "{$days} day" : "{$days} days";
	}

	return "{$diff} ago";
}


// Based on https://stackoverflow.com/a/9826656
// Returns false if any of the limits aren't contained on $string
function get_string_between($string, $start, $end) {
	// Return position of initial limit in our string
	// If position doesn't exist, then return false as string doesn't contain our start limit
	if (!($inipos = strpos($string, $start)))
		return false;

	// Add length of start limit, so our start position is the character AFTER the start limit
		$inipos += strlen($start);

	// Look for end string position starting on initial position (offset)
	// If position doesn't exist, then return false as string doesn't contain our end limit
		if (!($endpos = strpos($string, $end, $inipos)))
		return false;

	// Start on 'start limit position' and return string with substring length
		return substr($string, $inipos, $endpos-$inipos /*substring length*/);
}


function monthNumberToName($month) {
	return DateTime::createFromFormat('!m', $month)->format('F');
}


function dumpVar($var) {
	echo "<br>";
	highlight_string("<?php\n\$data =\n".var_export($var, true).";\n?>");
}


function resultsPerPage($combinedSearch, $extra = "") {
	global $a_pageresults, $get;

	$s_pageresults = "";

	foreach ($a_pageresults as $pageresult) {
		$s_pageresults .= "<a href=\"?{$extra}{$combinedSearch}r={$pageresult}\">";
		// If the current selected item, highlight
		$s_pageresults .= highlightText($pageresult, $get['r'] == $pageresult);
		$s_pageresults .= "</a>";

		// If not the last value then add a separator for the next value
		if ($pageresult !== end($a_pageresults)) { $s_pageresults .= "&nbsp;•&nbsp;"; }
	}
	return $s_pageresults;
}


// Checks whether indicated string is a Game ID or not
// Game ID validation: is alphanumeric, len = 9, last 5 characters are digits,
// 3rd character represents a valid region and 1st character represents a valid media
function isGameID($string) {
	global $a_flags, $a_media;

	return ctype_alnum($string) && strlen($string) == 9 && is_numeric(substr($string, 4, 5)) &&
	array_key_exists(strtoupper(substr($string, 2, 1)), $a_flags) && array_key_exists(strtoupper(substr($string, 0, 1)), $a_media);
}


// Runs a function while keeping track of the time it takes to run
// Returns amount of time in seconds
function runFunctionWithCronometer($function) {
	$start = microtime(true);
	$function();
	$finish = microtime(true);
	return round(($finish - $start), 4); // Seconds
}


// Gets status ID for a respective status title
function getStatusID($name) {
	global $a_status;

	foreach ($a_status as $id => $status) {
		if ($name == $status['name']) { return $id; }
	}

	return null;
}


// cURL JSON document and return the result as (HttpCode, JSON)
function curlJSON($url, &$cr = null) {
	// Use existing cURL resource or create a temporary one
	$ch = ($cr != null) ? $cr : curl_init();

	// Set the required cURL flags
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return result as raw output
	curl_setopt($ch, CURLOPT_URL, $url);
	if (strlen($url) >= 23 && substr($url, 0, 23) === "https://api.github.com/") {
		// We're cURLing the GitHub API, set GitHub Auth Token on headers
		curl_setopt($ch, CURLOPT_USERAGENT, "RPCS3 - Compatibility");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: token ".gh_token));
	} else {
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:66.0) Gecko/20100101 Firefox/66.0");
	}

	// Get the response and httpcode of that response
	$result = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	// Decode JSON
	$result = json_decode($result);

	// Close the temporary cURL resource or reset the given cURL resource
	if ($cr == null)
		curl_close($ch);
	else
		curl_reset($cr);

	return array('httpcode' => $httpcode, 'result' => $result);
}


// cURL XML document and return the result as (HttpCode, JSON)
function curlXML($url, &$cr = null) {
	// Use existing cURL resource or create a temporary one
	$ch = ($cr != null) ? $cr : curl_init();

	// Set the required cURL flags
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return result as raw output
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Do not verify SSL certs (PS3 Update API uses Private CA)
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:63.0) Gecko/20100101 Firefox/63.0");
	curl_setopt($ch, CURLOPT_URL, $url);

	// Get the response and httpcode of that response
	$result = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	// Convert from XML to JSON
	$result = simplexml_load_string($result);
	$result = json_encode($result);
	$result = json_decode($result, true);

	// Close the temporary cURL resource or reset the given cURL resource
	if ($cr == null)
		curl_close($ch);
	else
		curl_reset($cr);

	return array('httpcode' => $httpcode, 'result' => $result);
}


// Returns empty for no update, 'X.XX' string for the latest existing update
function getLatestGameUpdateVer($gid) {
	// cURL the PS3 Game Update API
	$curl = curlXML("https://a0.ww.np.dl.playstation.net/tpl/np/{$gid}/{$gid}-ver.xml");
	$json = $curl['result'];

	// No updates
	if ((!$json && $curl['httpcode'] == 200) || $curl['httpcode'] == 404)
		return "";

	// Some other HTTPCode that needs handling
	if ($curl['httpcode'] != 200)
		return null;

	// Has multiple updates, pick the latest one
	if (array_key_exists(0, $json['tag']['package']))
		return $json['tag']['package'][sizeof($json['tag']['package'])-1]['@attributes']['version'];

	// Has a single update
	if (array_key_exists("version", $json['tag']['package']['@attributes']))
		return $json['tag']['package']['@attributes']['version'];

	// Unknown error that needs handling
	return null;
}
