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

// Calls for the file that contains the needed functions
if(!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/objects/Game.php")) throw new Exception("Compat: Game.php is missing. Failed to include Game.php");


function cacheWindowsBuilds($full = false) {
	$db = getDatabase();

	if (!$full) {
		set_time_limit(60*5); // 5 minute limit
		// Get date from last merged PR. Subtract 1 day to it and check new merged PRs since then.
		// Note: If master builds are disabled we need to remove WHERE type = 'branch'
		$mergeDateQuery = mysqli_query($db, "SELECT DATE_SUB(`merge_datetime`, INTERVAL 1 DAY) AS `date` FROM `builds_windows` WHERE `type` = 'branch' ORDER BY `merge_datetime` DESC LIMIT 1;");
		$row = mysqli_fetch_object($mergeDateQuery);
		$date = date_format(date_create($row->date), 'Y-m-d');
	} elseif ($full) {
		// This can take a while to do...
		set_time_limit(60*60); // 1 hour limit
		// Start from indicated date (2015-08-10 for first PR with AppVeyor CI)
		$date = '2018-06-02';
	}

	// Get number of PRs (GitHub Search API)
	// repo:rpcs3/rpcs3, is:pr, is:merged, merged:>$date, sort=updated (asc)
	// TODO: Sort by merged date whenever it's available on the GitHub API
	$url = "https://api.github.com/search/issues?q=repo:rpcs3/rpcs3+is:pr+is:merged+sort:updated-asc+merged:%3E{$date}";
	$search = getJSON($url);

	// API Call Failed or no PRs to cache, end here
	// TODO: Log and handle API call fails differently depending on the fail
	if (!isset($search->total_count) || $search->total_count == 0) {
		mysqli_close($db);
		return;
	}

	$page_limit = 30; // Search API page limit: 30
	$pages = (int)(ceil($search->total_count / $page_limit));
	$a_PR = array();	// List of iterated PRs
	$i = 1;	// Current page

	// Loop through all pages and get PR information
	while ($i <= $pages) {

		$a = 0; // Current PR (page relative)
		
		// Define PR limit for current page
		$pr_limit = ($i == $pages) ? ($search->total_count - (($pages-1)*$page_limit)) : $page_limit;

		$i++; // Prepare for next page

		while ($a < $pr_limit) {

			$pr = $search->items[$a]->number;
			$a++;	// Prepare for next PR

			// If PR was already checked in this run, skip it
			if (in_array($pr, $a_PR)) {
				continue;
			}
			$a_PR[]  = $pr;

			// Check if PR is already cached
			$PRQuery = mysqli_query($db, "SELECT * FROM `builds_windows` WHERE `pr` = {$pr} LIMIT 1; ");

			// If PR is already cached and we're not in full mode, skip
			if (mysqli_num_rows($PRQuery) > 0 && !$full) {
				continue;
			}

			// Grab pull request information from GitHub REST API (v3)
			$pr_info = getJSON("https://api.github.com/repos/rpcs3/rpcs3/pulls/{$pr}");

			// Check if we aren't rate limited
			if (!array_key_exists('merge_commit_sha', $pr_info)) {
				continue;
			}

			// Merge time, Creation Time, Commit SHA, Author
			$merge_datetime = $pr_info->merged_at;
			$start_datetime = $pr_info->created_at;
			$commit = $pr_info->merge_commit_sha;
			$author = $pr_info->user->login;

			// Additions, Deletions, Changed Files
			$additions = $pr_info->additions;
			$deletions = $pr_info->deletions;
			$changed_files = $pr_info->changed_files;

			$info_release = getJSON("https://api.github.com/repos/rpcs3/rpcs3-binaries-win/releases/tags/build-{$commit}");

			// Error message found: Build doesn't exist in rpcs3-binaries-win yet, continue to check the next one
			if (isset($info_release->message)) {
				continue;
			}

			// Version name
			$version = $info_release->name;
			$type = "branch";

			// Simple sanity check: If build doesn't contain a slash then the buildname is invalid
			if (!(strpos($version, '-') !== false)) {
				continue;
			}

			// Checksum, Size, Filename
			$fileinfo = explode(';', $info_release->body);
			$checksum = $fileinfo[0];
			$size = floatval(preg_replace("/[^0-9.,]/", "", $fileinfo[1]))*1024*1024;
			$filename = $info_release->assets[0]->name;

			$aid = cacheContributor($author);

			// Checking author information failed
			// TODO: This should probably be logged, as other API call fails
			if ($aid == 0) {
				continue;
			}

			if (mysqli_num_rows(mysqli_query($db, "SELECT * FROM `builds_windows` WHERE `pr` = {$pr} LIMIT 1; ")) == 1) {
				$cachePRQuery = mysqli_query($db, "UPDATE `builds_windows` SET
				`commit` = '".mysqli_real_escape_string($db, $commit)."',
				`type` = '{$type}',
				`author` = '".mysqli_real_escape_string($db, $aid)."',
				`start_datetime` = '{$start_datetime}',
				`merge_datetime` = '{$merge_datetime}',
				`appveyor` = '{$version}',
				`filename` = '".mysqli_real_escape_string($db, $filename)."',
				`additions` = '{$additions}',
				`deletions` = '{$deletions}',
				`changed_files` = '{$changed_files}',
				`size` = '".mysqli_real_escape_string($db, $size)."',
				`checksum` = '".mysqli_real_escape_string($db, $checksum)."'
				WHERE `pr` = '{$pr}' LIMIT 1;");
			} else {
				$cachePRQuery = mysqli_query($db, "INSERT INTO `builds_windows`
				(`pr`, `commit`, `type`, `author`, `start_datetime`, `merge_datetime`, `appveyor`, `filename`, `additions`, `deletions`, `changed_files`, `size`, `checksum`)
				VALUES ('{$pr}', '".mysqli_real_escape_string($db, $commit)."', '{$type}', '".mysqli_real_escape_string($db, $aid)."', '{$start_datetime}', '{$merge_datetime}',
				'{$version}', '".mysqli_real_escape_string($db, $filename)."', '{$additions}', '{$deletions}', '{$changed_files}',
				'".mysqli_real_escape_string($db, $size)."', '".mysqli_real_escape_string($db, $checksum)."'); ");
			}

			// Recache commit => pr cache
			cacheCommitCache();

		}

		if ($i <= $pages)
			$search = getJSON("{$url}&page={$i}");

	}
	mysqli_close($db);
}


function cacheInitials() {
	$db = getDatabase();

	$theQuery = mysqli_query($db, "SELECT game_title FROM game_list;");

	while($row = mysqli_fetch_object($theQuery)) {

		// Divide game title by spaces between words
		$w = explode(" ", $row->game_title);
		$initials = "";

		foreach($w as $w) {

			// Skip empty strings
			if (empty($w)) { continue; }

			// We don't care about the following in initials
			// demo: several Demo games
			// pack and vol.: Idolmaster games
			// goty: Batman
			if (strtolower($w) == "demo" || strtolower($w) == "pack" || strtolower($w) == "vol." || strtolower($w) == "goty") { continue; }

			// For Steins;Gate/Chaos;Head/Robotics;Notes...
			if (strpos($w, ";") !== false) {
				$sg = explode(";", $w);
				foreach($sg as $sg) {
					$initials .= substr($sg, 0, 1);
				}
				continue;
			}

			// Games starting by a dot
			// Ex: .detuned | .hack//Versus
			if (strpos($w, ".") === 0) {
				// Remove the dot and continue
				$w = substr($w, 1);
			}

			// For .hack//Versus...
			if (strpos($w, "//") !== false) {
				$hv = explode("//", $w);
				foreach($hv as $hv) {
					$initials .= substr($hv, 0, 1);
				}
				continue;
			}


			// If word is alphanumeric then add first character to the initials, else ignore
			if (ctype_alnum(substr($w, 0, 1))) {
				$initials .= substr($w, 0, 1);

				// If the next character is a number then keep adding until it's not alphanumeric
				// Workaround for games like Disgaea D2 / Idolmaster G4U!
				if (ctype_digit(substr($w, 1, 1))) {
					$i = strlen($w) - 1;

					foreach(range(1, $i) as $n) {
						if (ctype_alnum($w[$n])) { $initials .= $w[$n]; }
					}
				}
			} elseif (!preg_match("/[a-z]/i", $w)) {
				// Workaround for games with numbers like 15 or 1942
				// Any word that doesn't have a-z A-Z
				$i = strlen($w) - 1;
				foreach(range(0, $i) as $n) {
					// If character is a number then add it to initials
					if (ctype_digit($w[$n])) { $initials .= $w[$n]; }
				}
			}

		}

		// We don't care about games with less than 2 initials
		if (strlen($initials) > 1) {

			// Check if value is already cached (two games can have the same initials so we use game_title)
			$checkQuery = mysqli_query($db, "SELECT * FROM initials_cache WHERE game_title = '".mysqli_real_escape_string($db, $row->game_title)."' LIMIT 1; ");

			// If value isn't cached, then cache it
			if(mysqli_num_rows($checkQuery) === 0) {
				mysqli_query($db, "INSERT INTO initials_cache (game_title, initials)
				VALUES ('".mysqli_real_escape_string($db, $row->game_title)."',
				'".mysqli_real_escape_string($db, $initials)."'); ");
			} else {
				$row2 = mysqli_fetch_object($checkQuery);
				// If value is cached but differs from newly calculated initials, update it
				if ($row2->initials != $initials) {
					mysqli_query($db, "UPDATE initials_cache SET initials = '".mysqli_real_escape_string($db, $initials)."'
					WHERE game_title = '".mysqli_real_escape_string($db, $row->game_title)."' LIMIT 1;");
				}
			}

		}
	}
	mysqli_close($db);
}


function cacheLibraryStatistics() {
	global $a_filter;

	$db = getDatabase();

	// Get all games in the database (ID + Title)
	$a_games = array();
	$query = mysqli_query($db, "SELECT * FROM game_list; ");

	$all = 0;

	while($row = mysqli_fetch_object($query)) {
		if (!empty($row->gid_EU)) { $a_games[] = $row->gid_EU; $all++; }
		if (!empty($row->gid_US)) { $a_games[] = $row->gid_US; $all++; }
		if (!empty($row->gid_JP)) { $a_games[] = $row->gid_JP; $all++; }
		if (!empty($row->gid_AS)) { $a_games[] = $row->gid_AS; $all++; }
		if (!empty($row->gid_KR)) { $a_games[] = $row->gid_KR; $all++; }
		if (!empty($row->gid_HK)) { $a_games[] = $row->gid_HK; $all++; }
	}

	mysqli_close($db);

	$f_ps3tdb = fopen(__DIR__.'/ps3tdb.txt', 'r');

	$tested = 0;
	$untested = 0;

	if ($f_ps3tdb) {

		while (($line = fgets($f_ps3tdb)) !== false) {
			// Type: mb_substr($line, 0, 4)
			if (in_array(mb_substr($line, 0, 4), $a_filter)) {
				// GameID: mb_substr($line, 0, 9)
				in_array(mb_substr($line, 0, 9), $a_games) ? $tested++ : $untested++;
			}
		}

		// Closes ps3tdb.txt file resource
		fclose($f_ps3tdb);

		// Open tested.txt and write number of tested games in one line
		$f_tested = fopen(__DIR__.'/cache/tested.txt', 'w');
		fwrite($f_tested, $tested);
		fclose($f_tested);

		// Open untested.txt and write number of untested games in one line
		$f_untested = fopen(__DIR__.'/cache/untested.txt', 'w');
		fwrite($f_untested, $untested);
		fclose($f_untested);

		// Open all.txt and write number of all Game IDs in database in one line
		$f_all = fopen(__DIR__.'/cache/all.txt', 'w');
		fwrite($f_all, $all);
		fclose($f_all);
	}
}


function cacheStatusModules() {
	$f_status = fopen(__DIR__.'/cache/mod.status.count.php', 'w');
	fwrite($f_status, "\n<!-- START: Status Module -->\n<!-- This file is automatically generated -->\n".generateStatusModule()."\n<!-- END: Status Module -->\n");
	fclose($f_status);

	$f_status = fopen(__DIR__.'/cache/mod.status.nocount.php', 'w');
	fwrite($f_status, "\n<!-- START: Status Module -->\n<!-- This file is automatically generated -->\n".generateStatusModule(false)."\n<!-- END: Status Module -->\n");
	fclose($f_status);
}


// Fetch all used commits => pull requests from builds_windows table
// and store on cache/a_commits.json
// Since this is rather static data, we're caching it to a file
// Saves up a lot of execution time
function cacheCommitCache() {
	$db = getDatabase();

	$a_cache = array();

	// This is faster than verifying one by one per row on storeResults()
	$q_builds = mysqli_query($db, "SELECT DISTINCT `pr`, `commit` FROM `builds_windows`
	LEFT JOIN `game_list` ON SUBSTR(`commit`, 1, 7) = SUBSTR(`build_commit`, 1, 7)
	WHERE `build_commit` IS NOT NULL
	ORDER BY `merge_datetime` DESC;");

	while ($row = mysqli_fetch_object($q_builds)) {
		$a_cache[substr($row->commit, 0, 7)] = array($row->commit, $row->pr);
	}

	$f_commits = fopen(__DIR__.'/cache/a_commits.json', 'w');
	fwrite($f_commits, json_encode($a_cache));
	fclose($f_commits);

	mysqli_close($db);

	return $a_cache;
}


function cacheStatusCount() {
	$db = getDatabase();

	$a_cache = array();

	// Fetch general count per status
	$q_status = mysqli_query($db, "SELECT status+0 AS sid, count(*) AS c FROM game_list GROUP BY status;");

	$a_cache[0][0] = 0;

	while ($row = mysqli_fetch_object($q_status)) {
		$a_cache[0][$row->sid] = (int)$row->c;
		$a_cache[0][0] += $a_cache[0][$row->sid];
	}

	$a_cache[1] = $a_cache[0];

	$f_count = fopen(__DIR__.'/cache/a_count.json', 'w');
	fwrite($f_count, json_encode($a_cache));
	fclose($f_count);

	mysqli_close($db);
}


function cacheContributor($username) {
	$db = getDatabase();

	$info_contributor = getJSON("https://api.github.com/users/{$username}");

	// If message is set, API call did not go well. Ignore caching.
	if (!isset($info_contributor->message)) {

		$aid = $info_contributor->id;
		$q_contributor = mysqli_query($db, "SELECT * FROM contributors WHERE id = ".mysqli_real_escape_string($db, $aid)." LIMIT 1; ");

		if (mysqli_num_rows($q_contributor) === 0) {
			// Contributor not yet cached on contributors table.
			mysqli_query($db, "INSERT INTO `contributors` (`id`, `username`) VALUES (".mysqli_real_escape_string($db, $aid).", '".mysqli_real_escape_string($db, $username)."');");
		} elseif (mysqli_fetch_object($q_contributor)->username != $username) {
			// Contributor on contributors table but changed GitHub username.
			mysqli_query($db, "UPDATE `contributors` SET `username` = '".mysqli_real_escape_string($db, $username)."' WHERE `id` = ".mysqli_real_escape_string($db, $aid).";");
		}

	}

	mysqli_close($db);

	return !isset($info_contributor->message) ? $aid : 0;
}


function cacheWikiIDs() {
	$db = getDatabase();

	$a_cache = file_exists(__DIR__.'/../cache/a_commits.json') ? json_decode(file_get_contents(__DIR__.'/../cache/a_commits.json'), true) : cacheCommitCache();

	$q_games = mysqli_query($db, "SELECT * FROM `game_list`;");
	$a_games = Game::queryToGames($q_games);

	$q_wiki = mysqli_query($db, "SELECT `page_id`, `page_title`, `rev_id`, `rev_len`, CONVERT(`old_text` USING utf8mb4) AS `text` FROM `rpcs3_wiki`.`page`
	LEFT JOIN `rpcs3_wiki`.`revision` ON `page_latest` = `rev_id`
	LEFT JOIN `rpcs3_wiki`.`text` ON `rev_text_id` = `old_id`
	WHERE page_namespace = 0; ");
	$a_wiki = array();
	while ($row = mysqli_fetch_object($q_wiki))
		$a_wiki[] = array($row->page_id, $row->text);

	$a_found = array();

	// For every game
	// For every ID
	// Look for the ID on all wiki pages
	foreach ($a_games as $game) {
		foreach ($game->IDs as $id) {
			foreach ($a_wiki as $wiki) {
				if (strpos($wiki[1], $id[0]) !== false) {
					$a_found[] = array($game->title, $wiki[0]);
					break 2;
				}
			}
		}
	}

	// Maybe delete all pages beforehand? Probably not needed as Wiki pages shouldn't be changing IDs.
	foreach ($a_found as $entry) {
		$q_update = mysqli_query($db, "UPDATE `game_list` SET `wiki`={$entry[1]} WHERE (`game_title`='".mysqli_real_escape_string($db, $entry[0])."');");
	}

	mysqli_close($db);
}
