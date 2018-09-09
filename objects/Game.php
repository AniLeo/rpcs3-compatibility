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


class Game {

	public $title;      // String
	public $title2;     // String
	public $status;     // Int
	public $date;       // String
	public $commit;     // String
	public $pr;         // Int
	public $wikiID;     // Int
	public $wikiTitle;  // String
	public $IDs;        // [(String, Int)]

	function __construct(&$a_cache, &$a_wiki, $maintitle, $alternativetitle, $status, $date, $wiki, $shortcommit,
	$gid_EU, $tid_EU, $gid_US, $tid_US, $gid_JP, $tid_JP, $gid_AS, $tid_AS, $gid_KR, $tid_KR, $gid_HK, $tid_HK) {

		$this->title = $maintitle;
		if (!is_null($alternativetitle))
			$this->title2 = $alternativetitle;

		$this->status = getStatusID($status);

		$this->date = $date;

		if (!is_null($a_wiki) && !is_null($wiki)) {
			$this->wikiID = $wiki;
			$this->wikiTitle = urlencode($a_wiki[$wiki]);
		}

		if (!is_null($a_cache) && ($shortcommit == '0' || !array_key_exists(substr($shortcommit, 0, 7), $a_cache))) {
			$this->commit = $shortcommit;
			$this->pr     = 0;
		} else {
			$this->commit = $a_cache[substr($shortcommit, 0, 7)][0];
			$this->pr     = $a_cache[substr($shortcommit, 0, 7)][1];
		}

		if (!is_null($gid_EU))
			$this->IDs[] = array($gid_EU, $tid_EU);

		if (!is_null($gid_US))
			$this->IDs[] = array($gid_US, $tid_US);

		if (!is_null($gid_JP))
			$this->IDs[] = array($gid_JP, $tid_JP);

		if (!is_null($gid_AS))
			$this->IDs[] = array($gid_AS, $tid_AS);

		if (!is_null($gid_KR))
			$this->IDs[] = array($gid_KR, $tid_KR);

		if (!is_null($gid_HK))
			$this->IDs[] = array($gid_HK, $tid_HK);

	}


	/**
		* rowToGame
		* Obtains a Game from given MySQL Row.
		*
		* @param object  $row       The MySQL Row (returned by mysqli_fetch_object($query))
		* @param object  $a_cache   Commit => PR cache
		* @param object  $a_wiki    Wiki Page ID => Page Title cache
		*
		* @return object $game      Game fetched from given Row
		*/
	public static function rowToGame($row, &$a_cache, &$a_wiki) {
		return new Game($a_cache, $a_wiki, $row->game_title, $row->alternative_title, $row->status, $row->last_update, $row->wiki, $row->build_commit,
		$row->gid_EU, $row->tid_EU, $row->gid_US, $row->tid_US, $row->gid_JP, $row->tid_JP, $row->gid_AS, $row->tid_AS, $row->gid_KR, $row->tid_KR, $row->gid_HK, $row->tid_HK);
	}

	/**
		* queryToGames
		* Obtains array of Games from given MySQL Query.
		*
		* @param object  $query        The MySQL Query (returned by mysqli_query())
		* @param bool    $commitData   Whether to include commit related data
		* @param bool    $wikiData     Whether to include wiki related data
		*
		* @return object $array        Array of Games fetched from given Query
		*/
	public static function queryToGames($query, $commitData = true, $wikiData = true) {
		$db = getDatabase();

		if ($commitData) {
			$a_cache = file_exists(__DIR__.'/../cache/a_commits.json') ? json_decode(file_get_contents(__DIR__.'/../cache/a_commits.json'), true) : cacheCommitCache();
		} else {
			$a_cache = NULL;
		}

		if ($wikiData) {
			$a_wiki = array();
			$q_wiki = mysqli_query($db, "SELECT `page_id`, `page_title` FROM `rpcs3_wiki`.`page` WHERE `page_namespace` = 0 ;");
			while ($row = mysqli_fetch_object($q_wiki))
				$a_wiki[$row->page_id] = $row->page_title;
		} else {
			$a_wiki = NULL;
		}

		mysqli_close($db);

		$a_games = array();
		while ($row = mysqli_fetch_object($query))
			$a_games[] = self::rowToGame($row, $a_cache, $a_wiki);

		return $a_games;
	}

	/**
		* sort
		* Sorts an array of Games.
		*
		* @param object  $array   The Games array that needs sorting
		* @param int     $type    Type of sorting (2:Title, 3:Status, 4:Date)
		* @param string  $order   Order of sorting (a:ASC, d:DESC)
		*
		* @return object $array   Sorted array of Games
		*/
	public static function sort(&$array, $type, $order) {
		global $a_status;

		$sorted = array();

		/*
		 * Game Title and Date
		 */
		if ($type == 2 || $type == 4) {

			// Temporary arrays to store game titles and original keys respectively
			$values = array();

			if ($type == 2) {
				foreach ($array as $key => $game)
					$values[$key] = $game->title;
			} else {
				foreach ($array as $key => $game)
					$values[$key] = $game->date;
			}

			// Alphabetical case-insensitive sort
			natcasesort($values);

			// Reverse array if we want DESC order
			if ($order == 'd')
				$values = array_reverse($values);

			// Move all entries from given array to a new sorted array in the correct order
			foreach ($values as $key => $value)
				$sorted[] = $array[$key];

		}

		/*
		 * Status
		 */
		if ($type == 3) {

			if ($order == 'a') {
				$i = 1;
				$limit = count($a_status);
			} elseif ($order == 'd') {
				$i = count($a_status);
				$limit = 1;
			}

			for ($i; $order == 'a' ? $i <= $limit : $i >= $limit; $order == 'a' ? $i++ : $i--) {
				foreach ($array as $key => $game) {
					if ($game->status == $i) {
						$sorted[] = $game;
						unset($array[$key]);
					}
				}
			}

		}

		$array = $sorted;
	}

}
