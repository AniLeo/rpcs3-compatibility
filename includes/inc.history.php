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
if (!@include_once(__DIR__."/../functions.php"))             throw new Exception("Compat: Failed to include functions.php");
if (!@include_once(__DIR__."/../classes/class.History.php")) throw new Exception("Compat: Failed to include classes/class.History.php");


// Profiler
Profiler::start_profiler("Profiler: History");

// Connect to database
Profiler::add_data("Inc: Database Connection");
$db = get_database("compat");

// Unreachable during normal usage as it's defined on index
if (!isset($get))
    $get = validateGet();

// Unreachable during normal usage as it's defined on index
if (!isset($a_currenthist) || !isset($a_histdates))
    die();

$a_existing = array();
$a_same = array();
$a_new = array();

$error_existing = "";
$error_same = "";
$error_new = "";

// Default date value
if ($get['h'] === true)
{
    $get['h']	= $a_currenthist[0];
}

// Updates do not store new_gid. Insertions do.
$cmd_existing = "SELECT `game_history`.`old_status`,
                        `game_history`.`new_status`,
                        `game_history`.`old_date`,
                        `game_history`.`new_date`,
                        `game_id`.`gid`,
                        `game_id`.`tid`,
                        `game_id`.`game_title`,
                        `game_list`.`move`
                 FROM `game_history`
                 INNER JOIN `game_list`
                     ON `game_history`.`game_key` = `game_list`.`key`
                 INNER JOIN `game_id`
                     ON `game_history`.`game_key` = `game_id`.`key` ";

$cmd_new = "SELECT `game_history`.`old_status`,
                   `game_history`.`new_status`,
                   `game_history`.`old_date`,
                   `game_history`.`new_date`,
                   `game_id`.`gid`,
                   `game_id`.`tid`,
                   `game_id`.`game_title`,
                   `game_list`.`move`
            FROM `game_history`
            INNER JOIN `game_list`
                ON `game_history`.`game_key` = `game_list`.`key`
            INNER JOIN `game_id`
                ON `game_history`.`new_gid` = `game_id`.`gid` ";

// Generate date part of the query
if ($get['h'] === $a_currenthist[0])
{
    $cmd_date = " AND `new_date` >= CAST('{$a_currenthist[2]}' AS DATE) ";
}
else
{
    $cmd_date = " AND `new_date` BETWEEN
    CAST('{$a_histdates[$get['h']][0]['y']}-{$a_histdates[$get['h']][0]['m']}-{$a_histdates[$get['h']][0]['d']}' AS DATE)
    AND CAST('{$a_histdates[$get['h']][1]['y']}-{$a_histdates[$get['h']][1]['m']}-{$a_histdates[$get['h']][1]['d']}' AS DATE) ";
}


// Existing entries
if (!isset($get['m']) || $get['m'] === "c")
{
    Profiler::add_data("Inc: Check Existing Entries");

    $q_existing = mysqli_query($db, "{$cmd_existing}
    WHERE `old_status` IS NOT NULL AND `old_status` <> `new_status` {$cmd_date}
    ORDER BY `new_status` ASC, -`old_status` DESC, `new_date` DESC, `game_id`.`game_title` ASC, `tid` DESC; ");

    if (is_bool($q_existing))
    {
        $error_existing = "Please try again. If this error persists, please contact the RPCS3 team.";
    }
    elseif (mysqli_num_rows($q_existing) === 0)
    {
        $error_existing = "No status changes to previously existing entries were reported and/or reviewed yet.";
    }
    else
    {
        $a_existing = HistoryEntry::query_to_history_entry($q_existing);
    }
}

// Same-status updates
if (!isset($get['m']) || $get['m'] === "s")
{
    Profiler::add_data("Inc: Check Same Status Entries");

    $q_same = mysqli_query($db, "{$cmd_existing}
    WHERE `old_status` IS NOT NULL AND `old_status` = `new_status` {$cmd_date}
    ORDER BY `new_status` ASC, `new_date` DESC, `game_id`.`game_title` ASC, `tid` DESC; ");

    if (is_bool($q_same))
    {
        $error_same = "Please try again. If this error persists, please contact the RPCS3 team.";
    }
    elseif (mysqli_num_rows($q_same) === 0)
    {
        $error_same = "No same-status updates to previously existing entries were reported and/or reviewed yet.";
    }
    else
    {
        $a_same = HistoryEntry::query_to_history_entry($q_same);
    }
}

// New entries
if (!isset($get['m']) || $get['m'] === "n")
{
    Profiler::add_data("Inc: Check New Entries");

    $q_new = mysqli_query($db, "{$cmd_new}
    WHERE `old_status` IS NULL {$cmd_date}
    ORDER BY `new_status` ASC, `new_date` DESC, `game_id`.`game_title` ASC, `tid` DESC; ");

    if (is_bool($q_new))
    {
        $error_new = "Please try again. If this error persists, please contact the RPCS3 team.";
    }
    elseif (mysqli_num_rows($q_new) === 0)
    {
        $error_new = "No newer entries were reported and/or reviewed yet.";
    }
    else
    {
        $a_new = HistoryEntry::query_to_history_entry($q_new);
    }
}


// Disconnect from database
Profiler::add_data("Inc: Close Database Connection");
mysqli_close($db);

Profiler::add_data("--- / ---");