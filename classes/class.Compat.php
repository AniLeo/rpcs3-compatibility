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


class Compat {

/***********
 * Sort By *
 ***********/
function getSortBy() {
	global $a_title, $a_desc, $scount, $get;

	// Initialize string
	$s_sortby = "";

	foreach (range(min(array_keys($a_title)), max(array_keys($a_title))) as $i) {
		// Displays status description when hovered on
		$s_sortby .= "<a title=\"{$a_desc[$i]}\" href=\"?";
		$s_sortby .= combinedSearch(true, false, true, true, false, true, true, true);
		$s_sortby .= "s={$i}\">";

		$temp = "{$a_title[$i]}&nbsp;({$scount[1][$i]})";

		// If the current selected status, highlight with bold
		$s_sortby .= ($get['s'] == $i) ? highlightText($temp) : $temp;

		$s_sortby .= "</a>";
	}
	return $s_sortby;
}


/********************
 * Results per page *
 ********************/
function getResultsPerPage() {
	global $a_pageresults, $s_pageresults, $get;

	foreach (range(min(array_keys($a_pageresults))+1, max(array_keys($a_pageresults))) as $i) {
		$s_pageresults .= "<a href=\"?";
		$s_pageresults .= combinedSearch(false, true, true, true, false, true, true, true);
		$s_pageresults .= "r={$i}\">";

		// If the current selected status, highlight with bold
		$s_pageresults .= ($get['r'] == $a_pageresults[$i]) ? highlightText($a_pageresults[$i]) : $a_pageresults[$i];

		$s_pageresults .= "</a>";

		// If not the last value then add a separator for the next value
		if ($i < max(array_keys($a_pageresults))) { $s_pageresults .= "&nbsp;•&nbsp;"; }
	}
	return $s_pageresults;
}


/***********************************
 * Clickable URL: Character search *
 **********************************/
function getCharSearch() {
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
		$s_charsearch .= ($get['c'] == $key) ? highlightText($value) : $value;
		$s_charsearch .= "</div></a></td>";
	}

	return "<tr>{$s_charsearch}</tr>";
}


function getTableMessages() {
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
function getTableHeaders() {
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
function getTableContent() {
	global $a_results, $a_regions;

	// Initialize string
	$s_tablecontent = "";

	foreach ($a_results as $key => $value) {

		$media = '';
		$multiple = false;

		// prof_flag("Page: Display Table Content: Row - GameID");
		$s = "<div class=\"divTableCell\">";
		foreach ($a_regions as $k => $region) {
			if (array_key_exists("gid_{$region}", $value)) {
				if ($multiple) { $s .= '<br>'; }
				$s .= getThread(getGameRegion($value["gid_{$region}"], false), $value["tid_{$region}"]);
				$s .= getThread($value["gid_{$region}"], $value["tid_{$region}"]);
				if ($media == '') { $media = getGameMedia($value["gid_{$region}"]); }
				$multiple = true;
			}
		}
		$s .= "</div>";

		// prof_flag("Page: Display Table Content: Row - Game Title");
		$s .= "<div class=\"divTableCell\">";
		$s .= "{$media}{$value['game_title']}";
		if (array_key_exists('alternative_title', $value)) {
			$s .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;({$value['alternative_title']})";
		}
		$s .= "</div>";

		// prof_flag("Page: Display Table Content: Row - Status");
		$s .= "<div class=\"divTableCell\">".getColoredStatus($value['status'])."</div>";

		// prof_flag("Page: Display Table Content: Row - Last Updated");
		$s .= "<div class=\"divTableCell\"><a href=\"?d=".str_replace('-', '', $value['last_update'])."\">".$value['last_update']."</a>&nbsp;&nbsp;&nbsp;";
		$s .= $value['pr'] == 0 ? "(<i>Unknown</i>)" : "(<a href='https://github.com/RPCS3/rpcs3/pull/{$value['pr']}'>Pull #{$value['pr']}</a>)";
		$s .= '</div>';

		$s_tablecontent .= "<div class=\"divTableRow\">{$s}</div>";

	}

	return "<div class=\"divTableBody\">{$s_tablecontent}</div>";
}


/*****************
 * Pages Counter *
 *****************/
function getPagesCounter() {
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

function APIv1() {
	global $q_main, $c_maintenance, $a_results, $l_title;

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

	foreach ($a_results as $key => $value) {

		if (array_key_exists('gid_EU', $value)) {
			$results['results'][$value['gid_EU']] = array(
			'title' => $value['game_title'],
			'status' => $value['status'],
			'date' => $value['last_update'],
			'thread' => (int) $value['tid_EU'],
			'commit' => $value['commit'],
			'pr' => $value['pr']
			);
		}
		if (array_key_exists('gid_US', $value)) {
			$results['results'][$value['gid_US']] = array(
			'title' => $value['game_title'],
			'status' => $value['status'],
			'date' => $value['last_update'],
			'thread' => (int) $value['tid_US'],
			'commit' => $value['commit'],
			'pr' => $value['pr']
			);
		}
		if (array_key_exists('gid_JP', $value)) {
			$results['results'][$value['gid_JP']] = array(
			'title' => $value['game_title'],
			'status' => $value['status'],
			'date' => $value['last_update'],
			'thread' => (int) $value['tid_JP'],
			'commit' => $value['commit'],
			'pr' => $value['pr']
			);
		}
		if (array_key_exists('gid_AS', $value)) {
			$results['results'][$value['gid_AS']] = array(
			'title' => $value['game_title'],
			'status' => $value['status'],
			'date' => $value['last_update'],
			'thread' => (int) $value['tid_AS'],
			'commit' => $value['commit'],
			'pr' => $value['pr']
			);
		}
		if (array_key_exists('gid_KR', $value)) {
			$results['results'][$value['gid_KR']] = array(
			'title' => $value['game_title'],
			'status' => $value['status'],
			'date' => $value['last_update'],
			'thread' => (int) $value['tid_KR'],
			'commit' => $value['commit'],
			'pr' => $value['pr']
			);
		}
		if (array_key_exists('gid_HK', $value)) {
			$results['results'][$value['gid_HK']] = array(
			'title' => $value['game_title'],
			'status' => $value['status'],
			'date' => $value['last_update'],
			'thread' => (int) $value['tid_HK'],
			'commit' => $value['commit'],
			'pr' => $value['pr']
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
