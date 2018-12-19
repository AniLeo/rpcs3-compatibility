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

if (!@include_once(__DIR__."/../functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/../cachers.php")) throw new Exception("Compat: cachers.php is missing. Failed to include cachers.php");
if (!@include_once(__DIR__."/../utils.php")) throw new Exception("Compat: utils.php is missing. Failed to include utils.php");
if (!@include_once(__DIR__."/../objects/Game.php")) throw new Exception("Compat: Game.php is missing. Failed to include Game.php");


/*
TODO: Login system
TODO: Self-made sessions system
TODO: User permissions system
TODO: Log commands with run time and datetime
*/

if ($get['a'] == 'generatePassword' && isset($_POST['pw'])) {
	$startA = getTime();
	$cost = 13;
	$iterations = pow(2, $cost);
	$salt  = substr(strtr(base64_encode(openssl_random_pseudo_bytes(22)), '+', '.'), 0, 22);
	$pass = crypt($_POST['pw'], '$2y$'.$cost.'$'.$salt);
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Hashed and salted secure password generated with {$iterations} iterations (".round(($finishA - $startA), 4)."s).<br><b>Password:</b> {$pass}<br><b>Salt:</b> {$salt}</p>";
}

if (array_key_exists($get['a'], $a_panel)) {
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> {$a_panel[$get['a']]['success']} (".runFunctionWithCronometer($get['a'])."s).</p>";
}


function checkInvalidThreads() {
	global $a_status;

	$db = getDatabase();

	$invalid = 0;
	$output = '';

	// Store forumID -> statusID
	$FidToSid = array();

	// Generate WHERE condition for our query
	// Includes all forum IDs for the game status sections
	$where = '';
	foreach ($a_status as $id => $status) {
		if ($where != '') $where .= "||";
		$where .= " `fid` = {$status['fid']} ";

		$FidToSid[$status['fid']] = $id;
	}

	$a_threads = array();
	$q_threads = mysqli_query($db, "SELECT `tid`, `subject`, `fid` FROM `rpcs3_forums`.`mybb_threads` WHERE {$where}; ");

	while ($row = mysqli_fetch_object($q_threads)) {
		// Game ID is always supposed to be at the end of the Thread Title as per Guidelines
		// We can't search for what's in between [ ] because at least one game uses those on title
		$gid = substr($row->subject, -10, 9);

		if (isGameID($gid)) {
			$a_threads[$row->tid][0] = $gid;
			$a_threads[$row->tid][1] = $FidToSid[$row->fid];
		} else {
			$output .= "<p class='compat-tvalidity-list'>Thread ".getThread($row->subject, $row->tid)." is incorrectly formatted.</p>";
		}
	}

	$a_games = Game::queryToGames(mysqli_query($db, "SELECT * FROM `game_list`;"));

	mysqli_close($db);

	foreach ($a_games as $game) {
		foreach ($game->IDs as $id) {
			if (!array_key_exists($id[1], $a_threads)) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread("{$id[1]}: [{$id[0]}] {$game->title}", $id[1])." doesn't exist.<br>";
				$output .= "- Compat: {$game->title} [{$id[0]}]<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($id[0] != $a_threads[$id[1]][0]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread("{$id[1]}: [{$id[0]}] {$game->title}", $id[1])." is incorrect.<br>";
				$output .= "- Compat: {$game->title} [{$id[0]}]<br>";
				$output .= "- Forums: {$a_threads[$id[1]][0]}<br>";
				$output .= "</p>";
				$invalid++;
			} elseif ($game->status != $a_threads[$id[1]][1]) {
				$output .= "<p class='compat-tvalidity-list'>";
				$output .= "Thread ".getThread("{$id[1]}: [{$id[0]}] {$game->title}", $id[1])." is in the wrong section.<br>";
				$output .= "- Compat: {$a_status[$game->status]['name']} <br>";
				$output .= "- Forums: {$a_status[$a_threads[$id[1]][1]]['name']}<br>";
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

}


function compareThreads($update = false) {
	global $a_histdates, $a_status, $a_regions;

	set_time_limit(300);
	$db = getDatabase();

	// Timestamp of last list update
	end($a_histdates);
	$lastkey = key($a_histdates);
	reset($a_histdates);
	$ts_lastupdate = strtotime("{$a_histdates[$lastkey][1]['y']}-{$a_histdates[$lastkey][1]['m']}-{$a_histdates[$lastkey][1]['d']}");

	// Store forumID -> statusID
	// Generate WHERE condition for our query
	// Includes all forum IDs for the game status sections
	$fid2sid = array();
	$where = '';
	foreach ($a_status as $id => $status) {
		if ($where != '') $where .= "||";
		$where .= " `fid` = {$status['fid']} ";
		$fid2sid[$status['fid']] = $id;
	}

	// Cache commits
	$q_commits = mysqli_query($db, "SELECT * FROM `builds_windows` ORDER BY `merge_datetime` DESC;");
	$a_commits = array();
	while ($row = mysqli_fetch_object($q_commits))
		$a_commits[substr($row->commit, 0, 8)] = array($row->commit, $row->merge_datetime);

	// Get all threads since the end of the last compatibility period
	$q_threads = mysqli_query($db, "SELECT `tid`, `fid`, `subject`, `dateline`, `lastpost`, `username`
	FROM `rpcs3_forums`.`mybb_threads`
	WHERE ({$where}) && `closed` NOT LIKE '%moved%' && `lastpost` > {$ts_lastupdate};");

	// Get all games in the database
	$a_games = Game::queryToGames(mysqli_query($db, "SELECT * FROM `game_list`;"));

	// Script data
	$a_inserts = array();
	$a_updates = array();
	// Visited Game IDs
	$a_gameIDs = array();

	while ($row = mysqli_fetch_object($q_threads)) {

		// Game ID is always supposed to be at the end of the Thread Title as per Guidelines
		$gid = substr($row->subject, -10, 9);
		$sid = $fid2sid[$row->fid];

		// Not a valid Game ID, continue to next thread entry
		if (!isGameID($gid)) {
			echo "Error! {$row->subject} (".getThread($row->subject, $row->tid).") (gid={$gid}) incorrectly formatted.<br>";
			continue;
		}

		// If a thread for this Game ID was already visited, continue to next thread entry
		if (!in_array($gid, $a_gameIDs)) {
			$a_gameIDs[] = $gid;
		} else {
			echo "Error! A thread for {$gid} was already visited. ".getThread($row->subject, $row->tid)." is a duplicate.<br>";
			continue;
		}

		// Thread ID validation
		// If game entry exists, get game data
		$tid = null;
		$cur_game = null;
		foreach($a_games as $game) {
			foreach($game->IDs as $id) {
				if ($id[0] == $gid) {
					$tid = $id[1];
					$cur_game = $game;
				}
			}
		}

		// New thread is a duplicate of an existing one
		if ($tid != null && $tid != $row->tid) {
			echo "<span style='color:red'><b>Error!</b> {$row->subject} (".getThread($row->tid, $row->tid).") duplicated thread of (".getThread($tid, $tid).").</span><br>";
			continue;
		}

		// New thread for the Game ID
		if ($tid == null) {

			// Extract game title from thread title
			$title = str_replace(" [{$gid}]", "", "{$row->subject}");

			// Handle PBKAC: When user can't properly format title
			if (substr($title, -2) == ' -')
				$title = substr($title, 0, -2);
			if (substr($title, -1) == ' ')
				$title = substr($title, 0, -1);

			// TODO: GID Structure Update
			$a_inserts[$row->tid] = array(
				"gid_{$a_regions[substr($gid, 2, 1)]}" => $gid,
				'region' => $a_regions[substr($gid, 2, 1)],
				'game_title' => $title,
				'status' => $sid,
				'commit' => 0,
				'last_update' => date('Y-m-d', $row->lastpost),
				'author' => $row->username
			);

			// Verify posts
			$q_post = mysqli_query($db, "SELECT `pid`, `dateline`, `message`
			FROM `rpcs3_forums`.`mybb_posts` WHERE `tid` = {$row->tid}
			ORDER BY `pid` DESC;");

			while ($post = mysqli_fetch_object($q_post)) {
				foreach ($a_commits as $key => $value) {
					if (stripos($post->message, (string)$key) !== false) {
						$a_inserts[$row->tid]['commit'] = $value[0];
						$a_inserts[$row->tid]['last_update'] = date('Y-m-d', $post->dateline);
						break;
					}
				}
			}

			// Green for existing commit, Red for non-existing commit
			$status_commit = $a_inserts[$row->tid]['commit'] !== 0 ? 'green' : 'red';
			$short_commit = $a_inserts[$row->tid]['commit'] !== 0 ? substr($a_inserts[$row->tid]['commit'], 0, 8) : 0;
			$date_commit = $a_inserts[$row->tid]['commit'] !== 0 ? "({$a_commits[$short_commit][1]})" : "";

			echo "<b>New:</b> {$row->subject} (tid:".getThread($row->tid, $row->tid).", author:{$a_inserts[$row->tid]['author']})<br>";
			echo "- Status: <span style='color:#{$a_status[$sid]['color']}'>{$a_status[$sid]['name']}</span><br>";
			echo "- Commit: <span style='color:{$status_commit}'>{$short_commit}</span> {$date_commit}<br>";
			echo "<br>";

		} elseif ($tid == $row->tid && $sid != $cur_game->status) {

			// This game entry was already checked before in this script
			// Update with the new information
			if (array_key_exists($cur_game->key, $a_updates)) {

				// Update status
				if ($a_updates[$cur_game->key]['status'] > $sid) {
					$a_updates[$cur_game->key]["gid_{$a_regions[substr($gid, 2, 1)]}"] = $gid;
					$a_updates[$cur_game->key]['status'] = $sid;
					$a_updates[$cur_game->key]['commit'] = 0;
					$a_updates[$cur_game->key]['last_update'] = date('Y-m-d', $row->lastpost);
				} elseif ($a_updates[$cur_game->key]['status'] < $sid) {
					echo "<b>Error!</b> Smaller status after a status update ({$gid}, {$a_updates[$cur_game->key]['status']} < {$sid})<br>";
					continue;
				}

			} else {

				$a_updates[$cur_game->key] = array(
					"gid_{$a_regions[substr($gid, 2, 1)]}" => $gid,
					'game_title' => $cur_game->title,
					'status' => $sid,
					'commit' => 0,
					'last_update' => date('Y-m-d', $row->lastpost),
					'action' => 'mov',
					'old_date' => $cur_game->date,
					'old_status' => $cur_game->status,
					'author' => ''
				);

			}

			// Verify posts
			$q_post = mysqli_query($db, "SELECT `pid`, `dateline`, `message`, `username`
			FROM `rpcs3_forums`.`mybb_posts` WHERE `tid` = {$row->tid} && `dateline` > {$a_updates[$cur_game->key]['old_date']}
			ORDER BY `pid` DESC;");

			while ($post = mysqli_fetch_object($q_post)) {
				foreach ($a_commits as $key => $value) {
					if (stripos($post->message, (string)$key) !== false) {
						// If current commit is newer than the previously recorded one, replace
						if (($a_updates[$cur_game->key]['commit'] == 0) ||
						($a_updates[$cur_game->key]['commit'] != 0 && strtotime($a_commits[substr($a_updates[$cur_game->key]['commit'], 0, 8)][1]) < strtotime($value[1]))) {
							$a_updates[$cur_game->key]['commit'] = $value[0];
							$a_updates[$cur_game->key]['last_update'] = date('Y-m-d', $post->dateline);
							$a_updates[$cur_game->key]['author'] = $post->username;
							break;
						}
					}
				}
			}

			// Green for existing commit, Red for non-existing commit
			$status_commit = $a_updates[$cur_game->key]['commit'] !== 0 ? 'green' : 'red';
			$short_commit = $a_updates[$cur_game->key]['commit'] !== 0 ? substr($a_updates[$cur_game->key]['commit'], 0, 8) : 0;
			$date_commit = $a_updates[$cur_game->key]['commit'] !== 0 ? "({$a_commits[$short_commit][1]})" : "";

			echo "<b>Mov:</b> {$gid} - {$cur_game->title} (tid:".getThread($row->tid, $row->tid).", author:{$a_updates[$cur_game->key]['author']})<br>";
			echo "- Status: <span style='color:#{$a_status[$sid]['color']}'>{$a_status[$sid]['name']}</span> <-- <span style='color:#{$a_status[$cur_game->status]['color']}'>{$a_status[$cur_game->status]['name']}</span><br>";
			echo "- Commit: <span style='color:{$status_commit}'>{$short_commit}</span> {$date_commit}<br>";
			echo "<br>";

		} else {
			// TODO: Updates within the same status
			// echo "<b>Skipping:</b> {$row->subject} {$tid}<br><br>";
			continue;
		}

	}

	if ($update) {

		/*
			Inserts
		*/
		foreach ($a_inserts as $tid => $game) {
			// TODO: GID Structure Update
			$tempColumn = '';
			if 		 (array_key_exists('gid_EU', $game)) { $tempColumn .= "gid_EU, tid_EU, "; }
			elseif (array_key_exists('gid_US', $game)) { $tempColumn .= "gid_US, tid_US, "; }
			elseif (array_key_exists('gid_JP', $game)) { $tempColumn .= "gid_JP, tid_JP, "; }
			elseif (array_key_exists('gid_AS', $game)) { $tempColumn .= "gid_AS, tid_AS, "; }
			elseif (array_key_exists('gid_KR', $game)) { $tempColumn .= "gid_KR, tid_KR, "; }
			elseif (array_key_exists('gid_HK', $game)) { $tempColumn .= "gid_HK, tid_HK, "; }
			$tempValues = '';
			if 		 (array_key_exists('gid_EU', $game)) { $tempValues .= "'{$game['gid_EU']}', {$tid}, "; }
			elseif (array_key_exists('gid_US', $game)) { $tempValues .= "'{$game['gid_US']}', {$tid}, "; }
			elseif (array_key_exists('gid_JP', $game)) { $tempValues .= "'{$game['gid_JP']}', {$tid}, "; }
			elseif (array_key_exists('gid_AS', $game)) { $tempValues .= "'{$game['gid_AS']}', {$tid}, "; }
			elseif (array_key_exists('gid_KR', $game)) { $tempValues .= "'{$game['gid_KR']}', {$tid}, "; }
			elseif (array_key_exists('gid_HK', $game)) { $tempValues .= "'{$game['gid_HK']}', {$tid}, "; }

			// Insert new entry on the game list
			$q_insert = mysqli_query($db, "INSERT INTO `game_list` ({$tempColumn}`game_title`, `build_commit`, `last_update`, `status`) VALUES
			({$tempValues}
			'".mysqli_real_escape_string($db, $game['game_title'])."',
			'".mysqli_real_escape_string($db, $game['commit'])."',
			'{$game['last_update']}',
			'{$game['status']}');");

			// TODO: GID Structure Update
			$tempColumn = '';
			if (array_key_exists('gid_EU', $game)) { $tempColumn .= "gid_EU, "; }
			if (array_key_exists('gid_US', $game)) { $tempColumn .= "gid_US, "; }
			if (array_key_exists('gid_JP', $game)) { $tempColumn .= "gid_JP, "; }
			if (array_key_exists('gid_AS', $game)) { $tempColumn .= "gid_AS, "; }
			if (array_key_exists('gid_KR', $game)) { $tempColumn .= "gid_KR, "; }
			if (array_key_exists('gid_HK', $game)) { $tempColumn .= "gid_HK, "; }
			$tempValues = '';
			if (array_key_exists('gid_EU', $game)) { $tempValues .= "'{$game['gid_EU']}', "; }
			if (array_key_exists('gid_US', $game)) { $tempValues .= "'{$game['gid_US']}', "; }
			if (array_key_exists('gid_JP', $game)) { $tempValues .= "'{$game['gid_JP']}', "; }
			if (array_key_exists('gid_AS', $game)) { $tempValues .= "'{$game['gid_AS']}', "; }
			if (array_key_exists('gid_KR', $game)) { $tempValues .= "'{$game['gid_KR']}', "; }
			if (array_key_exists('gid_HK', $game)) { $tempValues .= "'{$game['gid_HK']}', "; }

			// Log change to game history
			$q_history = mysqli_query($db, "INSERT INTO `game_history` ({$tempColumn}`new_status`, `new_date`) VALUES
			({$tempValues}
			'{$game['status']}',
			'{$game['last_update']}'
			);");
		}

		/*
			Updates
		*/
		foreach ($a_updates as $key => $game) {
			// TODO: GID Structure Update
			$tempColumn = '';
			if (array_key_exists('gid_EU', $game)) { $tempColumn .= "gid_EU, "; }
			if (array_key_exists('gid_US', $game)) { $tempColumn .= "gid_US, "; }
			if (array_key_exists('gid_JP', $game)) { $tempColumn .= "gid_JP, "; }
			if (array_key_exists('gid_AS', $game)) { $tempColumn .= "gid_AS, "; }
			if (array_key_exists('gid_KR', $game)) { $tempColumn .= "gid_KR, "; }
			if (array_key_exists('gid_HK', $game)) { $tempColumn .= "gid_HK, "; }
			$tempValues = '';
			if (array_key_exists('gid_EU', $game)) { $tempValues .= "'{$game['gid_EU']}', "; }
			if (array_key_exists('gid_US', $game)) { $tempValues .= "'{$game['gid_US']}', "; }
			if (array_key_exists('gid_JP', $game)) { $tempValues .= "'{$game['gid_JP']}', "; }
			if (array_key_exists('gid_AS', $game)) { $tempValues .= "'{$game['gid_AS']}', "; }
			if (array_key_exists('gid_KR', $game)) { $tempValues .= "'{$game['gid_KR']}', "; }
			if (array_key_exists('gid_HK', $game)) { $tempValues .= "'{$game['gid_HK']}', "; }

			// Update entry parameters on game list
			$q_update = mysqli_query($db, "UPDATE `game_list` SET
			`build_commit`='".mysqli_real_escape_string($db, $game['commit'])."',
			`last_update`='{$game['last_update']}',
			`status`='{$game['status']}'
			WHERE `key` = {$key};");

			// Log change to game history
			mysqli_query($db, "INSERT INTO game_history ({$tempColumn}old_status, old_date, new_status, new_date) VALUES ({$tempValues}
			'{$game['old_status']}',
			'{$game['old_date']}',
			'{$game['status']}',
			'{$game['last_update']}'
			); ");
		}

		// Recache commit cache as new additions may contain new commits
		cacheCommitCache();
		// Recache status counts for general search
		cacheStatusCount();
		// Recache initials cache
		cacheInitials();
		// Recache status modules
		cacheStatusModules();

	}

}
