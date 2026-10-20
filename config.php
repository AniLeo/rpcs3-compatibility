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

// Connection details for the MySQL server
define('db_compat_host', 'HOSTNAME');
define('db_compat_port', 'PORT');
define('db_compat_user', 'USERNAME');
define('db_compat_pass', 'PASSWORD');
define('db_compat_name', 'DATABASE_NAME');

define('db_forums_host', 'HOSTNAME');
define('db_forums_port', 'PORT');
define('db_forums_user', 'USERNAME');
define('db_forums_pass', 'PASSWORD');
define('db_forums_name', 'DATABASE_NAME');

define('db_wiki_host', 'HOSTNAME');
define('db_wiki_port', 'PORT');
define('db_wiki_user', 'USERNAME');
define('db_wiki_pass', 'PASSWORD');
define('db_wiki_name', 'DATABASE_NAME');

define('db_netplay_host', 'HOSTNAME');
define('db_netplay_port', 'PORT');
define('db_netplay_user', 'USERNAME');
define('db_netplay_pass', 'PASSWORD');
define('db_netplay_name', 'DATABASE_NAME');

// GitHub Access Token (Read-only)
define('gh_token', 'TOKEN');

// Global config variables
$c_pagelimit = 7; // Default page limit on pages counter (lim/2)
$c_maintenance = false; // Maintenance Mode
$c_profiler = true; // Profiling mode

// Default value for results per page
$c_pageresults = 25;

// Text to append at the beginning of the footer
$c_footer_before = 	"";

// Text to append at the end of the footer
$c_footer_after = 	"";

// Allowed values for results per page
$a_pageresults = array(25, 50, 100, 200, 400);

// Game status data
$a_status = array(
    1 => array(
        'name' => "Playable",
        'desc' => "Games that can be completed with playable performance and no game breaking glitches",
        'color' => "1ebc61",
        'fid' => array(5, 26)
    ),
    2 => array(
        'name' => "Ingame",
        'desc' => "Games that either can't be finished, have serious glitches or have insufficient performance",
        'color' => "f9b32f",
        'fid' => array(6, 27)
    ),
    3 => array(
        'name' => "Intro",
        'desc' => "Games that display image but don't make it past the menus",
        'color' => "e08a1e",
        'fid' => array(7, 28)
    ),
    4 => array(
        'name' => "Loadable",
        'desc' => "Games that display a black screen with a framerate on the window's title",
        'color' => "e74c3c",
        'fid' => array(8, 29)
    ),
    5 => array(
        'name' => "Nothing",
        'desc' => "Games that don't initialize properly, not loading at all and/or crashing the emulator",
        'color' => "455556",
        'fid' => array(9, 30)
    )
);

// Productcode: Game Media (1º Character)
$a_media = array(
    'B' => array(
        'name' => "Blu-Ray",
        'icon' => "/img/icons/compat/rom.png"
    ),
    'M' => array(
        'name' => "Blu-Ray",
        'icon' => "/img/icons/compat/rom.png"
    ),
    'N' => array(
        'name' => "Digital (PSN)",
        'icon' => "/img/icons/compat/psn.png"
    ),
    'X' => array(
        'name' => "Blu-Ray + Extras",
        'icon' => "/img/icons/compat/rom.png"
    )
);

// Regions (GameIDs)
$a_regions = array(
'E' => "EU", // Europe
'U' => "US", // America
'J' => "JP", // Japan
'A' => "AS", // Asia
'K' => "KR", // Korea
'H' => "HK", // Hong Kong
'I' => "IN", // International
'T' => "IN"  // MRTC
);

// Region flags
$a_flags = array(
"A" => "/img/icons/compat/AS.png", // Asia
"E" => "/img/icons/compat/EU.png", // Europe
"H" => "/img/icons/compat/HK.png", // Hong Kong
"K" => "/img/icons/compat/KR.png", // Korea
"J" => "/img/icons/compat/JP.png", // Japan
"U" => "/img/icons/compat/US.png", // America
"I" => "/img/icons/compat/IN.png", // International
"T" => "/img/icons/compat/IN.png"  // MRTC
);

// Functions available on debug panel (function_name => (title, success))
$a_panel_categories = array(
    0 => 'Compat',
    1 => 'Builds',
    2 => 'Forum',
    3 => 'Wiki'
);

$a_panel = array(
    'cache_initials' => array(
        'category' => 0,
        'title' => "Cache Initials",
        'success' => "Forced update on initials cache"
    ),
    'cache_game_count' => array(
        'category' => 0,
        'title' => "Cache Game Count",
        'success' => "Forced update on game count"
    ),
    'cache_games_updates' => array(
        'category' => 0,
        'title' => "Cache Game Updates",
        'success' => "Updated latest game version cache"
    ),
    'cache_builds' => array(
        'category' => 1,
        'title' => "Update Build Cache",
        'success' => "Forced update on builds cache"
    ),
    'refreshBuild' => array(
        'category' => 1,
        'title' => "Refresh Build",
        'success' => "Refreshed build"
    ),
    'flag_build_as_broken' => array(
        'category' => 1,
        'title' => "Flag Build as Broken",
        'success' => ""
    ),
    'export_build_backup' => array(
        'category' => 1,
        'title' => "Export Build Backup",
        'success' => ""
    ),
    'checkInvalidThreads' => array(
        'category' => 2,
        'title' => "Check Invalid Threads",
        'success' => ""
    ),
    'compatibilityUpdater' => array(
        'category' => 2,
        'title' => "Compatibility Updater",
        'success' => "Ran compatibility updater"
    ),
    'mergeGames' => array(
        'category' => 0,
        'title' => "Group Game IDs",
        'success' => "Ran game merger"
    ),
    'check_duplicated_entries' => array(
        'category' => 2,
        'title' => "Check Duplicated Entries",
        'success' => ""
    ),
    'cache_wiki_ids' => array(
        'category' => 3,
        'title' => "Cache Wiki IDs",
        'success' => "Forced update on Wiki IDs cache"
    ),
    'cache_patches' => array(
        'category' => 3,
        'title' => "Cache Game Patches",
        'success' => "Updated the game patches cache"
    ),
    'cache_game_settings' => array(
        'category' => 3,
        'title' => "Cache Game Settings",
        'success' => "Updated the game settings cache"
    ),
    'edit_game' => array(
        'category' => 0,
        'title' => "Edit Game",
        'success' => ""
    ),
    'refresh_incomplete_builds' => array(
        'category' => 1,
        'title' => "Refresh Incomplete Builds",
        'success' => "Refreshed incomplete builds"
    )
);

// Current month
$currenthist = '2026-10-01';
$a_currenthist = array(date('Y_m', strtotime($currenthist)),
                       date('F jS, Y', strtotime($currenthist)),
                       $currenthist);

$a_histdates = array();

// Generate date ranges
$firstMonth  = new DateTime('2017-03-01');
$currentMonth = new DateTime($currenthist);
$month = $firstMonth;
while ($month < $currentMonth)
{
    $key = $month->format('Y_m');

    $lastDay = $month;
    $lastDay->modify('last day of this month');

    $a_histdates[$key] = array(
        array(
            'y' => (int) $month->format('Y'),
            'm' => (int) $month->format('n'),
            'd' => 1
        ),
        array(
            'y' => (int) $lastDay->format('Y'),
            'm' => (int) $lastDay->format('n'),
            'd' => (int) $lastDay->format('j')
        )
    );

    $month->modify('first day of next month');
}