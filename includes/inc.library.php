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
if (!@include_once(__DIR__."/../functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/../classes/class.Library.php")) throw new Exception("Compat: class.Library.php is missing. Failed to include class.Library.php");


// Start: Microtime when page started loading
$start = getTime();


if (in_array($get['r'], $a_pageresults)) {
	if ($get['r'] == $a_pageresults[$c_pageresults]) { $g_pageresults = ''; }
	else { $g_pageresults = "r={$get['rID']}&"; }
}


// Count number of entries for page calculation and cache results on array
$entries = 1;
$a_db = array();
$handle = fopen(__DIR__."/../ps3tdb.txt", "r");
while (!feof($handle)) {
	$line = fgets($handle);
	if (in_array(mb_substr($line, 0, 4), $a_filter)) {

		$valid = true;

		if ($get['f'] != '') {
			if (strtolower(substr($line, 2, 1)) != $get['f']) { $valid = false; }
		}
		if ($get['t'] != '') {
			if (strtolower(substr($line, 0, 1)) != $get['t']) { $valid = false; }
		}

		if ($valid) {
			$a_db[$entries] = array(mb_substr($line, 0, 9) => mb_substr($line, 12));
			$entries++;
		}

	}

}
fclose($handle);
$pages = ceil($entries / $get['r']);
$currentPage = getCurrentPage($pages);
