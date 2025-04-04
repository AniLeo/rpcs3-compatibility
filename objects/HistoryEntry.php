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


class HistoryEntry
{
    public  string   $title;
    public ?string   $title2;
    public ?int      $old_status;
    public  int      $new_status;
    public ?string   $old_date;
    public  string   $new_date;

    public GameItem $game_item;

    function __construct( string $title,
                         ?string $title2,
                         ?string $old_status,
                          string $new_status,
                         ?string $old_date,
                          string $new_date,
                          string $gid,
                          int    $tid)
    {
        $this->title = $title;
        $this->title2 = $title2;

        if (!is_null($old_status))
            $this->old_status = getStatusID($old_status);
        else
            $this->old_status = null;

        $this->old_date = $old_date;

        if (getStatusID($new_status))
            $this->new_status = getStatusID($new_status);
        else
            $this->new_status = 0;

        $this->new_date = $new_date;

        $this->game_item = new GameItem($gid, $tid, null);
    }

    /**
    * @return array<HistoryEntry> $entries
    */
    public static function query_to_history_entry(mysqli_result $query) : array
    {
        $a_entries = array();

        while ($row = mysqli_fetch_object($query))
        {
            // This should be unreachable unless the database structure is damaged
            if (!property_exists($row, "game_title") ||
                !property_exists($row, "alternative_title") ||
                !property_exists($row, "old_status") ||
                !property_exists($row, "new_status") ||
                !property_exists($row, "old_date") ||
                !property_exists($row, "new_date") ||
                !property_exists($row, "gid") ||
                !property_exists($row, "tid"))
            {
                return array();
            }

            $a_entries[] = new HistoryEntry($row->game_title,
                                            $row->alternative_title,
                                            $row->old_status,
                                            $row->new_status,
                                            $row->old_date,
                                            $row->new_date,
                                            $row->gid,
                                            $row->tid);
        }

        return $a_entries;
    }
}
