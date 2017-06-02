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
$c_appveyor = 'https://ci.appveyor.com/project/rpcs3/rpcs3/build/';
$c_pageresults = 3; // Default results per page (50)
$c_pagelimit = 7; // Default page limit on pages counter (lim/2)
$c_maintenance = false; // Maintenance Mode

// Background color for unknown commit div
$c_unkcommit = 'ffd700';

// Results per page
if (isset($_GET['l'])) {
	$a_pageresults = array(
	1 => 25,
	2 => 50,
	3 => 100,
	4 => 250
	);
} else {
	$a_pageresults = array(
	1 => 15,
	2 => 25,
	3 => 50,
	4 => 100
	);
}

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
"A" => "/img/icons/compat/CN.png", // Asia
"E" => "/img/icons/compat/EU.png", // Europe
"H" => "/img/icons/compat/CN.png", // Southern Asia
"K" => "/img/icons/compat/HK.png", // Hong Kong
"J" => "/img/icons/compat/JP.png", // Japan
"U" => "/img/icons/compat/US.png"  // USA
);

// Game media icons
$a_media = array(
"N" => '/img/icons/list/psn.png',
"B" => '/img/icons/list/rom.png',
"X" => '/img/icons/list/rom.png'
);

// Dates for history backups
$a_histdates = array(
	'2017_02' => '',
	'2017_03' => array('March 1st, 2017', 'March 29th, 2017'),
	'2017_04' => array('March 30th, 2017', 'April 30th, 2017'),
);

// Current month
$a_currenthist = array('2017_05', 'May 1st, 2017');

?>
