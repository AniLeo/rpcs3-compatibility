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
// Calls for the file that contains the cachers
if (!@include_once(__DIR__."/../cachers.php")) throw new Exception("Compat: cachers.php is missing. Failed to include cachers.php");
// Calls for the file that contains the cachers
if (!@include_once(__DIR__."/../utils.php")) throw new Exception("Compat: utils.php is missing. Failed to include utils.php");


// Start: Microtime when page started loading
$start = getTime();

$get = obtainGet();

/*
TODO: Login system
TODO: Self-made sessions system
TODO: User permissions system
TODO: Log commands with run time and datetime
*/


if ($get['a'] == 'updateBuildCache') {
	$startA = getTime();
	cacheWindowsBuilds();
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Forced update on windows builds cache (".round(($finishA - $startA), 4)."s).</p>";
}

if ($get['a'] == 'updateInitialsCache') { 
	$startA = getTime();
	cacheInitials();
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Forced update on initials cache (".round(($finishA - $startA), 4)."s).</p>";
}

if ($get['a'] == 'updateLibraryCache') { 
	$startA = getTime();
	cacheLibraryStatistics();
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Forced update on library cache (".round(($finishA - $startA), 4)."s).</p>";
}

if ($get['a'] == 'updateRoadmapCache') {
	$startA = getTime();
	cacheRoadmap();
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Forced update on roadmap cache (".round(($finishA - $startA), 4)."s).</p>";
}

if ($get['a'] == 'updateStatusModule') {
	$startA = getTime();
	cacheStatusModule();
	cacheStatusModule(false);
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Forced update on status modules (".round(($finishA - $startA), 4)."s).</p>";
}

if ($get['a'] == 'updateCommitCache') {
	$startA = getTime();
	cacheCommitCache();
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Forced update on commit cache (".round(($finishA - $startA), 4)."s).</p>";
}


if ($get['a'] == 'generatePassword' && isset($_POST['pw'])) { 
	$startA = getTime();
	$cost = 13;
	$iterations = pow(2, $cost);
	$salt  = substr(strtr(base64_encode(openssl_random_pseudo_bytes(22)), '+', '.'), 0, 22);
	$pass = crypt($_POST['pw'], '$2y$'.$cost.'$'.$salt);
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Hashed and salted secure password generated with {$iterations} iterations (".round(($finishA - $startA), 4)."s).<br><b>Password:</b> {$pass}<br><b>Salt:</b> {$salt}</p>";
}


function checkInvalidThreads() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$invalid = 0;

	$a_status = array(
	'Playable' => 5,
	'Ingame' => 6,
	'Intro' => 7,
	'Loadable' => 8,
	'Nothing' => 9
	);
	
	
	$q_threads = mysqli_query($db, "SELECT tid, subject, fid  
	FROM rpcs3_forums.mybb_threads 
	WHERE fid > 4 && fid < 10; ");
	
	$a_threads = array();
	
	while ($row = mysqli_fetch_object($q_threads)) {
		if ($gid = get_string_between($row->subject, '[', ']')) {
			$a_threads[$row->tid][0] = $gid;
			$a_threads[$row->tid][1] = array_search($row->fid, $a_status);
		} else {
			$output .= "<p class='compat-tvalidity-list'>Thread ".getThread($row->subject, $row->tid)." is incorrectly formatted.</p>";
		}
	}
	
	$q_games = mysqli_query($db, "SELECT * FROM game_list; ");
	
	while ($row = mysqli_fetch_object($q_games)) {
		if (!empty($row->tid_EU)) {
			if (!array_key_exists($row->tid_EU, $a_threads)) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_EU, $row->tid_EU)." doesn't exist.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_EU}]<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->gid_EU != $a_threads[$row->tid_EU][0]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_EU, $row->tid_EU)." is incorrect.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_EU}]<br>";
				$output .= "- Forums: {$a_threads[$row->tid_EU][0]}<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->status != $a_threads[$row->tid_EU][1]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_EU, $row->tid_EU)." is in wrong forum.<br>";
				$output .= "- Compat: {$row->status} [{$row->gid_EU}] {$row->game_title}<br>";
				$output .= "- Forums: {$a_threads[$row->tid_EU][1]}<br>";
				$output .= "</p>";
				$invalid++;
			}
		}
		if (!empty($row->tid_US)) {
			if (!array_key_exists($row->tid_US, $a_threads)) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_US, $row->tid_US)." doesn't exist.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_US}]<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->gid_US != $a_threads[$row->tid_US][0]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_US, $row->tid_US)." is incorrect.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_US}]<br>";
				$output .= "- Forums: {$a_threads[$row->tid_US][0]}<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->status != $a_threads[$row->tid_US][1]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_US, $row->tid_US)." is in wrong forum.<br>";
				$output .= "- Compat: {$row->status} [{$row->gid_US}] {$row->game_title}<br>";
				$output .= "- Forums: {$a_threads[$row->tid_US][1]}<br>";
				$output .= "</p>";
				$invalid++;
			}
		}
		if (!empty($row->tid_JP)) {
			if (!array_key_exists($row->tid_JP, $a_threads)) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_JP, $row->tid_JP)." doesn't exist.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_JP}]<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->gid_JP != $a_threads[$row->tid_JP][0]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_JP, $row->tid_JP)." is incorrect.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_JP}]<br>";
				$output .= "- Forums: {$a_threads[$row->tid_JP][0]}<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->status != $a_threads[$row->tid_JP][1]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_JP, $row->tid_JP)." is in wrong forum.<br>";
				$output .= "- Compat: {$row->status} [{$row->gid_JP}] {$row->game_title}<br>";
				$output .= "- Forums: {$a_threads[$row->tid_JP][1]}<br>";
				$output .= "</p>";
				$invalid++;
			}
		}
		if (!empty($row->tid_AS)) {
			if (!array_key_exists($row->tid_AS, $a_threads)) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_AS, $row->tid_AS)." doesn't exist.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_AS}]<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->gid_AS != $a_threads[$row->tid_AS][0]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_AS, $row->tid_AS)." is incorrect.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_AS}]<br>";
				$output .= "- Forums: {$a_threads[$row->tid_AS][0]}<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->status != $a_threads[$row->tid_AS][1]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_AS, $row->tid_AS)." is in wrong forum.<br>";
				$output .= "- Compat: {$row->status} [{$row->gid_AS}] {$row->game_title}<br>";
				$output .= "- Forums: {$a_threads[$row->tid_AS][1]}<br>";
				$output .= "</p>";
				$invalid++;
			}
		}
		if (!empty($row->tid_KR)) {
			if (!array_key_exists($row->tid_KR, $a_threads)) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_KR, $row->tid_KR)." doesn't exist.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_KR}]<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->gid_KR != $a_threads[$row->tid_KR][0]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_KR, $row->tid_KR)." is incorrect.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_KR}]<br>";
				$output .= "- Forums: {$a_threads[$row->tid_KR][0]}<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->status != $a_threads[$row->tid_KR][1]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_KR, $row->tid_KR)." is in wrong forum.<br>";
				$output .= "- Compat: {$row->status} [{$row->gid_KR}] {$row->game_title}<br>";
				$output .= "- Forums: {$a_threads[$row->tid_KR][1]}<br>";
				$output .= "</p>";
				$invalid++;
			}
		}
		if (!empty($row->tid_HK)) {
			if (!array_key_exists($row->tid_HK, $a_threads)) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_HK, $row->tid_HK)." doesn't exist.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_HK}]<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->gid_HK != $a_threads[$row->tid_HK][0]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_HK, $row->tid_HK)." is incorrect.<br>";
				$output .= "- Compat: {$row->game_title} [{$row->gid_HK}]<br>";
				$output .= "- Forums: {$a_threads[$row->tid_HK][0]}<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($row->status != $a_threads[$row->tid_HK][1]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread($row->tid_HK, $row->tid_HK)." is in wrong forum.<br>";
				$output .= "- Compat: {$row->status} [{$row->gid_HK}] {$row->game_title}<br>";
				$output .= "- Forums: {$a_threads[$row->tid_HK][1]}<br>";
				$output .= "</p>";
				$invalid++;
			}
		}
	}
	
	if ($invalid > 0) {
		echo "<p class='compat-tvalidity-title color-red'><b>Not Naisu: Attention required! {$invalid} Invalid threads detected.<br><br></b></p>";
		echo $output;
	} else {
		echo "<p class='compat-tvalidity-title color-green'><b>Naisu! No invalid threads detected.</b></p>";
	}

	mysqli_close($db);
}


function compareThreads($update = false) {
	global $a_color, $a_title, $c_forum;
	
	set_time_limit(600);
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$a_status = array(
	'Playable' => 5,
	'Ingame' => 6,
	'Intro' => 7,
	'Loadable' => 8,
	'Nothing' => 9
	);
	
	$a_regions = array(
	'E' => 'EU',
	'U' => 'US',
	'J' => 'JP',
	'A' => 'AS',
	'K' => 'KR',
	'H' => 'HK'
	);
	
	// Timestamp of last list update
	// 1512086400 - 1st December 2017
	$timestamp = '1512086400';

	// Cache commits
	$q_commits = mysqli_query($db, "SELECT * FROM builds_windows ORDER by merge_datetime DESC;");
	$a_commits = array();
	while ($row = mysqli_fetch_object($q_commits)) {
		$a_commits[substr($row->commit, 0, 7)] = $row->merge_datetime;
	}
	
	$q_threads = mysqli_query($db, "SELECT tid, subject, fid, dateline, lastpost, closed, game_list.* 
	FROM rpcs3_forums.mybb_threads 
	LEFT JOIN rpcs3_compatibility.game_list ON 
	mybb_threads.tid = game_list.tid_EU OR
	mybb_threads.tid = game_list.tid_US OR
	mybb_threads.tid = game_list.tid_JP OR
	mybb_threads.tid = game_list.tid_AS OR
	mybb_threads.tid = game_list.tid_KR OR
	mybb_threads.tid = game_list.tid_HK 
	WHERE fid > 4 && fid < 10 
	&& closed NOT like '%moved%' 
	&& lastpost > {$timestamp}; ");
	
	// Cache array for games
	$a_games = array();
	// Cache array for duplicates
	$a_duplicates = array();
	
	echo "<p style='padding-top:10px; font-size:12px;'>";
	
	while ($row = mysqli_fetch_object($q_threads)) {
		
		if (!($gid = get_string_between($row->subject, '[', ']'))) {
			
			// If [GameID] is not present on thread title
			echo "Error! {$row->subject} (".getThread($row->subject, $row->tid).") incorrectly formatted.<br>";
			
		} elseif (strlen($gid) != 9 || !is_numeric(substr($gid, 4, 5)) || !array_key_exists(substr($gid, 2, 1), $a_regions)) {
			
			// If the GameID is invalid
			echo "Error! {$row->subject} (".getThread($row->subject, $row->tid).") incorrectly formatted.<br>";
			
		} else {
			
			$sid = $row->fid - 4;
		
			if (is_null($row->gid_EU) && is_null($row->gid_US) && is_null($row->gid_JP) 
			&& is_null($row->gid_AS) && is_null($row->gid_KR) && is_null($row->gid_HK)) {
			
				echo "<b>New:</b> {$row->subject} (tid:".getThread($row->tid, $row->tid).")<br>";
				echo "- To: <span style='color:#{$a_color[$sid]}'>".array_search($row->fid, $a_status)."</span><br>";

				$title = str_replace(" [{$gid}]", "", "{$row->subject}");
				
				// Check if there's an existing thread already, if so, flag as duplicated for manual correction.
				$duplicate = mysqli_query($db, "SELECT tid_{$a_regions[substr($gid, 2, 1)]} AS tid FROM game_list WHERE gid_{$a_regions[substr($gid, 2, 1)]} = '".mysqli_real_escape_string($db, $gid)."' LIMIT 1; ");
				if (mysqli_num_rows($duplicate) === 1) {
					$duplicateRow = mysqli_fetch_object($duplicate);
					echo "<span style='color:red'><b>Error!</b> {$row->subject} (".getThread($row->tid, $row->tid).") duplicated thread of (".getThread($duplicateRow->tid, $duplicateRow->tid).").</span><br>";
				} else {
				
					// When user can't properly format title
					if (substr($title, -2) == ' -') {
						$title = substr($title, 0, -2);
					}
					if (substr($title, -1) == ' ') {
						$title = substr($title, 0, -1);
					}
					
					if (array_key_exists($gid, $a_games)) {
						echo "Error! {$row->subject} (".getThread($row->tid, $row->tid).") duplicated thread.<br>";
					} else {
						
						$a_games[$row->tid] = array(
						"gid_{$a_regions[substr($gid, 2, 1)]}" => $gid, 
						'region' => $a_regions[substr($gid, 2, 1)], 
						'game_title' => $title, 
						'status' => array_search($row->fid, $a_status),
						'commit' => 0,
						'last_update' => date('Y-m-d', $row->lastpost),
						'action' => 'new',
						);
					}
					
				}
			
			} elseif ($a_status[$row->status] != $row->fid) {
				// TODO: Fix mov, handle other threads on the same entry
				echo "<b>Mov:</b> {$gid} - {$row->game_title} (tid:".getThread($row->tid, $row->tid).")<br>";
				echo "- To: <span style='color:#{$a_color[$sid]}'>".array_search($row->fid, $a_status)."</span><br>";
				echo "- From: <span style='color:#{$a_color[$a_status[$row->status]-4]}'>{$row->status}</span><br>";
				
				if (array_key_exists($row->tid, $a_duplicates)) {
					
					// Update status
					if ($a_status[$a_games[$a_duplicates[$row->tid]]['status']] < $row->fid) {
						$a_games[$a_duplicates[$row->tid]]['status'] = array_search($row->fid, $a_status);
					}
					// Update last_update
					if (strtotime($a_games[$a_duplicates[$row->tid]]['last_update']) < $row->lastpost) {
						$a_games[$a_duplicates[$row->tid]]['last_update'] = date('Y-m-d', $row->lastpost);
					}
					
				} else {
					
					$a_games[$row->tid] = array(
					"gid_{$a_regions[substr($gid, 2, 1)]}" => $gid,
					'region' => $a_regions[substr($gid, 2, 1)],
					'game_title' => $row->game_title,
					'status' => array_search($row->fid, $a_status),
					'commit' => 0,
					'last_update' => date('Y-m-d', $row->lastpost),
					'action' => 'mov',
					'old_date' => $row->last_update,
					'old_status' => $row->status,
					);
					
					
					if (!empty($row->tid_EU) && $row->tid_EU != $row->tid) {
						$a_duplicates[$row->tid_EU] = $row->tid;
					}
					if (!empty($row->gid_US) && $row->tid_US != $row->tid) {
						$a_duplicates[$row->tid_US] = $row->tid;
					}
					if (!empty($row->gid_JP) && $row->tid_JP != $row->tid) {
						$a_duplicates[$row->tid_JP] = $row->tid;
					}
					if (!empty($row->gid_AS) && $row->tid_AS != $row->tid) {
						$a_duplicates[$row->tid_AS] = $row->tid;
					}
					if (!empty($row->gid_KR) && $row->tid_KR != $row->tid) {
						$a_duplicates[$row->tid_KR] = $row->tid;
					}
					if (!empty($row->gid_HK) && $row->tid_HK != $row->tid) {
						$a_duplicates[$row->tid_KR] = $row->tid;
					}
				
				}
			}
		}
	}
	
	// TODO: Dynamic timestamp, check all new posts since entry's last_update
	$q_posts = mysqli_query($db, "SELECT pid, tid, fid, subject, dateline, message, game_list.* 
	FROM rpcs3_forums.mybb_posts 
	LEFT JOIN rpcs3_compatibility.game_list ON 
	mybb_posts.tid = game_list.tid_EU OR
	mybb_posts.tid = game_list.tid_US OR
	mybb_posts.tid = game_list.tid_JP OR
	mybb_posts.tid = game_list.tid_AS OR
	mybb_posts.tid = game_list.tid_KR OR
	mybb_posts.tid = game_list.tid_HK 
	WHERE fid > 4 && fid < 10 
	&& dateline > {$timestamp} 
	ORDER by tid, pid DESC;");
	
	$found = array();
	
	while ($row = mysqli_fetch_object($q_posts)) {
		
		if (isset($a_games[$row->tid]) && $a_games[$row->tid]['commit'] == '0') {
	
			// Also log threads where commits weren't found
			if (array_key_exists($row->tid, $found)) {
				$found[$row->tid] = 0;
			}
		
			foreach ($a_commits as $commit => $date) {				
				
				// Note: If commit is an int and not a string and one doesn't cast it then it breaks it
				if (stripos($row->message, (string)$commit) !== false) {
					
					// Check if more recent data exists from other region check
					if ($a_games[$row->tid]['commit'] == 0 || ($a_games[$row->tid]['commit'] != 0 && strtotime($a_games[$row->tid]['last_update']) < $row->dateline)) {
						$region = $a_games[$row->tid]['region'];
						$a_games[$row->tid]['commit'] = $commit;
						$a_games[$row->tid]['last_update'] = date('Y-m-d', $row->dateline);
						echo "<b>{$a_games[$row->tid]['gid_'.$region]}</b>: Commit found: {$commit} ({$date}) (pid:<a href='{$c_forum}/post-{$row->pid}.html'>{$row->pid}</a>)<br>";
					}
					$found[$row->tid] = 1;
					break;
				}
				
			}
		
			if ($found[$row->tid] == 0) {
				$region = $a_games[$row->tid]['region'];
				echo "<b>{$a_games[$row->tid]['gid_'.$region]}</b>: Commit not found (tid:".getThread($row->tid, $row->tid).")<br>";
				$found[$row->tid] = 1; // Log only once per thread
			}
			
		} elseif (array_key_exists($row->tid, $a_duplicates)) {
			
			$found_c = 0;
			$found_d = 0;
			
			foreach ($a_commits as $commit => $date) {	
				if (stripos($row->message, (string)$commit) !== false) {
					$found_c = $commit;
					$found_d = $date;
					break;
				}
			}
			
			if ($found_d != 0) {
				
				// TODO: Fixme, it's overwriting data with older posts from other region threads. Check not working properly
				if ($a_games[$a_duplicates[$row->tid]]['commit'] == 0 || ($a_games[$a_duplicates[$row->tid]]['commit'] != 0 && strtotime($a_games[$a_duplicates[$row->tid]]['last_update']) < $row->dateline)) {
					$a_games[$a_duplicates[$row->tid]]['last_update'] = date('Y-m-d', $row->dateline);
					$a_games[$a_duplicates[$row->tid]]['commit'] = $found_c;
					$region = $a_games[$a_duplicates[$row->tid]]['region'];
					echo "<b>{$a_games[$a_duplicates[$row->tid]]['gid_'.$region]}</b>: Commit found (other region): {$found_c} ({$found_d}) (pid:<a href='{$c_forum}/post-{$row->pid}.html'>{$row->pid}</a>)<br>";
				}
				
			}
				
		}
		
	}
	
	
	if ($update) {
		foreach ($a_games as $key => $value) {
		
			$region = $a_games[$key]['region'];
			
			$tempColumn = '';
			if (array_key_exists('gid_EU', $a_games[$key])) { $tempColumn .= "gid_EU, "; }
			if (array_key_exists('gid_US', $a_games[$key])) { $tempColumn .= "gid_US, "; }
			if (array_key_exists('gid_JP', $a_games[$key])) { $tempColumn .= "gid_JP, "; }
			if (array_key_exists('gid_AS', $a_games[$key])) { $tempColumn .= "gid_AS, "; }
			if (array_key_exists('gid_KR', $a_games[$key])) { $tempColumn .= "gid_KR, "; }
			if (array_key_exists('gid_HK', $a_games[$key])) { $tempColumn .= "gid_HK, "; }
			$tempValues = '';
			if (array_key_exists('gid_EU', $a_games[$key])) { $tempValues .= "'{$a_games[$key]['gid_EU']}', "; }
			if (array_key_exists('gid_US', $a_games[$key])) { $tempValues .= "'{$a_games[$key]['gid_US']}', "; }
			if (array_key_exists('gid_JP', $a_games[$key])) { $tempValues .= "'{$a_games[$key]['gid_JP']}', "; }
			if (array_key_exists('gid_AS', $a_games[$key])) { $tempValues .= "'{$a_games[$key]['gid_AS']}', "; }
			if (array_key_exists('gid_KR', $a_games[$key])) { $tempValues .= "'{$a_games[$key]['gid_KR']}', "; }
			if (array_key_exists('gid_HK', $a_games[$key])) { $tempValues .= "'{$a_games[$key]['gid_HK']}', "; }
		
			// No need to escape all params but meh
			if ($a_games[$key]['action'] == 'new') {
				
				// `gid_{$a_games[$key]['region']}`, 
				
				$insertCmd = "INSERT INTO `game_list` ({$tempColumn}game_title, build_commit, tid_{$a_games[$key]['region']}, last_update, status) VALUES ({$tempValues} 
				'".mysqli_real_escape_string($db, $a_games[$key]['game_title'])."', 
				'".mysqli_real_escape_string($db, $a_games[$key]['commit'])."', 
				'{$key}', 
				'{$a_games[$key]['last_update']}',
				'{$a_games[$key]['status']}' ) ;";
				mysqli_query($db, $insertCmd);
				
				// Log change to game_history
				$logCmd = "INSERT INTO game_history ({$tempColumn}new_status, new_date) VALUES ({$tempValues} 
				'{$a_games[$key]['status']}', 
				'{$a_games[$key]['last_update']}'
				); ";
				mysqli_query($db, $logCmd);
				
			} elseif ($a_games[$key]['action'] == 'mov') {
				
				$updateCmd = "UPDATE `game_list` SET ";
				if (array_key_exists('gid_EU', $a_games[$key])) { $updateCmd .= "`gid_EU`='{$a_games[$key]['gid_EU']}', "; }
				if (array_key_exists('gid_US', $a_games[$key])) { $updateCmd .= "`gid_US`='{$a_games[$key]['gid_US']}', "; }
				if (array_key_exists('gid_JP', $a_games[$key])) { $updateCmd .= "`gid_JP`='{$a_games[$key]['gid_JP']}', "; }
				if (array_key_exists('gid_AS', $a_games[$key])) { $updateCmd .= "`gid_AS`='{$a_games[$key]['gid_AS']}', "; }
				if (array_key_exists('gid_KR', $a_games[$key])) { $updateCmd .= "`gid_KR`='{$a_games[$key]['gid_KR']}', "; }
				if (array_key_exists('gid_HK', $a_games[$key])) { $updateCmd .= "`gid_HK`='{$a_games[$key]['gid_HK']}', "; }
				$updateCmd .= "
				`build_commit`='".mysqli_real_escape_string($db, $a_games[$key]['commit'])."', 
				`last_update`='{$a_games[$key]['last_update']}', 
				`status`='{$a_games[$key]['status']}' 
				WHERE (`gid_{$a_games[$key]['region']}`='".$a_games[$key]['gid_'.$region]."'); ";
				
				mysqli_query($db, $updateCmd);
				
				// Log change to game_history
				mysqli_query($db, "INSERT INTO game_history ({$tempColumn}old_status, old_date, new_status, new_date) VALUES ({$tempValues}
				'{$a_games[$key]['old_status']}', 
				'{$a_games[$key]['old_date']}',
				'{$a_games[$key]['status']}', 
				'{$a_games[$key]['last_update']}'
				); ");
				
			}
		
		}
		// Update other threads on updated games
		foreach ($a_duplicates as $key => $value) {	
			$fid = $a_status[$a_games[$value]['status']];
			mysqli_query($db, "UPDATE rpcs3_forums.mybb_threads SET fid = '{$fid}' WHERE tid = '{$key}'; ");
		}
	}
	
	echo "</p>";
	
	mysqli_close($db);
}


/* WIP
function getNewTests() {
	
	global $a_color, $a_title;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$a_status = array(
	'Playable' => 5,
	'Ingame' => 6,
	'Intro' => 7,
	'Loadable' => 8,
	'Nothing' => 9
	);

	// Cache commits
	$q_commits = mysqli_query($db, "SELECT * FROM builds_windows ORDER by merge_datetime DESC;");
	$a_commits = array();
	while ($row = mysqli_fetch_object($q_commits)) {
		$a_commits[substr($row->commit, 0, 7)] = $row->merge_datetime;
	}
	
	$q_threads = mysqli_query($db, "SELECT *
	FROM rpcs3_compatibility.game_list 
	WHERE status = 'Playable';"); // Playable only
	
	// Cache games
	$a_games = array();
	
	while ($row = mysqli_fetch_object($q_threads)) {
		$a_games[$row->thread_id] = array(
		'game_id' => $row->game_id, 
		'game_title' => $row->game_title, 
		'status' => $row->status,
		'currentCommit' => $row->build_commit,
		'currentDate' => date('Y-m-d',  strtotime($row->last_update)),
		'newCommit' => $row->build_commit,
		'newDate' => date('Y-m-d', strtotime($row->last_update)),
		'parent_id' => $parent_id
		);
	}

	// Manually locked to Playable games
	$q_posts = mysqli_query($db, "SELECT pid, tid, fid, subject, dateline, message, game_id, game_title, build_commit, status, last_update 
	FROM rpcs3_forums.mybb_posts 
	LEFT JOIN rpcs3_compatibility.game_list
	ON tid = thread_id 
	WHERE fid = 5
	ORDER by tid, pid DESC;");
	
	$found = array();
	
	echo "<p style='padding-top:10px; font-size:12px;'>";
	
	while ($row = mysqli_fetch_object($q_posts)) {
		
		if (isset($a_games[$row->tid])) {
	
			if (!array_key_exists($row->tid, $found)) {
				$found[$row->tid] = 0;
			}
	
			if ($found[$row->tid] == 0) { 
			
				foreach ($a_commits as $commit => $date) {		
				
					// Note: If commit is an int and not a string and one doesn't cast it then it breaks it
					if (stripos($row->message, (string)$commit) !== false) {
						
						$newDate = date('Y-m-d', $row->dateline);
						
						// If new date is after the current one and the commits are different
						if ( $newDate > $a_games[$row->tid]['currentDate'] && substr($a_games[$row->tid]['currentCommit'], 0, 7) != substr((string)$commit, 0, 7)) {
							
							$a_games[$row->tid]['newCommit'] = $commit;
							$a_games[$row->tid]['newDate'] = $newDate;
							echo "<b>{$a_games[$row->tid]['game_id']}</b>: Commit found: &nbsp;&nbsp;&nbsp; {$commit} (".date('Y-m-d', strtotime($date))." | {$newDate} | {$a_games[$row->tid]['currentDate']}) (pid:<a href='https://forums.rpcs3.net/post-{$row->pid}.html#pid{$row->pid}'>{$row->pid}</a>)<br>";
							$found[$row->tid] = 1;
							break;
							
						}
					
					}
				
				}
				
			} elseif (stripos($row->message, (string)$a_games[$row->tid]['newCommit']) !== false) {
				
				// Discards useless quote duplicates: If commit belongs to an older post, set date to that post's
				$newDate = date('Y-m-d', $row->dateline);
				$a_games[$row->tid]['newDate'] = $newDate;
				echo "<b>{$a_games[$row->tid]['game_id']}</b>: Older date found: {$a_games[$row->tid]['newCommit']} ({$newDate} | {$a_games[$row->tid]['currentDate']}) (pid:<a href='https://forums.rpcs3.net/post-{$row->pid}.html#pid{$row->pid}'>{$row->pid}</a>)<br>";
				
			}
			
		}
		
	}
	
	
	echo "<br>";
	highlight_string("<?php\n\$data =\n".var_export($a_games, true).";\n?>");
	
	
	echo "</p>";
	
	mysqli_close($db);	
	
}
*/
