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
if (!@include_once(__DIR__."/../functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/../objects/Game.php")) throw new Exception("Compat: Game.php is missing. Failed to include Game.php");
if (!@include_once(__DIR__."/../objects/Profiler.php")) throw new Exception("Compat: Profiler.php is missing. Failed to include Profiler.php");



class Compat {


// Generates query from given GET parameters
static function generateQuery($get, &$db) {

	if ($db == null) {
		$db = getDatabase();
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
			// Regular expression: Starts with a number
			$genquery .= " (`game_title` REGEXP '^[0-9]' OR `alternative_title` REGEXP '^[0-9]') ";
		} elseif ($get['c'] == 'sym') {
			// Allowed characters: ' .
			$genquery .= " (`game_title` LIKE '.%' OR `game_title` LIKE '\'%' OR `alternative_title` LIKE '.%' OR `alternative_title` LIKE '\'%') ";
		} else {
			$genquery .= " (`game_title` LIKE '{$get['c']}%' OR `alternative_title` LIKE '{$get['c']}%') ";
		}
		$and = true;
	}

	// QUERYGEN: Searchbox
	if ($get['g'] != '') {
		if ($and) { $genquery .= " AND "; }
		$s_g = mysqli_real_escape_string($db, $get['g']);
		$genquery .= " (`game_title` LIKE '%{$s_g}%' OR `alternative_title` LIKE '%{$s_g}%' OR `gid_EU` LIKE '%{$s_g}%' OR `gid_US` LIKE '%{$s_g}%' OR `gid_JP` LIKE '%{$s_g}%'
		OR `gid_AS` LIKE '%{$s_g}%' OR `gid_KR` LIKE '%{$s_g}%' OR `gid_HK` LIKE '%{$s_g}%') ";
		$and = true;
	}

	// QUERYGEN: Search by media type
	if ($get['t'] != '') {
		if ($and) { $genquery .= " AND "; }
		$genquery .= " (
		(`gid_EU` IS NOT NULL && SUBSTR(`gid_EU`,1,1) = '{$get['t']}') OR
		(`gid_US` IS NOT NULL && SUBSTR(`gid_US`,1,1) = '{$get['t']}') OR
		(`gid_JP` IS NOT NULL && SUBSTR(`gid_JP`,1,1) = '{$get['t']}') OR
		(`gid_AS` IS NOT NULL && SUBSTR(`gid_AS`,1,1) = '{$get['t']}') OR
		(`gid_HK` IS NOT NULL && SUBSTR(`gid_HK`,1,1) = '{$get['t']}') OR
		(`gid_KR` IS NOT NULL && SUBSTR(`gid_KR`,1,1) = '{$get['t']}')
		) ";
		$and = true;
	}

	// QUERYGEN: Search by date
	if ($get['d'] != '') {
		if ($and) { $genquery .= " AND "; }
		$s_d = mysqli_real_escape_string($db, $get['d']);
		$genquery .= " `last_update` = '{$s_d}' ";
		$and = true;
	}

	// QUERYGEN: Status
	if ($get['s'] != 0) {
		if ($and) { $status .= " AND "; }
		$status .= " `status` = {$get['s']} ";
		$and = true;
	}

	if ($close) {
		mysqli_close($db);
	}

	// 0 => With specified status
	// 1 => Without specified status
	return array($genquery.$status, $genquery);
}


/***********
 * Sort By *
 ***********/
public static function getSortBy() {
	global $a_status, $scount, $get;

	// All
	$s_sortby = "<a title=\"Show games from all statuses\" href=\"?".combinedSearch(true, false, true, true, false, true, true, true)."s=0\">";
	$s_sortby .= ($get['s'] == 0) ? highlightText("All ({$scount[1][0]})") : "All ({$scount[1][0]})";
	$s_sortby .= "</a>";

	foreach ($a_status as $id => $status) {
		// Displays status description when hovered on
		$s_sortby .= "<a title=\"{$status['desc']}\" href=\"?";
		$s_sortby .= combinedSearch(true, false, true, true, false, true, true, true);
		$s_sortby .= "s={$id}\">";

		// If the current selected status, highlight with bold
		$s_sortby .= ($get['s'] == $id) ? highlightText("{$status['name']} ({$scount[1][$id]})") : "{$status['name']} ({$scount[1][$id]})";

		$s_sortby .= "</a>";
	}
	return $s_sortby;
}


/********************
 * Results per page *
 ********************/
public static function getResultsPerPage() {
	return resultsPerPage(combinedSearch(false, true, true, true, false, true, true, true));
}


/***********************************
 * Clickable URL: Character search *
 **********************************/
public static function getCharSearch() {
	global $get;

	$a_chars[""] = "All";
	$a_chars["09"] = "0-9";
	foreach (range('a', 'z') as $i) {
		$a_chars[$i] = strtoupper($i);
	}
	$a_chars["sym"] = "#";

	/* Commonly used code: so we don't have to waste lines repeating this */
	$common = "<td><a href=\"?";
	$common .= combinedSearch(true, true, false, false, false, true, true, false);

	// Initialize string
	$s_charsearch = "";

	foreach ($a_chars as $key => $value) {
		$s_charsearch .= "{$common}c={$key}\"><div class='compat-search-character'>";
		$s_charsearch .= ($get['c'] == $key) ? "<span style=\"font-size:16px;\">{$value}</span>" : $value;
		$s_charsearch .= "</div></a></td>";
	}

	return "<tr>{$s_charsearch}</tr>";
}


public static function getTableMessages() {
	global $info, $error;
	if (!is_null($info)) { return "<p class=\"compat-tx1-criteria\">{$info}</p>"; }
	elseif (!is_null($error)) { return "<p class=\"compat-tx1-criteria\">{$error}</p>"; }
}


/*****************
 * Table Headers *
 *****************/
public static function getTableHeaders() {
	global $error;

	if (!is_null($error)) return "";

	$extra = combinedSearch(true, true, true, true, false, true, true, false);

	$headers = array(
		'Game IDs' => 0,
		'Game Title' => 2,
		'Status' => 3,
		'Updated on' => 4
	);

	return getTableHeaders($headers, $extra);
}


/*****************
 * Table Content *
 *****************/
public static function getTableContent() {
	global $games, $error, $a_status;

	if (!is_null($error)) return "";

	// Initialize string
	$s_tablecontent = "";

	foreach ($games as $game) {

		$media = '';
		$multiple = false;

		$s_tablecontent .= "<div class=\"divTableRow\">";

		// Cell 1: Regions and GameIDs
		$cell = '';
		foreach ($game->IDs as $ID) {
			if ($multiple)
				$cell .= "<br>";

			$cell .= getThread(getGameRegion($ID[0], false), $ID[1]);
			$cell .= getThread($ID[0], $ID[1]);

			if ($media == '')
				$media = getGameMediaIcon($ID[0]);

			$multiple = true;
		}
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 2: Game Media and Titles
		$title = !is_null($game->wikiID) ? "<a href=\"https://wiki.rpcs3.net/index.php?title={$game->wikiTitle}\">{$game->title}</a>" : $game->title;
		$cell = "{$media}{$title}";
		if (!is_null($game->title2))
			$cell .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;({$game->title2})";
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 3: Status
		$cell = '';
		if (!is_null($game->status))
			$cell = "<div class=\"txt-compat-status\" style=\"background: #{$a_status[$game->status]['color']};\">{$a_status[$game->status]['name']}</div>";
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 4: Last Test
		$cell = "<a href=\"?d=".str_replace('-', '', $game->date)."\">".$game->date."</a>&nbsp;&nbsp;&nbsp;";
		$cell .= $game->pr == 0 ? "(<i>Unknown</i>)" : "(<a href='https://github.com/RPCS3/rpcs3/pull/{$game->pr}'>Pull #{$game->pr}</a>)";
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";


		$s_tablecontent .= "</div>";

	}

	return "<div class=\"divTableBody\">{$s_tablecontent}</div>";
}


/*****************
 * Pages Counter *
 *****************/
public static function getPagesCounter() {
	global $pages, $currentPage;

	$extra = combinedSearch(true, true, true, true, false, true, true, true);

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

public static function APIv1() {
	global $c_maintenance, $games, $info, $error, $l_title, $a_status;

	// Array to returned, then encoded in JSON
	$results = array();
	$results['return_code'] = 0;

	if ($c_maintenance) {
		$results['return_code'] = -2;
		return $results;
	}

	if ($error == "Please try again. If this error persists, please contact the RPCS3 team.") {
		$results['return_code'] = -1;
		return $results;
	}

	if (isset($_GET['g']) && !empty($_GET['g']) && !isValid($_GET['g'])) {
		$results['return_code'] = -3;
		return $results;
	}

	if (is_null($games)) {
		$results['return_code'] = 1;
		return $results;
	}

	// No results found for {$l_orig}. Displaying results for {$l_title}.
	if (!is_null($info)) {
		$results['return_code'] = 2;
		$results['search_term'] = $l_title;
	}

	foreach ($games as $game) {
		foreach ($game->IDs as $id) {
			$results['results'][$id[0]] = array(
			'title' => $game->title,
			'alternative-title' => $game->title2,
			'wiki-title' => $game->wikiTitle,
			'status' => $a_status[$game->status]['name'],
			'date' => $game->date,
			'thread' => (int) $id[1],
			'commit' => $game->commit,
			'pr' => (int) $game->pr
			);
		}
	}

	return $results;
}

} // End of Class
