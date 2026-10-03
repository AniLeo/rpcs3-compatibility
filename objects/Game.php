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
if (!@include_once(__DIR__."/../functions.php"))  throw new Exception("Compat: Failed to include functions.php");
if (!@include_once(__DIR__."/GameItem.php"))      throw new Exception("Compat: Failed to include objects/GameItem.php");
if (!@include_once(__DIR__."/GameUpdateTag.php")) throw new Exception("Compat: Failed to include objects/GameUpdateTag.php");
if (!@include_once(__DIR__."/Build.php"))         throw new Exception("Compat: Failed to include objects/Build.php");


class Game
{
    public  int     $key;
    public  string  $type;
    public  int     $status;
    public  string  $date;
    public  int     $network;
    public  int     $move;
    public  int     $stereo_3d;
    public ?int     $pr;
    public ?string  $version;
    public ?string  $commit;
    public ?int     $wiki_id;
    /** @var array<GameItem> $game_item **/
    public  array   $game_item;

    function __construct( int    $key,
                          string $type,
                          int    $status,
                          string $date,
                          int    $network,
                          int    $move,
                          int    $stereo_3d,
                         ?int    $pr,
                         ?string $version,
                         ?string $commit,
                         ?int    $wiki_id)
    {
        $this->key        = $key;
        $this->type       = $type;
        $this->status     = $status;
        $this->date       = $date;
        $this->pr         = $pr;
        $this->version    = $version;
        $this->commit     = $commit;
        $this->network    = $network;
        $this->move       = $move;
        $this->stereo_3d  = $stereo_3d;
        $this->wiki_id    = $wiki_id;
    }

    public function title() : string
    {
        return $this->game_item[0]->title;
    }

     /** @return array<string> **/
    public function other_titles() : array
    {
        $titles = array();

        foreach ($this->game_item as $item)
        {
            if ($item->title !== $this->title() && !in_array($item->title, $titles, true))
                $titles[] = $item->title;
        }
        
        return $titles;
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
    public static function import_update_tags(array &$games, mysqli $db) : void
    {
        $a_game_ids = array();

        foreach ($games as $game)
        {
            foreach ($game->game_item as $game_item)
            {
                $a_game_ids[$game_item->game_id] = true;
            }
        }

        if (empty($a_game_ids))
        {
            return;
        }

        $clauses = array();
        foreach (array_keys($a_game_ids) as $gid)
        {
            $clauses[] = "`name` LIKE '" . mysqli_real_escape_string($db, $gid) . "%'";
        }

        $cmd_where = implode(" OR ", $clauses);
        $q_tags = mysqli_query($db, "SELECT `name`, `popup`, `signoff`, `popup_delay`, `min_system_ver`
                                    FROM `game_update_tag`
                                    WHERE {$cmd_where}");

        if (is_bool($q_tags))
        {
            trigger_error("[COMPAT] game_update_tag query failed: " . mysqli_error($db), E_USER_WARNING);
            return;
        }

        if (mysqli_num_rows($q_tags) === 0)
        {
            return;
        }

        $a_tags = array();
        while ($row = mysqli_fetch_object($q_tags))
        {
            $a_tags[] = new GameUpdateTag($row->name,
                                        $row->popup,
                                        $row->signoff,
                                        $row->popup_delay,
                                        $row->min_system_ver);
        }

        GameUpdateTag::import_update_packages($a_tags, $db);
        GameUpdateTag::import_update_changelogs($a_tags, $db);
        GameUpdateTag::import_update_titles($a_tags, $db);

        $a_tags_sorted = array();

        foreach ($a_tags as $tag)
        {
            $a_tags_sorted[substr($tag->tag_id, 0, 9)][] = $tag;
        }

        foreach ($games as $game)
        {
            foreach ($game->game_item as $item)
            {
                if (isset($a_tags_sorted[$item->game_id]))
                    $item->tags = $a_tags_sorted[$item->game_id];
            }
        }
    }

    // Import Game Items to a Game array
    /**
    * @param array<Game> $games
    */
    public static function import_game_items(array &$games, mysqli $db) : void
    {
        if (empty($games))
            return;

        $keys = array();
        foreach ($games as $game)
            $keys[] = (int) $game->key;

        $in = implode(",", $keys);
        $q_items = mysqli_query($db, "SELECT `key`, `gid`, `game_title`, `tid`, `latest_ver`
                                      FROM `game_id`
                                      WHERE `key` IN ({$in})
                                      ORDER BY `gid` ASC; ");

        if (is_bool($q_items))
            return;

        $a_items = array();

        while ($row = mysqli_fetch_object($q_items))
        {
            // This should be unreachable unless the database structure is damaged
            if (!property_exists($row, "key") ||
                !property_exists($row, "gid") ||
                !property_exists($row, "game_title") ||
                !property_exists($row, "tid") ||
                !property_exists($row, "latest_ver"))
            {
                continue;
            }

            $a_items[$row->key][] = new GameItem($row->gid,
                                                 $row->game_title,
                                                 $row->tid,
                                                 $row->latest_ver);
        }

        foreach ($games as $game)
        {
            $game->game_item = $a_items[$game->key];
        }
    }

    // Returns a Game array from a mysqli_result object
    /**
    * @return array<Game> $games
    */
    public static function query_to_games(mysqli_result $query, mysqli $db) : array
    {
        $a_games = array();

        if (mysqli_num_rows($query) === 0)
            return $a_games;

        while ($row = mysqli_fetch_object($query))
        {
            // This should be unreachable unless the database structure is damaged
            if (!property_exists($row, "key") ||
                !property_exists($row, "type") ||
                !property_exists($row, "status") ||
                !property_exists($row, "last_update") ||
                !property_exists($row, "network") ||
                !property_exists($row, "move") ||
                !property_exists($row, "3d") ||
                !property_exists($row, "pr") ||
                !property_exists($row, "version") ||
                !property_exists($row, "build_commit") ||
                !property_exists($row, "wiki") ||
                is_null(getStatusID($row->status)))
            {
                continue;
            }

            $a_games[] = new Game($row->key,
                                  $row->type,
                                  getStatusID($row->status),
                                  $row->last_update,
                                  $row->network,
                                  $row->move,
                                  $row->{"3d"},
                                  $row->pr,
                                  $row->version,
                                  $row->build_commit,
                                  $row->wiki);
        }

        self::import_game_items($a_games, $db);

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
                    $values[$key] = $game->title();
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
