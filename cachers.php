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

if(!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");


function cacheWindowsBuilds($full = false) {
	global $c_github;
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	// This takes a while to do...
	set_time_limit(60*60*3); // 3 hours
	
	if (!$full) {
		// Get date from last merged PR. Subtract 1 day to it and check new merged PRs since then.
		// Note: If master builds are disabled we need to remove WHERE type = 'branch'
		$mergeDateQuery = mysqli_query($db, "SELECT merge_datetime FROM builds_windows WHERE type = 'branch' ORDER BY merge_datetime DESC LIMIT 1;");
		$row = mysqli_fetch_object($mergeDateQuery);
		
		$merge_datetime = date_create($row->merge_datetime);
		date_sub($merge_datetime, date_interval_create_from_date_string('1 day'));
		$date = date_format($merge_datetime, 'Y-m-d');
	}	

	if ($full) { 
		// Get last page from GitHub PR lists: For first time run, only works when there are several pages of PRs.
		$date = '2015-08-10'; // 2015-08-10
	}
	
	// Get number of PRs
	$content = file_get_contents("{$c_github}/pulls?utf8=%E2%9C%93&q=is%3Apr%20is%3Amerged%20sort%3Amerged-asc%20merged%3A%3E{$date}");
	$step_1 = explode("<div class=\"table-list-header-toggle states float-left pl-3\">", $content);
	$step_2 = explode("</div>" , $step_1[1]);
	$step_3 = explode("</svg>" , $step_2[0]);
	$step_4 = explode(" Total" , $step_3[1]);
	$PRs = $step_4[0];
	
	// Remove comma from numbers above 999
	// Otherwise string to int implicit cast evaluates to 0
	$PRs = str_replace(',', '', $PRs);

	if ($PRs == 0) {
		// No PRs to cache, end here
		return;
	}
	
	$pages = (int)(($PRs / 25)+1);
	
	$a_PR = array();
	
	// Loop through all pages and get PR information
	for ($i=1; $i<=$pages; $i++) {
		$content = file_get_contents("https://github.com/RPCS3/rpcs3/pulls?page={$i}&q=is%3Apr+is%3Amerged+sort%3Amerged-asc+merged%3A%3E{$date}&utf8=%E2%9C%93");
		
		$step_1 = explode("\" class=\"link-gray-dark v-align-middle no-underline h4 js-navigation-open\">", $content);
		
		$a = 0; // Current PR (page relative)
		$b = 0; // Current exploded iteration
		
		while ($a<25) {
			$pr = substr($step_1[$a], -4); // Future proof: Remember that this needs to be changed to -5 after PR #9999
			
			// At the end it prints something like l>. We know when it's not a number that it reached the end.
			if (!ctype_digit($pr)) {
				break 2;
			}
			
			// If PR isn't already in then add it, else ignore
			if (!in_array($pr, $a_PR)) {
				
				// No need to escape here, we break before if it's not numeric only
				$PRQuery = mysqli_query($db, "SELECT * FROM builds_windows WHERE pr = {$pr} LIMIT 1; ");
				$a_PR[]  = $pr;
				
				// If PR isn't cached and we're not in full mode, then just DO IT!
				if ( (mysqli_num_rows($PRQuery) === 0 && !$full) || $full ) {
					
					// Grab pull request information from GitHub REST API (v3)
					$pr_info = getJSON("https://api.github.com/repos/rpcs3/rpcs3/pulls/{$pr}");
					
					// Check if we aren't rate limited
					if (array_key_exists('merge_commit_sha', $pr_info)) {
					
						// Merge time, Creation time, Commit SHA
						$merge_datetime = $pr_info->merged_at;
						$start_datetime = $pr_info->created_at;
						$commit = $pr_info->merge_commit_sha;
						
						// Additions, deletions, changed files
						$additions = $pr_info->additions;
						$deletions = $pr_info->deletions;
						$changed_files = $pr_info->changed_files;

						// Content for the commits page starting at the commit we obtained
						$content_commit = file_get_contents("https://github.com/RPCS3/rpcs3/commits/{$commit}");
						
						// Get first commit data only
						$start = "<div class=\"commit-meta commit-author-section";
						$end = "<div class=\"commit-links-cell table-list-cell\">";
						$content_commit = explode($end, explode($start, $content_commit)[1])[0];
						
						// Section that contains AppVeyor data that we want
						$start1 = "\"AppVeyor CI (@appveyor) generated this status.\"";
						$end1 = "</svg>";
						$type = "pr_alt_nocheck"; // Commit doesn't contain any checks
						
						if (strpos($content_commit, "\"AppVeyor CI (@appveyor) generated this status.\"") !== false) {
							
							$appveyor = explode($end1, explode($start1, $content_commit)[1])[0]; 
							
							// Get build type
							if (strpos($appveyor, '/branch') !== false) { $type = "branch"; } // Rebuilt on master after merge
							elseif (strpos($appveyor, '/pr') !== false) { $type = "pr"; }     // Last pull request artifact before merge
							
						} else {
							sleep(10); // It probably tried to recache the second it was merged and it caught the limbo of nocheck

							// Retry
							$content_commit = file_get_contents("https://github.com/RPCS3/rpcs3/commits/{$commit}");
							$content_commit = explode($end, explode($start, $content_commit)[1])[0];
							
							if (strpos($content_commit, "\"AppVeyor CI (@appveyor) generated this status.\"") !== false) {
							
								$appveyor = explode($end1, explode($start1, $content_commit)[1])[0]; 
								
								// Get build type
								if (strpos($appveyor, 'branch') !== false) { $type = "branch"; } // Rebuilt on master after merge
								elseif (strpos($appveyor, 'pr') !== false) { $type = "pr"; }     // Last pull request artifact before merge
							}
						}
						
						$status = "unknown";

						if ($type == "unknown" || $type == "pr_alt_nocheck") {
							// If code reaches here, do it the old way
							
							// Page content for the current PR
							$content_pr = file_get_contents("https://github.com/RPCS3/rpcs3/pull/{$pr}");
							
							$start = "<a href=\"https://ci.appveyor.com/project/rpcs3/rpcs3/build/";
							$end = "\" class=\"ml-2\">";
							$build = explode($end, explode($start, $content_pr)[1])[0]; 
							$status = "Succeeded";
						} else {
							$start = "<a class=\"status-actions\" href=\"https://ci.appveyor.com/project/rpcs3/rpcs3/build/";
							$end = "\">Details</a>";
							$build = explode($end, explode($start, $content_commit)[1])[0];

							// Get build status
							/*
							AppVeyor build succeeded
							Waiting for AppVeyor build to complete
							AppVeyor build failed
							AppVeyor was unable to build non-mergeable pull request
							*/
							if (strpos($appveyor, 'Waiting') !== false) {
								$status = "Building";
							} elseif (strpos($appveyor, 'succeeded') !== false) {
								$status = "Succeeded";
							} elseif (strpos($appveyor, 'failed') !== false || strpos($appveyor, 'unable') !== false) {
								$status = "Failed";
							}						
						}
						
						// If listed build is failed/unknown, force using old method again
						if ($status == "unknown" || $status == "Failed") {
							$start = "<a class=\"status-actions\" href=\"https://ci.appveyor.com/project/rpcs3/rpcs3/build/";
							$end = "\">Details</a>";
							$build = explode($end, explode($start, $content_commit)[1])[0];
							if ($status == "Failed") {
								$type = "pr_alt_failed";
							} else {
								$type = "pr_alt";
							}
							$status = "Succeeded";
						}

						// Only caches if the post-merge build has been successfully built.
						if ($status == "Succeeded" && $build != '1.0.106' /* Blacklist first non-artifacted build */) {
							
							// 0 - JobID, 1 - Filename, 2 - Size; 3 - Author; 4 - Checksum
							$data = getAppVeyorData($build);
							
							// Checksum support for newer builds
							if (array_key_exists(4, $data)) {
								$checksum_insert1 = ", `checksum`";
								$checksum_insert2 = ", '".mysqli_real_escape_string($db, $data[4])."'";
								$checksum_update = ", `checksum` = '".mysqli_real_escape_string($db, $data[4])."'";
							} else {
								$checksum_insert1 = '';
								$checksum_insert2 = '';
								$checksum_update = '';
							}
							
							if (mysqli_num_rows(mysqli_query($db, "SELECT * FROM builds_windows WHERE pr = {$pr} LIMIT 1; ")) == 1) {
								$cachePRQuery = mysqli_query($db, "UPDATE `builds_windows` SET 
								`commit` = '".mysqli_real_escape_string($db, $commit)."', 
								`type` = '{$type}', 
								`author` = '".mysqli_real_escape_string($db, $data[3])."', 
								`start_datetime` = '{$start_datetime}',
								`merge_datetime` = '{$merge_datetime}', 
								`appveyor` = '{$build}', 
								`buildjob` = '".mysqli_real_escape_string($db, $data[0])."', 
								`filename` = '".mysqli_real_escape_string($db, $data[1])."', 
								`additions` = '{$additions}', 
								`deletions` = '{$deletions}', 
								`changed_files` = '{$changed_files}', 
								`size` = '".mysqli_real_escape_string($db, $data[2])."', 
								{$checksum_update} 
								WHERE `pr` = '{$pr}' LIMIT 1;");
							} else {
								$cachePRQuery = mysqli_query($db, "INSERT INTO `builds_windows` (`pr`, `commit`, `type`, `author`, `start_datetime`, `merge_datetime`, `appveyor`, `buildjob`, `filename`, `additions`, `deletions`, `changed_files`, `size`{$checksum_insert1}) 
								VALUES ('{$pr}', '".mysqli_real_escape_string($db, $commit)."', '{$type}', '".mysqli_real_escape_string($db, $data[3])."', '{$start_datetime}', '{$merge_datetime}', '{$build}', '".mysqli_real_escape_string($db, $data[0])."', '".mysqli_real_escape_string($db, $data[1])."', '{$additions}', '{$deletions}', '{$changed_files}', '".mysqli_real_escape_string($db, $data[2])."'{$checksum_insert2}); ");
							}
							
							// Recache commit => pr cache
							cacheCommitCache();
						} else {
							// If Building then we wait for the next script run...
						}
					}
				}
				$a++;
			}
			$b++;
		}
	}
		
	mysqli_close($db);
}


function cacheInitials() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$theQuery = mysqli_query($db, "SELECT game_title FROM game_list;");

	while($row = mysqli_fetch_object($theQuery)) {
		
		// Divide game title by spaces between words
		$w = explode(" ", $row->game_title);
		$initials = "";
		
		foreach($w as $w) {
			
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
			
			// For .hack//Versus...
			if (strpos($w, ".") === 0) {
				// explode() expects parameter 2 to be string, array given
				$hv = explode("//", explode(".", $w));
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
	
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

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


function cacheStatusModule($count = true) {
	if ($count) {
		$f_status = fopen(__DIR__.'/cache/mod.status.count.php', 'w');
		fwrite($f_status, "\n<!-- START: Status Module -->\n<!-- This file is automatically generated -->\n".generateStatusModule()."\n<!-- END: Status Module -->\n");
		fclose($f_status);
	} else {
		$f_status = fopen(__DIR__.'/cache/mod.status.nocount.php', 'w');
		fwrite($f_status, "\n<!-- START: Status Module -->\n<!-- This file is automatically generated -->\n".generateStatusModule(false)."\n<!-- END: Status Module -->\n");
		fclose($f_status);
	}
}


function cacheCommitCache() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$a_cache = array();

	// Fetch all used commits => pull requests from builds_windows table
	// This is faster than verifying one by one per row on storeResults()
	$q_builds = mysqli_query($db, "SELECT pr,commit FROM builds_windows LEFT JOIN game_list on
	SUBSTR(commit, 1, 7) = SUBSTR(build_commit, 1, 7) 
	WHERE build_commit IS NOT NULL 
	GROUP BY commit 
	ORDER BY merge_datetime DESC;");
	while ($row = mysqli_fetch_object($q_builds)) {
		$a_cache[substr($row->commit, 0, 7)] = array($row->commit, $row->pr);
	}
	
	$f_commits = fopen(__DIR__.'/cache/a_commits.json', 'w');
	fwrite($f_commits, json_encode($a_cache));
	fclose($f_commits);
	
	mysqli_close($db);
}


function cacheStatusCount() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
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

