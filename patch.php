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


/*
API v1
Check for game patches

return_code
    -3 - Illegal search
    -2 - Maintenance mode
    -1 - No patches found for the specified version
     0 - Newer patch found
     1 - No newer patches found when hash is specified
*/

/**
* @return array<string, mixed> $results
*/
function exportGamePatches() : array
{
    global $c_maintenance, $get;

    if ($c_maintenance)
    {
        $results['return_code'] = -2;
        return $results;
    }

    if (!isset($get['v']))
    {
        $results['return_code'] = -3;
        return $results;
    }

    $db = getDatabase();
    $db_version = mysqli_real_escape_string($db, $get['v']);
    $patches = mysqli_query($db, "SELECT * FROM `game_patch`
                                  WHERE `version` = '{$db_version}'
                                  ORDER BY `wiki_id` ASC; ");
    mysqli_close($db);

    if (is_bool($patches) || mysqli_num_rows($patches) === 0)
    {
        $results['return_code'] = -1;
        return $results;
    }

    $results['return_code'] = 0;
    $results['version'] = $get['v'];
    $results['sha256'] = "";

    // Generate a valid patch file
    $results['patch'] = "Version: {$results['version']}\n";
    while ($row = mysqli_fetch_object($patches))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row, "patch"))
        {
            continue;
        }

        $results['patch'] .= "\n";
        $results['patch'] .= $row->patch;
        $results['patch'] .= "\n";
    }

    // Hash the returned results
    $results['sha256'] = hash('sha256', $results['patch']);

    if (isset($get['sha256']) && $results['sha256'] === strtolower($get['sha256']))
    {
        // Client hash matches match our patch, no new patch found
        unset($results['patch']);
        $results['return_code'] = 1;
    }

    return $results;
}
