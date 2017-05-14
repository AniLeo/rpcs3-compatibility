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


function builds_getTableHeaders() {
	$headers = array(
		'Pull Request' => 1,
		'Author' => 2,
		'Build Date' => 4,
		'Download' => 0
	);	
	return getTableHeaders($headers, 'b&');
}


function builds_getTableContent() {
	global $get, $c_appveyor, $a_order;
	
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
			<td><a href=\"https://github.com/RPCS3/rpcs3/pull/{$row->pr}\"><img width='15px' height='15px' src=\"/img/icons/compat/github.png\">&nbsp;&nbsp;#{$row->pr}</a></td>
			<td><a href=\"https://github.com/{$row->author}\">{$row->author}</a></td>
			<td>{$diffdate} ({$fulldate})</td>";
			if ($row->appveyor != "0") { 
				$s_tablecontent .= "<td><a href=\"{$c_appveyor}{$row->appveyor}/artifacts\"><img width='15px' height='15px' src=\"/img/icons/compat/download.png\">&nbsp;&nbsp;{$row->appveyor}</a></td>";
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
	
	return getPagesCounter($pages, $currentPage, "b&");
}

?>
