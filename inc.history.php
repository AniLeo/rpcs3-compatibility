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


function getHistoryDescription() {
	global $get, $a_histdates, $a_currenthist;
	
	$s_desc .= "You're now watching the updates that altered a game's status for RPCS3's Compatibility List ";
	
	if ($get['h1'] == db_table) {
		$s_desc .= "since <b>{$a_currenthist[1]}</b>.";
	} else {
		$s_desc .= "from <b>{$a_histdates[$get['h1']][0]}</b> to <b>{$a_histdates[$get['h1']][1]}</b>.";
	}

	return "<p id='compat-history-description'>{$s_desc}</p>";
}


function getHistoryMonths() {
	global $get;
	
	$m1 = "<a href=\"?h=2017_03\">March 2017</a>";
	$m2 = "<a href=\"?h=2017_04\">April 2017</a>";
	$m3 = "<a href=\"?h=2017_05\">May 2017</a>";
	$m4 = "<a href=\"?h=2017_06\">June 2017</a>";
	$m = "<a href=\"?h\">July 2017</a>";
	$spacer = "&nbsp;&#8226;&nbsp;";
	
	$s_months .= "<strong>Month:&nbsp;</strong>";
	
	if ($get['h2'] == "2017_02") { $s_months .= highlightBold($m1); } 
	else                         { $s_months .= $m1;}
	$s_months .= $spacer;
	
	if ($get['h2'] == "2017_03") { $s_months .= highlightBold($m2); } 
	else                         { $s_months .= $m2; }
	$s_months .= $spacer;
	
	if ($get['h2'] == "2017_04") { $s_months .= highlightBold($m3); } 
	else                         { $s_months .= $m3; }
	$s_months .= $spacer;
	
	if ($get['h2'] == "2017_05") { $s_months .= highlightBold($m4); } 
	else                         { $s_months .= $m4; }
	$s_months .= $spacer;
	
	if ($get['h1'] == db_table)  { $s_months .= highlightBold($m); } 
	else                         { $s_months .= $m; }	
	
	return "<p id='compat-history-months'>{$s_months}</p>";
}


function getHistoryOptions() {
	global $get;
	
	if ($get['h1'] != "" && $get['h1'] != db_table) { $h = "={$get['h1']}"; } 
	else                                            { $h = ""; }
	
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


// Compatibility History: Pulls information from backup and compares with current database
function getHistoryContent() {
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
			$s_content .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		} elseif (mysqli_num_rows($cQuery) == 0) {
			$s_content .= "<p class=\"compat-tx1-criteria\">No updates to previously existent entries were reported yet.</p>";
		} else {
			
			$s_content .= "<table class='compat-hist-container'>";
			$s_content .= getHistoryHeaders();

			while($row = mysqli_fetch_object($cQuery)) {
				$s_content .= "<tr>
				<td>".getGameRegion($row->gid, false)."&nbsp;&nbsp;".getThread($row->gid, $row->tid)."</td>
				<td>".getGameMedia($row->gid)."&nbsp;&nbsp;".getThread($row->title, $row->tid)."</td>
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
		$nQuery = mysqli_query($db, 
		"SELECT t1.game_id AS gid, t1.game_title AS title, t1.thread_id AS tid, t1.status AS new_status, t2.status AS old_status, t1.last_edit AS new_date, t2.last_edit AS old_date
		FROM {$get['h1']} AS t1
		LEFT JOIN {$get['h2']} AS t2
		ON t1.game_id=t2.game_id
		WHERE t2.status IS NULL
		ORDER BY new_status ASC, -old_status DESC, title ASC; ");
		
		if (!$nQuery) { 
			$s_content .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
		} elseif (mysqli_num_rows($nQuery) == 0) {
			$s_content .= "<p class=\"compat-tx1-criteria\">No newer entries were reported yet.</p>";
		} else {
			
			$s_content .= "<p class=\"compat-tx1-criteria\"><strong>Newly reported games</strong></p>";
			$s_content .= "<table class='compat-hist-container'>";
			$s_content .= getHistoryHeaders(false);
			
			while($row = mysqli_fetch_object($nQuery)) {
				$s_content .= "<tr>
				<td>".getGameRegion($row->gid, false)."&nbsp;&nbsp;".getThread($row->gid, $row->tid)."</td>
				<td>".getGameMedia($row->gid)."&nbsp;&nbsp;".getThread($row->title, $row->tid)."</td>
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