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


function cacheBuilds(bool $full = false) : void
{
	$db = getDatabase();
	$cr = curl_init();

	if (!$full) {
		set_time_limit(60*5); // 5 minute limit
		// Get date from last merged PR. Subtract 1 day to it and check new merged PRs since then.
		// Note: If master builds are disabled we need to remove WHERE type = 'branch'
		$mergeDateQuery = mysqli_query($db, "SELECT DATE_SUB(`merge_datetime`, INTERVAL 1 DAY) AS `date` FROM `builds` WHERE `type` = 'branch' ORDER BY `merge_datetime` DESC LIMIT 1;");
		$row = mysqli_fetch_object($mergeDateQuery);
		$date = date_format(date_create($row->date), 'Y-m-d');
	} else /* if ($full) */ {
		// This can take a while to do...
		set_time_limit(60*60); // 1 hour limit
		// Start from indicated date (2015-08-10 for first PR with AppVeyor CI)
		$date = '2018-06-02';
	}

	// Get number of PRs (GitHub Search API)
	// repo:rpcs3/rpcs3, is:pr, is:merged, merged:>$date, sort=updated (asc)
	// TODO: Sort by merged date whenever it's available on the GitHub API
	$url = "https://api.github.com/search/issues?q=repo:rpcs3/rpcs3+is:pr+is:merged+sort:updated-asc+merged:%3E{$date}";
	$search = curlJSON($url, $cr)['result'];

	// API Call Failed or no PRs to cache, end here
	// TODO: Log and handle API call fails differently depending on the fail
	if (!isset($search->total_count) || $search->total_count == 0) {
		mysqli_close($db);
		curl_close($cr);
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

			$pr = (int)$search->items[$a]->number;
			$a++;	// Prepare for next PR

			// If PR was already checked in this run, skip it
			if (in_array($pr, $a_PR)) {
				continue;
			}
			$a_PR[]  = $pr;

			// Check if PR is already cached
			$PRQuery = mysqli_query($db, "SELECT * FROM `builds` WHERE `pr` = {$pr} LIMIT 1; ");

			// If PR is already cached and we're not in full mode, skip
			if (mysqli_num_rows($PRQuery) > 0 && !$full) {
				continue;
			}

			cacheBuild($pr);

		}

		if ($i <= $pages)
			$search = curlJSON("{$url}&page={$i}", $cr)['result'];

	}
	mysqli_close($db);
	curl_close($cr);
}


function cacheBuild(int $pr) : void
{
	// Malformed ID
	if (!is_int($pr) || $pr <= 0) {
		return;
	}

	$cr = curl_init();

	// Grab pull request information from GitHub REST API (v3)
	$pr_info = curlJSON("https://api.github.com/repos/rpcs3/rpcs3/pulls/{$pr}", $cr)['result'];

	// Check if we aren't rate limited
	if (!isset($pr_info->merge_commit_sha)) {
		curl_close($cr);
		return;
	}

	// Merge time, Creation Time, Commit SHA, Author
	$merge_datetime = $pr_info->merged_at;
	$start_datetime = $pr_info->created_at;
	$commit = $pr_info->merge_commit_sha;
	$author = $pr_info->user->login;

	// Additions, Deletions, Changed Files
	$additions = (int) $pr_info->additions;
	$deletions = (int) $pr_info->deletions;
	$changed_files = (int) $pr_info->changed_files;

	// Currently unused
	$type = "branch";

	$aid = cacheContributor($author);
	// Checking author information failed
	// TODO: This should probably be logged, as other API call fails
	if ($aid == 0) {
		echo "Error: Checking author information failed";
		curl_close($cr);
		return;
	}

	// Windows build metadata
	$info_release_win = curlJSON("https://api.github.com/repos/rpcs3/rpcs3-binaries-win/releases/tags/build-{$commit}", $cr)['result'];

	// Linux build metadata
	$info_release_linux = curlJSON("https://api.github.com/repos/rpcs3/rpcs3-binaries-linux/releases/tags/build-{$commit}", $cr)['result'];

	// Error message found: Build doesn't exist in rpcs3-binaries-win or rpcs3-binaries-linux yet, continue to check the next one
	if (isset($info_release_win->message) || isset($info_release_linux->message)) {
		curl_close($cr);
		return;
	}

	// Version name
	$version = $info_release_win->name;

	// Simple sanity check: If version name doesn't contain a slash then the current entry is invalid
	if (strpos($version, '-') === false) {
		curl_close($cr);
		return;
	}

	// Truncate apostrophes on version name if they exist
	if (strpos($version, '\'') !== false) {
		$version = str_replace('\'', '', $version);
	}

	// Filename
	$filename_win = $info_release_win->assets[0]->name;
	$filename_linux = $info_release_linux->assets[0]->name;
	if (empty($filename_win) || empty($filename_linux)) {
		curl_close($cr);
		return;
	}

	// Checksum and Size
	$fileinfo_win = explode(';', $info_release_win->body);
	$checksum_win = strtoupper($fileinfo_win[0]);
	$size_win = floatval(preg_replace("/[^0-9.,]/", "", $fileinfo_win[1]));
	if (strpos($fileinfo_win[1], "MB") !== false) {
		$size_win *= 1024 * 1024;
	} elseif (strpos($fileinfo_win[1], "KB") !== false) {
		$size_win *= 1024;
	}

	$fileinfo_linux = explode(';', $info_release_linux->body);
	$checksum_linux = strtoupper($fileinfo_linux[0]);
	$size_linux = floatval(preg_replace("/[^0-9.,]/", "", $fileinfo_linux[1]));
	if (strpos($fileinfo_linux[1], "MB") !== false) {
		$size_linux *= 1024 * 1024;
	} elseif (strpos($fileinfo_linux[1], "KB") !== false) {
		$size_linux *= 1024;
	}

	$size_win = (string) $size_win;
	$size_linux = (string) $size_linux;

	$db = getDatabase();

	if (mysqli_num_rows(mysqli_query($db, "SELECT * FROM `builds` WHERE `pr` = {$pr} LIMIT 1; ")) === 1) {
		$cachePRQuery = mysqli_query($db, "UPDATE `builds` SET
		`commit` = '".mysqli_real_escape_string($db, $commit)."',
		`type` = '".mysqli_real_escape_string($db, $type)."',
		`author` = '".mysqli_real_escape_string($db, (string) $aid)."',
		`start_datetime` = '".mysqli_real_escape_string($db, $start_datetime)."',
		`merge_datetime` = '".mysqli_real_escape_string($db, $merge_datetime)."',
		`version` = '".mysqli_real_escape_string($db, $version)."',
		`additions` = '{$additions}',
		`deletions` = '{$deletions}',
		`changed_files` = '{$changed_files}',
		`size_win` = '".mysqli_real_escape_string($db, $size_win)."',
		`checksum_win` = '".mysqli_real_escape_string($db, $checksum_win)."',
		`filename_win` = '".mysqli_real_escape_string($db, $filename_win)."',
		`size_linux` = '".mysqli_real_escape_string($db, $size_linux)."',
		`checksum_linux` = '".mysqli_real_escape_string($db, $checksum_linux)."',
		`filename_linux` = '".mysqli_real_escape_string($db, $filename_linux)."'
		WHERE `pr` = '{$pr}' LIMIT 1;");
	} else {
		$cachePRQuery = mysqli_query($db, "INSERT INTO `builds`
		(`pr`, `commit`, `type`, `author`, `start_datetime`, `merge_datetime`, `version`, `additions`, `deletions`, `changed_files`, `size_win`, `checksum_win`, `filename_win`, `size_linux`, `checksum_linux`, `filename_linux`)
		VALUES ('{$pr}', '".mysqli_real_escape_string($db, $commit)."', '".mysqli_real_escape_string($db, $type)."', '".mysqli_real_escape_string($db, (string) $aid)."',
		'".mysqli_real_escape_string($db, $start_datetime)."', '".mysqli_real_escape_string($db, $merge_datetime)."',
		'".mysqli_real_escape_string($db, $version)."', '{$additions}', '{$deletions}', '{$changed_files}',
		'".mysqli_real_escape_string($db, $size_win)."', '".mysqli_real_escape_string($db, $checksum_win)."', '".mysqli_real_escape_string($db, $filename_win)."',
		'".mysqli_real_escape_string($db, $size_linux)."', '".mysqli_real_escape_string($db, $checksum_linux)."', '".mysqli_real_escape_string($db, $filename_linux)."'); ");
	}

	// Recache commit => pr cache
	cacheCommitCache();

	mysqli_close($db);
}


function cacheInitials() : void
{
	$db = getDatabase();

	// Pack and Vol.: Idolmaster
	// GOTY: Batman
	$words_blacklisted = array("demo", "pack", "vol.", "goty");
	$words_whitelisted = array("hd");

	$q_initials = mysqli_query($db, "SELECT DISTINCT(`game_title`), `alternative_title` FROM `game_list`;");

	// No games present in the database
	if (mysqli_num_rows($q_initials) < 1)
	{
		return;
	}

	$a_titles = array();

	while ($row = mysqli_fetch_object($q_initials)) {
		$a_titles[] = $row->game_title;
		if (!is_null($row->alternative_title))
			$a_titles[] = $row->alternative_title;
	}

	foreach ($a_titles as $title) {

		// Original title
		$original = $title;

		// For games with semi-colons: replace those with spaces
		// Science Adventure Games (Steins;Gate/Chaos;Head/Robotics;Notes...)
		$title = str_replace(';', ' ', $title);

		// For games with double dots: replace those with spaces
		$title = str_replace(':', ' ', $title);

		// For games with double slashes: replace those with spaces
		$title = str_replace('//', ' ', $title);

		// For games with single slashes: replace those with spaces
		$title = str_replace('/', ' ', $title);

		// For games with hyphen: replace those with spaces
		$title = str_replace('-', ' ', $title);

		// For games starting with a dot: remove it (.detuned and .hack//Versus)
		if (strpos($title, '.') === 0)
			$title = substr($title, 1);

		// Divide game title by spaces between words
		$words = explode(' ', $title);

		// Variable to store initials result
		$initials = "";

		foreach ($words as $word) {
			// Skip empty words
			if (empty($word))
				continue;

			// Include whitelisted words and skip
			if (in_array(strtolower($word), $words_whitelisted)) {
				$initials .= $word;
				continue;
			}

			// Skip blacklisted words without including
			if (in_array(strtolower($word), $words_blacklisted))
				continue;

			// Handle roman numerals
			// Note: This catches some false positives, but the result is better than without this step
			if (preg_match("/^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/", $word)) {
				$initials .= $word;
				continue;
			}

			// If the first character is alphanumeric then add it to the initials, else ignore
			if (ctype_alnum($word[0])) {
				$initials .= $word[0];

				// If the next character is a digit, add next characters to initials
				// until an non-alphanumeric character is hit
				// For games like Disgaea D2 and Idolmaster G4U!
				if (strlen($word) > 1 && ctype_digit($word[1])) {
					$len = strlen($word);
					for ($i = 1; $i < $len; $i++)
						if (ctype_alnum($word[$i]))
							$initials .= $word[$i];
						else
							break;
				}
			}
			// Any word that doesn't have a-z A-Z
			// For games with numbers like 15 or 1942
			elseif (!ctype_alpha($word)) {
				$len = strlen($word);
				// While character is a number, add it to initials
				for ($i = 0; $i < $len; $i++)
					if (ctype_digit($word[$i]))
						$initials .= $word[$i];
					else
						break;
			}
		}

		// We don't care about games with less than 2 initials
		if (strlen($initials) > 1) {
			$original = mysqli_real_escape_string($db, $original);

			// Check if value is already cached (two games can have the same initials so we use game_title)
			$q_check = mysqli_query($db, "SELECT * FROM `initials_cache`
				WHERE `game_title` = '{$original}' LIMIT 1; ");

			// If value isn't cached, then cache it
			if (mysqli_num_rows($q_check) === 0) {
				mysqli_query($db, "INSERT INTO `initials_cache` (`game_title`, `initials`)
				VALUES ('{$original}', '".mysqli_real_escape_string($db, $initials)."'); ");
			} else {
				// If value is cached but differs from newly calculated initials, update it
				$row = mysqli_fetch_object($q_check);
				if ($row->initials != $initials) {
					mysqli_query($db, "UPDATE `initials_cache`
					SET `initials` = '".mysqli_real_escape_string($db, $initials)."'
					WHERE `game_title` = '{$original}' LIMIT 1;");
				}
			}
		}

	}
	mysqli_close($db);
}


function cacheLibraryStatistics() : void
{
	global $a_filter;

	$db = getDatabase();

	// Get all game IDs in the database
	$a_games = array();
	$query = mysqli_query($db, "SELECT * FROM `game_id`; ");

	while($row = mysqli_fetch_object($query))
		$a_games[] = $row->gid;
	$all = sizeof($a_games);

	mysqli_close($db);

	$f_ps3tdb = fopen(__DIR__.'/ps3tdb.txt', 'r');

	if (!$f_ps3tdb)
		return;

	$tested = 0;
	$untested = 0;

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
	fwrite($f_tested, (string) $tested);
	fclose($f_tested);

	// Open untested.txt and write number of untested games in one line
	$f_untested = fopen(__DIR__.'/cache/untested.txt', 'w');
	fwrite($f_untested, (string) $untested);
	fclose($f_untested);

	// Open all.txt and write number of all Game IDs in database in one line
	$f_all = fopen(__DIR__.'/cache/all.txt', 'w');
	fwrite($f_all, (string) $all);
	fclose($f_all);
}


function cacheStatusModules() : void
{
	$f_status = fopen(__DIR__.'/cache/mod.status.count.php', 'w');
	fwrite($f_status, "\n<!-- START: Status Module -->\n<!-- This file is automatically generated -->\n".generateStatusModule()."\n<!-- END: Status Module -->\n");
	fclose($f_status);

	$f_status = fopen(__DIR__.'/cache/mod.status.nocount.php', 'w');
	fwrite($f_status, "\n<!-- START: Status Module -->\n<!-- This file is automatically generated -->\n".generateStatusModule(false)."\n<!-- END: Status Module -->\n");
	fclose($f_status);
}


// Fetch all used commits => pull requests from builds table
// and store on cache/a_commits.json
// Since this is rather static data, we're caching it to a file
// Saves up a lot of execution time
function cacheCommitCache() : array
{
	$db = getDatabase();

	$a_cache = array();

	// This is faster than verifying one by one per row on storeResults()
	$q_builds = mysqli_query($db, "SELECT DISTINCT `pr`, `commit` FROM `builds`
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


function cacheStatusCount() : void
{
	$db = getDatabase();

	$a_cache = array();

	// Fetch general count per status
	$q_status = mysqli_query($db, "SELECT status+0 AS sid, count(*) AS c FROM `game_list`
	WHERE `network` = 0 OR (`network` = 1 && `status` <= 2) GROUP BY `status`;");

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


function cacheContributor(string $username) : int
{
	$db = getDatabase();

	$info_contributor = curlJSON("https://api.github.com/users/{$username}")['result'];

	// If message is set, API call did not go well. Ignore caching.
	if (!isset($info_contributor->message) && isset($info_contributor->id)) {

		$q_contributor = mysqli_query($db, "SELECT * FROM `contributors` WHERE `id` = ".mysqli_real_escape_string($db, $info_contributor->id)." LIMIT 1; ");

		if (mysqli_num_rows($q_contributor) === 0) {
			// Contributor not yet cached on contributors table.
			mysqli_query($db, "INSERT INTO `contributors` (`id`, `username`) VALUES (".mysqli_real_escape_string($db, $info_contributor->id).", '".mysqli_real_escape_string($db, $username)."');");
		} elseif (mysqli_fetch_object($q_contributor)->username != $username) {
			// Contributor on contributors table but changed GitHub username.
			mysqli_query($db, "UPDATE `contributors` SET `username` = '".mysqli_real_escape_string($db, $username)."' WHERE `id` = ".mysqli_real_escape_string($db, $info_contributor->id).";");
		}

	}

	mysqli_close($db);

	return !isset($info_contributor->message) && isset($info_contributor->id) ? $info_contributor->id : 0;
}


function cacheWikiIDs() : void
{
	$db = getDatabase();

	$q_games = mysqli_query($db, "SELECT * FROM `game_list`;");
	$a_games = Game::queryToGames($q_games);

	// Fetch all game patches that contain a Game ID
	$q_wiki = mysqli_query($db, "SELECT `page_id`, `page_title`, CONVERT(`old_text` USING utf8mb4) AS `text` FROM `rpcs3_wiki`.`page`
	LEFT JOIN `rpcs3_wiki`.`slots` ON `page`.`page_latest` = `slots`.`slot_revision_id`
	LEFT JOIN `rpcs3_wiki`.`content` ON `slots`.`slot_content_id` = `content`.`content_id`
	LEFT JOIN `rpcs3_wiki`.`text` ON SUBSTR(`content`.`content_address`, 4) = `text`.`old_id`
	WHERE `page_namespace` = 0
	HAVING `text` RLIKE '[A-Z]{4}[0-9]{5}'; ");

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
					$a_found[] = array('wiki_id' => $wiki[0], 'title' => $game->title);
					break 2;
				}
			}
		}
	}



	// Update compatibility list entries with the found Wiki IDs
	// Maybe delete all pages beforehand? Probably not needed as Wiki pages shouldn't be changing IDs.
	foreach ($a_found as $entry)
	{
		$db_id = mysqli_real_escape_string($db, $entry['wiki_id']);
		$db_title = mysqli_real_escape_string($db, $entry['title']);
		
		$q_update = mysqli_query($db, "UPDATE `game_list` SET `wiki` = '{$db_id}'
		WHERE `game_title` = '{$db_title}' OR `alternative_title` = '{$db_title}';");
	}

	mysqli_close($db);
}


function cacheGameLatestVer() : void
{
	$db = getDatabase();

	$q_ids = mysqli_query($db, "SELECT * FROM `game_id` WHERE `latest_ver` IS NULL;");
	while ($row = mysqli_fetch_object($q_ids)) {
		// Get latest game update ver for this game
		$updateVer = getLatestGameUpdateVer($row->gid);

		// If we failed to get the latest version from the API
		if (is_null($updateVer)) {
			echo "<b>Error:</b> Could not fetch game latest version for {$row->gid}.<br><br>";
			continue;
		}

		// Insert Game and Thread IDs on the ID table
		$q_insert = mysqli_query($db, "UPDATE `game_id` SET `latest_ver`='".mysqli_real_escape_string($db, $updateVer)."' WHERE `gid`='{$row->gid}';");
	}
	mysqli_close($db);
}


function cachePatches() : void
{
	$db = getDatabase();

	// ID for the SPU Patches page, containing the general use SPU patches
	$id_patches_spu = 1090;

	// Select all page IDs present on game list
	$q_wiki = mysqli_query($db, "SELECT `page_id`, `page_title`, `page_touched`, CONVERT(`old_text` USING utf8mb4) AS `text` FROM `rpcs3_wiki`.`page`
	LEFT JOIN `rpcs3_compatibility`.`game_list` ON `page`.`page_id` = `game_list`.`wiki`
	LEFT JOIN `rpcs3_wiki`.`slots` ON `page`.`page_latest` = `slots`.`slot_revision_id`
	LEFT JOIN `rpcs3_wiki`.`content` ON `slots`.`slot_content_id` = `content`.`content_id`
	LEFT JOIN `rpcs3_wiki`.`text` ON SUBSTR(`content`.`content_address`, 4) = `text`.`old_id`
	WHERE (`page`.`page_namespace` = 0 AND `game_list`.`wiki` IS NOT NULL) OR `page`.`page_id` = {$id_patches_spu}; ");

	// No wiki pages, return here
	if (mysqli_num_rows($q_wiki) === 0)
		return;

	// Disabled by default, but it's disabled here again in case it's enabled
	ini_set("yaml.decode_php", 0);

	// Results array [id, text, date]
	$a_wiki = array();
	while ($row = mysqli_fetch_object($q_wiki))
		$a_wiki[] = array("id" => $row->page_id, "text" => $row->text, "date" => $row->page_touched);

	// Select all game patches currently on database
	$q_patch = mysqli_query($db, "SELECT `wiki_id`, `version`, `touched` FROM `rpcs3_compatibility`.`game_patch`; ");

	// Results array [version, touched]
	$a_patch = array();
	if (mysqli_num_rows($q_patch) !== 0)
	{
		while ($row = mysqli_fetch_object($q_patch))
			$a_patch[$row->wiki_id] = array("version" => $row->version, "touched" => $row->touched);
	}

	foreach ($a_wiki as $i => $result)
	{
		// Discard wiki pages with no patches
		if (strpos($result["text"], "{{patch") === false)
		{
			unset($a_wiki[$i]);
			continue;
		}

		// Get patch header information
		$header = get_string_between($result["text"], "{{patch", "|content");

		// Invalid information header
		if (!is_string($header) || empty($header))
		{
			echo "Invalid patch header syntax on Wiki Page {$result["id"]} <br>";
			unset($a_wiki[$i]);
			continue;
		}

		// Get the three characters representing the patch type after "type    = "
		$type = substr($header, strpos($header, "type    = ") + strlen("type    = "), 3);

		// Check if patch version syntax is valid (number, underscore, number)
		if (!is_string($type) || strlen($type) !== 3 || !ctype_alpha($type))
		{
			echo "Invalid patch type syntax on Wiki Page {$result["id"]} <br>";
			unset($a_wiki[$i]);
			continue;
		}

		// Only accept SPU patches from the SPU page
		if ($result["id"] === $id_patches_spu && $type !== "SPU")
		{
			unset($a_wiki[$i]);
			continue;
		}

		// Get the three characters representing the version number after "version = "
		$version = substr($header, strpos($header, "version = ") + strlen("version = "), 3);

		// Check if patch version syntax is valid (number, underscore, number)
		if (!is_string($version) || strlen($version) !== 3 || !ctype_digit($version[0]) || $version[1] !== '.' || !ctype_digit($version[2]))
		{
			echo "Invalid patch version syntax on Wiki Page {$result["id"]} <br>";
			unset($a_wiki[$i]);
			continue;
		}

		// Grab the YAML code between the designated HTML tags
		$txt_patch = get_string_between($result["text"], "|content =", "}}");

		// Remove any spacing and newlines before the beginning of the patch
		while (ctype_space($txt_patch[0]))
			$txt_patch = substr($txt_patch, 1);

		// Remove any spacing and newlines before the end of the patch
		while (ctype_space($txt_patch[-1]))
			$txt_patch = substr($txt_patch, 0, -1);

		// Validate whether the YAML code we fetched has valid YAML syntax
		$yml_patch = yaml_parse($txt_patch);

		// Discard patches with invalid YAML syntax
		if ($yml_patch === false)
		{
			echo "Invalid YAML syntax on Wiki Page {$result["id"]} <br>";
			unset($a_wiki[$i]);
			continue;
		}

		$db_id = mysqli_real_escape_string($db, $result["id"]);
		$db_date = mysqli_real_escape_string($db, $result["date"]);
		$db_version = mysqli_real_escape_string($db, $version);
		$db_patch = mysqli_real_escape_string($db, $txt_patch);

		// No existing patch found, insert new patch
		if (!isset($a_patch[$result["id"]]))
		{
			$q_insert = mysqli_query($db, "INSERT INTO `rpcs3_compatibility`.`game_patch` (`wiki_id`, `version`, `touched`, `patch`)
			VALUES ('{$db_id}', '{$db_version}', '{$db_date}', '{$db_patch}'); ");
		}

		// Existing patch found with older touch date, update it
		else if ($db_date !== $a_patch[$result["id"]]["touched"])
		{
			$q_update = mysqli_query($db, "UPDATE `rpcs3_compatibility`.`game_patch` SET `touched` = '{$db_date}', `patch` = '{$db_patch}'
			WHERE `wiki_id` = '{$db_id}' AND `version` = '{$db_version}'; ");
		}
	}
}
