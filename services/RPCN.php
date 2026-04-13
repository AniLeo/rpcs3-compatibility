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
if (!@include_once(__DIR__."/../functions.php")) throw new Exception("Compat: Failed to include functions.php");


function cache_netplay_statistics() : bool
{
    $q_updates = "";

    // Reset current cURL resource to use default values before using it
    $np_stats = curl_json(np_api, null);

    if (is_null($np_stats))
    {
        echo "cache_netplay_statistics(): Failed to poll the NP API".PHP_EOL;
        return false;
    }

    // Global players data
    if (!property_exists($np_stats, "num_users"))
    {
        echo "cache_netplay_statistics(): NP API does not contain the num_users property".PHP_EOL;
        return false;
    }

    $db = get_database("compat");

    $s_players = mysqli_real_escape_string($db, (string) $np_stats->num_users);
    $q_updates .= "INSERT INTO `np_players` (`timestamp`, `players`) VALUES (CONVERT_TZ(NOW(),'SYSTEM','+00:00'), '{$s_players}'); ";

    // PSN games data
    if (!property_exists($np_stats, "psn_games"))
    {
        echo "cache_netplay_statistics(): NP API does not contain the psn_games property".PHP_EOL;
        mysqli_close($db);
        return false;
    }

    foreach ($np_stats->psn_games as $comm_id => $np_data)
    {
        if (!isset($np_data[0]))
        {
            echo "cache_netplay_statistics(): NP API does not contain the player count for {$comm_id}".PHP_EOL;
            mysqli_close($db);
            return false;
        }

        $s_comm_id  = mysqli_real_escape_string($db, (string) $comm_id);
        $s_players  = mysqli_real_escape_string($db, (string) $np_data[0]);
        $q_updates .= "INSERT INTO `np_psn_games` (`timestamp`, `comm_id`, `players`) ";
        $q_updates .= "VALUES (CONVERT_TZ(NOW(),'SYSTEM','+00:00'), '{$s_comm_id}', '{$s_players}'); ";
    }

    // Ticket games data
    if (!property_exists($np_stats, "ticket_games"))
    {
        echo "cache_netplay_statistics(): NP API does not contain the ticket_games property".PHP_EOL;
        mysqli_close($db);
        return false;
    }

    foreach ($np_stats->ticket_games as $content_id => $players)
    {
        $s_content_id = mysqli_real_escape_string($db, (string) $content_id);
        $s_players    = mysqli_real_escape_string($db, (string) $players);
        $q_updates   .= "INSERT INTO `np_ticket_games` (`timestamp`, `content_id`, `players`) ";
        $q_updates   .= "VALUES (CONVERT_TZ(NOW(),'SYSTEM','+00:00'), '{$s_content_id}', '{$s_players}'); ";
    }

    mysqli_multi_query($db, $q_updates);
    mysqli_close($db);
    return true;
}