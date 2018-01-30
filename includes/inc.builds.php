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

// Order queries
$a_order = array(
'' => 'ORDER BY merge_datetime DESC',
'1a' => 'ORDER BY pr ASC',
'1d' => 'ORDER BY pr DESC',
'2a' => 'ORDER BY author ASC',
'2d' => 'ORDER BY author DESC',
'3a' => 'ORDER BY merge_datetime ASC',
'3d' => 'ORDER BY merge_datetime DESC',
'4a' => 'ORDER BY merge_datetime ASC',
'4d' => 'ORDER BY merge_datetime DESC'
);

// Obtain values from get
$get = obtainGet();

// Connect to database
$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
mysqli_set_charset($db, 'utf8');

// Calculate pages and current page
$pages = ceil(mysqli_fetch_object(mysqli_query($db, "SELECT count(*) AS c FROM builds_windows"))->c / 25);
$currentPage = getCurrentPage($pages);

// Disconnect from database
mysqli_close($db);


// TODO: Cleanup
// TODO: Costum results per page
// TODO: No listing builds with experimental warning 13/14-08/2017 and up + branch only


// Main query
$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
mysqli_set_charset($db, 'utf8');

$buildsCommand = "SELECT * FROM builds_windows {$a_order[$get['o']]} LIMIT ".(25*$currentPage-25).", 25; ";
$buildsQuery = mysqli_query($db, $buildsCommand);

mysqli_close($db);


function builds_getTableMessages() {
	global $buildsQuery;
	
	if (!$buildsQuery) {
		// Query generator fail error
		$s_messages .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
	} elseif (mysqli_num_rows($buildsQuery) === 0) {
		$s_messages .= "<p class=\"compat-tx1-criteria\">No builds are listed yet.</p>";
	} 
	
	return $s_messages;
	
}


function builds_getTableHeaders() {
	$headers = array(
		'Pull Request' => 1,
		'Author' => 2,
		'Added' => 0,
		'Deleted' => 0,
		'Build Date' => 4,
		'Download' => 0
	);	
	return getTableHeaders($headers, 'b&');
}


function builds_getTableContent() {
	global $get, $c_appveyor, $c_github, $a_order, $currentPage, $buildsQuery;
	
	if (mysqli_num_rows($buildsQuery) > 0) {
		while ($row = mysqli_fetch_object($buildsQuery)) { 
		
			$fulldate = date_format(date_create($row->merge_datetime), "Y-m-d");
			$diff = getDateDiff($row->merge_datetime);
	
			$s_tablecontent .= "<div class=\"divTableRow\">
			<div class=\"divTableCell\"><a href=\"{$c_github}/pull/{$row->pr}\"><img class='builds-icon' alt='GitHub' src=\"/img/icons/compat/github.png\">&nbsp;&nbsp;#{$row->pr}</a></div>
			<div class=\"divTableCell\"><a href=\"https://github.com/{$row->author}\">{$row->author}</a></div>";
			
			if (!is_null($row->additions)) {
				$s_tablecontent .= "<div class=\"divTableCell\"><span style='color:#4cd137'>+{$row->additions}</span></div>";
			} else {
				$s_tablecontent .= "<div class=\"divTableCell\"><i>?</i></div>";
			}
			if (!is_null($row->deletions)) {
				$s_tablecontent .= "<div class=\"divTableCell\"><span style='color:#e84118'>-{$row->deletions}</span></div>";
			} else {
				$s_tablecontent .= "<div class=\"divTableCell\"><i>?</i></div>";
			}
			
			$s_tablecontent .= "<div class=\"divTableCell\">{$diff} ({$fulldate})</div>";
			if ($row->appveyor != "0") { 
				$s_tablecontent .= "<div class=\"divTableCell\"><a href=\"{$c_appveyor}{$row->appveyor}/artifacts\"><img class='builds-icon' alt='Download' src=\"/img/icons/compat/download.png\">&nbsp;&nbsp;".str_replace("1.0.", "0.0.0-", $row->appveyor)."</a></div>";
			} else {
				$s_tablecontent .= "<div class=\"divTableCell\"><i>None</i></div>";
			}
			$s_tablecontent .= "</div>";
		}
	}
	
	return "<div class=\"divTableBody\">{$s_tablecontent}</div>";
}


function builds_getPagesCounter() {
	global $pages, $currentPage;
	
	return getPagesCounter($pages, $currentPage, "b&");
}


function getBuildsRSS() {
	global $a_order, $currentPage, $c_appveyor;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$buildsQuery = mysqli_query($db, "SELECT * FROM builds_windows {$a_order[$get['o']]} LIMIT ".(25*$currentPage-25).", 25; ");
	
	mysqli_close($db);
	
	if (!$buildsQuery) {
		return "An error occurred. Please try again. If the issue persists contact RPCS3 team.";
	}
	
	$url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	$url = str_replace('&', '&amp;', $url);
	
	if (mysqli_num_rows($buildsQuery) > 0) {
		while ($row = mysqli_fetch_object($buildsQuery)) { 
		
			$diff = getDateDiff($row->merge_datetime);
		
			$rssfeed .= "
						<item>
							<title><![CDATA[{$row->appveyor} (PR #{$row->pr})]]></title>
							<link>{$c_appveyor}{$row->appveyor}/artifacts</link>
							<guid>{$c_appveyor}{$row->appveyor}/artifacts</guid>
							<description>Pull Request #{$row->pr} by {$row->author} was merged {$diff}.</description>
							<pubDate>".date('r', strtotime($row->merge_datetime))."</pubDate>
						</item>
						";
		}
	}

	return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
	<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
		<channel>
			<title>RPCS3 Builds History's RSS feed</title>
			<link>https://rpcs3.net/compatibility?b</link>
			<description>For more information about RPCS3 visit https://rpcs3.net</description>
			<language>en-uk</language>
			<atom:link href=\"{$url}\" rel=\"self\" type=\"application/rss+xml\" />
				{$rssfeed}
			</channel>
	</rss>";
}
