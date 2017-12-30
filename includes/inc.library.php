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


if (in_array($get['r'], $a_pageresults)) {
	if ($get['r'] == $a_pageresults[$c_pageresults]) { $g_pageresults = ''; }
	else { $g_pageresults = "r={$get['rID']}&"; }
}


// Count number of entries for page calculation and cache results on array
$entries = 1;
$a_db = array();
$handle = fopen(__DIR__."/../ps3tdb.txt", "r");
while (!feof($handle)) {
	$line = fgets($handle);
	if (in_array(mb_substr($line, 0, 4), $a_filter)) {	
		
		$valid = true;
		
		if ($get['f'] != '') {
			if (strtolower(substr($line, 2, 1)) != $get['f']) { $valid = false; } 
		}
		if ($get['t'] != '') {
			if (strtolower(substr($line, 0, 1)) != $get['t']) { $valid = false; }
		} 
		
		if ($valid) {
			$a_db[$entries] = array(mb_substr($line, 0, 9) => mb_substr($line, 12));
			$entries++;
		}
	
	}

}
fclose($handle);
$pages = ceil($entries / $get['r']);
$currentPage = getCurrentPage($pages);


function getResultsPerPage() {
	global $a_pageresults, $s_pageresults, $get;
	
	foreach (range(min(array_keys($a_pageresults)), max(array_keys($a_pageresults))) as $i) { 
		$s_pageresults .= "<a href=\"?l&".combinedSearch(false, false, false, false, true, true, false, false)."r=$i\">"; 
		
		// If the current selected status, highlight with bold
		if ($get['r'] == $a_pageresults[$i]) { $s_pageresults .= highlightBold($a_pageresults[$i]); } 
		else { $s_pageresults .= $a_pageresults[$i]; }

		$s_pageresults .= "</a>";
		
		// If not the last value then add a separator for the next value
		if ($i < max(array_keys($a_pageresults))) {$s_pageresults .= "&nbsp;•&nbsp;";} 
	}
	return $s_pageresults;
}


function getTestedContents() {
	global $get, $pages, $currentPage, $a_db;
	
	if (!$a_db) {
		echo "<p class=\"compat-tx1-criteria\">There are no games present in the selected categories.</p>";
		return;
	}
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	// Get all games in the database (ID + Title)
	$a_games = array();
	$query = mysqli_query($db, "SELECT *
	FROM game_list 
	LEFT JOIN builds_windows
	ON SUBSTR(commit,1,7) = SUBSTR(build_commit,1,7) ");
	while($row = mysqli_fetch_object($query)) {
		// TODO: Cleanup
		if (!empty($row->gid_EU)) {
			$a_games[$row->gid_EU] = array(
			'title' => $row->game_title,
			'thread' => $row->tid_EU,
			'last_update' => $row->last_update,
			'pr' => $row->pr);
		}
		if (!empty($row->gid_US)) {
			$a_games[$row->gid_US] = array(
			'title' => $row->game_title,
			'thread' => $row->tid_US,
			'last_update' => $row->last_update,
			'pr' => $row->pr);
		}
		if (!empty($row->gid_JP)) {
			$a_games[$row->gid_JP] = array(
			'title' => $row->game_title,
			'thread' => $row->tid_JP,
			'last_update' => $row->last_update,
			'pr' => $row->pr);
		}
		if (!empty($row->gid_AS)) {
			$a_games[$row->gid_AS] = array(
			'title' => $row->game_title,
			'thread' => $row->tid_AS,
			'last_update' => $row->last_update,
			'pr' => $row->pr);
		}
		if (!empty($row->gid_KR)) {
			$a_games[$row->gid_KR] = array(
			'title' => $row->game_title,
			'thread' => $row->tid_KR,
			'last_update' => $row->last_update,
			'pr' => $row->pr);
		}
		if (!empty($row->gid_HK)) {
			$a_games[$row->gid_HK] = array(
			'title' => $row->game_title,
			'thread' => $row->tid_HK,
			'last_update' => $row->last_update,
			'pr' => $row->pr);
		}
	}
	
	mysqli_close($db);
	
	$start = $get['r']*$currentPage-$get['r']+1;
	$end = ($pages == $currentPage) ? max(array_keys($a_db)) : $get['r']*$currentPage;
	
	echo "<div class=\"divTable library-table\">
	<div class=\"divTableHeading\">
		<div class=\"divTableHead\">ID</div>
		<div class=\"divTableHead\">Title</div>
		<div class=\"divTableHead\">Last Tested</div>
	</div>
	<div class=\"divTableBody\">";
	
	foreach (range($start, $end) as $i) { 
		$gameID = key($a_db[$i]);
		$gameTitle = $a_db[$i][$gameID];
		
		if (!array_key_exists($gameID, $a_games)) {
			echo "
			<div class=\"divTableRow\">
				<div class=\"divTableCell\" style='color:#e74c3c;'>"
				.getGameRegion($gameID, true, 'l&'.combinedSearch(false, false, false, false, false, true, false, false))."&nbsp;&nbsp;
				<a style='color:#e74c3c;' href='http://www.gametdb.com/PS3/{$gameID}' target='_blank'>{$gameID}</a>
				</div>
				<div class=\"divTableCell\"  style='color:#e74c3c'>"
				.getGameMedia($gameID, true, 'compat-icon-media icon-library', 'l&'.combinedSearch(false, false, false, false, true, false, false, false))."&nbsp;&nbsp;
				<a style='color:#e74c3c;' href='http://www.gametdb.com/PS3/{$gameID}' target='_blank'>{$gameTitle}</a>
				</div>
				<div class=\"divTableCell\"  style='color:#e74c3c;'>Untested</div>
			</div>";
		} else {
			
			// If the game hasn't been tested for more than 6 months color = yellow, otherwise color = green
			$color = (time() - strtotime($a_games[$gameID]['last_update']) > 60*60*24*30*6) ? '#f39c12' : '#27ae60';

			echo "
			<div class=\"divTableRow\">
				<div class=\"divTableCell\" style='color:{$color};'>"
				.getGameRegion($gameID, true, 'l&'.combinedSearch(false, false, false, false, false, true, false, false))."&nbsp;&nbsp;
				".getThread($gameID, $a_games[$gameID]['thread'])."
				</div>
				<div class=\"divTableCell\" style='color:{$color}'>"
				.getGameMedia($gameID, true, 'compat-icon-media icon-library', 'l&'.combinedSearch(false, false, false, false, true, false, false, false))."&nbsp;&nbsp;
				".getThread($a_games[$gameID]['title'], $a_games[$gameID]['thread'])."
				</div>
				<div class=\"divTableCell\"style='color:{$color};'>
				{$a_games[$gameID]['last_update']}&nbsp;&nbsp;&nbsp;";
				echo $a_games[$gameID]['pr'] == 0 ? "(<i>Unknown</i>)" : "(<a href='https://github.com/RPCS3/rpcs3/pull/{$a_games[$gameID]['pr']}'>Pull #{$a_games[$gameID]['pr']}</a>)";
				
				echo "</div>
			</div>";
		}
	}
	echo "</div>
	</div>";
}


function tested_getPagesCounter() {
	global $pages, $currentPage;
	
	$extra = combinedSearch(true, false, false, false, true, true, false, false);
	
	return getPagesCounter($pages, $currentPage, "l&{$extra}");
}


function getGames($tested) {
	$file = $tested ? 'tested.txt' : 'untested.txt';
	return fgets(fopen(__DIR__."/../{$file}", 'r'));
}
