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
	
	if ($cid == "0") { return "<div class='{$a_css["NOBUILD"]}' style='background: #{$c_unkcommit};'>Unknown</div>"; }
	
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
		return "<a class='{$a_css["BUILD"]}' href=\"{$c_github}{$cid}\">".mb_substr($cid, 0, 8)."</a>";
	} else {
		return "<div class='{$a_css["NOBUILD"]}' style='background: #{$c_unkcommit};'><i>{$cid}</i></div>";
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
  * cacheCommits
  *
  * Caches the validity of commits obtained by isValidCommit.
  * This is required because validating takes too much time and kills page load times.
  *
  * @param bool $mode Recache mode (true = full / false = partial)
  *
  */
function cacheCommits($mode) {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$commitQuery = mysqli_query($db, "SELECT t1.build_commit, t2.commit_id, t2.valid 
	FROM ".db_table." AS t1 LEFT JOIN commit_cache AS t2 on t1.build_commit != t2.commit_id 
	WHERE build_commit != '0' GROUP BY build_commit; ");

	while($row = mysqli_fetch_object($commitQuery)) {
		
		$checkQuery = mysqli_query($db, "SELECT * FROM commit_cache WHERE commit_id = '".mysqli_real_escape_string($db, $row->build_commit)."' LIMIT 1; ");
		$row2 = mysqli_fetch_object($checkQuery);
		
		// Partial recache: If value isn't cached, then cache it 
		if (mysqli_num_rows($checkQuery) === 0) {
			if (isValidCommit($row->build_commit)) { $valid = 1; } else { $valid = 0; }
			mysqli_query($db, "INSERT INTO commit_cache (commit_id, valid) VALUES ('".mysqli_real_escape_string($db, $row->build_commit)."', '{$valid}'); ");
		} 
		
		// Full recache: Updates currently existent entries (commits don't dissappear, this option shouldn't be needed...)
		elseif ($mode) {
			if (isValidCommit($row->build_commit)) { $valid = 1; } else { $valid = 0; }
			// If value is cached but differs on validation, update it	
			if ($row2->valid != $valid) {
				mysqli_query($db, "UPDATE commit_cache SET valid = '{$valid}' WHERE commit_id = '".mysqli_real_escape_string($db, $row->build_commit)."' LIMIT 1; ");
			}
		}
	}
	mysqli_close($db);
}


function cacheWindowsBuilds(){
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	// Get date from last merged PR. Subtract 1 day to it and check new merged PRs since then.
	$mergeDateQuery = mysqli_query($db, "SELECT merge_datetime FROM builds_windows ORDER BY merge_datetime DESC LIMIT 1;");
	$row = mysqli_fetch_object($mergeDateQuery);
	
	$merge_datetime = date_create($row->merge_datetime);
	date_sub($merge_datetime, date_interval_create_from_date_string('1 day'));
	$date = date_format($merge_datetime, 'Y-m-d');

	// This takes a while to do...
	set_time_limit(60*60);
	
	/*
	Get last page from GitHub PR lists: For first time run, only works when there are several pages of PRs.
	$content = file_get_contents("https://github.com/RPCS3/rpcs3/pulls?utf8=%E2%9C%93&q=is%3Apr%20is%3Amerged%20sort%3Acreated-asc%20merged%3A%3E{$date}");
	$step_1 = explode("<span class=\"gap\">&hellip;</span>", $content);
	$step_2 = explode("<a class=\"next_page\" rel=\"next\"" , $step_1[1]);
	$step_3 = explode(">" , $step_2[0]);
	$step_4 = explode("</a" , $step_3[3]);
	$last_page = $step_4[0];
	*/

	$last_page = 1;
	$a_PR = array();

	// Loop through all pages and get PR information
	for ($i=1; $i<=$last_page; $i++) {
		$content = file_get_contents("https://github.com/RPCS3/rpcs3/pulls?page={$i}&q=is%3Apr+is%3Amerged+sort%3Acreated-asc+created%3A%3E{$date}&utf8=%E2%9C%93");
		
		$step_1 = explode("\" class=\"link-gray-dark no-underline h4 js-navigation-open\">", $content);
		
		$a = 0; // Current PR (page relative)
		$b = 0; // Current exploded iteration
		
		while ($a<25) {
			$pr = substr($step_1[$a], -4); // Future proof: Remember that this needs to be changed to -5 after PR #9999
			
			// At the end it prints something like l>. We know when it's not a number that it reached the end.
			if (!ctype_digit($pr)) {
				break 2;
			}
			
			// If PR isn't already in then add it, else ignore
			if (!in_array($pr, $a_PR)) {
				
				// No need to escape here, we break before if it's not numeric only
				$PRQuery = mysqli_query($db, "SELECT * FROM builds_windows WHERE pr = {$pr} LIMIT 1; ");
				array_push($a_PR, $pr);
				
				// If PR isn't cached, then just DO IT!
				if (mysqli_num_rows($PRQuery) === 0) {
				
					$content2 = file_get_contents("https://github.com/RPCS3/rpcs3/pull/{$pr}");
					
					$e_author = explode(" class=\"author\">", $content2);
					$author = explode("</a>", $e_author[1])[0];
					
					$e_datetime = explode("<relative-time datetime=\"", $content2);
					$merge_datetime = explode("\"", $e_datetime[1])[0];
					$start_datetime = explode("\"", $e_datetime[2])[0];
					
					$e_appveyor = explode("<a class=\"status-actions\" href=\"https://ci.appveyor.com/project/rpcs3/rpcs3/build/", $content2);
					$build = explode("\"", $e_appveyor[1])[0];
				
					$cachePRQuery = mysqli_query($db, " INSERT INTO `builds_windows` (`pr`, `author`, `start_datetime`, `merge_datetime`, `appveyor`) 
					VALUES ('{$pr}', '".mysqli_real_escape_string($db, $author)."', '{$start_datetime}', '{$merge_datetime}', '{$build}'); ");
				}
				$a++;
			}
			$b++;
		}
	}
		
	mysqli_close($db);
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
	global $a_pageresults, $c_pageresults, $a_title, $a_order, $a_flags, $a_histdates, $currenthist, $a_admin;
	
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
	$get['a'] = ''; // Admin debug Mode
	
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
	
	// Admin debug mode
	if (isset($_GET['a']) && in_array($_SERVER["REMOTE_ADDR"], $a_admin)) {
		$get['a'] = $_GET['a'];
	}
	
	return $get;
}


// Generates query from given GET parameters
function generateQuery($get, $status) {
	global $a_title, $a_order;

	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
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
	
	mysqli_close($db);
	
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


function getTime() {
	$t = explode(' ', microtime());
	return $t[1] + $t[0];
}


/********************************************************************************************************************/


function builds_getTableHeaders() {
	global $get;
	
	$s_tableheaders .= "<tr>";
	
	/* Commonly used code: so we don't have to waste lines repeating this */
	$common .= "<th><a href =\"?b&";
	
	
	/* Pull Request */
	$s_tableheaders .= $common;
	// Order by: Pull Request (ASC, DESC)
	if ($get['o'] == "1a") { $s_tableheaders .= "o=1d\">Pull Request &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "1d") { $s_tableheaders .= "\">Pull Request &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=1a\">Pull Request</a></th>"; } 

	/* Author */
	$s_tableheaders .= $common;
	// Order by: Author (ASC, DESC)
	if ($get['o'] == "2a") { $s_tableheaders .= "o=2d\">Author &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "2d") { $s_tableheaders .= "\">Author &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=2a\">Author</a></th>"; }

	/* Build Date  */
	$s_tableheaders .= $common;
	// Order by: Build Date (ASC, DESC)
	if ($get['o'] == "4a") { $s_tableheaders .= "o=4d\">Build Date &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "4d") { $s_tableheaders .= "\">Build Date &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=4a\">Build Date </a></th>"; }
	
	/* AppVeyor Download */
	$s_tableheaders .= "<th>Download</th>";
	
	$s_tableheaders .= "</tr>";
	
	return $s_tableheaders;
}


function builds_getTableContent() {
	global $get, $c_appveyor;

	// Order queries
	$a_order = array(
	'' => 'ORDER BY merge_datetime DESC',
	'1a' => 'ORDER BY pr ASC',
	'1d' => 'ORDER BY pr DESC',
	'2a' => 'ORDER BY author ASC',
	'2d' => 'ORDER BY author DESC',
	'3a' => 'ORDER BY merge_datetime ASC',
	'3d' => 'ORDER BY merge_datetime DESC',
	'4a' => 'ORDER BY merge_datetime DESC',
	'4d' => 'ORDER BY merge_datetime DESC'
	);
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$pagesQuery = mysqli_query($db, "SELECT count(*) AS c FROM builds_windows");
	$pages = ceil(mysqli_fetch_object($pagesQuery)->c / 25);
	
	if (isset($_GET['p'])) {
		$currentPage = intval($_GET['p']);
		if ($currentPage > $pages) { $currentPage = 1; }		
	} else { $currentPage = 1; }
	
	// TODO: Costum results per page
	$buildsCommand = "SELECT * FROM builds_windows {$a_order[$get['o']]} LIMIT ".(25*$currentPage-25).", 25; ";
	
	$buildsQuery = mysqli_query($db, $buildsCommand);
	
	if ($buildsQuery && mysqli_num_rows($buildsQuery) > 0) {
		while ($row = mysqli_fetch_object($buildsQuery)) { 
		
			$days = floor( (time() - strtotime($row->merge_datetime) ) / 86400 );			
			if ($days == 0) {
				$hours = floor( (time() - strtotime($row->merge_datetime) ) / 86400 );
				$diffdate = $hours . " hours ago";
			} elseif ($days == 1) { $diffdate = $days . " day ago"; } 
			else {  $diffdate = $days . " days ago"; }
			$fulldate = date_format(date_create($row->merge_datetime), "Y-m-d");
	
			$s_tablecontent .= "<tr>
			<td><a href=\"https://github.com/RPCS3/rpcs3/pull/{$row->pr}\"><img width='15px' height='15px' src=\"/img/icons/menu/github.png\">&nbsp;&nbsp;#{$row->pr}</a></td>
			<td><a href=\"https://github.com/{$row->author}\">{$row->author}</a></td>
			<td>{$diffdate} ({$fulldate})</td>";
			if ($row->appveyor != "0") { 
				$s_tablecontent .= "<td><a href=\"{$c_appveyor}{$row->appveyor}/artifacts\"><img width='15px' height='15px' src=\"/img/icons/menu/download.png\">&nbsp;&nbsp;{$row->appveyor}</a></td>";
			} else {
				$s_tablecontent .= "<td><i>None</i></td>";
			}
			$s_tablecontent .= "</tr>";
		}
	} else {
		// Query generator fail error
		$s_tablecontent .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
	}

	mysqli_close($db);
	
	return $s_tablecontent;
}


function builds_getPagesCounter() {
	global $get;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$pagesQuery = mysqli_query($db, "SELECT count(*) AS c FROM builds_windows");
	$pages = ceil(mysqli_fetch_object($pagesQuery)->c / 25);
	
	if (isset($_GET['p'])) {
		$currentPage = intval($_GET['p']);
		if ($currentPage > $pages) { $currentPage = 1; }		
	} else { $currentPage = 1; }

	mysqli_close($db);
	
	$lim = 7; // Limit for amount of pages to be shown on pages counter (for each side of current page)
	
	// IF no results are found then something went wrong
	if ($pages == 0) { 
		$s_pagescounter .= 'An error has occoured.';
	} 
	// Shows current page and total pages
	else { 
		$s_pagescounter .= "Page {$currentPage} of {$pages} - "; 
	}
	
	// Commonly used code
	$common = "<a href=\"?b&";
	
	// Page support: Order by
	if ($get['o'] != "") {$common .= "o={$get['o']}&";} 
	
	// If there's less pages to the left than current limit it loads the excess amount to the right for balance
	if ($lim > $currentPage) {
		$a = $lim - $currentPage;
		$lim = $lim + $a + 1;
	}
	
	// If there's less pages to the right than current limit it loads the excess amount to the left for balance
	if ($lim > $pages - $currentPage) {
		$a = $lim - ($pages - $currentPage);
		$lim = $lim + $a + 1;
	}
	
	// Loop for each page link and make it properly clickable until there are no more pages left
	for ($i=1; $i<=$pages; $i++) { 
	
		if ( ($i >= $currentPage-$lim && $i <= $currentPage) || ($i+$lim >= $currentPage && $i <= $currentPage+$lim) ) {
			
			$s_pagescounter .= $common;
			
			// Display number of the page and highlight if current page
			$s_pagescounter .= "p=$i\">";
			if ($i == $currentPage) { if ($i < 10) { $s_pagescounter .= highlightBold("0"); } $s_pagescounter .= highlightBold($i); }
			else { if ($i < 10) { $s_pagescounter .= "0"; } $s_pagescounter .= $i; }
			
			$s_pagescounter .= "</a>&nbsp;&#32;"; 
		
		} elseif ($i == 1) {
			// First page
			$s_pagescounter .= $common;
			$s_pagescounter .= "p=$i\">01</a>&nbsp;&#32;"; 
			if ($currentPage != $lim+2) { $s_pagescounter .= "...&nbsp;&#32;"; }
		} elseif ($pages == $i) {
			// Last page
			$s_pagescounter .= "...&nbsp;&#32;";
			$s_pagescounter .= $common;
			$s_pagescounter .= "p=$pages\">$pages</a>&nbsp;&#32;"; 
		}
		
	}

	return $s_pagescounter;
}

?>
