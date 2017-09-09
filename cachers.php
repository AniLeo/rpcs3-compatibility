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


function cacheWindowsBuilds($full = false){
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	// This takes a while to do...
	set_time_limit(60*60);
	
	if (!$full) {
		// Get date from last merged PR. Subtract 1 day to it and check new merged PRs since then.
		$mergeDateQuery = mysqli_query($db, "SELECT merge_datetime FROM builds_windows ORDER BY merge_datetime DESC LIMIT 1;");
		$row = mysqli_fetch_object($mergeDateQuery);
		
		$merge_datetime = date_create($row->merge_datetime);
		date_sub($merge_datetime, date_interval_create_from_date_string('1 day'));
		$date = date_format($merge_datetime, 'Y-m-d');
		$last_page = 1;
	}	
	
	if ($full) {
		// Get last page from GitHub PR lists: For first time run, only works when there are several pages of PRs.
		$date = '2015-08-10';
		$content = file_get_contents("https://github.com/RPCS3/rpcs3/pulls?utf8=%E2%9C%93&q=is%3Apr%20is%3Amerged%20sort%3Acreated-asc%20merged%3A%3E{$date}");
		$step_1 = explode("<span class=\"gap\">&hellip;</span>", $content);
		$step_2 = explode("<a class=\"next_page\" rel=\"next\"" , $step_1[1]);
		$step_3 = explode(">" , $step_2[0]);
		$step_4 = explode("</a" , $step_3[3]);
		$last_page = $step_4[0];
	}

	$a_PR = array();
	
	// Loop through all pages and get PR information
	for ($i=1; $i<=$last_page; $i++) {
		$content = file_get_contents("https://github.com/RPCS3/rpcs3/pulls?page={$i}&q=is%3Apr+is%3Amerged+sort%3Acreated-asc+merged%3A%3E{$date}&utf8=%E2%9C%93");
		
		$step_1 = explode("\" class=\"link-gray-dark no-underline h4 js-navigation-open\">", $content);
		
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
				array_push($a_PR, $pr);
				
				// If PR isn't cached, then just DO IT!
				if (mysqli_num_rows($PRQuery) === 0) {
					
					// Content for the current PR
					$content_pr = file_get_contents("https://github.com/RPCS3/rpcs3/pull/{$pr}");
					
					// Author
					$start = " class=\"author\">";
					$end = "</a>";
					$author = explode($end, explode($start, $content_pr)[1])[0]; 
					
					// Merge Datetime
					$e_datetime = explode("<relative-time datetime=\"", $content_pr);
					$merge_datetime = explode("\"", $e_datetime[1])[0];
					$start_datetime = explode("\"", $e_datetime[2])[0];
					
					// Commit
					$start = "merged commit <a href=\"/RPCS3/rpcs3/commit/";
					$end = "\"><code class=\"discussion-item-entity\">";
					$commit = explode($end, explode($start, $content_pr)[1])[0]; 

					// Content for the commits page starting at the commit we obtained
					$content_commit = file_get_contents("https://github.com/RPCS3/rpcs3/commits/{$commit}");
					
					// Get first commit data only
					$start = "<div class=\"commit-meta commit-author-section\">";
					$end = "<div class=\"commit-links-cell table-list-cell\">";
					$content_commit = explode($end, explode($start, $content_commit)[1])[0];
					
					$type = "unknown";
					
					if (strpos($content_commit, '<a href="http://www.appveyor.com" class="d-inline-block tooltipped tooltipped-e muted-link mr-2" aria-label="AppVeyor CI (@appveyor) generated this status.">') !== false) {
						// Section that contains AppVeyor data that we want
						$start = "\"AppVeyor CI (@appveyor) generated this status.\">";
						$end = "</svg>";
						$appveyor = explode($end, explode($start, $content_commit)[1])[0]; 
						
						// Get build type
						if (strpos($appveyor, 'branch') !== false) {
							$type = "branch"; // Rebuilt on master after merge
						} elseif (strpos($appveyor, 'pr') !== false) {
							$type = "pr";     // Last pull request artifact before merge
						}
					} else {
						$type = "pr_alt_nocheck"; // Commit doesn't contain any checks
					}
					
					$status = "unknown";

					if ($type == "unknown" || $type == "pr_alt_nocheck") {
						// If code reaches here, do it the old way
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
					if ($status == "Succeeded") {
						$cachePRQuery = mysqli_query($db, " INSERT INTO `builds_windows` (`pr`, `commit`, `type`, `author`, `start_datetime`, `merge_datetime`, `appveyor`) 
						VALUES ('{$pr}', '".mysqli_real_escape_string($db, $commit)."', '{$type}', '".mysqli_real_escape_string($db, $author)."', '{$start_datetime}', '{$merge_datetime}', '{$build}'); ");
					} else {
						// If Building then we wait for the next script run...
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

	$theQuery = mysqli_query($db, "SELECT game_title FROM ".db_table.";");

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
	$query = mysqli_query($db, "SELECT * FROM rpcs3; ");
	
	while($row = mysqli_fetch_object($query)) {
		$a_games[] = $row->game_id;
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
		$f_tested = fopen(__DIR__.'/tested.txt', 'w');
		fwrite($f_tested, $tested);
		fclose($f_tested);

		// Open untested.txt and write number of untested games in one line
		$f_untested = fopen(__DIR__.'/untested.txt', 'w');
		fwrite($f_untested, $untested);
		fclose($f_untested);
		
	} 
}


function cacheStatusModule($count = true) {
	if ($count) {
		$f_status = fopen(__DIR__.'/modules/mod.status.count.php', 'w');
		fwrite($f_status, "\n<!-- START: Status Module -->\n<!-- This file is automatically generated -->\n".generateStatusModule()."\n<!-- END: Status Module -->\n");
		fclose($f_status);
	} else {
		$f_status = fopen(__DIR__.'/modules/mod.status.nocount.php', 'w');
		fwrite($f_status, "\n<!-- START: Status Module -->\n<!-- This file is automatically generated -->\n".generateStatusModule(false)."\n<!-- END: Status Module -->\n");
		fclose($f_status);
	}
}
