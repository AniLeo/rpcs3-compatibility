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
if (!@include_once(__DIR__."/../objects/Game.php")) throw new Exception("Compat: Game.php is missing. Failed to include Game.php");


/*
TODO: Login system
TODO: Self-made sessions system
TODO: User permissions system
TODO: Log commands with run time and datetime

if ($get['a'] == 'generatePassword' && isset($_POST['pw'])) {
	$startA = getTime();
	$cost = 13;
	$iterations = pow(2, $cost);
	$salt = substr(strtr(base64_encode(openssl_random_pseudo_bytes(22)), '+', '.'), 0, 22);
	$pass = crypt($_POST['pw'], '$2y$'.$cost.'$'.$salt);
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Hashed and salted secure password generated with {$iterations} iterations (".round(($finishA - $startA), 4)."s).<br><b>Password:</b> {$pass}<br><b>Salt:</b> {$salt}</p>";
}
*/


function runFunctions() {
	global $get, $a_panel;

	echo "<div style=\"font-size:12px;\">";

	if (array_key_exists($get['a'], $a_panel)) {
		$ret = runFunctionWithCronometer($get['a']);
		if (!empty($a_panel[$get['a']]['success']))
			echo "<p><b>Debug mode:</b> {$a_panel[$get['a']]['success']} ({$ret}s).</p>";
	}

	echo "</div>";
}


function checkInvalidThreads() {
	global $a_status, $get;

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
		echo "<p class='compat-tvalidity-title color-red'><b>Attention required! {$invalid} Invalid threads detected<br><br></b></p>";
		if ($get['a'] == 'checkInvalidThreads')
			echo $output;
	} else {
		echo "<p class='compat-tvalidity-title color-green'><b>No invalid threads detected</b></p>";
	}

}


function compatibilityUpdater() {
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
	$q_commits = mysqli_query($db, "SELECT * FROM `builds` ORDER BY `merge_datetime` DESC;");
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

	echo "<p>"; // Start paragraph

	while ($row = mysqli_fetch_object($q_threads)) {

		// Game ID is always supposed to be at the end of the Thread Title as per Guidelines
		$gid = substr($row->subject, -10, 9);
		$sid = $fid2sid[$row->fid];

		// Not a valid Game ID, continue to next thread entry
		if (!isGameID($gid)) {
			$bin = bin2hex($gid);
			echo "Error! {$row->subject} (".getThread($row->subject, $row->tid).") (gid={$gid}, hex=0x{$bin}) incorrectly formatted.<br><br>";
			continue;
		}

		// If a thread for this Game ID was already visited, continue to next thread entry
		if (!in_array($gid, $a_gameIDs)) {
			$a_gameIDs[] = $gid;
		} else {
			echo "Error! A thread for {$gid} was already visited. ".getThread($row->subject, $row->tid)." is a duplicate.<br><br>";
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
			echo "<span style='color:red'><b>Error!</b> {$row->subject} (".getThread($row->tid, $row->tid).") duplicated thread of (".getThread($tid, $tid).").</span><br><br>";
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

			$a_inserts[$row->tid] = array(
				'gid' => $gid,
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
				if ($a_updates[$cur_game->key]['status'] < $sid) {
					echo "<b>Error!</b> Smaller status after a status update ({$gid}, {$a_updates[$cur_game->key]['status']} < {$sid})<br><br>";
					continue;
				} elseif ($a_updates[$cur_game->key]['commit'] === 0) {
					echo "<b>Replacing:</b> Entry on key {$cur_game->key}: {$a_updates[$cur_game->key]['gid']} for {$gid}<br><br>";
					$a_updates[$cur_game->key]['gid'] = $gid;
					$a_updates[$cur_game->key]['status'] = $sid;
					$a_updates[$cur_game->key]['commit'] = 0;
					$a_updates[$cur_game->key]['last_update'] = date('Y-m-d', $row->lastpost);
				}

			} else {

				$a_updates[$cur_game->key] = array(
					'gid' => $gid,
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
						if (($a_updates[$cur_game->key]['commit'] === 0) ||
						($a_updates[$cur_game->key]['commit'] !== 0 && strtotime($a_commits[substr($a_updates[$cur_game->key]['commit'], 0, 8)][1]) < strtotime($value[1]))) {
							// echo "<b>Commit Replacement:</b> {$a_updates[$cur_game->key]['commit']} (".strtotime($a_commits[substr($a_updates[$cur_game->key]['commit'], 0, 8)][1]).") for commit {$value[0]} ($value[1])<br><br>";
							$a_updates[$cur_game->key]['commit'] = $value[0];
							$a_updates[$cur_game->key]['last_update'] = date('Y-m-d', $post->dateline);
							$a_updates[$cur_game->key]['author'] = $post->username;
							break 2;
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

	echo "</p>"; // End paragraph

	if (isset($_POST['updateCompatibility'])) {

		/*
			Inserts
		*/
		foreach ($a_inserts as $tid => $game) {
			// Insert new entry on the game list
			$q_insert = mysqli_query($db, "INSERT INTO `game_list` (`game_title`, `build_commit`, `last_update`, `status`) VALUES
			('".mysqli_real_escape_string($db, $game['game_title'])."',
			'".mysqli_real_escape_string($db, $game['commit'])."',
			'{$game['last_update']}',
			'{$game['status']}');");

			// Get the key from the entry that was just inserted
			$q_fetchkey = mysqli_query($db, "SELECT `key` FROM `game_list` WHERE
			`game_title` = '".mysqli_real_escape_string($db, $game['game_title'])."' AND
			`build_commit` = '".mysqli_real_escape_string($db, $game['commit'])."' AND
			`last_update` = '{$game['last_update']}' AND
			`status` = {$game['status']}
			ORDER BY `key` DESC LIMIT 1");
			$key = mysqli_fetch_object($q_fetchkey)->key;

			// Insert Game and Thread IDs on the ID table
			$q_insert = mysqli_query($db, "INSERT INTO `game_id` (`key`, `gid`, `tid`) VALUES ({$key}, '{$game['gid']}', {$tid}); ");

			// Sanity check, this should be unreachable
			if ($key == NULL)
				echo "<b>Fatal error:</b> Could not fetch key. Current game dump: {$game}<br><br>";

			// Log change to game history
			$q_history = mysqli_query($db, "INSERT INTO `game_history` (`game_key`, `new_gid`, `new_status`, `new_date`) VALUES
			({$key}, '".mysqli_real_escape_string($db, $game['gid'])."', '{$game['status']}', '{$game['last_update']}');");
		}

		/*
			Updates
		*/
		foreach ($a_updates as $key => $game) {
			// Update entry parameters on game list
			$q_update = mysqli_query($db, "UPDATE `game_list` SET
			`build_commit`='".mysqli_real_escape_string($db, $game['commit'])."',
			`last_update`='{$game['last_update']}',
			`status`='{$game['status']}'
			WHERE `key` = {$key};");

			// Log change to game history
			mysqli_query($db, "INSERT INTO `game_history` (`game_key`, `old_status`, `old_date`, `new_status`, `new_date`) VALUES
			({$key}, '{$game['old_status']}', '{$game['old_date']}', '{$game['status']}', '{$game['last_update']}'); ");
		}

		// Recache commit cache as new additions may contain new commits
		cacheCommitCache();
		// Recache status counts for general search
		cacheStatusCount();
		// Recache initials cache
		cacheInitials();
		// Recache status modules
		cacheStatusModules();

	} else {

		// Display update button
		echo "<form action=\"\" method=\"post\">
						<button name=\"updateCompatibility\" type=\"submit\">Update Compatibility</button>
					</form><br>";

	}

	mysqli_close($db);

}


function mergeGames() {
	global $a_status;

	$gid1 = isset($_POST['gid1']) ? $_POST['gid1'] : "";
	$gid2 = isset($_POST['gid2']) ? $_POST['gid2'] : "";

	echo "<form action=\"\" method=\"post\">
					<input name=\"gid1\" type=\"text\" value=\"{$gid1}\" placeholder=\"Game ID 1\"><br>
					<input name=\"gid2\" type=\"text\" value=\"{$gid2}\" placeholder=\"Game ID 2\"><br>
					<br>
					<button name=\"mergeRequest\" type=\"submit\">Merge Request</button>
					<button name=\"mergeConfirm\" type=\"submit\">Merge Confirm</button>
				</form><br>";

	if (!isset($_POST['mergeRequest']) && !isset($_POST['mergeConfirm']))
		return;

	if (!isGameID($gid1)) {
		echo "<p><b>Error:</b> Game ID 1 is not a valid Game ID</p>";
		return;
	}
	if (!isGameID($gid2)) {
		echo "<p><b>Error:</b> Game ID 2 is not a valid Game ID</p>";
		return;
	}

	$db = getDatabase();

	$s_gid1 = mysqli_real_escape_string($db, $_POST['gid1']);
	$s_gid2 = mysqli_real_escape_string($db, $_POST['gid2']);

	$game1 = Game::queryToGames(mysqli_query($db, "SELECT * FROM `game_list` WHERE `key` IN(SELECT `key` FROM `game_id` WHERE `gid` = '{$s_gid1}');"))[0];
	if (empty($game1)) {
		echo "<p><b>Error:</b> Game ID 1 could not be found</p>";
		return;
	}

	$game2 = Game::queryToGames(mysqli_query($db, "SELECT * FROM `game_list` WHERE `key` IN(SELECT `key` FROM `game_id` WHERE `gid` = '{$s_gid2}');"))[0];
	if (empty($game2)) {
		echo "<p><b>Error:</b> Game ID 2 could not be found</p>";
		return;
	}

	if ($game1->key == $game2->key) {
		echo "<p><b>Error:</b> Both Game IDs belong to the same Game Entry</p>";
		return;
	}

	if (substr($game1->IDs[0][0], 0, 1) != substr($game2->IDs[0][0], 0, 1)) {
		echo "<p><b>Error:</b> Cannot merge entries of different Game Media</p>";
		return;
	}

	echo "<p>"; // Start paragraph

	echo "<b>Game 1: {$game1->title} (status: {$a_status[$game1->status]['name']}, pr: {$game1->pr}, date: {$game1->date})</b><br>";
		foreach ($game1->IDs as $id)
			echo "- {$id[0]} (tid: $id[1])<br>";
	echo "<br>";

	echo "<b>Game 2: {$game2->title} (status: {$a_status[$game2->status]['name']}, pr: {$game2->pr}, date: {$game2->date})</b><br>";
		foreach ($game2->IDs as $id)
			echo "- {$id[0]} (tid: $id[1])<br>";
	echo "<br>";

	$time1 = strtotime($game1->date);
	$time2 = strtotime($game2->date);

	// If the most recent entry doesn't have a PR and the oldest one has
	// allow for 1 month tolerance to use the older key if the difference between them is 1 month at max
	if ($game1->pr == 0 && $game2->pr != 0)
		$time1 -= 2678400;
	if ($game1->pr != 0 && $game2->pr == 0)
		$time2 -= 2678400;

	if ($time1 == $time2 && $game1->pr != $game2->pr) {
		// If the update date is the same, pick the one with the most recent PR
		$newKey = $game1->pr > $game2->pr ? $game1->key : $game2->key;
		$oldKey = $game1->pr > $game2->pr ? $game2->key : $game1->key;
	} else {
		// If the update date differs, pick the one with the most recent update date
		$newKey = $time1 > $time2 ? $game1->key : $game2->key;
		$oldKey = $time1 > $time2 ? $game2->key : $game1->key;
	}

	// Update: Set both game keys to the same previous picked key
	if (isset($_POST['mergeConfirm'])) {
		mysqli_query($db, "DELETE FROM `game_list` WHERE (`key`='{$oldKey}')");
		mysqli_query($db, "UPDATE `game_id` SET `key`='{$newKey}' WHERE (`key`='{$oldKey}')");
		mysqli_query($db, "UPDATE `game_history` SET `game_key`='{$newKey}' WHERE (`game_key`='{$oldKey}')");
		// Recache status counts for general search
		cacheStatusCount();
		// Recache status modules
		cacheStatusModules();
		echo "<b>Games successfully merged!</b><br>";
	}

	echo "</p>"; // End paragraph
}
