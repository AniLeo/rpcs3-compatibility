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
define('db_host', 'HOSTNAME');
define('db_port', 'PORT');
define('db_user', 'USERNAME');
define('db_pass', 'PASSWORD');
define('db_name', 'DATABASE_NAME');

// GitHub Access Token (Read-only)
define('gh_token', 'TOKEN');

// Netplay Statistics API
define('np_api', 'URL');

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
$a_pageresults = array(25, 50, 100, 200);

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
    0 => 'Recache',
    1 => 'Builds',
    2 => 'Forum'
);

$a_panel = array(
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
    'cacheInitials' => array(
        'category' => 0,
        'title' => "Cache Initials",
        'success' => "Forced update on initials cache"
    ),
    'cacheGameCount' => array(
        'category' => 0,
        'title' => "Cache Game Count",
        'success' => "Forced update on game count"
    ),
    'cacheWikiIDs' => array(
        'category' => 3,
        'title' => "Cache Wiki IDs",
        'success' => "Forced update on Wiki IDs cache"
    ),
    'cache_games_updates' => array(
        'category' => 0,
        'title' => "Cache Game Updates",
        'success' => "Updated latest game version cache"
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
        'category' => 2,
        'title' => "Merge Games",
        'success' => "Ran game merger"
    ),
    'cachePatches' => array(
        'category' => 3,
        'title' => "Cache Patches",
        'success' => "Forced update on game patches cache"
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
    'cache_netplay_statistics' => array(
        'category' => 0,
        'title' => "Cache Netplay Statistics",
        'success' => "Cached Netplay Statistics for the current timestamp"
    ),
    'check_duplicated_entries' => array(
        'category' => 2,
        'title' => "Check Duplicated Entries",
        'success' => ""
    )
);

// Dates for history backups
$a_histdates = array(
    '2017_03' => array(array('y' => 2017, 'm' => 3,  'd' => 01), array('y' => 2017, 'm' => 3,  'd' => 29)),
    '2017_04' => array(array('y' => 2017, 'm' => 3,  'd' => 30), array('y' => 2017, 'm' => 4,  'd' => 30)),
    '2017_05' => array(array('y' => 2017, 'm' => 5,  'd' => 01), array('y' => 2017, 'm' => 5,  'd' => 31)),
    '2017_06' => array(array('y' => 2017, 'm' => 6,  'd' => 01), array('y' => 2017, 'm' => 6,  'd' => 30)),
    '2017_07' => array(array('y' => 2017, 'm' => 7,  'd' => 01), array('y' => 2017, 'm' => 7,  'd' => 31)),
    '2017_08' => array(array('y' => 2017, 'm' => 8,  'd' => 01), array('y' => 2017, 'm' => 9,  'd' => 01)),
    '2017_09' => array(array('y' => 2017, 'm' => 9,  'd' => 02), array('y' => 2017, 'm' => 9,  'd' => 30)),
    '2017_10' => array(array('y' => 2017, 'm' => 10, 'd' => 01), array('y' => 2017, 'm' => 11, 'd' => 02)),
    '2017_11' => array(array('y' => 2017, 'm' => 11, 'd' => 03), array('y' => 2017, 'm' => 11, 'd' => 30)),
    '2017_12' => array(array('y' => 2017, 'm' => 12, 'd' => 01), array('y' => 2017, 'm' => 12, 'd' => 31)),
    '2018_01' => array(array('y' => 2018, 'm' => 1,  'd' => 01), array('y' => 2018, 'm' => 1,  'd' => 31)),
    '2018_02' => array(array('y' => 2018, 'm' => 2,  'd' => 01), array('y' => 2018, 'm' => 3,  'd' => 01)),
    '2018_03' => array(array('y' => 2018, 'm' => 3,  'd' => 02), array('y' => 2018, 'm' => 3,  'd' => 31)),
    '2018_04' => array(array('y' => 2018, 'm' => 4,  'd' => 01), array('y' => 2018, 'm' => 5,  'd' => 01)),
    '2018_05' => array(array('y' => 2018, 'm' => 5,  'd' => 02), array('y' => 2018, 'm' => 5,  'd' => 31)),
    '2018_06' => array(array('y' => 2018, 'm' => 6,  'd' => 01), array('y' => 2018, 'm' => 6,  'd' => 30)),
    '2018_07' => array(array('y' => 2018, 'm' => 7,  'd' => 01), array('y' => 2018, 'm' => 7,  'd' => 31)),
    '2018_08' => array(array('y' => 2018, 'm' => 8,  'd' => 01), array('y' => 2018, 'm' => 8,  'd' => 31)),
    '2018_09' => array(array('y' => 2018, 'm' => 9,  'd' => 01), array('y' => 2018, 'm' => 9,  'd' => 30)),
    '2018_10' => array(array('y' => 2018, 'm' => 10, 'd' => 01), array('y' => 2018, 'm' => 10, 'd' => 31)),
    '2018_11' => array(array('y' => 2018, 'm' => 11, 'd' => 01), array('y' => 2018, 'm' => 11, 'd' => 30)),
    '2018_12' => array(array('y' => 2018, 'm' => 12, 'd' => 01), array('y' => 2018, 'm' => 12, 'd' => 31)),
    '2019_01' => array(array('y' => 2019, 'm' => 1,  'd' => 01), array('y' => 2019, 'm' => 1,  'd' => 31)),
    '2019_02' => array(array('y' => 2019, 'm' => 2,  'd' => 01), array('y' => 2019, 'm' => 2,  'd' => 28)),
    '2019_03' => array(array('y' => 2019, 'm' => 3,  'd' => 01), array('y' => 2019, 'm' => 3,  'd' => 31)),
    '2019_04' => array(array('y' => 2019, 'm' => 4,  'd' => 01), array('y' => 2019, 'm' => 4,  'd' => 30)),
    '2019_05' => array(array('y' => 2019, 'm' => 5,  'd' => 01), array('y' => 2019, 'm' => 5,  'd' => 31)),
    '2019_06' => array(array('y' => 2019, 'm' => 6,  'd' => 01), array('y' => 2019, 'm' => 6,  'd' => 30)),
    '2019_07' => array(array('y' => 2019, 'm' => 7,  'd' => 01), array('y' => 2019, 'm' => 7,  'd' => 31)),
    '2019_08' => array(array('y' => 2019, 'm' => 8,  'd' => 01), array('y' => 2019, 'm' => 8,  'd' => 31)),
    '2019_09' => array(array('y' => 2019, 'm' => 9,  'd' => 01), array('y' => 2019, 'm' => 9,  'd' => 30)),
    '2019_10' => array(array('y' => 2019, 'm' => 10, 'd' => 01), array('y' => 2019, 'm' => 10, 'd' => 31)),
    '2019_11' => array(array('y' => 2019, 'm' => 11, 'd' => 01), array('y' => 2019, 'm' => 11, 'd' => 30)),
    '2019_12' => array(array('y' => 2019, 'm' => 12, 'd' => 01), array('y' => 2019, 'm' => 12, 'd' => 31)),
    '2020_01' => array(array('y' => 2020, 'm' => 1,  'd' => 01), array('y' => 2020, 'm' => 1,  'd' => 31)),
    '2020_02' => array(array('y' => 2020, 'm' => 2,  'd' => 01), array('y' => 2020, 'm' => 2,  'd' => 29)),
    '2020_03' => array(array('y' => 2020, 'm' => 3,  'd' => 01), array('y' => 2020, 'm' => 3,  'd' => 31)),
    '2020_04' => array(array('y' => 2020, 'm' => 4,  'd' => 01), array('y' => 2020, 'm' => 4,  'd' => 30)),
    '2020_05' => array(array('y' => 2020, 'm' => 5,  'd' => 01), array('y' => 2020, 'm' => 5,  'd' => 31)),
    '2020_06' => array(array('y' => 2020, 'm' => 6,  'd' => 01), array('y' => 2020, 'm' => 6,  'd' => 30)),
    '2020_07' => array(array('y' => 2020, 'm' => 7,  'd' => 01), array('y' => 2020, 'm' => 7,  'd' => 31)),
    '2020_08' => array(array('y' => 2020, 'm' => 8,  'd' => 01), array('y' => 2020, 'm' => 8,  'd' => 31)),
    '2020_09' => array(array('y' => 2020, 'm' => 9,  'd' => 01), array('y' => 2020, 'm' => 9,  'd' => 30)),
    '2020_10' => array(array('y' => 2020, 'm' => 10, 'd' => 01), array('y' => 2020, 'm' => 10, 'd' => 31)),
    '2020_11' => array(array('y' => 2020, 'm' => 11, 'd' => 01), array('y' => 2020, 'm' => 11, 'd' => 30)),
    '2020_12' => array(array('y' => 2020, 'm' => 12, 'd' => 01), array('y' => 2020, 'm' => 12, 'd' => 31)),
    '2021_01' => array(array('y' => 2021, 'm' => 1,  'd' => 01), array('y' => 2021, 'm' => 1,  'd' => 31)),
    '2021_02' => array(array('y' => 2021, 'm' => 2,  'd' => 01), array('y' => 2021, 'm' => 2,  'd' => 28)),
    '2021_03' => array(array('y' => 2021, 'm' => 3,  'd' => 01), array('y' => 2021, 'm' => 3,  'd' => 31)),
    '2021_04' => array(array('y' => 2021, 'm' => 4,  'd' => 01), array('y' => 2021, 'm' => 4,  'd' => 30)),
    '2021_05' => array(array('y' => 2021, 'm' => 5,  'd' => 01), array('y' => 2021, 'm' => 5,  'd' => 31)),
    '2021_06' => array(array('y' => 2021, 'm' => 6,  'd' => 01), array('y' => 2021, 'm' => 6,  'd' => 30)),
    '2021_07' => array(array('y' => 2021, 'm' => 7,  'd' => 01), array('y' => 2021, 'm' => 7,  'd' => 31)),
    '2021_08' => array(array('y' => 2021, 'm' => 8,  'd' => 01), array('y' => 2021, 'm' => 8,  'd' => 31)),
    '2021_09' => array(array('y' => 2021, 'm' => 9,  'd' => 01), array('y' => 2021, 'm' => 9,  'd' => 30)),
    '2021_10' => array(array('y' => 2021, 'm' => 10, 'd' => 01), array('y' => 2021, 'm' => 10, 'd' => 31)),
    '2021_11' => array(array('y' => 2021, 'm' => 11, 'd' => 01), array('y' => 2021, 'm' => 11, 'd' => 30)),
    '2021_12' => array(array('y' => 2021, 'm' => 12, 'd' => 01), array('y' => 2021, 'm' => 12, 'd' => 31)),
    '2022_01' => array(array('y' => 2022, 'm' => 1,  'd' => 01), array('y' => 2022, 'm' => 1,  'd' => 31)),
    '2022_02' => array(array('y' => 2022, 'm' => 2,  'd' => 01), array('y' => 2022, 'm' => 2,  'd' => 28)),
    '2022_03' => array(array('y' => 2022, 'm' => 3,  'd' => 01), array('y' => 2022, 'm' => 3,  'd' => 31)),
    '2022_04' => array(array('y' => 2022, 'm' => 4,  'd' => 01), array('y' => 2022, 'm' => 4,  'd' => 30)),
    '2022_05' => array(array('y' => 2022, 'm' => 5,  'd' => 01), array('y' => 2022, 'm' => 5,  'd' => 31)),
    '2022_06' => array(array('y' => 2022, 'm' => 6,  'd' => 01), array('y' => 2022, 'm' => 6,  'd' => 30)),
    '2022_07' => array(array('y' => 2022, 'm' => 7,  'd' => 01), array('y' => 2022, 'm' => 7,  'd' => 31)),
    '2022_08' => array(array('y' => 2022, 'm' => 8,  'd' => 01), array('y' => 2022, 'm' => 8,  'd' => 31)),
    '2022_09' => array(array('y' => 2022, 'm' => 9,  'd' => 01), array('y' => 2022, 'm' => 9,  'd' => 30)),
    '2022_10' => array(array('y' => 2022, 'm' => 10, 'd' => 01), array('y' => 2022, 'm' => 10, 'd' => 31)),
    '2022_11' => array(array('y' => 2022, 'm' => 11, 'd' => 01), array('y' => 2022, 'm' => 11, 'd' => 30)),
    '2022_12' => array(array('y' => 2022, 'm' => 12, 'd' => 01), array('y' => 2022, 'm' => 12, 'd' => 31)),
    '2023_01' => array(array('y' => 2023, 'm' => 1,  'd' => 01), array('y' => 2023, 'm' => 1,  'd' => 31)),
    '2023_02' => array(array('y' => 2023, 'm' => 2,  'd' => 01), array('y' => 2023, 'm' => 2,  'd' => 28)),
    '2023_03' => array(array('y' => 2023, 'm' => 3,  'd' => 01), array('y' => 2023, 'm' => 3,  'd' => 31)),
    '2023_04' => array(array('y' => 2023, 'm' => 4,  'd' => 01), array('y' => 2023, 'm' => 4,  'd' => 30)),
    '2023_05' => array(array('y' => 2023, 'm' => 5,  'd' => 01), array('y' => 2023, 'm' => 5,  'd' => 31)),
    '2023_06' => array(array('y' => 2023, 'm' => 6,  'd' => 01), array('y' => 2023, 'm' => 6,  'd' => 30)),
    '2023_07' => array(array('y' => 2023, 'm' => 7,  'd' => 01), array('y' => 2023, 'm' => 7,  'd' => 31)),
    '2023_08' => array(array('y' => 2023, 'm' => 8,  'd' => 01), array('y' => 2023, 'm' => 8,  'd' => 31)),
    '2023_09' => array(array('y' => 2023, 'm' => 9,  'd' => 01), array('y' => 2023, 'm' => 9,  'd' => 30)),
    '2023_10' => array(array('y' => 2023, 'm' => 10, 'd' => 01), array('y' => 2023, 'm' => 10, 'd' => 31)),
    '2023_11' => array(array('y' => 2023, 'm' => 11, 'd' => 01), array('y' => 2023, 'm' => 11, 'd' => 30)),
    '2023_12' => array(array('y' => 2023, 'm' => 12, 'd' => 01), array('y' => 2023, 'm' => 12, 'd' => 31)),
    '2024_01' => array(array('y' => 2024, 'm' => 1,  'd' => 01), array('y' => 2024, 'm' => 1,  'd' => 31)),
    '2024_02' => array(array('y' => 2024, 'm' => 2,  'd' => 01), array('y' => 2024, 'm' => 2,  'd' => 29)),
    '2024_03' => array(array('y' => 2024, 'm' => 3,  'd' => 01), array('y' => 2024, 'm' => 3,  'd' => 31)),
    '2024_04' => array(array('y' => 2024, 'm' => 4,  'd' => 01), array('y' => 2024, 'm' => 4,  'd' => 30)),
    '2024_05' => array(array('y' => 2024, 'm' => 5,  'd' => 01), array('y' => 2024, 'm' => 5,  'd' => 31)),
    '2024_06' => array(array('y' => 2024, 'm' => 6,  'd' => 01), array('y' => 2024, 'm' => 6,  'd' => 30)),
    '2024_07' => array(array('y' => 2024, 'm' => 7,  'd' => 01), array('y' => 2024, 'm' => 7,  'd' => 31)),
    '2024_08' => array(array('y' => 2024, 'm' => 8,  'd' => 01), array('y' => 2024, 'm' => 8,  'd' => 31)),
    '2024_09' => array(array('y' => 2024, 'm' => 9,  'd' => 01), array('y' => 2024, 'm' => 9,  'd' => 30)),
    '2024_10' => array(array('y' => 2024, 'm' => 10, 'd' => 01), array('y' => 2024, 'm' => 10, 'd' => 31)),
    '2024_11' => array(array('y' => 2024, 'm' => 11, 'd' => 01), array('y' => 2024, 'm' => 11, 'd' => 30)),
    '2024_12' => array(array('y' => 2024, 'm' => 12, 'd' => 01), array('y' => 2024, 'm' => 12, 'd' => 31)),
    '2025_01' => array(array('y' => 2025, 'm' => 1,  'd' => 01), array('y' => 2025, 'm' => 1,  'd' => 31)),
    '2025_02' => array(array('y' => 2025, 'm' => 2,  'd' => 01), array('y' => 2025, 'm' => 2,  'd' => 30)),
    '2025_03' => array(array('y' => 2025, 'm' => 3,  'd' => 01), array('y' => 2025, 'm' => 3,  'd' => 31)),
    '2025_04' => array(array('y' => 2025, 'm' => 4,  'd' => 01), array('y' => 2025, 'm' => 4,  'd' => 30)),
    '2025_05' => array(array('y' => 2025, 'm' => 5,  'd' => 01), array('y' => 2025, 'm' => 5,  'd' => 31)),
    '2025_06' => array(array('y' => 2025, 'm' => 6,  'd' => 01), array('y' => 2025, 'm' => 6,  'd' => 30))
);

// Current month
$a_currenthist = array('2025_07', 'July 1st, 2025', '2025-07-01');
