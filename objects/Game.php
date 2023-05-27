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
if (!@include_once(__DIR__."/../functions.php"))
	throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/GameItem.php"))
	throw new Exception("Compat: GameItem.php is missing. Failed to include GameItem.php");
if (!@include_once(__DIR__."/GameUpdateTag.php"))
	throw new Exception("Compat: GameUpdateTag.php is missing. Failed to include GameUpdateTag.php");


class Game
{
	public  int     $key;
	public  string  $title;
	public ?string  $title2;
	public  int     $status;
	public  string  $date;
	public  int     $network;
	public  int     $move;
	public  int     $stereo_3d;
	public ?int     $pr;
	public ?string  $commit;
	public ?int     $wiki_id;
	public ?string  $wiki_title;
	/** @var array<GameItem> $game_item **/
	public  array   $game_item;

	function __construct( int    $key,
	                      string $title,
	                     ?string $title2,
	                      int    $status,
	                      string $date,
	                      int    $network,
	                      int    $move,
	                      int    $stereo_3d,
	                     ?int    $pr,
	                     ?string $commit,
	                     ?int    $wiki_id,
	                     ?string $wiki_title)
	{
		$this->key        = $key;
		$this->title      = $title;
		$this->title2     = $title2;
		$this->status     = $status;
		$this->date       = $date;
		$this->commit     = $commit;
		$this->pr         = $pr;
		$this->network    = $network;
		$this->move       = $move;
		$this->stereo_3d  = $stereo_3d;
		$this->wiki_id    = $wiki_id;
		$this->wiki_title = $wiki_title;
	}

	public function get_media_id() : ?string
	{
		foreach ($this->game_item as $item)
		{
			// Skip MRTC
			if ($item->get_media_id() === 'M')
				continue;

			return $item->get_media_id();
		}

		return null;
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

	/**
	* @param array<Game> $games
	*/
	public static function import_update_tags(array &$games) : void
	{
		$db = getDatabase();

		$a_tags = array();
		$q_tags = mysqli_query($db, "SELECT * FROM `game_update_tag`; ");

		mysqli_close($db);

		if (is_bool($q_tags))
			return;

		if (mysqli_num_rows($q_tags) === 0)
			return;

		while ($row = mysqli_fetch_object($q_tags))
		{
			// This should be unreachable unless the database structure is damaged
			if (!property_exists($row, "name") ||
					!property_exists($row, "popup") ||
					!property_exists($row, "signoff") ||
					!property_exists($row, "popup_delay") ||
					!property_exists($row, "min_system_ver"))
			{
				continue;
			}

			$a_tags[] = new GameUpdateTag($row->name,
			                              $row->popup,
			                              $row->signoff,
			                              $row->popup_delay,
			                              $row->min_system_ver);
		}

		GameUpdateTag::import_update_packages($a_tags);
		GameUpdateTag::import_update_changelogs($a_tags);
		GameUpdateTag::import_update_titles($a_tags);

		$a_tags_sorted = array();

		// Convert to associative array game_id => tags
		foreach ($a_tags as $tag)
		{
			$a_tags_sorted[substr($tag->tag_id, 0, 9)][] = $tag;
		}

		// For each game id, attach the tags array if it exists
		foreach ($games as $game)
		{
			foreach ($game->game_item as $item)
			{
				if (isset($a_tags_sorted[$item->game_id]))
				{
					$item->tags = $a_tags_sorted[$item->game_id];
				}
			}
		}
	}

	// Import wiki related information to a Game array
	/**
	* @param array<Game> $games
	*/
	public static function import_wiki(array &$games) : void
	{
		$db = getDatabase();

		$a_wiki = array();
		$q_wiki = mysqli_query($db, "SELECT `page_id`, `page_title`
		                             FROM   `rpcs3_wiki`.`page`
		                             WHERE  `page_namespace` = 0; ");

		if (is_bool($q_wiki))
		 	return;

		while ($row = mysqli_fetch_object($q_wiki))
		{
			// This should be unreachable unless the database structure is damaged
			if (!property_exists($row, "page_id") ||
					!property_exists($row, "page_title"))
			{
				continue;
			}

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
	/**
	* @param array<Game> $games
	*/
	public static function import_game_items(array &$games) : void
	{
		$db = getDatabase();

		$a_items = array();
		$q_items = mysqli_query($db, "SELECT *
		                              FROM `game_id`
		                              ORDER BY `gid` ASC; ");

		if (is_bool($q_items))
			return;

		while ($row = mysqli_fetch_object($q_items))
		{
			// This should be unreachable unless the database structure is damaged
			if (!property_exists($row, "key") ||
					!property_exists($row, "gid") ||
					!property_exists($row, "tid") ||
					!property_exists($row, "latest_ver"))
			{
				continue;
			}

			$a_items[$row->key][] = new GameItem($row->gid,
			                                     $row->tid,
			                                     $row->latest_ver);
		}

		foreach ($games as $game)
		{
			$game->game_item = $a_items[$game->key];
		}

		mysqli_close($db);
	}

	// Returns a Game array from a mysqli_result object
	/**
	* @return array<Game> $games
	*/
	public static function query_to_games(mysqli_result $query) : array
	{
		$a_games = array();

		if (mysqli_num_rows($query) === 0)
			return $a_games;

		while ($row = mysqli_fetch_object($query))
		{
			// This should be unreachable unless the database structure is damaged
			if (!property_exists($row, "key") ||
					!property_exists($row, "game_title") ||
					!property_exists($row, "alternative_title") ||
					!property_exists($row, "status") ||
					!property_exists($row, "last_update") ||
					!property_exists($row, "network") ||
					!property_exists($row, "move") ||
					!property_exists($row, "3d") ||
					!property_exists($row, "pr") ||
					!property_exists($row, "build_commit") ||
					!property_exists($row, "wiki") ||
					is_null(getStatusID($row->status)))
			{
				continue;
			}

			$a_games[] = new Game($row->key,
			                      $row->game_title,
			                      $row->alternative_title,
			                      getStatusID($row->status),
			                      $row->last_update,
			                      $row->network,
			                      $row->move,
			                      $row->{"3d"},
			                      $row->pr,
			                      $row->build_commit,
			                      $row->wiki,
			                      NULL);
		}

		self::import_game_items($a_games);

		return $a_games;
	}

	// Type of sorting (2:Title, 3:Status, 4:Date)
	// Order of sorting (a:ASC, d:DESC)
	/**
	* @param array<Game> $games
	*/
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
