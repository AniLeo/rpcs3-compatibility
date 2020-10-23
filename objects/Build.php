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


class Build
{
	public $pr;         // Int
	public $commit;     // String (40)
	public $version;    // String
	public $merge;      // Datetime
	public $additions;  // Int
	public $deletions;  // Int
	public $files;      // Int
	public $broken;     // Bool

	public $checksum_win;   // String
	public $size_win;       // Int
	public $filename_win;   // String

	public $checksum_linux; // String
	public $size_linux;     // Int
	public $filename_linux; // String

	public $fulldate;   // String
	public $diffdate;   // String

	public $author;      // String
	public $author_id;   // Int

	function __construct(int $pr, string $commit, string $version, int $author_id,
	                     string $merge, ?int $additions, ?int $deletions, ?int $files,
	                     ?string $checksum_win, ?int $size_win, ?string $filename_win,
	                     ?string $checksum_linux, ?int $size_linux, ?string $filename_linux, bool $broken)
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
		$this->broken         = $broken;

		// A bug in GitHub API returns +0 -0 on some PRs
		if (!is_null($files) && $files > 0)
		{
			$this->additions = $additions;
			$this->deletions = $deletions;
			$this->files = $files;
		}

		$this->fulldate = date_format(date_create($this->merge), "Y-m-d");
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

	public function get_url_author() : ?string
	{
		if (!is_null($this->author))
		{
			return "https://github.com/{$this->author}";
		}
		return null;
	}

	public function get_url_author_avatar() : ?string
	{
		if (!is_null($this->author_id))
		{
			return "https://avatars.githubusercontent.com/u/{$this->author_id}";
		}
		return null;
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

	public function get_commit_short() : string
	{
		return substr($this->commit, 0, 8);
	}

	public static function import_authors(array &$builds) : void
	{
		$db = getDatabase();

		$a_contributors = array();
		$q_contributors = mysqli_query($db, "SELECT * FROM `contributors`;");

		while ($row = mysqli_fetch_object($q_contributors))
		{
			$a_contributors[$row->id] = $row->username;
		}

		foreach ($builds as $build)
		{
			$build->author = $a_contributors[$build->author_id];
		}

		mysqli_close($db);
	}

	public static function query_to_build(mysqli_result $query) : array
	{
		$a_builds = array();

		if (mysqli_num_rows($query) === 0)
			return $a_builds;

		while ($row = mysqli_fetch_object($query))
		{
			$a_builds[] = new Build($row->pr, $row->commit, $row->version, $row->author, $row->merge_datetime,
			$row->additions, $row->deletions, $row->changed_files,
			$row->checksum_win, $row->size_win, $row->filename_win, $row->checksum_linux, $row->size_linux, $row->filename_linux, !is_null($row->broken));
		}

		self::import_authors($a_builds);

		return $a_builds;
	}

	public static function get_latest() : ?Build
	{
		$db = getDatabase();

		$query = mysqli_query($db, "SELECT * FROM `builds` WHERE `broken` IS NULL OR `broken` != 1 ORDER BY `merge_datetime` DESC LIMIT 1;");

		if (mysqli_num_rows($query) === 0)
			return null;

		$row = mysqli_fetch_object($query);
		mysqli_close($db);

		$build = new Build($row->pr, $row->commit, $row->version, $row->author, $row->merge_datetime,
		$row->additions, $row->deletions, $row->changed_files,
		$row->checksum_win, $row->size_win, $row->filename_win, $row->checksum_linux, $row->size_linux, $row->filename_linux, !is_null($row->broken));

		$ret = array($build);
		self::import_authors($ret);

		return $ret[0];
	}
}
