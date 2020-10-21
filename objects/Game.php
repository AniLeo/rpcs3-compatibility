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

	public $key;        // Int
	public $title;      // String
	public $title2;     // String
	public $status;     // Int
	public $date;       // String
	public $commit;     // String
	public $pr;         // Int
	public $network;    // Int
	public $wikiID;     // Int
	public $wikiTitle;  // String
	public $IDs;        // [("gid" => String, "tid" => Int, "latest" => String)]

	function __construct(&$a_ids, &$a_wiki, $key, $maintitle, $alternativetitle, $status, $date, $network, $wiki, $pr, $commit)
	{
		$this->key = $key;

		$this->title = $maintitle;
		$this->title2 = $alternativetitle;

		$this->status = getStatusID($status);

		$this->date = $date;

		$this->commit = $commit;

		if (!is_null($pr))
			$this->pr = (int) $pr;

		$this->network = (int) $network;

		if (!is_null($a_wiki) && !is_null($wiki)) {
			$this->wikiID = (int) $wiki;
			$this->wikiTitle = urlencode($a_wiki[$wiki]);
		}

		if (!is_null($a_ids))
			foreach ($a_ids[$key] as $id)
				$this->IDs[] = $id;
	}


	/**
		* rowToGame
		* Obtains a Game from given MySQL Row.
		*
		* @param object  $row       The MySQL Row (returned by mysqli_fetch_object($query))
		* @param object  $a_ids     Array containing key => Game and Thread IDs to be included on the Game object
		* @param object  $a_wiki    Wiki Page ID => Page Title cache
		*
		* @return object $game      Game fetched from given Row
		*/
	public static function rowToGame($row, &$a_ids, &$a_wiki)
	{
		return new Game($a_ids, $a_wiki, $row->key, $row->game_title, $row->alternative_title,
		$row->status, $row->last_update, $row->network, $row->wiki, $row->pr, $row->build_commit);
	}

	/**
		* queryToGames
		* Obtains array of Games from given MySQL Query.
		*
		* @param object  $query        The MySQL Query (returned by mysqli_query())
		* @param bool    $wikiData     Whether to include wiki related data
		*
		* @return array  $array        Array of Games fetched from given Query
		*/
	public static function queryToGames($query, bool $wikiData = true) : array
	{
		$db = getDatabase();

		$a_games = array();

		if (mysqli_num_rows($query) === 0)
			return $a_games;

		if ($wikiData) {
			$a_wiki = array();
			$q_wiki = mysqli_query($db, "SELECT `page_id`, `page_title` FROM `rpcs3_wiki`.`page` WHERE `page_namespace` = 0 ;");
			while ($row = mysqli_fetch_object($q_wiki))
				$a_wiki[$row->page_id] = $row->page_title;
		} else {
			$a_wiki = NULL;
		}

		// Get GIDs and TIDs
		$c_ids = "SELECT * FROM `game_id` WHERE ";
		for ($i = 0; $row = mysqli_fetch_object($query); $i++) {
			if ($i > 0)
				$c_ids .= " OR ";
			$c_ids .= " `key` = {$row->key} ";
		}
		$c_ids .= " ORDER BY `gid` ASC ";

		// All (or most) games being fetch
		if (mysqli_num_rows($query) > 1000)
			$q_ids = mysqli_query($db, "SELECT * FROM `game_id` ORDER BY `gid` ASC");
		else
			$q_ids = mysqli_query($db, $c_ids);

		$a_ids = array();
		while ($row = mysqli_fetch_object($q_ids))
			$a_ids[$row->key][] = array("gid" => $row->gid, "tid" => $row->tid, "latest" => $row->latest_ver);

		mysqli_data_seek($query, 0);
		while ($row = mysqli_fetch_object($query))
			$a_games[] = self::rowToGame($row, $a_ids, $a_wiki);

		mysqli_close($db);
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
		*/
	public static function sort(&$array, int $type, string $order)
	{
		global $a_status;

		if ($order !== 'a' && $order !== 'd')
		{
			return;
		}

		$sorted = array();

		/*
		 * Game Title and Date
		 */
		if ($type === 2 || $type === 4) {

			// Temporary arrays to store game titles and original keys respectively
			$values = array();

			if ($type === 2) {
				foreach ($array as $key => $game)
					$values[$key] = $game->title;
			} else {
				foreach ($array as $key => $game)
					$values[$key] = $game->date;
			}

			// Alphabetical case-insensitive sort
			natcasesort($values);

			// Reverse array if we want DESC order
			if ($order === 'd')
				$values = array_reverse($values);

			// Move all entries from given array to a new sorted array in the correct order
			foreach ($values as $key => $value)
				$sorted[] = $array[$key];

		}

		/*
		 * Status
		 */
		if ($type == 3) {

			if ($order === 'a') {
				$i = 1;
				$limit = count($a_status);
			} else /* if ($order === 'd') */ {
				$i = count($a_status);
				$limit = 1;
			}

			for ($i; $order === 'a' ? $i <= $limit : $i >= $limit; $order === 'a' ? $i++ : $i--) {
				foreach ($array as $key => $game) {
					if ($game->status === $i) {
						$sorted[] = $game;
						unset($array[$key]);
					}
				}
			}

		}

		$array = $sorted;
	}

}
