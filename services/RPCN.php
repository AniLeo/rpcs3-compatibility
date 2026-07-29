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

    $db = get_database("netplay");

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


function cache_netplay_statistics_peak() : bool
{
    $db = get_database("netplay");

    $q_updates = "";

    // Select currently cached peak ticket entries
    $a_peak_ticket = array();
    $q_select_peak = mysqli_query($db, "SELECT * FROM `np_ticket_games_peak`;");

    if (is_bool($q_select_peak))
    {
        return false;
    }

    if (mysqli_num_rows($q_select_peak) > 0)
    {
        while ($row = mysqli_fetch_object($q_select_peak))
        {
            $a_peak_ticket[$row->content_id] = (int) $row->players;
        }
    }

    // Select current peak ticket games
    $q_select_ticket = mysqli_query($db, "SELECT `content_id`, MAX(`players`) AS `players`, `timestamp` 
                                          FROM `np_ticket_games` 
                                          GROUP BY `content_id` 
                                          ORDER BY `content_id` ASC;");

    if (is_bool($q_select_ticket))
    {
        return false;
    }

    // Update peak ticket games cache
    while ($row = mysqli_fetch_object($q_select_ticket))
    {
        $db_id = mysqli_real_escape_string($db, $row->content_id);

        if (!array_key_exists($row->content_id, $a_peak_ticket))
        {
            $q_updates .= "INSERT INTO `np_ticket_games_peak` (`content_id`, `timestamp`, `players`) VALUES ('{$db_id}', '{$row->timestamp}', '{$row->players}');";
            continue;
        }

        if ($row->players >= $a_peak_ticket[$row->content_id])
        {
            $q_updates .= "UPDATE `np_ticket_games_peak` SET `players` = '{$row->players}' WHERE `content_id` = '{$db_id}';";
        }
    }


    // Select currently cached peak psn entries
    $a_peak_psn = array();
    $q_select_peak = mysqli_query($db, "SELECT * FROM `np_psn_games_peak`;");

    if (is_bool($q_select_peak))
    {
        return false;
    }

    if (mysqli_num_rows($q_select_peak) > 0)
    {
        while ($row = mysqli_fetch_object($q_select_peak))
        {
            $a_peak_psn[$row->comm_id] = (int) $row->players;
        }
    }

    // Select current peak psn games
    $q_select_psn = mysqli_query($db, "SELECT `comm_id`, MAX(`players`) AS `players`, `timestamp` 
                                       FROM `np_psn_games` 
                                       GROUP BY `comm_id` 
                                       ORDER BY `comm_id` ASC;");

    if (is_bool($q_select_psn))
    {
        return false;
    }

    // Update peak psn games cache
    while ($row = mysqli_fetch_object($q_select_psn))
    {
        $db_id = mysqli_real_escape_string($db, $row->comm_id);

        if (!array_key_exists($row->comm_id, $a_peak_psn))
        {
            $q_updates .= "INSERT INTO `np_psn_games_peak` (`comm_id`, `timestamp`, `players`) VALUES ('{$db_id}', '{$row->timestamp}', '{$row->players}');";
            continue;
        }

        if ($row->players >= $a_peak_psn[$row->comm_id])
        {
            $q_updates .= "UPDATE `np_psn_games_peak` SET `players` = '{$row->players}' WHERE `comm_id` = '{$db_id}';";
        }
    }

    mysqli_multi_query($db, $q_updates);
    mysqli_close($db);
    return true;
}