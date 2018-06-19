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


function checkForUpdates($commit) {

	// Standalone maintenance mode
	$maintenance = false;

	if ($maintenance) {
		$results['return_code'] = -2;
		return $results;
	}

	// If commit length is smaller than 7 chars
	if (!ctype_alnum($commit) || strlen($commit) < 7) {
		$results['return_code'] = -3;
		return $results;
	}

	$db = getDatabase();

	$e_commit = mysqli_real_escape_string($db, substr($commit, 0, 7));

	// Get user build information
	$q_checkWin   = mysqli_query($db, "SELECT * FROM builds_windows WHERE commit LIKE '{$e_commit}%' AND type = 'branch' ORDER BY merge_datetime DESC LIMIT 1;" );
	$q_checkLinux = mysqli_query($db, "SELECT * FROM builds_linux WHERE commitID LIKE '{$e_commit}%' ORDER BY datetime DESC LIMIT 1;" );

	// Default return_code to 0 - No newer build found
	$results['return_code'] = 0;

	// Get latest build information
	$q_latestWin = mysqli_query($db, "SELECT * FROM builds_windows ORDER BY merge_datetime DESC LIMIT 1; ");
	$q_latestLinux = mysqli_query($db, "SELECT * FROM builds_linux ORDER BY datetime DESC LIMIT 1;" );

	$r_latestWin = mysqli_fetch_object($q_latestWin);
	$r_latestLinux = mysqli_fetch_object($q_latestLinux);

	$results['latest_build']['pr'] = $r_latestWin->pr;
	$results['latest_build']['windows']['datetime'] = $r_latestWin->merge_datetime;
	$results['latest_build']['windows']['download'] = "https://github.com/RPCS3/rpcs3-binaries-win/releases/download/build-{$r_latestWin->commit}/{$r_latestWin->filename}";

	$results['latest_build']['linux']['datetime'] = $r_latestLinux->datetime;
	$results['latest_build']['linux']['download'] = "https://rpcs3.net/cdn/builds/{$r_latestLinux->buildname}";


	// Check if the build exists as a master build
	if (mysqli_num_rows($q_checkWin) === 0 || mysqli_num_rows($q_checkLinux) === 0) {

		$results['return_code'] = -1; // Not a master build

	} else {

		$r_checkWin = mysqli_fetch_object($q_checkWin);
		$r_checkLinux = mysqli_fetch_object($q_checkLinux);

		if ($r_checkWin->commit != $r_latestWin->commit) {
			$results['return_code'] = 1; // Newer build found
		}

		$results['current_build']['pr'] = $r_checkWin->pr;
		$results['current_build']['windows']['datetime'] = $r_checkWin->merge_datetime;
		$results['current_build']['linux']['datetime'] = $r_checkLinux->datetime;

	}

	mysqli_close($db);

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
if (isset($_GET['c'])) {
	header('Content-Type: application/json');
	echo json_encode(checkForUpdates($_GET['c']), JSON_PRETTY_PRINT);
}
