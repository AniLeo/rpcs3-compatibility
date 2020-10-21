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
public static function generateQuery(array $get, &$db) : array
{
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
		$searchbox = " `game_title` LIKE '%{$s_g}%' OR `alternative_title` LIKE '%{$s_g}%' OR `key` = ANY (SELECT `key` FROM `game_id` WHERE `gid` LIKE '%{$s_g}%') ";

		// Initials cache search
		if (strlen($get['g']) >= 2) {
				$searchbox .= " OR `game_title` = ANY (SELECT `game_title` FROM `initials_cache` WHERE `initials` LIKE '%{$s_g}%')
				OR `alternative_title` = ANY (SELECT `game_title` FROM `initials_cache` WHERE `initials` LIKE '%{$s_g}%') ";
		}

		$genquery .= " ({$searchbox}) ";
		$and = true;
	}

	// QUERYGEN: Search by media type
	if ($get['t'] != '') {
		if ($and) { $genquery .= " AND "; }
		$genquery .= " ( `key` = ANY (SELECT `key` FROM `game_id` WHERE SUBSTR(`gid`,1,1) = '{$get['t']}') ) ";
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
	if ($get['s'] !== 0) {
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


/**********************
 * Print: Status Sort *
 **********************/
public static function printStatusSort() : void
{
	global $a_status, $scount, $get;

	// Get combined search parameters
	$s_combined = combinedSearch(true, false, true, true, false, true, true, true);

	if (!empty($s_combined))
		$s_combined .= "&";

	// All statuses
	echo "<a title=\"Show games from all statuses\" href=\"?{$s_combined}s=0\">";
	echo highlightText("All ({$scount["nostatus"][0]})", $get['s'] === 0);
	echo "</a>";

	// Individual statuses
	foreach ($a_status as $id => $status) {
		echo "<a title=\"{$status["desc"]}\" href=\"?{$s_combined}s={$id}\">";
		// If it's the currently selected status, highlight it
		echo highlightText("{$status["name"]} ({$scount["nostatus"][$id]})", $get['s'] === $id);
		echo "</a>";
	}
}


/***************************
 * Print: Results per page *
 ***************************/
public static function printResultsPerPage() : void
{
	echo resultsPerPage(combinedSearch(false, true, true, true, false, true, true, true));
}


/***************************
 * Print: Character search *
 ***************************/
public static function printCharSearch() : void
{
	global $get;

	// Get combined search parameters
	$s_combined = combinedSearch(true, true, false, false, false, true, true, false);

	// Build characters array
	$a_chars[''] = 'All';
	$a_chars['09'] = '0-9';
	foreach (range('a', 'z') as $i)
		$a_chars[$i] = strtoupper($i);
	$a_chars['sym'] = '#';

	echo '<table id="compat-con-search"><tr>';
	foreach ($a_chars as $key => $value) {
		echo "<td><a href=\"?{$s_combined}c={$key}\"><div class='compat-search-character'>";
		echo highlightText($value, $get['c'] == $key);
		echo "</div></a></td>";
	}
	echo '</tr></table>';
}


/*******************
 * Print: Messages *
 *******************/
public static function printMessages() : void
{
	global $info, $error;

	if (!is_null($info))
		echo "<p class=\"compat-tx1-criteria\">{$info}</p>";
	elseif (!is_null($error))
		echo "<p class=\"compat-tx1-criteria\">{$error}</p>";
}


/*****************
 * Print: Table  *
 *****************/
public static function printTable() : void
{
	global $games, $error, $a_status, $c_github;

	if (!is_null($error) || is_null($games))
		return;

	// Start table
	echo "<div class=\"compat-table-outside\">";
	echo "<div class=\"compat-table-inside\">";

	// Print table headers
	$extra = combinedSearch(true, true, true, true, false, true, true, false);
	$headers = array(
		array(
			'name' => 'Game IDs',
			'class' => 'compat-table-cell compat-table-cell-gameid',
			'sort' => 0
		),
		array(
			'name' => 'Game Title',
			'class' => 'compat-table-cell',
			'sort' => 2
		),
		array(
			'name' => 'Status',
			'class' => 'compat-table-cell compat-table-cell-status',
			'sort' => 3
		),
		array(
			'name' => 'Updated',
			'class' => 'compat-table-cell compat-table-cell-updated',
			'sort' => 4
		)
	);
	echo getTableHeaders($headers, $extra);

	// Print table body
	foreach ($games as $game) {

		$media = '';

		echo "<label for=\"compat-table-checkbox-{$game->key}\" class=\"compat-table-row\">";

		// Cell 1: Regions and GameIDs
		$cell = '';
		foreach ($game->game_item as $item) {
			if (!empty($cell))
				$cell .= "<br>";

			$cell .= getThread(getGameRegion($item->game_id, false), $item->thread_id);
			$cell .= getThread($item->game_id, $item->thread_id);

			if ($media == '')
				$media = getGameMediaIcon($item->game_id);
		}
		echo "<div class=\"compat-table-cell compat-table-cell-gameid\">{$cell}</div>";

		// Cell 2: Game Media, Titles and Network
		$title = !is_null($game->wikiID) ? "<a href=\"https://wiki.rpcs3.net/index.php?title={$game->wikiTitle}\">{$game->title}</a>" : $game->title;
		$cell = "{$media}{$title}";
		if ($game->network === 1)
			$cell .= "<img class=\"compat-network-icon\" title=\"Online only\" alt=\"Online only\" src=\"/img/icons/compat/onlineonly.png\"></img>";
		if (!is_null($game->title2))
			$cell .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;({$game->title2})";
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		// Cell 3: Status
		$cell = '';
		if (!is_null($game->status))
			$cell = "<div class=\"txt-compat-status\" style=\"background: #{$a_status[$game->status]['color']};\">{$a_status[$game->status]['name']}</div>";
		echo "<div class=\"compat-table-cell compat-table-cell-status\">{$cell}</div>";

		// Cell 4: Last Test
		$cell = "<a href=\"?d=".str_replace('-', '', $game->date)."\">".$game->date."</a>";
		$cell .= is_null($game->pr) ? "" : "&nbsp;&nbsp;&nbsp;(<a href='{$c_github}/pull/{$game->pr}'>#{$game->pr}</a>)";
		echo "<div class=\"compat-table-cell compat-table-cell-updated\">{$cell}</div>";

		echo "</label>";

		// Dropdown
		echo "<input type=\"checkbox\" id=\"compat-table-checkbox-{$game->key}\">";
		echo "<div class=\"compat-table-row compat-table-dropdown\">";

		$count_id = count($game->game_item);
		foreach ($game->game_item as $i => $item)
		{
			if (!is_null($item->update) && !empty($item->update))
			{
				echo "{$item->game_id}'s latest known version is <b>{$item->update}</b>";
			}
			else
			{
				echo "{$item->game_id} has no known updates";
			}
			if ($count_id !== $i + 1)
			{
				echo ", ";
			}
		}

		echo "</div>";
	}

	// End table
	echo "</div>";
	echo "</div>";
}


/************************
 * Print: Pages Counter *
 ************************/
public static function printPagesCounter() : void
{
	global $pages, $currentPage;

	$extra = combinedSearch(true, true, true, true, false, true, true, true);

	echo "<div class=\"compat-con-pages\">";
	echo getPagesCounter($pages, $currentPage, $extra);
	echo "</div>";
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

public static function APIv1() : array
{
	global $c_maintenance, $games, $info, $error, $l_title, $a_status, $get;

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

	if (!isset($get['g']) && isset($_GET['g'])) {
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
		foreach ($game->game_item as $item) {
			$results['results'][$item->game_id] = array(
			'title' => $game->title,
			'alternative-title' => $game->title2,
			'wiki-title' => $game->wikiTitle,
			'status' => $a_status[$game->status]['name'],
			'date' => $game->date,
			'thread' => (int) $item->thread_id,
			'commit' => is_null($game->commit) ? 0 : $game->commit,
			'pr' => is_null($game->pr) ? 0 : $game->pr,
			'network' => $game->network
			);
		}
	}

	return $results;
}

} // End of Class
