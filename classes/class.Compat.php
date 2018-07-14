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



class Compat {


/***********
 * Sort By *
 ***********/
public static function getSortBy() {
	global $a_title, $a_desc, $scount, $get;

	// Initialize string
	$s_sortby = "";

	foreach ($a_title as $i => $title) {
		// Displays status description when hovered on
		$s_sortby .= "<a title=\"{$a_desc[$i]}\" href=\"?";
		$s_sortby .= combinedSearch(true, false, true, true, false, true, true, true);
		$s_sortby .= "s={$i}\">";

		$temp = "{$title} ({$scount[1][$i]})";

		// If the current selected status, highlight with bold
		$s_sortby .= ($get['s'] == $i) ? highlightText($temp) : $temp;

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
	global $q_main, $l_title, $l_orig, $get;

	// Initialize string
	$s_message = "";

	if ($q_main) {
		if (mysqli_num_rows($q_main) > 0) {
			if ($l_title != "") {
				$s_message .= "<p class=\"compat-tx1-criteria\">No results found for <i>{$l_orig}</i>. </br>
				Displaying results for <b><a style=\"color:#06c;\" href=\"?g=".urlencode($l_title)."\">{$l_title}</a></b>.</p>";
			}
		} elseif (strlen($get['g'] == 9 && is_numeric(substr($get['g'], 4, 5))))  {
			$s_message .= "<p class=\"compat-tx1-criteria\">The Game ID you just tried to search for isn't registered in our compatibility list yet.</p>";
		}
	} else {
		$s_message .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
	}

	return $s_message;

}


/*****************
 * Table Headers *
 *****************/
public static function getTableHeaders() {
	$extra = combinedSearch(true, true, true, true, false, true, true, false);

	$headers = array(
		'Game Regions + IDs' => 0,
		'Game Title' => 2,
		'Status' => 3,
		'Last Test' => 4
	);

	return getTableHeaders($headers, $extra);
}


/*****************
 * Table Content *
 *****************/
public static function getTableContent() {
	global $games, $a_regions;

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
				$media = getGameMedia($ID[0]);

			$multiple = true;
		}
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 2: Game Media and Titles
		$cell = "{$media}{$game->title}";
		if (!is_null($game->title2))
			$cell .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;({$game->title2})";
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 3: Status
		$cell = getColoredStatus($game->status);
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
	global $q_main, $c_maintenance, $games, $l_title;

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

	foreach ($games as $game) {
		foreach ($game->IDs as $id) {
			$results['results'][$id[0]] = array(
			'title' => $game->title,
			'alternative-title' => $game->title2,
			'status' => $game->status,
			'date' => $game->date,
			'thread' => (int) $id[1],
			'commit' => $game->commit,
			'pr' => (int) $game->pr
			);
		}
	}

	if ($q_main) {
		if (mysqli_num_rows($q_main) > 0) {
			if ($l_title != "") {
				$results['return_code'] = 2; // No results found for {$l_orig}. Displaying results for {$l_title}.
				$results['search_term'] = $l_title;
			}
		} else {
			$results['return_code'] = 1;
		}
	} else {
		$results['return_code'] = -1;
	}

	return $results;
}

} // End of Class
