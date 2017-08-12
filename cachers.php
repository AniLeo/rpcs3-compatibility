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


/**
  * cacheCommits
  *
  * Caches the validity of commits obtained by isValidCommit.
  * This is required because validating takes too much time and kills page load times.
  *
  * @param bool $mode Recache mode (true = full / false = partial)
  *
  */
function cacheCommits($mode) {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$commitQuery = mysqli_query($db, "SELECT t1.build_commit, t2.commit_id, t2.valid 
	FROM ".db_table." AS t1 LEFT JOIN commit_cache AS t2 on t1.build_commit != t2.commit_id 
	WHERE build_commit != '0' GROUP BY build_commit; ");

	while($row = mysqli_fetch_object($commitQuery)) {
		
		$cid = mysqli_real_escape_string($db, $row->build_commit);
		$checkQuery = mysqli_query($db, "SELECT * FROM commit_cache WHERE commit_id = '{$cid}' LIMIT 1; ");
		$row2 = mysqli_fetch_object($checkQuery);
		
		// Partial recache: If value isn't cached, then cache it 
		if (mysqli_num_rows($checkQuery) === 0) {
			$valid = isValidCommit($row->build_commit);
			mysqli_query($db, "INSERT INTO commit_cache (commit_id, valid) VALUES ('{$cid}', '{$valid}'); ");
		} 
		
		// Full recache: Updates currently existent entries (commits don't dissappear, this option shouldn't be needed...)
		elseif ($mode) {
			$valid = isValidCommit($row->build_commit);
			// If value is cached but differs on validation, update it	
			if ($row2->valid != $valid) {
				mysqli_query($db, "UPDATE commit_cache SET valid = '{$valid}' WHERE commit_id = '{$cid}' LIMIT 1; ");
			}
		}
	}
	mysqli_close($db);
}


function cacheThreadValidity($mode = false) {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$threadCommand = "SELECT t1.game_id, t1.thread_id, t2.tid, t2.valid 
	FROM ".db_table." AS t1 
	LEFT JOIN cache_threads AS t2 
	ON t1.thread_id = t2.tid ";
	
	// Partial recache: Only check for non-valid or uncached values
	if (!$mode) {
		$threadCommand .= " WHERE valid IS null OR valid != 1 ";
	} // else: Full recache: Check all listed threads
	
	$threadQuery = mysqli_query($db, $threadCommand);
	
	while ($row = mysqli_fetch_object($threadQuery)) {
		
		$tid = mysqli_real_escape_string($db, $row->thread_id);
		$checkQuery = mysqli_query($db, "SELECT * FROM cache_threads WHERE tid = '{$tid}' LIMIT 1; ");		
		$valid = isValidThread($tid, $row->game_id);
		
		if (mysqli_num_rows($checkQuery) === 0) {
			mysqli_query($db, "INSERT INTO cache_threads (tid, valid) VALUES ('{$tid}', '{$valid}'); ");
		} elseif ($row->valid != $valid) {
			mysqli_query($db, "UPDATE cache_threads SET valid = '{$valid}' WHERE (tid = '{$tid}'); ");
		}
	
	}
	mysqli_close($db);
}


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
				
					$content2 = file_get_contents("https://github.com/RPCS3/rpcs3/pull/{$pr}");
					
					$e_author = explode(" class=\"author\">", $content2);
					$author = explode("</a>", $e_author[1])[0];
					
					$e_datetime = explode("<relative-time datetime=\"", $content2);
					$merge_datetime = explode("\"", $e_datetime[1])[0];
					$start_datetime = explode("\"", $e_datetime[2])[0];
					
					// TODO: Handle false positives from where people comment links
					// <a href="https://ci.appveyor.com/project/rpcs3/rpcs3/build/ID" class="ml-2">Details</a>
					$e_appveyor = explode("<a href=\"https://ci.appveyor.com/project/rpcs3/rpcs3/build/", $content2);
					$build = explode("\" class=\"ml-2\">", $e_appveyor[1])[0];
					
					// Only caches if the post-merge build has been successefully built.
					if (!empty($build)) {
						$cachePRQuery = mysqli_query($db, " INSERT INTO `builds_windows` (`pr`, `author`, `start_datetime`, `merge_datetime`, `appveyor`) 
						VALUES ('{$pr}', '".mysqli_real_escape_string($db, $author)."', '{$start_datetime}', '{$merge_datetime}', '{$build}'); ");
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

?>