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

/**
* @return array<string, mixed> $results
*/
function checkForUpdates(string $api, string $commit = '') : array
{
	// Standalone maintenance mode
	/*
	$maintenance = false;

	if ($maintenance)
	{
		$results['return_code'] = -2;
		return $results;
	}
	*/

	// Default return code
	$results['return_code'] = 0;

	// If commit length is smaller than 7 chars
	if (!empty($commit) && (!ctype_alnum($commit) || strlen($commit) < 7))
	{
		$results['return_code'] = -3;
		return $results;
	}

	// Get latest build information
	$latest = Build::get_latest();

	if (is_null($latest))
	{
		$results['return_code'] = -2;
		return $results;
	}

	$results['latest_build']['pr']                  = $latest->pr;
	$results['latest_build']['datetime']            = $latest->merge;
	$results['latest_build']['version']             = $latest->version;
	$results['latest_build']['windows']['download'] = $latest->get_url_windows();
	$results['latest_build']['windows']['size']     = $latest->size_win;
	$results['latest_build']['windows']['checksum'] = $latest->checksum_win;
	$results['latest_build']['linux']['download']   = $latest->get_url_linux();
	$results['latest_build']['linux']['size']       = $latest->size_linux;
	$results['latest_build']['linux']['checksum']   = $latest->checksum_linux;
	$results['latest_build']['mac']['download']     = $latest->get_url_mac();
	$results['latest_build']['mac']['size']         = $latest->size_mac;
	$results['latest_build']['mac']['checksum']     = $latest->checksum_mac;

	if (!empty($commit))
	{
		// Get user build information
		$db = getDatabase();
		$current = array();
		$e_commit = mysqli_real_escape_string($db, substr($commit, 0, 7));
		$q_check = mysqli_query($db, "SELECT * FROM `builds`
		                              WHERE `commit` LIKE '{$e_commit}%'
		                                AND `type` = 'branch'
		                              ORDER BY `merge_datetime` DESC
		                              LIMIT 1;");
		if (!is_bool($q_check))
		{
			$current = Build::query_to_builds($q_check);
		}

		// Check if the build exists as a master build
		if (empty($current))
		{
			$results['return_code'] = -1;	// Current build not found
		}
		else
		{
			$current = $current[0];

			$results['current_build']['pr']                  = $current->pr;
			$results['current_build']['datetime']            = $current->merge;
			$results['current_build']['version']             = $current->version;
			$results['current_build']['windows']['download'] = $current->get_url_windows();
			$results['current_build']['windows']['size']     = $current->size_win;
			$results['current_build']['windows']['checksum'] = $current->checksum_win;
			$results['current_build']['linux']['download']   = $current->get_url_linux();
			$results['current_build']['linux']['size']       = $current->size_linux;
			$results['current_build']['linux']['checksum']   = $current->checksum_linux;
			$results['current_build']['mac']['download']     = $current->get_url_mac();
			$results['current_build']['mac']['size']         = $current->size_mac;
			$results['current_build']['mac']['checksum']     = $current->checksum_mac;

			if ($latest->pr !== $current->pr)
			{
				// v2 code
				// When current and old build are master builds
				if ($api === "v2")
				{
					$q_between = mysqli_query($db, "SELECT `version`, `title` FROM `builds`
					                                WHERE `merge_datetime`
					                                BETWEEN CAST('{$current->merge}' AS DATETIME) + INTERVAL 1 SECOND
					                                AND CAST('{$latest->merge}' AS DATETIME)
					                                ORDER BY `merge_datetime` DESC
					                                LIMIT 500;");

					if (!is_bool($q_between))
					{
						while ($row = mysqli_fetch_object($q_between))
						{
							// This should be unreachable unless the database structure is damaged
							if (!property_exists($row, "version") ||
									!property_exists($row, "title"))
							{
								continue;
							}

							$results['changelog'][] = array("version" => $row->version,
							                                "title" => $row->title);
						}
					}
				}

				mysqli_query($db, "UPDATE `builds`
				                   SET `ping_outdated` = `ping_outdated` + 1
				                   WHERE `pr` = {$current->pr}
				                   LIMIT 1;");
				$results['return_code'] = 1;
			}
			else
			{
				mysqli_query($db, "UPDATE `builds`
				                   SET `ping_updated` = `ping_updated` + 1
				                   WHERE `pr` = {$current->pr}
				                   LIMIT 1;");
			}
		}
		mysqli_close($db);
	}

	return $results;
}


/*
API v1
Check for updated builds with provided commit

return_code
	-3 - Illegal search
	-2 - Maintenance mode
	-1 - Current build is not a master build
	 0 - No newer build found
	 1 - Newer build found
*/
if (isset($_GET['api']) && !is_array($_GET['api']) && ($_GET['api'] === "v1" || $_GET['api'] === "v2"))
{
	if (!isset($_GET['c']) || (isset($_GET['c']) && !is_array($_GET['c'])))
	{
		header('Content-Type: application/json');
		echo json_encode(checkForUpdates($_GET['api'], $_GET['c']), JSON_PRETTY_PRINT);
	}
}
