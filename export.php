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
if (!@include_once('functions.php')) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/objects/Game.php")) throw new Exception("Compat: Game.php is missing. Failed to include Game.php");


/**
* @return array<string, mixed> $results
*/
function exportDatabase() : array
{
    global $c_maintenance, $a_status;

    if ($c_maintenance)
    {
        $results['return_code'] = -2;
        return $results;
    }

    $db = get_database("compat");
    $q_games = mysqli_query($db, "SELECT * FROM `game_list`;");

    if (is_bool($q_games))
    {
        $results['return_code'] = -1;
        return $results;
    }

    $games = Game::query_to_games($q_games);
    Game::import_update_tags($games);
    mysqli_close($db);

    if (empty($games))
    {
        $results['return_code'] = -1;
        return $results;
    }

    $results['return_code'] = 0;

    foreach ($games as $game)
    {
        foreach ($game->game_item as $item)
        {
            $a_data = array(
                'status' => $a_status[$game->status]['name'],
                'date' => $game->date
            );

            if (!is_null($item->update))
                $a_data['update'] = $item->update;

            $a_data['patchsets'] = $item->tags;

            $results['results'][$item->game_id] = $a_data;
        }
    }

    return $results;
}
