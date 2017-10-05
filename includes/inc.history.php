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

// Calls for the file that contains the functions needed
if (!@include_once(__DIR__."/../functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");

// Start: Microtime when page started loading
$start = getTime();

// Obtain values from get
$get = obtainGet();


function getHistoryDescription() {
	global $get, $a_histdates, $a_currenthist;
	
	$s_desc .= "You're now watching the updates that altered a game's status for RPCS3's Compatibility List ";

	if ($get['h'] == $a_currenthist[0]) {
		$s_desc .= "since <b>{$a_currenthist[1]}</b>.";
	} else {
		$v = $a_histdates[$get['h']];
		$m1 = monthNumberToName($v[0]['m']);
		$m2 = monthNumberToName($v[1]['m']);
		$s_desc .= "from <b>{$m1} {$v[0]['d']}, {$v[0]['y']}</b> to <b>{$m2} {$v[1]['d']}, {$v[1]['y']}</b>.";
	}

	return "<p id='compat-history-description'>{$s_desc}</p>";
}


function getHistoryMonths() {
	global $get, $a_histdates, $a_currenthist;
	
	$s_months .= "<strong>Month:&nbsp;</strong>";
	$spacer = "&nbsp;&#8226;&nbsp;";
	
	foreach($a_histdates as $k => $v) {
		
		$month = monthNumberToName(substr($k, -2));
		$year  = substr($k, 0, 4);
		
		$m = "<a href=\"?h={$k}\">{$month} {$year}</a>";
		
		if ($get['h'] == $k) { $s_months .= highlightBold($m); } 
		else                 { $s_months .= $m; }
		$s_months .= $spacer;
	
	}
	
	$month = monthNumberToName(substr($a_currenthist[0], -2));
	$year = substr($a_currenthist[0], 0, 4);
	
	$m = "<a href=\"?h\">{$month} {$year}</a>";
	
	if ($get['h'] == $a_currenthist[0]) { $s_months .= highlightBold($m); } 
	else                                { $s_months .= $m; }	
	
	return "<p id='compat-history-months'>{$s_months}</p>";
}


function getHistoryOptions() {
	global $get, $a_currenthist;
	
	if ($get['h'] != $a_currenthist[0]) { $h = "={$get['h']}"; } 
	else                                { $h = ""; }
	
	$o1 = "<a href=\"?h{$h}\">Show all entries</a>";
	$o2 = "<a href=\"?h{$h}&m=c\">Show only previously existent entries</a>";
	$o3 = "<a href=\"?h{$h}&m=n\">Show only new entries</a>";
	$spacer = "&nbsp;&#8226;&nbsp;";
	
	if ($get['m'] == "")  { $s_options .= highlightBold($o1); } 
	else                  { $s_options .= $o1; }
	$s_options .= " <a href=\"?h{$h}&rss\">(RSS)</a>{$spacer}";
	
	if ($get['m'] == "c") { $s_options .= highlightBold($o2); } 
	else                  { $s_options .= $o2; }
	$s_options .= " <a href=\"?h{$h}&m=c&rss\">(RSS)</a>{$spacer}";
	
	if ($get['m'] == "n") { $s_options .= highlightBold($o3); } 
	else                  { $s_options .= $o3; }
	$s_options .= " <a href=\"?h{$h}&m=n&rss\">(RSS)</a>";
	
	return "<p id='compat-history-options'>{$s_options}</p>";
}


function getHistoryHeaders($full = true) {
	if ($full) {
		$headers = array(
			'Game ID' => 0,
			'Game Title' => 0,
			'New Status' => 0,
			'New Date' => 0,
			'Old Status' => 0,
			'Old Date' => 0
		);
	} else {
		$headers = array(
			'Game ID' => 0,
			'Game Title' => 0,
			'Status' => 0,
			'Date' => 0
		);
	}
	
	return getTableHeaders($headers, 'h');
}


function getHistoryContent() {
	global $get, $a_histdates, $a_currenthist;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	if ($get['h'] == $a_currenthist[0]) {
		$dateQuery = " AND new_date >= CAST('{$a_currenthist[2]}' AS DATE) ";
	} else {
		$dateQuery = " AND new_date BETWEEN 
		CAST('{$a_histdates[$get['h']][0][y]}-{$a_histdates[$get['h']][0][m]}-{$a_histdates[$get['h']][0][d]}' AS DATE) 
		AND CAST('{$a_histdates[$get['h']][1][y]}-{$a_histdates[$get['h']][1][m]}-{$a_histdates[$get['h']][1][d]}' AS DATE) ";
	}
	
	if ($get['m'] == "c" || $get['m'] == "") {
		$cQuery = mysqli_query($db, "SELECT * FROM game_history 
		LEFT JOIN game_list ON game_history.game_id = game_list.game_id 
		WHERE old_status IS NOT NULL 
		{$dateQuery} 
		ORDER BY new_status ASC, -old_status DESC, game_title ASC; ");
	
		if (!$cQuery) { 
			$s_content .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		} elseif (mysqli_num_rows($cQuery) == 0) {
			$s_content .= "<p class=\"compat-tx1-criteria\">No updates to previously existing entries were reported and/or reviewed yet.</p>";
		} else {
			
			$s_content .= "<table class='history-table'>";
			$s_content .= getHistoryHeaders();

			while($row = mysqli_fetch_object($cQuery)) {
				$s_content .= "<tr>
				<td>".getGameRegion($row->game_id, false)."&nbsp;&nbsp;".getThread($row->game_id, $row->thread_id)."</td>
				<td>".getGameMedia($row->game_id)."&nbsp;&nbsp;".getThread($row->game_title, $row->thread_id)."</td>
				<td>".getColoredStatus($row->new_status)."</td>
				<td>{$row->new_date}</td>
				<td>".getColoredStatus($row->old_status)."</td>
				<td>{$row->old_date}</td>
				</tr>";	
			}
			$s_content .= "</table><br>";
		}
	}
	
	if ($get['m'] == "n" || $get['m'] == "") {
		$nQuery = mysqli_query($db, "SELECT * FROM game_history 
		LEFT JOIN game_list ON game_history.game_id = game_list.game_id 
		WHERE old_status IS NULL 
		{$dateQuery} 
		ORDER BY new_status ASC, -old_status DESC, game_title ASC; ");
		
		if (!$nQuery) { 
			$s_content .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		} elseif (mysqli_num_rows($nQuery) == 0) {
			$s_content .= "<p class=\"compat-tx1-criteria\">No newer entries were reported and/or reviewed yet.</p>";
		} else {
			
			$s_content .= "<p class=\"compat-tx1-criteria\"><strong>Newly reported games</strong></p>";
			$s_content .= "<table class='history-table'>";
			$s_content .= getHistoryHeaders(false);
			
			while($row = mysqli_fetch_object($nQuery)) {
				$s_content .= "<tr>
				<td>".getGameRegion($row->game_id, false)."&nbsp;&nbsp;".getThread($row->game_id, $row->thread_id)."</td>
				<td>".getGameMedia($row->game_id)."&nbsp;&nbsp;".getThread($row->game_title, $row->thread_id)."</td>
				<td>".getColoredStatus($row->new_status)."</td>
				<td>{$row->new_date}</td>
				</tr>";	
			}
			$s_content .= "</table><br>";
		}
	}
	
	// Close MySQL connection again since it won't be required
	mysqli_close($db);
	
	return $s_content;
}


function getHistoryRSS(){
	global $c_forum, $get, $a_currenthist;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	if ($get['h'] == $a_currenthist[0]) {
		$dateQuery = " AND new_date >= CAST('{$a_currenthist[2]}' AS DATE) ";
	} else {
		$dateQuery = " AND new_date BETWEEN 
		CAST('{$a_histdates[$get['h']][0][y]}-{$a_histdates[$get['h']][0][m]}-{$a_histdates[$get['h']][0][d]}' AS DATE) 
		AND CAST('{$a_histdates[$get['h']][1][y]}-{$a_histdates[$get['h']][1][m]}-{$a_histdates[$get['h']][1][d]}' AS DATE) ";
	}
	
	$rssCmd = "SELECT * FROM game_history LEFT JOIN game_list ON game_history.game_id = game_list.game_id ";
	if ($get['m'] == "c") {
		$rssCmd .= " WHERE old_status IS NOT NULL ";
	} elseif ($get['m'] == "n") {
		$rssCmd .= " WHERE old_status IS NULL ";
	} else {
		$rssCmd .= " WHERE game_list.game_id IS NOT NULL ";
	}
	$rssCmd .= " {$dateQuery} ORDER BY new_date DESC, new_status ASC, -old_status DESC, game_title ASC; ";
	
	$rssQuery = mysqli_query($db, $rssCmd);
	
	if (!$rssQuery) {
		return "An error occurred. Please try again. If the issue persists contact RPCS3 team.";
	}
	
	$url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	$url = str_replace('&', '&amp;', $url);
	
    $rssfeed = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
				<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
				<channel>
				<title>RPCS3 Compatibility List History's RSS feed</title>
				<link>https://rpcs3.net/compatibility?h</link>
				<description>For more information about RPCS3 visit https://rpcs3.net</description>
				<language>en-uk</language>
				<atom:link href=\"{$url}\" rel=\"self\" type=\"application/rss+xml\" />";
 
    while($row = mysqli_fetch_object($rssQuery)) {
 
        $rssfeed .= "<item>
					<title><![CDATA[{$row->game_id} - {$row->game_title}]]></title>
					<link>{$c_forum}{$row->thread_id}</link>
					<guid>{$c_forum}{$row->thread_id}</guid>";
		
		if ($row->old_status !== NULL) {
			$rssfeed .= "<description>Updated from {$row->old_status} ({$row->old_date}) to {$row->new_status} ({$row->new_date})</description>";
		} else {
			$rssfeed .= "<description>New entry for {$row->new_status} ({$row->new_date})</description>";
		}
        
		$rssfeed .= "<pubDate>".date('r', strtotime($row->new_date))."</pubDate>
					</item>";
    }
 
    $rssfeed .= "</channel>
				</rss>";
				
	// Close MySQL connection again since it won't be required
	mysqli_close($db);
	
    return $rssfeed;
}
