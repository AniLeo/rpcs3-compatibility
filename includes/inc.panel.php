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

	$q_invalidThreads = mysqli_query($db, "SELECT game_id, game_title, thread_id, tid, subject
	FROM rpcs3_compatibility.game_list AS t1
	LEFT JOIN rpcs3_forums.mybb_threads AS t2 
	ON t1.thread_id = t2.tid
	WHERE t2.subject NOT LIKE CONCAT('%',game_id,'%') OR t2.subject IS null; ");

	if (mysqli_num_rows($q_invalidThreads) > 0) {
		echo "<p class='compat-tvalidity-title color-red'><b>Not Naisu: Attention required! Invalid threads detected.</b></p>";
		
		echo "<p class='compat-tvalidity-list'>";
		while ($row = mysqli_fetch_object($q_invalidThreads)) {
			if (is_null($row->subject)) {
				echo "Thread ".getThread($row->thread_id, $row->thread_id)." doesn't exist.<br>";
				echo "- Compat: {$row->game_title} [{$row->game_id}] <br>";
			} else {
				echo "Thread ".getThread($row->thread_id, $row->thread_id)." is incorrect.<br>";
				echo "- Compat: {$row->game_title} [{$row->game_id}]<br>";
				echo "- Forums: {$row->subject}<br>";
			}
			echo "<br>";
		}
		echo "</p>";
	} else {
		echo "<p class='compat-tvalidity-title color-green'><b>Naisu! No invalid threads detected.</b></p>";
	}

	mysqli_close($db);
}


function compareThreads($update = false) {
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
	
	// Timestamp of last list update
	// 1498867200 - 1st July 2017
	$timestamp = '1498867200'; // 1503864000

	// Cache commits
	$q_commits = mysqli_query($db, "SELECT * FROM builds_windows ORDER by merge_datetime DESC;");
	$a_commits = array();
	while ($row = mysqli_fetch_object($q_commits)) {
		$a_commits[substr($row->commit, 0, 7)] = $row->merge_datetime;
	}
	
	$q_threads = mysqli_query($db, "SELECT tid, subject, fid, dateline, lastpost, closed, game_id, game_title, build_commit, thread_id, status, last_edit, parent_id
	FROM rpcs3_forums.mybb_threads 
	LEFT JOIN rpcs3_compatibility.game_list 
	ON tid = thread_id 
	LEFT JOIN game_status
	ON game_list.parent_id = game_status.id
	WHERE fid > 4 && fid < 10 && closed NOT like '%moved%'
	&& lastpost > {$timestamp} 
	ORDER BY game_id;");
	
	// Cache games
	$a_games = array();
	
	echo "<p style='padding-top:10px; font-size:12px;'>";
	
	$parent_id = mysqli_fetch_object(mysqli_query($db, "SELECT parent_id FROM game_list ORDER BY parent_id DESC LIMIT 1;"))->parent_id;
	
	while ($row = mysqli_fetch_object($q_threads)) {
		$sid = $row->fid - 4;
		if (is_null($row->game_id)) {
			echo "<b>New:</b> {$row->subject} (tid:".getThread($row->tid, $row->tid).")<br>";
			echo "- To: <span style='color:#{$a_color[$sid]}'>".array_search($row->fid, $a_status)."</span><br>";
			
			$gid = get_string_between($row->subject, '[', ']');
			if ($gid == '') {
				echo "Error! {$row->subject} (".getThread($row->tid, $row->tid).") incorrectly formatted.<br>";
			} else {
				$title = $bodytag = str_replace(" [{$gid}]", "", "{$row->subject}");
				
				// Check if there's an existing thread already, if so, flag as duplicated for manual correction.
				$duplicate = mysqli_query($db, "SELECT * FROM game_list WHERE game_id = '".mysqli_real_escape_string($db, $gid)."' LIMIT 1; ");
				if (mysqli_num_rows($duplicate) === 1) {
					$duplicateRow = mysqli_fetch_object($duplicate);
					echo "<span style='color:red'><b>Error!</b> {$row->subject} (".getThread($row->tid, $row->tid).") duplicated thread of (".getThread($duplicateRow->thread_id, $duplicateRow->thread_id).").</span><br>";
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

						$parent_id++;
						
						$a_games[$row->tid] = array(
						'game_id' => $gid, 
						'game_title' => $title, 
						'status' => array_search($row->fid, $a_status),
						'commit' => 0,
						'last_edit' => date('Y-m-d', $row->lastpost),
						'action' => 'new',
						'parent_id' => $parent_id
						);
					}
					
				}
			}
		} elseif ($a_status[$row->status] != $row->fid) {
			echo "<b>Mov:</b> {$row->game_id} - {$row->game_title} (tid:".getThread($row->tid, $row->tid).")<br>";
			echo "- To: <span style='color:#{$a_color[$sid]}'>".array_search($row->fid, $a_status)."</span><br>";
			echo "- From: <span style='color:#{$a_color[$a_status[$row->status]-4]}'>{$row->status}</span><br>";
			
			$a_games[$row->tid] = array(
			'game_id' => $row->game_id,
			'game_title' => $row->game_title,
			'status' => array_search($row->fid, $a_status),
			'commit' => 0,
			'last_edit' => date('Y-m-d', $row->lastpost),
			'action' => 'mov',
			'old_date' => $row->last_edit,
			'old_status' => $row->status,
			'parent_id' => $row->parent_id
			);
		}
	}
	
	$q_posts = mysqli_query($db, "SELECT pid, tid, fid, subject, dateline, message, game_id, game_title, build_commit, status, last_edit 
	FROM rpcs3_forums.mybb_posts 
	LEFT JOIN rpcs3_compatibility.game_list
	ON tid = thread_id 
	LEFT JOIN game_status
	ON game_list.parent_id = game_status.id 
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
					$a_games[$row->tid]['commit'] = $commit;
					$a_games[$row->tid]['last_edit'] = date('Y-m-d', $row->dateline);
					echo "<b>{$a_games[$row->tid]['game_id']}</b>: Commit found: {$commit} ({$date}) (pid:<a href='https://forums.rpcs3.net/post-{$row->pid}.html'>{$row->pid}</a>)<br>";
					$found[$row->tid] = 1;
					break;
				}
				
			}
		
			if ($found[$row->tid] == 0) 
			{
				echo "<b>{$a_games[$row->tid]['game_id']}</b>: Commit not found (tid:".getThread($row->tid, $row->tid).")<br>";
				$found[$row->tid] = 1; // Log only once per thread
			}
			
		}
		
	}
	
	
	if ($update) {
		foreach ($a_games as $key => $value) {
		
			// No need to escape all params but meh
			if ($a_games[$key]['action'] == 'new') {
				mysqli_query($db, "INSERT INTO `game_list` (`game_id`, `game_title`, `build_commit`, `thread_id`, `parent_id`, `last_edit`) VALUES (
				'".mysqli_real_escape_string($db, $a_games[$key]['game_id'])."', 
				'".mysqli_real_escape_string($db, $a_games[$key]['game_title'])."', 
				'".mysqli_real_escape_string($db, $a_games[$key]['commit'])."', 
				'{$key}', 
				'{$a_games[$key]['parent_id']}', 
				'{$a_games[$key]['last_edit']}'
				);");
				
				mysqli_query($db, "INSERT INTO game_status (id, status) VALUES ({$a_games[$key]['parent_id']}, '{$a_games[$key]['status']}'); ");
				
				// Log change to game_history
				mysqli_query($db, "INSERT INTO game_history (game_id, new_status, new_date) VALUES (
				'".mysqli_real_escape_string($db, $a_games[$key]['game_id'])."', 
				'{$a_games[$key]['status']}', 
				'{$a_games[$key]['last_edit']}'
				); ");
				
			} elseif ($a_games[$key]['action'] == 'mov') {
				mysqli_query($db, "UPDATE `game_list` SET 
				`build_commit`='".mysqli_real_escape_string($db, $a_games[$key]['commit'])."', 
				`last_edit`='{$a_games[$key]['last_edit']}' 
				WHERE (`game_id`='{$a_games[$key]['game_id']}'); ");
				
				mysqli_query($db, "UPDATE game_status SET status = '{$a_games[$key]['status']}' WHERE id = {$a_games[$key]['parent_id']}; ");
				
				// Log change to game_history
				mysqli_query($db, "INSERT INTO game_history (game_id, old_status, old_date, new_status, new_date) VALUES (
				'".mysqli_real_escape_string($db, $a_games[$key]['game_id'])."', 
				'{$a_games[$key]['old_status']}', 
				'{$a_games[$key]['old_date']}',
				'{$a_games[$key]['status']}', 
				'{$a_games[$key]['last_edit']}'
				); ");
				
			}
		
		}
	}
	
	/*
	echo "<br>";
	highlight_string("<?php\n\$data =\n".var_export($a_games, true).";\n?>");
	*/
	
	echo "</p>";
	
	mysqli_close($db);
}
