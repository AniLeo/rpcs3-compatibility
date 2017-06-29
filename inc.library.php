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
if (!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");


if (in_array($get['r'], $a_pageresults)) {
	if ($get['r'] == $a_pageresults[$c_pageresults]) { $g_pageresults = ''; }
	else { $g_pageresults = "r={$get['rID']}&"; }
}


// Count number of entries for page calculation and cache results on array
$entries = 1;
$a_db = array();
$handle = fopen(__DIR__."/ps3tdb.txt", "r");
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
	global $get, $pages, $currentPage, $a_filter, $a_db;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	if (!$a_db) {
		echo "<p class=\"compat-tx1-criteria\">There are no games present in the selected categories.</p>";
		return;
	}
	
	// Get all games in the database (ID + Title)
	$a_games = array();
	$query = mysqli_query($db, "SELECT * FROM rpcs3; ");
	while($row = mysqli_fetch_object($query)) {
		$a_games[$row->game_id] = $row->game_title;
		$a_threads[$row->game_id] = $row->thread_id;
		$a_tested[$row->game_id] = $row->last_edit;
		$a_commit[$row->game_id] = $row->build_commit;
	}
	
	mysqli_close($db);
	
	
	$start = $get['r']*$currentPage-$get['r']+1;
	if ($pages == $currentPage) {
		$end = max(array_keys($a_db));
	} else {
		$end = $get['r']*$currentPage;
	}
	
	echo "<table class='compat-tested-table'>
			<tr>
			<th>ID</th>
			<th>Title</th>
			<th>Last Tested</th>
			</tr>";
	
	foreach (range($start, $end) as $i) { 
		$gameID = key($a_db[$i]);
		$gameTitle = $a_db[$i][$gameID];
		
		if (!array_key_exists($gameID, $a_games)) {
			echo "<tr>
			<td style='color:#e74c3c;'>".getGameRegion($gameID, true, 'l&'.combinedSearch(false, false, false, false, false, true, false, false))."&nbsp;&nbsp;<a style='color:#e74c3c;' href='http://www.gametdb.com/PS3/{$gameID}' target='_blank'>{$gameID}</a></td>
			<td style='color:#e74c3c'>".getGameMedia($gameID, true, '1px', 'l&'.combinedSearch(false, false, false, false, true, false, false, false))."&nbsp;&nbsp;<a style='color:#e74c3c;' href='http://www.gametdb.com/PS3/{$gameID}' target='_blank'>{$gameTitle}</a></td>
			<td style='color:#e74c3c;'>Untested</td>
			</tr>";
		} else {
			if (time() - strtotime($a_tested[$gameID]) > 60*60*24*30*6) {
				$color = '#f39c12';
			} else {
				$color = '#27ae60';
			}
			echo "<tr style=''>
			<td style='color:{$color};'>".getGameRegion($gameID, true, 'l&'.combinedSearch(false, false, false, false, false, true, false, false))."&nbsp;&nbsp;".getThread($gameID, $a_threads[$gameID])."</td>
			<td style='color:{$color}'>".getGameMedia($gameID, true, '1px', 'l&'.combinedSearch(false, false, false, false, true, false, false, false))."&nbsp;&nbsp;".getThread($a_games[$gameID], $a_threads[$gameID])."</td>
			<td style='color:{$color};'>{$a_tested[$gameID]}</a>&nbsp;&nbsp;&nbsp;(".getCommit($a_commit[$gameID]).")</td>
			</tr>";
		}
	}
	echo "</table>";
}


function tested_getPagesCounter() {
	global $pages, $currentPage;
	
	$extra = combinedSearch(true, false, false, false, true, true, false, false);
	
	return getPagesCounter($pages, $currentPage, "l&{$extra}");
}


function getGames($tested) {
	if ($tested) { $file = 'tested.txt'; } else { $file = 'untested.txt'; } 
	return fgets(fopen(__DIR__."/{$file}", 'r'));
}


?>