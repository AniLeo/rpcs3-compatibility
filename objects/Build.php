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


class Build {

	public $pr;         // Int
	public $commit;     // String (40)
	public $author;     // String
	public $authorID;   // Int
	public $merge;      // Datetime
	public $version;    // String
	public $additions;  // Int
	public $deletions;  // Int
	public $files;      // Int

	public $fulldate;   // String
	public $diffdate;   // String

	public $checksum_win;   // String
	public $size_win;       // Int
	public $filename_win;   // String
	public $sizeMB_win;     // Float
	public $url_win;        // String

	public $checksum_linux; 	// String
	public $size_linux;       // Int
	public $filename_linux;   // String
	public $sizeMB_linux;     // Float
	public $url_linux;        // String


	function __construct(&$a_contributors, $pr, $commit, $version, $authorID, $merge, $additions, $deletions, $files, $buildjob,
	$checksum_win, $size_win, $filename_win, $checksum_linux, $size_linux, $filename_linux) {
		$this->pr = (Int) $pr;
		$this->commit = (String) $commit;

		// Gets author username from ID
		// Use contributors array if existent, otherwise fetch directly from the database
		if (!is_null($a_contributors)) {
			$this->author = (String) $a_contributors[$authorID];
		} else {
			$db = getDatabase();
			$this->author = (String) mysqli_fetch_object(mysqli_query($db, "SELECT `username` FROM `contributors` WHERE `id` = {$authorID};"))->username;
			mysqli_close($db);
		}
		$this->authorID = $authorID;

		$this->merge = $merge;

		$this->version = substr($version, 0, 4) == "1.0." ? str_replace("1.0.", "0.0.0-", $version) : $version;

		// A bug in GitHub API returns +0 -0 on some PRs
		if (!is_null($files) && $files > 0) {
			$this->additions = (Int) $additions;
			$this->deletions = (Int) $deletions;
			$this->files = (Int) $files;
		} else {
			$this->additions = '?';
			$this->deletions = '?';
			$this->files = '?';
		}

		$this->fulldate = date_format(date_create($this->merge), "Y-m-d");
		$this->diffdate = getDateDiff($this->merge);

		if (!is_null($checksum_win)) {
			$this->checksum_win = (String) $checksum_win;
		}

		if (!is_null($size_win)) {
			$this->size_win = (Int) $size_win;
			$this->sizeMB_win = !is_null($this->size_win) ? round(((int)$this->size_win) / 1024 / 1024, 1) : 0;
		}

		if (!is_null($filename_win) && is_null($buildjob)) {
			$this->filename_win = $filename_win;
			$this->url_win = "https://github.com/RPCS3/rpcs3-binaries-win/releases/download/build-{$this->commit}/{$this->filename_win}";
		}

		if (!is_null($checksum_linux)) {
			$this->checksum_linux = (String) $checksum_linux;
		}

		if (!is_null($size_linux)) {
			$this->size_linux = (Int) $size_linux;
			$this->sizeMB_linux = !is_null($this->size_linux) ? round(((int)$this->size_linux) / 1024 / 1024, 1) : 0;
		}

		if (!is_null($filename_linux)) {
			$this->filename_linux = $filename_linux;
			$this->url_linux = "https://github.com/RPCS3/rpcs3-binaries-linux/releases/download/build-{$this->commit}/{$this->filename_linux}";
		}

	}


	/**
		* rowToBuild
		* Obtains a Build from given MySQL Row.
		*
		* @param object  $row       The MySQL Row (returned by mysqli_fetch_object($query))
		*
		* @return object $build     Build fetched from given Row
		*/
	public static function rowToBuild($row, &$a_contributors) {
		return new Build($a_contributors, $row->pr, $row->commit, $row->version, $row->author, $row->merge_datetime,
		$row->additions, $row->deletions, $row->changed_files, $row->buildjob,
		$row->checksum_win, $row->size_win, $row->filename_win, $row->checksum_linux, $row->size_linux, $row->filename_linux);
	}

	/**
		* queryToBuild
		* Obtains array of Builds from given MySQL Query.
		*
		* @param object  $query        The MySQL Query (returned by mysqli_query())
		*
		* @return object $array        Array of Builds fetched from given Query
		*/
	public static function queryToBuild($query) {
		$db = getDatabase();

		$a_contributors = array();
		$q_contributors = mysqli_query($db, "SELECT * FROM `contributors`;");
		while ($row = mysqli_fetch_object($q_contributors))
			$a_contributors[$row->id] = $row->username;

		$a_builds = array();
		while ($row = mysqli_fetch_object($query))
			$a_builds[] = self::rowToBuild($row, $a_contributors);

		mysqli_close($db);

		return $a_builds;
	}

	/**
		* getLatest
		* Obtains the most recent Build.
		*
		* @return object $build        Most recent build
		*/
	public static function getLatest() {
		$db = getDatabase();
		$query = mysqli_query($db, "SELECT * FROM `builds` ORDER BY `merge_datetime` DESC LIMIT 1;");
		if (mysqli_num_rows($query) === 0) return null;
		$row = mysqli_fetch_object($query);
		mysqli_close($db);

		$a_contributors = null; // Strict Standards: Only variables should be passed by reference

		return self::rowToBuild($row, $a_contributors);
	}

}
