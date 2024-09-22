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

/**
* @return array<string, mixed> $results
*/
function check_for_updates( string $api,
                           ?string $commit,
                           ?string $os_type,
                           ?string $os_arch,
                           ?string $os_version) : array
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

	// If the API version string is valid
	if (strlen($api) === 2 && substr($api, 0, 1) === 'v' && ctype_digit(substr($api, 1, 1)))
	{
		// Only accept v1 to v3
		if (!(substr($api, 1, 1) >= 1 && substr($api, 1, 1) <= 3))
		{
			$results['return_code'] = -3;
			return $results;
		}
	}

	// If commit exists, it's not alphanumeric and length is smaller than 7 chars
	if (!is_null($commit) && (!ctype_alnum($commit) || strlen($commit) < 7))
	{
		$results['return_code'] = -3;
		return $results;
	}

  // The latest build to be returned by the API
  // This can be the absolute latest, or an override depending on API v3 params
  $version = "latest";

	// If the API version is at least v3
	if (substr($api, 1, 1) >= 3)
	{
		// If any of the v3 required parameters is null
		if (is_null($os_type) || is_null($os_arch) || is_null($os_version))
		{
			$results['return_code'] = -3;
			return $results;
		}

    // v0.0.33-16940: Latest build to support macOS 12
    if ($os_type === "macos" && $os_arch === "x64" && (int)substr($os_version, 0, 2) < 13)
    {
      $version = "0.0.33-16940";
    }
	}

  // Get latest build information
  $latest = $version === "latest" ? Build::get_latest() : Build::get_version($version);

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

	if (!is_null($commit))
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
				// API v2 or newer code
				// When current and old build are master builds
				// in_array: Workaround for builds that freeze on boot if changelog data is sent
				if (substr($api, 1, 1) >= 2 && !in_array($current->pr, array("15390", "15392", "15394", "15395")))
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
Update API
Check for updated builds with provided commit

api
	v1 - Check for updated builds with provided commit
	v2 - Also returns changelog
	v3 - Accepts os_type, os_arch, os_version to determine latest compatible build

return_code
	-3 - Illegal search
	-2 - Maintenance mode
	-1 - Current build is not a master build
	 0 - No newer build found
	 1 - Newer build found
*/
if (isset($_GET['api']) && is_string($_GET['api']))
{
	$commit     = null;
	$os_type    = null;
	$os_arch    = null;
	$os_version = null;

	if (isset($_GET['c']) && is_string($_GET['c']))
	{
		$commit = $_GET['c'];
	}

	if (isset($_GET['os_type'])    && is_string($_GET['os_type']) &&
	    isset($_GET['os_arch'])    && is_string($_GET['os_arch']) &&
	    isset($_GET['os_version']) && is_string($_GET['os_version']))
	{
		$os_type    = $_GET['os_type'];
		$os_arch    = $_GET['os_arch'];
		$os_version = $_GET['os_version'];
	}

	$json = check_for_updates($_GET['api'], $commit, $os_type, $os_arch, $os_version);

	header('Content-Type: application/json');
	print(json_encode($json, JSON_PRETTY_PRINT));
}
