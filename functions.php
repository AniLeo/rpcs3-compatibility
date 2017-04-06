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

// Productcode info: PSDevWiki (http://www.psdevwiki.com/ps3/Productcode)


/**
  * getGameMedia
  *
  * Obtains Game Media by checking Game ID's first character. 
  * Returns Game Media as an image with MEDIA_ICON CSS class,
  *  empty string if Game Media is invalid.
  *
  * @param string $gid GameID: 9 character ID that identifies a game
  *
  * @return string
  */
function getGameMedia($gid) {
	global $a_media, $a_css;
	
	if     (substr($gid, 0, 1) == "N")  { return "<img alt=\"Digital\" src=\"{$a_media["PSN"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PSN Retail
	elseif (substr($gid, 0, 1) == "B")  { return "<img alt=\"Blu-Ray\" src=\"{$a_media["BLR"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PS3 Blu-Ray
	elseif (substr($gid, 0, 1) == "X")  { return "<img alt=\"Blu-Ray\" src=\"{$a_media["BLR"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PS3 Blu-Ray + Extras
	else                                { return ""; }
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
function getGameRegion($gid, $url) {
	global $a_flags;
	
	$l = substr($gid, 2, 1);
	
	// If it's not a valid / known region then we return an empty string
	if (!array_key_exists($l, $a_flags)) {
		return "";
	}
	
	if ($url) {
		// Returns clickable flag for region (flag) search
		return "<a href=\"?f=".strtolower($l)."\"><img src=\"{$a_flags[$l]}\"></a>";
	} else {
		// Returns unclickable flag
		return "<img src=\"$a_flags[$l]\">";
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
	global $c_github, $a_css, $c_unkcommit;
	
	// If the commit is unknown we input 0.
	if ($cid != "0") { return "<a class='{$a_css["BUILD"]}' href=\"{$c_github}{$cid}\">".mb_substr($cid, 0, 8)."</a>"; } 
	else             { return "<div class='{$a_css["NOBUILD"]}' style='background: #{$c_unkcommit};'>Unknown</div>"; }
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
	global $a_title, $a_color, $a_css;
	
	foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $i) { 
		if ($sn == $a_title[$i]) { return "<div class='{$a_css["STATUS"]}' style='background: #{$a_color[$i]};'>{$a_title[$i]}</div>"; }
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
	global $a_pageresults, $c_pageresults, $a_title, $a_order, $a_flags, $a_histdates, $currenthist;
	
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
	$get['h'] = '';
	$get['h1'] = db_table;
	$get['h2'] = '2017_03';
	$get['m'] = '';
	
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
	if (isset($_GET['o']) && array_key_exists($_GET['o'], $a_order)) {
		$get['o'] = strtolower($_GET['o']);
	}
	
	// Character
	if (isset($_GET['c'])) {
		// If it is a single alphabetic character 
		if (ctype_alpha($_GET['c']) && (strlen($_GET['c']) == 1)) {
			$get['c'] = strtolower($_GET['c']);
		}
		if ($_GET['c'] == "09")  { $get['c'] = "09";  } // Numbers
		if ($_GET['c'] == "sym") { $get['c'] = "sym"; } // Symbols
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
	if (isset($_GET['f']) && array_key_exists($_GET['f'], $a_flags)) {
		$get['f'] = strtolower($_GET['f']); 
	}
	
	// History
	if (isset($_GET['h']) && array_key_exists($_GET['h'], $a_histdates)) {
		$keys = array_keys($a_histdates);
		$index = array_search($_GET['h'], $keys);
		
		if ($index >= 0 && $currenthist != $_GET['h']) { 
			$get['h1'] = $_GET['h'];
			$get['h2'] = $keys[$index-1]; 
		}
	}
	
	// History mode
	if (isset($_GET['m']) && ($_GET['m'] == "c" || $_GET['m'] == "n")) {
		$get['m'] = strtolower($_GET['m']);
	}
	
	return $get;
}


// Generates query from given GET parameters
function generateQuery($db, $get, $status) {
	global $a_title, $a_order;

	$genquery = "";
	
	// Force status to be All
	if (!$status) {
		$get['s'] = 0;
	}
	
	// QUERYGEN: Status
	if ($get['s'] > min(array_keys($a_title))) { $genquery .= " status = {$get['s']} "; } 
	
	// QUERYGEN: Character
	if ($get['c'] != "") {
		if ($get['c'] == '09') {
			if ($get['s'] > min(array_keys($a_title))) { $genquery .= " AND "; }
			$genquery .= " (game_title LIKE '0%' OR game_title LIKE '1%' OR game_title LIKE '2%'
			OR game_title LIKE '3%' OR game_title LIKE '4%' OR game_title LIKE '5%' OR game_title LIKE '6%' OR game_title LIKE '7%'
			OR game_title LIKE '8%' OR game_title LIKE '9%') ";
		} elseif ($get['c'] == 'sym') {
			if ($get['s'] > min(array_keys($a_title))) { $genquery .= " AND "; }
			$genquery .= " (game_title LIKE '.%' OR game_title LIKE '&%') "; // TODO: Add more symbols when they show up
		} else {
			if ($get['s'] > min(array_keys($a_title))) { $genquery .= " AND "; }
			$genquery .= " game_title LIKE '{$get['c']}%' ";
		}
	}

	// QUERYGEN: Searchbox
	if ($get['g'] != "") {
		if ($get['s'] > min(array_keys($a_title)) && $get['c'] == "") { $genquery .= " AND "; }
		if ($get['c'] != "") { $genquery .= " AND "; }
		$ssf = mysqli_real_escape_string($db, $get['g']);
		$genquery .= " (game_title LIKE '%{$ssf}%' OR game_id LIKE '%{$ssf}%') ";
	}

	// QUERYGEN: Search by region
	if ($get['f'] != "") {
		if ($get['s'] > min(array_keys($a_title)) && $get['c'] == "") { $genquery .= " AND "; }
		if ($get['c'] != "" || $get['g'] != "") { $genquery .= " AND "; }
		$genquery .= " SUBSTR(game_id, 3, 1) = '{$get['f']}' ";
	}

	// QUERYGEN: Search by date
	if ($get['d'] != "") {
		if ($get['s'] > min(array_keys($a_title)) && $get['c'] == "" && $get['f'] != "") { $genquery .= " AND "; }
		if ($get['c'] != "" || $get['g'] != "" || $get['f'] != "") { $genquery .= " AND "; }
		$sd = mysqli_real_escape_string($db, $get['d']);
		$genquery .= " last_edit = '{$sd}' "; 
	}
	
	return $genquery;
}


// Select the count of games in each status, subjective to query restrictions
function countGames($query) {
	global $a_title, $get;
	
	// Connect to database
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $s) { 
	
		if ($query == "") {
			// Empty query or general query with order only, all games returned
			$squery[$s] = "SELECT count(*) AS c FROM ".db_table." WHERE status = {$s}";
		} else {
			// Query defined, return count of games with searched parameters
			$squery[$s] = "SELECT count(*) AS c FROM ".db_table." WHERE ({$query}) AND status = {$s}";
		}
		
		$scount[$s] = mysqli_fetch_object(mysqli_query($db, $squery[$s]))->c;
		
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

?>
