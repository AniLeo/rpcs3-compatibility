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
if (!@include_once(__DIR__."/GameItem.php")) throw new Exception("Compat: GameItem.php is missing. Failed to include GameItem.php");


class Game
{
	public $key;        // Int
	public $title;      // String
	public $title2;     // String
	public $status;     // Int
	public $date;       // String
	public $network;    // Int
	public $pr;         // Int
	public $commit;     // String
	public $wiki_id;    // Int
	public $wiki_title; // String
	public $game_item;  // GameItem[]

	function __construct(int $key, string $title, ?string $title2, int $status, string $date,
	                     int $network, ?int $pr, ?string $commit, ?int $wiki_id, ?string $wiki_title)
	{
		$this->key        = $key;
		$this->title      = $title;
		$this->title2     = $title2;
		$this->status     = $status;
		$this->date       = $date;
		$this->commit     = $commit;
		$this->pr         = $pr;
		$this->network    = $network;
		$this->wiki_id    = $wiki_id;
		$this->wiki_title = $wiki_title;
	}

	// Get Wiki URL for this Game
	public function get_url_wiki() : ?string
	{
		// Prefer title based URL
		if (!is_null($this->wiki_title))
		{
			return "https://wiki.rpcs3.net/index.php?title=".urlencode($this->wiki_title);
		}
		// Fallback to ID based URL
		if (!is_null($this->wiki_id))
		{
			return "https://wiki.rpcs3.net/index.php?curid={$this->wiki_id}";
		}
		return null;
	}

	public function get_url_pr() : ?string
	{
		if (!is_null($this->pr))
		{
			return "https://github.com/RPCS3/rpcs3/pull/{$this->pr}";
		}
		return null;
	}

	// Import wiki related information to a Game array
	public static function import_wiki(array &$games) : void
	{
		$db = getDatabase();

		$a_wiki = array();
		$q_wiki = mysqli_query($db, "SELECT `page_id`, `page_title` FROM `rpcs3_wiki`.`page` WHERE `page_namespace` = 0; ");

		while ($row = mysqli_fetch_object($q_wiki))
		{
			$a_wiki[$row->page_id] = $row->page_title;
		}

		foreach ($games as $game)
		{
			if (is_null($game->wiki_id))
				continue;

			$game->wiki_title = $a_wiki[$game->wiki_id];
		}

		mysqli_close($db);
	}

	// Import Game Items to a Game array
	public static function import_game_items(array &$games) : void
	{
		$db = getDatabase();

		$a_items = array();
		$q_items = mysqli_query($db, "SELECT * FROM `game_id` ORDER BY `gid` ASC; ");

		while ($row = mysqli_fetch_object($q_items))
		{
			$a_items[$row->key][] = new GameItem($row->gid, $row->tid, $row->latest_ver);
		}

		foreach ($games as $game)
		{
			$game->game_item = $a_items[$game->key];
		}

		mysqli_close($db);
	}

	// Returns a Game array from a mysqli_result object
	public static function query_to_games(mysqli_result $query) : array
	{
		$a_games = array();

		if (mysqli_num_rows($query) === 0)
			return $a_games;

		while ($row = mysqli_fetch_object($query))
		{
			$a_games[] = new Game($row->key, $row->game_title, $row->alternative_title,
			getStatusID($row->status), $row->last_update, $row->network, $row->pr, $row->build_commit, $row->wiki, NULL);
		}

		self::import_game_items($a_games);

		return $a_games;
	}

	// Type of sorting (2:Title, 3:Status, 4:Date)
	// Order of sorting (a:ASC, d:DESC)
	public static function sort(array &$games, int $type, string $order) : void
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
		if ($type === 2 || $type === 4)
		{
			// Temporary arrays to store game titles and original keys respectively
			$values = array();

			if ($type === 2)
			{
				foreach ($games as $key => $game)
					$values[$key] = $game->title;
			}
			else
			{
				foreach ($games as $key => $game)
					$values[$key] = $game->date;
			}

			// Alphabetical case-insensitive sort
			natcasesort($values);

			// Reverse array if we want DESC order
			if ($order === 'd')
				$values = array_reverse($values);

			// Move all entries from given array to a new sorted array in the correct order
			foreach ($values as $key => $value)
				$sorted[] = $games[$key];
		}

		/*
		 * Status
		 */
		if ($type == 3)
		{
			if ($order === 'a')
			{
				$i = 1;
				$limit = count($a_status);
			}
			else /* if ($order === 'd') */
			{
				$i = count($a_status);
				$limit = 1;
			}

			for ($i; $order === 'a' ? $i <= $limit : $i >= $limit; $order === 'a' ? $i++ : $i--)
			{
				foreach ($games as $key => $game)
				{
					if ($game->status === $i)
					{
						$sorted[] = $game;
						unset($games[$key]);
					}
				}
			}
		}

		$games = $sorted;
	}
}
