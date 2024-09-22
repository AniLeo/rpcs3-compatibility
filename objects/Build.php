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
if (!@include_once(__DIR__."/../functions.php"))
	throw new Exception("Compat: functions.php is missing. Failed to include functions.php");


class Build
{
	public  int    $pr;
	public  string $commit;
	public  string $version;
	public  string $merge;
	public ?int    $additions;
	public ?int    $deletions;
	public ?int    $files;
	public  bool   $broken;
	public ?string $title;

	public ?string $checksum_win;
	public ?int    $size_win;
	public ?string $filename_win;

	public ?string $checksum_linux;
	public ?int    $size_linux;
	public ?string $filename_linux;

	public ?string $checksum_mac;
	public ?int    $size_mac;
	public ?string $filename_mac;

	public  int    $author_id;
	public  string $author;

	public  string $fulldate;
	public  string $diffdate;

	function __construct( int    $pr,
	                      string $commit,
	                      string $version,
	                      int    $author_id,
	                      string $merge,
	                     ?int    $additions,
	                     ?int    $deletions,
	                     ?int    $files,
	                     ?string $checksum_win,
	                     ?int    $size_win,
	                     ?string $filename_win,
	                     ?string $checksum_linux,
	                     ?int    $size_linux,
	                     ?string $filename_linux,
	                     ?string $checksum_mac,
	                     ?int    $size_mac,
	                     ?string $filename_mac,
	                      bool   $broken,
	                     ?string $title)
	{
		$this->pr             = $pr;
		$this->commit         = $commit;
		$this->author_id      = $author_id;
		$this->merge          = $merge;
		$this->version        = substr($version, 0, 4) === "1.0." ? str_replace("1.0.", "0.0.0-", $version) : $version;
		$this->checksum_win   = $checksum_win;
		$this->size_win       = $size_win;
		$this->filename_win   = $filename_win;
		$this->checksum_linux = $checksum_linux;
		$this->size_linux     = $size_linux;
		$this->filename_linux = $filename_linux;
		$this->checksum_mac   = $checksum_mac;
		$this->size_mac       = $size_mac;
		$this->filename_mac   = $filename_mac;
		$this->broken         = $broken;
		$this->title          = $title;

		// A bug in GitHub API returns +0 -0 on some PRs
		if (!is_null($files) && $files > 0)
		{
			$this->additions = $additions;
			$this->deletions = $deletions;
			$this->files = $files;
		}
		else
		{
			$this->additions = null;
			$this->deletions = null;
			$this->files = null;
		}

		$datetime = date_create($this->merge);

		if (!$datetime)
			$this->fulldate = "0000-00-00";
		else
			$this->fulldate = date_format($datetime, "Y-m-d");

		$this->diffdate = getDateDiff($this->merge);
	}

	public function get_url_pr() : string
	{
		return "https://github.com/RPCS3/rpcs3/pull/{$this->pr}";
	}

	public function get_url_commit() : string
	{
		return "https://github.com/RPCS3/rpcs3/commit/{$this->commit}";
	}

	public function get_url_windows() : ?string
	{
		if (!is_null($this->filename_win))
		{
			// Previous builds are not on the GitHub binaries repository
			if (strtotime($this->fulldate) < strtotime("2018-06-07"))
			{
				return null;
			}

			return "https://github.com/RPCS3/rpcs3-binaries-win/releases/download/build-{$this->commit}/{$this->filename_win}";
		}
		return null;
	}

	public function get_url_linux() : ?string
	{
		if (!is_null($this->filename_linux))
		{
			return "https://github.com/RPCS3/rpcs3-binaries-linux/releases/download/build-{$this->commit}/{$this->filename_linux}";
		}
		return null;
	}

	public function get_url_mac() : ?string
	{
		if (!is_null($this->filename_mac))
		{
			return "https://github.com/RPCS3/rpcs3-binaries-mac/releases/download/build-{$this->commit}/{$this->filename_mac}";
		}
		return null;
	}

	public function get_url_author() : ?string
	{
		return "https://github.com/{$this->author}";
	}

	public function get_url_author_avatar() : ?string
	{
		return "https://avatars.githubusercontent.com/u/{$this->author_id}";
	}

	public function get_size_mb_windows() : ?float
	{
		if (!is_null($this->size_win))
		{
			return round($this->size_win / 1024 / 1024, 1);
		}
		return null;
	}

	public function get_size_mb_linux() : ?float
	{
		if (!is_null($this->size_linux))
		{
			return round($this->size_linux / 1024 / 1024, 1);
		}
		return null;
	}

	public function get_size_mb_mac() : ?float
	{
		if (!is_null($this->size_mac))
		{
			return round($this->size_mac / 1024 / 1024, 1);
		}
		return null;
	}

	public function get_commit_short() : string
	{
		return substr($this->commit, 0, 8);
	}

	/**
	* @param array<Build> $builds
	*/
	public static function import_authors(array &$builds) : void
	{
		$db = getDatabase();

		$a_contributors = array();
		$q_contributors = mysqli_query($db, "SELECT * FROM `contributors`;");

		if (is_bool($q_contributors))
			return;

		while ($row = mysqli_fetch_object($q_contributors))
		{
			// This should be unreachable unless the database structure is damaged
			if (!property_exists($row, "id") ||
					!property_exists($row, "username"))
			{
				continue;
			}

			$a_contributors[$row->id] = $row->username;
		}

		foreach ($builds as $build)
		{
			$build->author = $a_contributors[$build->author_id];
		}

		mysqli_close($db);
	}

	/**
	* @return array<Build> $builds
	*/
	public static function query_to_builds(mysqli_result $query) : array
	{
		$a_builds = array();

		if (mysqli_num_rows($query) === 0)
			return $a_builds;

		while ($row = mysqli_fetch_object($query))
		{
			// This should be unreachable unless the database structure is damaged
			if (!property_exists($row, "pr") ||
					!property_exists($row, "commit") ||
					!property_exists($row, "version") ||
					!property_exists($row, "author") ||
					!property_exists($row, "merge_datetime") ||
					!property_exists($row, "additions") ||
					!property_exists($row, "deletions") ||
					!property_exists($row, "changed_files") ||
					!property_exists($row, "checksum_win") ||
					!property_exists($row, "size_win") ||
					!property_exists($row, "filename_win") ||
					!property_exists($row, "checksum_linux") ||
					!property_exists($row, "size_linux") ||
					!property_exists($row, "filename_linux") ||
					!property_exists($row, "checksum_mac") ||
					!property_exists($row, "size_mac") ||
					!property_exists($row, "filename_mac") ||
					!property_exists($row, "broken") ||
					!property_exists($row, "title"))
			{
				continue;
			}

			$a_builds[] = new Build($row->pr,
			                        $row->commit,
			                        $row->version,
			                        $row->author,
			                        $row->merge_datetime,
			                        $row->additions,
			                        $row->deletions,
			                        $row->changed_files,
			                        $row->checksum_win,
			                        $row->size_win,
			                        $row->filename_win,
			                        $row->checksum_linux,
			                        $row->size_linux,
			                        $row->filename_linux,
			                        $row->checksum_mac,
			                        $row->size_mac,
			                        $row->filename_mac,
			                        !is_null($row->broken) && $row->broken == 1,
			                        $row->title);
		}

		self::import_authors($a_builds);

		return $a_builds;
	}

	public static function get_latest() : ?Build
	{
		$db = getDatabase();

		$query = mysqli_query($db, "SELECT * FROM `builds`
		                            WHERE `broken` IS NULL OR `broken` != 1
		                            ORDER BY `merge_datetime` DESC LIMIT 1;");

		if (is_bool($query))
			return null;

		$ret = self::query_to_builds($query);
		return $ret[0];
	}

	public static function get_version(string $version) : ?Build
	{
		$db = getDatabase();
		$s_version = mysqli_real_escape_string($db, $version);

		$query = mysqli_query($db, "SELECT * FROM `builds`
		                            WHERE `version` = \"{$s_version}\"
		                            AND (`broken` IS NULL OR `broken` != 1)
		                            ORDER BY `merge_datetime` DESC LIMIT 1;");

		if (is_bool($query))
			return null;

		$ret = self::query_to_builds($query);
		return $ret[0];
	}
}
