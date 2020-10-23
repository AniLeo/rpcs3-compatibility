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
if (!@include_once(__DIR__."/../classes/class.Library.php")) throw new Exception("Compat: class.Library.php is missing. Failed to include class.Library.php");


// Profiler
Profiler::setTitle("Profiler: Library");

// Count number of entries for page calculation and cache results on array
Profiler::addData("Profiler: Count and Cache Entries");
$entries = 1;
$a_db = array();
$handle = fopen(__DIR__."/../ps3tdb.txt", "r");

if ($handle) {
	while (!feof($handle)) {
		$line = fgets($handle);

		if (!in_array(mb_substr($line, 0, 4), $a_filter))
			continue;

		if ( ($get['f'] == '' || strtolower(substr($line, 2, 1)) == $get['f'])
			&& ($get['t'] == '' || strtolower(substr($line, 0, 1)) == $get['t']) ) {
			$a_db[$entries] = array(mb_substr($line, 0, 9) => mb_substr($line, 12));
			$entries++;
		}
	}
	fclose($handle);
}

Profiler::addData("Profiler: Count Pages");
$pages = countPages($get, $entries);
Profiler::addData("Profiler: Get Current Page");
$currentPage = getCurrentPage($pages);

// If the above search returns no games in the selected categories, no need to waste database time
if ($a_db) {
	Profiler::addData("Profiler: Get All Database Game Entries");
	// Get all games in the database (ID => Data)
	$db = getDatabase();
	$games = Game::query_to_games(mysqli_query($db, "SELECT * FROM `game_list`"));
	$a_games = array();
	foreach ($games as $game)
		foreach ($game->game_item as $item)
			$a_games[$item->game_id] = array(
				'title' => $game->title,
				'thread' => $item->tid,
				'last_update' => $game->date,
				'pr' => $game->pr
			);
	mysqli_close($db);
}

Profiler::addData("--- / ---");
