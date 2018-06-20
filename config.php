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

define('gh_client', 'OAUTH_ID');
define('gh_secret', 'OAUTH_SECRET');

// Global config variables
$c_github = 'https://github.com/RPCS3/rpcs3';
$c_forum = 'https://forums.rpcs3.net';
$c_appveyor = 'https://ci.appveyor.com/project/rpcs3/rpcs3/build/';
$c_pagelimit = 7; // Default page limit on pages counter (lim/2)
$c_maintenance = false; // Maintenance Mode
$c_profiler = true; // Profiling mode
$c_cloudflare = true; // Use cloudflare

// Default value for results per page
// Builds: 25 | Compat and Library = 50;
if (isset($_GET['b']))	{ $c_pageresults = 25; }
else										{ $c_pageresults = 50; }

// Allowed values for results per page
$a_pageresults = array(15, 25, 50, 100);

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
'Games that can be properly played from start to finish',
'Games that either can\'t be finished, have serious glitches or have insufficient performance',
'Games that display image but don\'t make it past the menus',
'Games that display a black screen with a framerate on the window\'s title',
'Games that don\'t initialize properly, not loading at all and/or crashing the emulator'
);

// Status colors
$a_color = array(
1 => '1ebc61',
2 => 'f9b32f',
3 => 'e08a1e',
4 => 'e74c3c',
5 => '455556'
);

// Regions (GameIDs)
$a_regions = array(
"EU",
"US",
"JP",
"AS",
"KR",
"HK"
);

// Region flags
$a_flags = array(
"A" => "/img/icons/compat/CN.png", // Asia
"E" => "/img/icons/compat/EU.png", // Europe
"H" => "/img/icons/compat/HK.png", // Hong Kong
"K" => "/img/icons/compat/KR.png", // Korea
"J" => "/img/icons/compat/JP.png", // Japan
"U" => "/img/icons/compat/US.png"  // USA
);

// Game media icons
$a_media = array(
"N" => '/img/icons/list/psn.png',
"B" => '/img/icons/list/rom.png',
"X" => '/img/icons/list/rom.png'
);

// Game ID filters for library page
$a_filter = array(
	'BCAS', 'BCES', 'BCJS', 'BCKS', 'BCUS', 'BLAS', 'BLES', 'BLJM', 'BLJS', 'BLKS', 'BLUS', 'NPEA', 'NPEB', 'NPUA', 'NPUB', 'NPHA', 'NPHB', 'NPJA', 'NPJB'
);

// Dates for history backups
$a_histdates = array(
	'2017_03' => array(array('y' => 2017, 'm' => 3,  'd' => 01),  array('y' => 2017, 'm' => 3,  'd' => 29)),
	'2017_04' => array(array('y' => 2017, 'm' => 3,  'd' => 30),  array('y' => 2017, 'm' => 4,  'd' => 30)),
	'2017_05' => array(array('y' => 2017, 'm' => 5,  'd' => 01),  array('y' => 2017, 'm' => 5,  'd' => 31)),
	'2017_06' => array(array('y' => 2017, 'm' => 6,  'd' => 01),  array('y' => 2017, 'm' => 6,  'd' => 30)),
	'2017_07' => array(array('y' => 2017, 'm' => 7,  'd' => 01),  array('y' => 2017, 'm' => 7,  'd' => 31)),
	'2017_08' => array(array('y' => 2017, 'm' => 8,  'd' => 01),  array('y' => 2017, 'm' => 9,  'd' => 01)),
	'2017_09' => array(array('y' => 2017, 'm' => 9,  'd' => 02),  array('y' => 2017, 'm' => 9,  'd' => 30)),
	'2017_10' => array(array('y' => 2017, 'm' => 10, 'd' => 01),  array('y' => 2017, 'm' => 11,  'd' => 02)),
	'2017_11' => array(array('y' => 2017, 'm' => 11, 'd' => 03),  array('y' => 2017, 'm' => 11,  'd' => 30)),
	'2017_12' => array(array('y' => 2017, 'm' => 12, 'd' => 01),  array('y' => 2017, 'm' => 12,  'd' => 31)),
	'2018_01' => array(array('y' => 2018, 'm' => 01, 'd' => 01),  array('y' => 2018, 'm' => 01,  'd' => 31)),
	'2018_02' => array(array('y' => 2018, 'm' => 02, 'd' => 01),  array('y' => 2018, 'm' => 03,  'd' => 01)),
	'2018_03' => array(array('y' => 2018, 'm' => 03, 'd' => 02),  array('y' => 2018, 'm' => 03,  'd' => 31)),
	'2018_04' => array(array('y' => 2018, 'm' => 04, 'd' => 01),  array('y' => 2018, 'm' => 05,  'd' => 01)),
	'2018_05' => array(array('y' => 2018, 'm' => 05, 'd' => 02),  array('y' => 2018, 'm' => 05,  'd' => 31))
);

// Current month
$a_currenthist = array('2018_06', 'June 1st, 2018', '2018-06-01');
