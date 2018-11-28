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
if (!@include_once(__DIR__."/../classes/class.History.php")) throw new Exception("Compat: class.History.php is missing. Failed to include class.History.php");


// Profiler
Profiler::setTitle("Profiler: History");

// Connect to database
Profiler::addData("Inc: Database Connection");
$db = getDatabase();


$a_existing = NULL;
$a_new = NULL;
$a_rss = NULL;
$error_existing = "";
$error_new = "";
$error_rss = "";


// Main part of the query
$cmd_main = "SELECT `old_status`, `old_date`, `new_status`, `new_date`,
`game_list`.`tid_EU`, `game_list`.`tid_US`, `game_list`.`tid_JP`,
`game_list`.`tid_AS`, `game_list`.`tid_KR`, `game_list`.`tid_HK`,
`game_title`, `alternative_title`, `game_history`.* FROM `game_history`
LEFT JOIN `game_list` ON
`game_history`.`gid_EU` = `game_list`.`gid_EU` OR
`game_history`.`gid_US` = `game_list`.`gid_US` OR
`game_history`.`gid_AS` = `game_list`.`gid_AS` OR
`game_history`.`gid_JP` = `game_list`.`gid_JP` OR
`game_history`.`gid_KR` = `game_list`.`gid_KR` OR
`game_history`.`gid_HK` = `game_list`.`gid_HK` ";


// Generate date part of the query
if ($get['h'] == $a_currenthist[0]) {
  $cmd_date = " AND `new_date` >= CAST('{$a_currenthist[2]}' AS DATE) ";
} else {
  $cmd_date = " AND `new_date` BETWEEN
  CAST('{$a_histdates[$get['h']][0]['y']}-{$a_histdates[$get['h']][0]['m']}-{$a_histdates[$get['h']][0]['d']}' AS DATE)
  AND CAST('{$a_histdates[$get['h']][1]['y']}-{$a_histdates[$get['h']][1]['m']}-{$a_histdates[$get['h']][1]['d']}' AS DATE) ";
}


// Existing entries
Profiler::addData("Inc: Check Existing Entries");
if (!isset($_GET['rss']) && ($get['m'] == "c" || $get['m'] == "")) {
  $q_existing = mysqli_query($db, "{$cmd_main} WHERE `old_status` IS NOT NULL {$cmd_date}
  ORDER BY `new_status` ASC, -`old_status` DESC, `new_date` DESC, `game_title` ASC; ");

  if (!$q_existing) {
    $error_existing = "Please try again. If this error persists, please contact the RPCS3 team.";
  } elseif (mysqli_num_rows($q_existing) === 0) {
    $error_existing = "No updates to previously existing entries were reported and/or reviewed yet.";
  }

  $a_existing = array();

  while ($row = mysqli_fetch_object($q_existing))
    $a_existing[] = HistoryEntry::rowToHistoryEntry($row);
}


// New entries
Profiler::addData("Inc: Check New Entries");
if (!isset($_GET['rss']) && ($get['m'] == "n" || $get['m'] == "")) {
  $q_new = mysqli_query($db, "{$cmd_main} WHERE `old_status` IS NULL {$cmd_date}
  ORDER BY `new_status` ASC, `new_date` DESC, `game_title` ASC; ");

  if (!$q_new) {
    $error_new = "Please try again. If this error persists, please contact the RPCS3 team.";
  } elseif (mysqli_num_rows($q_new) === 0) {
    $error_new = "No newer entries were reported and/or reviewed yet.";
  }

  $a_new = array();

  while ($row = mysqli_fetch_object($q_new))
    $a_new[] = HistoryEntry::rowToHistoryEntry($row);
}


// RSS - Existing/New entries
if (isset($_GET['rss'])) {
  $cmd_rss = "";
  if ($get['m'] == "c")     { $cmd_rss .= " AND `old_status` IS NOT NULL "; }
  elseif ($get['m'] == "n") { $cmd_rss .= " AND `old_status` IS NULL "; }

  $q_rss = mysqli_query($db, "{$cmd_main} WHERE `game_title` IS NOT NULL {$cmd_rss} {$cmd_date}
  ORDER BY new_date DESC, new_status ASC, -old_status DESC, game_title ASC; ");

  if (!$q_rss) {
    $error_rss = "Please try again. If this error persists, please contact the RPCS3 team.";
  } elseif (mysqli_num_rows($q_rss) === 0) {
    $error_rss = "No newer entries were reported and/or reviewed yet for this period of time.";
  }

  $a_rss = array();

  while ($row = mysqli_fetch_object($q_rss))
    $a_rss[] = HistoryEntry::rowToHistoryEntry($row);
}


// Disconnect from database
Profiler::addData("Inc: Close Database Connection");
mysqli_close($db);

Profiler::addData("--- / ---");
