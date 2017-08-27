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

if ($get['a'] == 'updateThreadsCache') { 
	$startA = getTime();
	cacheThreadValidity(false);
	$finishA = getTime();
	$message = "<p class=\"compat-tx1-criteria\"><b>Debug mode:</b> Forced update on threads cache (".round(($finishA - $startA), 4)."s).</p>";
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
	FROM rpcs3_compatibility.rpcs3 AS t1
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
?>