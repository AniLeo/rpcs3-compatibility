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

// Calls for the file that contains the functions needed
if (!@include_once('functions.php')) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once("objects/Build.php")) throw new Exception("Compat: Build.php is missing. Failed to include Build.php");

/*
return_code
    -3 - Illegal search
    -2 - Maintenance mode
    -1 - Current build is not a master build
     0 - No newer build found
     1 - Newer build found
*/
function checkForUpdates($commit = '') {

	// Standalone maintenance mode
	$maintenance = false;

	if ($maintenance) {
		$results['return_code'] = -2;
		return $results;
	}

	// If commit length is smaller than 7 chars
	if (!empty($commit) && (!ctype_alnum($commit) || strlen($commit) < 7)) {
		$results['return_code'] = -3;
		return $results;
	}

	// Default return code
	$results['return_code'] = 0;

	// Get latest build information
	$latest = Build::getLast();

	$results['latest_build']['pr'] = $latest->pr;
	$results['latest_build']['datetime'] = $latest->merge;
	$results['latest_build']['version'] = $latest->version;
	$results['latest_build']['windows']['download'] = $latest->url_win;
	$results['latest_build']['windows']['size'] = $latest->size_win;
	$results['latest_build']['windows']['checksum'] = $latest->checksum_win;
	$results['latest_build']['linux']['download'] = $latest->url_linux;
	$results['latest_build']['linux']['size'] = $latest->size_linux;
	$results['latest_build']['linux']['checksum'] = $latest->checksum_linux;

	if (!empty($commit)) {
		// Get user build information
		$db = getDatabase();
		$e_commit = mysqli_real_escape_string($db, substr($commit, 0, 7));
		$q_check = mysqli_query($db, "SELECT * FROM `builds` WHERE `commit` LIKE '{$e_commit}%' AND `type` = 'branch' ORDER BY `merge_datetime` DESC LIMIT 1;" );
		mysqli_close($db);

		// Check if the build exists as a master build
		if (mysqli_num_rows($q_check) === 0) {
			$results['return_code'] = -1;	// Current build not found
		} else {
			$r_check = mysqli_fetch_object($q_check);
			$results['current_build']['pr'] = (int) $r_check->pr;
			$results['current_build']['datetime'] = $r_check->merge_datetime;
			$results['current_build']['version'] = $r_check->version;
			if ((int) $r_check->pr !== $latest->pr)
				$results['return_code'] = 1;
		}
	}

	return $results;

}


/*
Check for updated builds with provided commit

return_code
	-3 - Illegal search
	-2 - Maintenance mode
	-1 - Current build is not a master build
	 0 - No newer build found
	 1 - Newer build found
*/
if (!isset($_GET['c']) || (isset($_GET['c']) && !is_array($_GET['c']))) {
	header('Content-Type: application/json');
	echo json_encode(checkForUpdates($_GET['c']), JSON_PRETTY_PRINT);
}
