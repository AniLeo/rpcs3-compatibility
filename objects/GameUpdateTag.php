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
if (!@include_once(__DIR__."/GameUpdatePackage.php")) throw new Exception("Compat: GameUpdatePackage.php is missing. Failed to include GameUpdatePackage.php");


class GameUpdateTag
{
	public $tag_id;         // string
	public $popup;          // string
	public $signoff;        // string
	public $popup_delay;    // ?int
	public $min_system_ver; // ?string

	public $packages;       // GameUpdatePackage[]
	public $changelogs;     // GameUpdateChangelog[]

	function __construct(string  $tag_id,
	                     string  $popup,
	                     string  $signoff,
	                     ?int    $popup_delay,
	                     ?string $min_system_ver)
	{
		$this->tag_id         = $tag_id;
		$this->popup          = $popup;
		$this->signoff        = $signoff;
		$this->popup_delay    = $popup_delay;
		$this->min_system_ver = $min_system_ver;
		$this->packages       = array();
	}

	public static function import_update_changelogs(array &$tags) : void
	{
		$db = getDatabase();

		$a_changelogs = array();
		$q_changelogs = mysqli_query($db, "SELECT `tag`, `package_version`, `paramhip_type`, `paramhip_content`
		FROM `game_update_paramhip`; ");

		while ($row = mysqli_fetch_object($q_changelogs))
		{
			$a_changelogs[$row->tag][$row->package_version][] = new GameUpdateChangelog($row->paramhip_type,
			                                                                            $row->paramhip_content);
		}

		foreach ($tags as $tag)
		{
			foreach ($tag->packages as $package)
			{
				if (isset($a_changelogs[$tag->tag_id][$package->version]))
				{
					$package->changelogs = $a_changelogs[$tag->tag_id][$package->version];
				}
			}
		}

		mysqli_close($db);
	}

	public static function import_update_packages(array &$tags) : void
	{
		$db = getDatabase();

		/*
		LEFT JOIN `game_update_paramhip`
		  ON SUBSTR(`game_update_package`.`tag`, 1, 9) = `game_update_paramhip`.`titleid`
			AND `game_update_package`.`version` = `game_update_paramhip`.`package_version`
		WHERE `paramhip_type` IS NULL OR `paramhip_type` = 'paramhip';
		*/

		$a_packages = array();
		$q_packages = mysqli_query($db, "SELECT `tag`, `version`, `size`, `sha1sum`, `ps3_system_ver`, `drm_type`
		FROM `game_update_package`");

		while ($row = mysqli_fetch_object($q_packages))
		{
			$a_packages[$row->tag][] = new GameUpdatePackage($row->version,
			                                                 $row->size,
			                                                 $row->sha1sum,
			                                                 $row->ps3_system_ver,
			                                                 $row->drm_type,
			                                                 "");
		}

		foreach ($tags as $tag)
		{
			$tag->packages = $a_packages[$tag->tag_id];
		}

		mysqli_close($db);
	}
}
