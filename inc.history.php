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
if (!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");

// Start: Microtime when page started loading
$start = getTime();

// Obtain values from get
$get = obtainGet();


function getHistoryOptions() {
	global $get, $a_histdates;
	
	if ($get['h1'] == db_table) {
		$s_historyoptions .= "<p>You're now watching the updates that altered a game's status for RPCS3's Compatibility List since <b>{$a_histdates[$get['h2']]}</b>.</p>";
	} else {
		$s_historyoptions .= "<p>You're now watching the updates that altered a game's status for RPCS3's Compatibility List from <b>{$a_histdates[$get['h2']]}</b> to <b>{$a_histdates[$get['h1']]}</b>.</p>";
	}

	$m1 = "<a href=\"?h=2017_03\">March 2017</a>";
	$m2 = "<a href=\"?h\">April 2017</a>";
	
	$s_historyoptions .= "<br><p style=\"font-size:13px\">
	<strong>Month:&nbsp;</strong>";
	
	if ($get['h2'] == "2017_02") { 
		$s_historyoptions .= highlightBold($m1);
	} else {
		$s_historyoptions .= $m1;
	}
	
	$s_historyoptions .= "&nbsp;&#8226;&nbsp";
	
	if ($get['h1'] == db_table) { 
		$s_historyoptions .= highlightBold($m2);
	} else {
		$s_historyoptions .= $m2;
	}
	
	$s_historyoptions .= "<br>";
	
	if ($get['h1'] != "" && $get['h1'] != db_table) {
		$h = "={$get['h1']}";
	} else {
		$h = "";
	}
	
	$o1 = "<a href=\"?h{$h}\">Show all entries</a>";
	$o2 = "<a href=\"?h{$h}&m=c\">Show only previously existent entries</a>";
	$o3 = "<a href=\"?h{$h}&m=n\">Show only new entries</a>";
	
	if ($get['m'] == "") { 
		$s_historyoptions .= highlightBold($o1);
	} else {
		$s_historyoptions .= $o1;
	}
	$s_historyoptions .= " <a href=\"?h{$h}&rss\">(RSS)</a>&nbsp;&#8226;&nbsp;";
	
	if ($get['m'] == "c") { 
		$s_historyoptions .= highlightBold($o2);
	} else {
		$s_historyoptions .= $o2;
	}
	$s_historyoptions .= " <a href=\"?h{$h}&m=c&rss\">(RSS)</a>&nbsp;&#8226;&nbsp;";
	
	if ($get['m'] == "n") { 
		$s_historyoptions .= highlightBold($o3);
	} else {
		$s_historyoptions .= $o3;
	}
	$s_historyoptions .= " <a href=\"?h{$h}&m=n&rss\">(RSS)</a>&nbsp;&#8226;&nbsp;";
	
	$s_historyoptions .= "<a href=\"?\">Back to Compatibility List</a>";
	
	$s_historyoptions .= "</p>";
	
	return $s_historyoptions;
}


// Compatibility History: Pulls information from backup and compares with current database
function getHistory(){
	global $get; 
	
	// Establish MySQL connection to be used for history
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	if ($get['m'] == "c" || $get['m'] == "") {
		$cQuery = mysqli_query($db, 
		"SELECT t1.game_id AS gid, t1.game_title AS title, t1.thread_id AS tid, t1.status AS new_status, t2.status AS old_status, t1.last_edit AS new_date, t2.last_edit AS old_date
		FROM {$get['h1']} AS t1
		LEFT JOIN {$get['h2']} AS t2
		ON t1.game_id=t2.game_id
		WHERE t1.status != t2.status 
		ORDER BY new_status ASC, -old_status DESC, title ASC; ");
	
		if (!$cQuery) { 
			echo "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		} elseif (mysqli_num_rows($cQuery) == 0) {
			echo "<p class=\"compat-tx1-criteria\">No results found for the selected criteria.</p>";
		} else {
			echo "
			<table class='compat-con-container'><tr>
			<th>Game ID</th>
			<th>Game Title</th>
			<th>New Status</th>
			<th>New Date</th>
			<th>Old Status</th>
			<th>Old Date</th>
			</tr>";
			
			while($row = mysqli_fetch_object($cQuery)) {
				echo "<tr>
				<td>".getGameRegion($row->gid, false)."&nbsp;&nbsp;".getThread($row->gid, $row->tid)."</td>
				<td>".getGameMedia($row->gid)."&nbsp;&nbsp;".getThread($row->title, $row->tid)."</td>
				<td>".getColoredStatus($row->new_status)."</td>
				<td>{$row->new_date}</td>
				<td>".getColoredStatus($row->old_status)."</td>
				<td>{$row->old_date}</td>
				</tr>";	
			}
			echo "</table><br>";
		}
	}
	
	if ($get['m'] == "n" || $get['m'] == "") {
		$nQuery = mysqli_query($db, 
		"SELECT t1.game_id AS gid, t1.game_title AS title, t1.thread_id AS tid, t1.status AS new_status, t2.status AS old_status, t1.last_edit AS new_date, t2.last_edit AS old_date
		FROM {$get['h1']} AS t1
		LEFT JOIN {$get['h2']} AS t2
		ON t1.game_id=t2.game_id
		WHERE t2.status IS NULL
		ORDER BY new_status ASC, -old_status DESC, title ASC; ");
		
		if (!$nQuery) { 
			echo "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		} elseif (mysqli_num_rows($nQuery) == 0) {
			echo "<p class=\"compat-tx1-criteria\">No results found for the selected criteria.</p>";
		} else {
			echo "
			<p class=\"compat-tx1-criteria\"><strong>Newly reported games</strong></p>
			<table class='compat-con-container'><tr>
			<th>Game ID</th>
			<th>Game Title</th>
			<th>Status</th>
			<th>Date</th>
			</tr>";
			
			while($row = mysqli_fetch_object($nQuery)) {
				echo "<tr>
				<td>".getGameRegion($row->gid, false)."&nbsp;&nbsp;".getThread($row->gid, $row->tid)."</td>
				<td>".getGameMedia($row->gid)."&nbsp;&nbsp;".getThread($row->title, $row->tid)."</td>
				<td>".getColoredStatus($row->new_status)."</td>
				<td>{$row->new_date}</td>
				</tr>";	
			}
			echo "</table><br>";
		}
	}
	
	// Close MySQL connection again since it won't be required
	mysqli_close($db);
}


function getHistoryRSS(){
	global $c_forum, $get;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$rssCmd = "
	SELECT t1.game_id AS gid, t1.game_title AS title, t1.thread_id AS tid, t1.status AS new_status, t2.status AS old_status, t1.last_edit AS new_date, t2.last_edit AS old_date
	FROM {$get['h1']} AS t1
	LEFT JOIN {$get['h2']} AS t2
	ON t1.game_id=t2.game_id ";
	if ($get['m'] == "c") {
		$rssCmd .= " WHERE t1.status != t2.status ";
	} elseif ($get['m'] == "n") {
		$rssCmd .= " WHERE t2.status IS NULL ";
	} else {
		$rssCmd .= " WHERE t1.status != t2.status OR t2.status IS NULL ";
	}
	$rssCmd .= "ORDER BY new_date DESC, new_status ASC, -old_status DESC, title ASC; ";
	
	$rssQuery = mysqli_query($db, $rssCmd);
	
	if (!$rssQuery) {
		return "An error occoured. Please try again. If the issue persists contact RPCS3 team.";
	}
	
	$url = "https://{$_SERVER[HTTP_HOST]}{$_SERVER[REQUEST_URI]}";
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
					<title><![CDATA[{$row->gid} - {$row->title}]]></title>
					<link>{$c_forum}{$row->tid}</link>
					<guid>{$c_forum}{$row->tid}</guid>";
		
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

?>