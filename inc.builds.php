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


function builds_getTableHeaders() {
	global $get;
	
	$s_tableheaders .= "<tr>";
	
	/* Commonly used code: so we don't have to waste lines repeating this */
	$common .= "<th><a href =\"?b&";
	
	
	/* Pull Request */
	$s_tableheaders .= $common;
	// Order by: Pull Request (ASC, DESC)
	if ($get['o'] == "1a") { $s_tableheaders .= "o=1d\">Pull Request &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "1d") { $s_tableheaders .= "\">Pull Request &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=1a\">Pull Request</a></th>"; } 

	/* Author */
	$s_tableheaders .= $common;
	// Order by: Author (ASC, DESC)
	if ($get['o'] == "2a") { $s_tableheaders .= "o=2d\">Author &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "2d") { $s_tableheaders .= "\">Author &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=2a\">Author</a></th>"; }

	/* Build Date  */
	$s_tableheaders .= $common;
	// Order by: Build Date (ASC, DESC)
	if ($get['o'] == "4a") { $s_tableheaders .= "o=4d\">Build Date &nbsp; &#8593;</a></th>"; }
	elseif ($get['o'] == "4d") { $s_tableheaders .= "\">Build Date &nbsp; &#8595;</a></th>"; }
	else { $s_tableheaders .= "o=4a\">Build Date </a></th>"; }
	
	/* AppVeyor Download */
	$s_tableheaders .= "<th>Download</th>";
	
	$s_tableheaders .= "</tr>";
	
	return $s_tableheaders;
}


function builds_getTableContent() {
	global $get, $c_appveyor;

	// Order queries
	$a_order = array(
	'' => 'ORDER BY merge_datetime DESC',
	'1a' => 'ORDER BY pr ASC',
	'1d' => 'ORDER BY pr DESC',
	'2a' => 'ORDER BY author ASC',
	'2d' => 'ORDER BY author DESC',
	'3a' => 'ORDER BY merge_datetime ASC',
	'3d' => 'ORDER BY merge_datetime DESC',
	'4a' => 'ORDER BY merge_datetime DESC',
	'4d' => 'ORDER BY merge_datetime DESC'
	);
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$pagesQuery = mysqli_query($db, "SELECT count(*) AS c FROM builds_windows");
	$pages = ceil(mysqli_fetch_object($pagesQuery)->c / 25);
	
	if (isset($_GET['p'])) {
		$currentPage = intval($_GET['p']);
		if ($currentPage > $pages) { $currentPage = 1; }		
	} else { $currentPage = 1; }
	
	// TODO: Costum results per page
	$buildsCommand = "SELECT * FROM builds_windows {$a_order[$get['o']]} LIMIT ".(25*$currentPage-25).", 25; ";
	
	$buildsQuery = mysqli_query($db, $buildsCommand);
	
	if ($buildsQuery && mysqli_num_rows($buildsQuery) > 0) {
		while ($row = mysqli_fetch_object($buildsQuery)) { 
		
			$days = floor( (time() - strtotime($row->merge_datetime) ) / 86400 );			
			if ($days == 0) {
				$hours = floor( (time() - strtotime($row->merge_datetime) ) / 3600 );
				$diffdate = $hours . " hours ago";
			} elseif ($days == 1) { $diffdate = $days . " day ago"; } 
			else {  $diffdate = $days . " days ago"; }
			$fulldate = date_format(date_create($row->merge_datetime), "Y-m-d");
	
			$s_tablecontent .= "<tr>
			<td><a href=\"https://github.com/RPCS3/rpcs3/pull/{$row->pr}\"><img width='15px' height='15px' src=\"/img/icons/menu/github.png\">&nbsp;&nbsp;#{$row->pr}</a></td>
			<td><a href=\"https://github.com/{$row->author}\">{$row->author}</a></td>
			<td>{$diffdate} ({$fulldate})</td>";
			if ($row->appveyor != "0") { 
				$s_tablecontent .= "<td><a href=\"{$c_appveyor}{$row->appveyor}/artifacts\"><img width='15px' height='15px' src=\"/img/icons/menu/download.png\">&nbsp;&nbsp;{$row->appveyor}</a></td>";
			} else {
				$s_tablecontent .= "<td><i>None</i></td>";
			}
			$s_tablecontent .= "</tr>";
		}
	} else {
		// Query generator fail error
		$s_tablecontent .= "<p class=\"compat-tx1-criteria\">Please try again. If this error persists, please contact the RPCS3 team.</p>";
	}

	mysqli_close($db);
	
	return $s_tablecontent;
}


function builds_getPagesCounter() {
	global $get;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$pagesQuery = mysqli_query($db, "SELECT count(*) AS c FROM builds_windows");
	$pages = ceil(mysqli_fetch_object($pagesQuery)->c / 25);
	
	if (isset($_GET['p'])) {
		$currentPage = intval($_GET['p']);
		if ($currentPage > $pages) { $currentPage = 1; }		
	} else { $currentPage = 1; }

	mysqli_close($db);
	
	return getPagesCounter($pages, $currentPage, "");
}

?>