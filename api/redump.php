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


/*
API v1
Check for the redump integrity database

return_code
    -2 - Maintenance mode
    -1 - No database found
     0 - Database found
     1 - No newer database found when hash is specified
*/

/**
* @return array<string, int|string> $results
*/
function exportRedumpDatabase() : array
{
    // Standalone maintenance mode
    /*
    $maintenance = false;

    if ($maintenance)
    {
        $results['return_code'] = -2;
        return $results;
    }
    */
    
    $dat_path = __DIR__."/../redump.dat";

    if (!file_exists($dat_path))
    {
        $results['return_code'] = -1;
        return $results;
    }

    $dat_content = file_get_contents($dat_path);

    if (is_bool($dat_content))
    {
        $results['return_code'] = -1;
        return $results;
    }
    
    $results['return_code'] = 0;
    $results['sha256'] = hash('sha256', $dat_content);
    $results['redump'] = $dat_content;

    // Check if client hash matches match our integrity database
    if (isset($_GET['sha256']) && is_string($_GET['sha256']) && $results['sha256'] === strtolower($_GET['sha256']))
    {
        $results['return_code'] = 1;
        unset($results['redump']);
    }

    return $results;
}