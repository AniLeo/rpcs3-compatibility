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
define('db_table' , 'TABLE_NAME');

// Global config variables
$c_github = 'https://github.com/RPCS3/rpcs3/commit/';
$c_forum = 'http://www.emunewz.net/forum/showthread.php?tid=';
$c_pageresults = 3; // Default results per page (50)

// Background color for unknown commit div
$c_unkcommit = 'ffd700';

// Results per page
$a_pageresults = array(
1 => 15,
2 => 25,
3 => 50,
4 => 100
);

// Status titles 
$a_title = array(
'All',
'Playable',
'Ingame',
'Intro',
'Loadable',
'Nothing'
);

// Status descriptions 
$a_desc = array(
'Show games from all statuses',
'Games that can be played from start to finish',
'Games that go somewhere but not far enough to be considered playable',
'Games that only display some screens',
'Games that display a black screen with an active framerate',
'Games that show nothing'
);

// Status colors 
$a_color = array(
1 => '2ecc71',
2 => 'f1c40f',
3 => 'f39c12',
4 => 'e74c3c',
5 => '2c3e50'
);

// Region flags
$a_flags = array(
"A" => "img/icons/flags/CN.png", // Asia
"E" => "img/icons/flags/EU.png", // Europe
"H" => "img/icons/flags/CN.png", // Southern Asia
"K" => "img/icons/flags/HK.png", // Hong Kong
"J" => "img/icons/flags/JP.png", // Japan
"U" => "img/icons/flags/US.png"  // USA
);

// Game media icons
$a_media = array(
"PSN" => '/img/icons/list/psn.png',
"BLR" => '/img/icons/list/rom.png'
);

// CSS elements
$a_css = array(
"MEDIA_ICON" => 'div-compat-fmat',
"CHARACTER_SEARCH" => 'compat-inr-search',
"STATUS" => 'txt-compat-status',
"BUILD" => 'txt-compat-build',
"NOBUILD" => 'txt-compat-nobuild'
);

// Dates for history backups
$a_histdates = array(
'2017_02' => 'March 1st, 2017',
'2017_03' => 'March 30th, 2017'
);

// Current month
$currenthist = '2017_04';

?>
