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
	if (!$db) return null;
	mysqli_set_charset($db, 'utf8mb4');
	return $db;
}


/**
	* getGameMedia
	*
	* Obtains Game Media by checking Game ID's first character.
	* Returns Game Media as an image, empty string if Game Media is invalid.
	*
	* @param string $gid   GameID, 9 character ID that identifies a game
	* @param bool   $url   Whether to return Game Media as a clickable(1) or non-clickable(0) flag
	* @param string $extra Extra params for clickable URL (combined search)
	*
	* @return string
	*/
function getGameMedia($gid, $url = true, $extra = '') {
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

	$img = "<img title=\"{$alt}\" alt=\"{$alt}\" src=\"{$a_media[$l]}\" class=\"compat-icon-media\">";

	// Get the module we're on so we can reset back to the correct module
	$ex = $extra != '' ? substr($extra, 0, 1) : '';

	// Allow for filter resetting by clicking the icon again
	if ($get['t'] == strtolower($l) && $url) {
		return "<a href=\"?{$ex}\">{$img}</a>";
	}

	if ($url) {
		// Returns clickable icon for type (media) search
		return "<a href=\"?{$extra}t=".strtolower($l)."\">{$img}</a>";
	} else {
		// Returns unclickable icon
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
		return "<a href=\"?{$ex}\"><img title=\"{$gid}\" alt=\"{$l}\" src=\"{$a_flags[$l]}\"></a>";
	}

	if ($url) {
		// Returns clickable flag for region (flag) search
		return "<a href=\"?{$extra}f=".strtolower($l)."\"><img title=\"{$gid}\" alt=\"{$l}\" src=\"{$a_flags[$l]}\"></a>";
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
	global $a_status;

	foreach ($a_status as $id => $status) {
		if ($sn == $status['name']) { return "<div class='txt-compat-status' style='background: #{$status['color']};'>{$status['name']}</div>"; }
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
	return !preg_match("/[^A-Za-z0-9.#&~;: \/\'-]/", $str);
}


/**
	* highlightText
	*
	* Returns provided string with increased size and font-weight
	*
	* @param string $str Some text
	*
	* @return string
	*/
function highlightText($str) {
	return "<span class=\"highlightedText\">{$str}</span>";
}


function validateGet($db = null) {
	global $a_pageresults, $c_pageresults, $a_status, $a_order, $a_flags, $a_histdates, $a_currenthist, $a_media;

	// Start new $get array
	$get = array();

	// rss - Global
	// api - Global

	// Set default values
	$get['r'] = $c_pageresults;
	$get['s'] = 0; // All
	$get['o'] = '';
	$get['c'] = '';
	$get['g'] = "";
	$get['d'] = '';
	$get['f'] = '';
	$get['t'] = '';
	$get['h'] = $a_currenthist[0];
	$get['m'] = '';

	// PS3 Games List
	if (isset($_GET['l'])) {
		$get['l'] = $_GET['l'];
	}

	// Results per page
	if (isset($_GET['r']) && in_array($_GET['r'], $a_pageresults)) {
		$get['r'] = $_GET['r'];
	}

	// Status
	if (isset($_GET['s']) && ($_GET['s'] == 0 || array_key_exists($_GET['s'], $a_status))) {
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

	// Media type
	if (isset($_GET['t']) && array_key_exists(strtoupper($_GET['t']), $a_media)) {
		$get['t'] = strtolower($_GET['t']);
	}

	// Region
	if (isset($_GET['f']) && array_key_exists(strtoupper($_GET['f']), $a_flags)) {
		$get['f'] = strtolower($_GET['f']);
	}

	// History
	if (isset($_GET['h']) && array_key_exists($_GET['h'], $a_histdates)) {
		$get['h'] = $_GET['h'];
	}

	// History mode
	if (isset($_GET['m']) && ($_GET['m'] == "c" || $_GET['m'] == "n")) {
		$get['m'] = strtolower($_GET['m']);
	}

	// Is whitelisted?
	$get['w'] = isWhitelisted($db) ? true : false;

	// Enable error reporting for admins
	if ($get['w']) {
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
	}

	// Admin debug mode
	if (isset($_GET['a']) && $get['w']) {
		$get['a'] = $_GET['a'];
	}

	return $get;
}


// Select the count of games in each status, subjective to query restrictions
function countGames($db = null, $query = '') {
	global $get, $a_status;

	if ($db == null) {
		$db = getDatabase();
		if (is_null($db)) {
			return 0; // If there's no database connection, return 0 games
		}
		$close = true;
	} else {
		$close = false;
	}

	if ($query == 'all') {
		// Unique game count
		return mysqli_fetch_object(mysqli_query($db, "SELECT count(*) AS c FROM game_list"))->c;
	}

	if ($query == '') {

		// Empty query or general query with order only, all games returned
		if (file_exists(__DIR__.'/cache/a_count.json') && $get['s'] == 0) {
			// If we're running a general search, use cached count results
			$a_count = json_decode(file_get_contents(__DIR__.'/cache/a_count.json'), true);
			return $a_count;
		} else {
			$q_gen = mysqli_query($db, "SELECT status+0 AS statusID, count(*) AS c FROM game_list GROUP BY status;");
		}

	} else {
		// Query defined, return count of games with searched parameters
		$q_gen = mysqli_query($db, "SELECT status+0 AS statusID, count(*) AS c FROM game_list WHERE ({$query}) GROUP BY status;");
	}

	// Zero-fill the array keys that are going to be used
	$scount[0][0] = 0;
	$scount[1][0] = 0;
	foreach ($a_status as $id => $status) {
		$scount[0][$id] = 0;
		$scount[1][$id] = 0;
	}

	while ($row = mysqli_fetch_object($q_gen)) {

		// For count with specified status, include only results for specified status
		if ($row->statusID == $get['s']) {
			$scount[0][$row->statusID] = (int)$row->c;
			$scount[0][0] = (int)$row->c;
		}

		// Add count from status to the array
		$scount[1][$row->statusID] = (int)$row->c;

		// Instead of querying the database once more add all the previous counts to get the total count
		$scount[1][0] += $scount[1][$row->statusID];
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

	// Initialize string
	$s_pagescounter = "";

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

			$s_pagescounter .= "{$common}p=$i\">";

			// Add zero padding if it is a single digit number
			$p = ($i < 10) ? "0{$i}" : "{$i}";

			// Highlights the page if it's the one we're currently in
			$s_pagescounter .= ($i == $currentPage) ? highlightText($p) : $p;

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

	$tableHead_open  = "<div class=\"divTableHead\">";
	$tableHead_close = "</div>";

	// Initialize string
	$s_tableheaders = "";

	foreach ($headers as $k => $v) {
		if     ($v == 0)              { $s_tableheaders .= "{$tableHead_open}{$k}{$tableHead_close}"; }
		elseif ($get['o'] == "{$v}a") { $s_tableheaders .= "{$tableHead_open}<a href =\"?{$extra}o={$v}d\">{$k} &nbsp; &#8593;</a>{$tableHead_close}"; }
		elseif ($get['o'] == "{$v}d") { $s_tableheaders .= "{$tableHead_open}<a href =\"?{$extra}\">{$k} &nbsp; &#8595;</a>{$tableHead_close}"; }
		else                          { $s_tableheaders .= "{$tableHead_open}<a href =\"?{$extra}o={$v}a\">{$k}</a>{$tableHead_close}"; }
	}

	return "<div class=\"divTableHeading\">{$s_tableheaders}</div>";
}


function getFooter() {
	global $prof_desc, $c_profiler, $get, $start_time, $start_memory;

	// Total time in miliseconds
	$total_time = round((getTime() - $start_time)*1000,2);

	$s = "Compatibility list developed and maintained by
	<a href='https://github.com/AniLeo' target=\"_blank\">AniLeo</a>
	&nbsp;-&nbsp;
	Page loaded in {$total_time}ms";

	// Memory information
	if ($get['w']) {
		$s .= "<p style='line-height:20px; padding-bottom:15px;'>";
		$s .= "Start Memory: ".round($start_memory/1024, 2)."kB<br>";
		$s .= "End Memory: ".round(memory_get_usage(false)/1024, 2)."kB<br>";
		$s .= "Peak Memory: ".round(memory_get_peak_usage(false)/1024, 2)."kB<br>";
		$s .= "</p>";
	}

	// Profiler information
	if ($get['w'] && $c_profiler && !empty($prof_desc)) {
		$s .= "<p style='line-height:20px; padding-bottom:15px;'><b>{$prof_desc}</b><br>".prof_print()."</p>";
	}

	return "<div id=\"compat-author\"><p>{$s}</p></div>";
}


function getMenu($c, $h, $b, $l, $a) {
	global $get;

	$menu = '';

	if ($c) { $menu .= "<a href='?'>Compatibility List</a>"; }
	if ($h) { $menu .= "<a href='?h'>Compatibility List History</a>"; }
	if ($b) { $menu .= "<a href='?b'>RPCS3 Builds History</a>";	}
	if ($l) { $menu .= "<a href='?l'>PS3 Game Library</a>"; }
	if ($get['w'] && $a) { $menu .= "<a href='?a'>Debug Panel</a>"; }

	return "<p id='title2'>{$menu}</p>";
}


// Get current page user is on
function getCurrentPage($pages) {
	if (isset($_GET['p'])) {
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
	$count = countGames()[0];

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


// Checks if IP is on whitelist
function isWhitelisted($db = null) {
	global $c_cloudflare;

	if ($db == null) {
		$db = getDatabase();
		if (is_null($db)) {
			return false; // If there's no database connection, just assume user isn't whitelisted
		}
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

	return mysqli_num_rows($ipQuery) === 1;
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
	if ($get['g'] != "" && $scount[0] > 0 && $g) {$combined .= "g=".urlencode($get['g'])."&";}
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

		// Initialize string
		$s = "";

		for ($i=0;$i<$size - 1; $i++) {
				$s .= sprintf("%05dμs&nbsp;-&nbsp;%s<br>", $prof_timing[$i+1]-$prof_timing[$i], $prof_names[$i]);
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


function getJSON($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if (strpos($url, 'github.com') !== false) {
		curl_setopt($ch, CURLOPT_USERAGENT, 'RPCS3 - Compatibility');
		curl_setopt($ch, CURLOPT_URL, $url."?client_id=".gh_client."&client_secret=".gh_secret);
	} else {
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0');
		curl_setopt($ch, CURLOPT_URL, $url);
	}
	$result = curl_exec($ch);
	curl_close($ch);
	return json_decode($result);
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

		// If the current selected item, highlight with bold
		$s_pageresults .= ($get['r'] == $pageresult) ? highlightText($pageresult) : $pageresult;

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
