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
if (!@include_once(__DIR__."/../functions.php"))    throw new Exception("Compat: Failed to include functions.php");
if (!@include_once(__DIR__."/../objects/Game.php")) throw new Exception("Compat: Failed to include objects/Game.php");


/*
API v1
Check for game configs

return_code
    -2 - Maintenance mode
    -1 - No configs found for the specified version
     0 - Newer configs found
*/

/**
* @return array<string, mixed> $results
*/
function exportGameConfigs() : array
{
    global $c_maintenance, $get;

    if ($c_maintenance)
    {
        $results['return_code'] = -2;
        return $results;
    }

    $db = get_database("compat");
    $configs = mysqli_query($db, "SELECT `game_id`, `config`, `timestamp`   
                                  FROM `game_settings`
                                  ORDER BY `game_id` ASC; ");
    mysqli_close($db);

    if (is_bool($configs) || mysqli_num_rows($configs) === 0)
    {
        $results['return_code'] = -1;
        return $results;
    }

    $results['return_code'] = 0;
    $results['timestamp'] = 0;
    $results['games'] = array();

    while ($row = mysqli_fetch_object($configs))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row, "game_id") || 
            !property_exists($row, "timestamp") || 
            !property_exists($row, "config"))
        {
            continue;
        }
        
        if (strtotime($row->timestamp) > $results['timestamp'])
        {
            $results['timestamp'] = strtotime($row->timestamp);
        }

        //$results['games'][$row->game_id]['timestamp'] = strtotime($row->timestamp);
        $results['games'][$row->game_id]['config'] = $row->config;
    }

    return $results;
}
