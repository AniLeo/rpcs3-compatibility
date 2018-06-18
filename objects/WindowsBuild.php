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


class WindowsBuild {

  public $pr;         // Int
  public $commit;     // String (40)
  public $author;     // String
  public $merge;      // Datetime
  public $version;    // String
  public $additions;  // Int
  public $deletions;  // Int
  public $files;      // Int
  public $checksum;   // String
  public $size;       // Int
  public $filename;   // String

  public $sizeMB;     // Float
  public $fulldate;   // String
  public $diffdate;   // String
  public $url;        // String


  function __construct($pr, $commit, $author, $merge, $version, $additions, $deletions, $files, $checksum, $size, $filename) {

    global $c_appveyor;

    $this->pr = (Int) $pr;
    $this->commit = (String) $commit;
    $this->author = (String) $author;
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

    if (!is_null($checksum)) {
      $this->checksum = (String) $checksum;
    }

    if (!is_null($size)) {
      $this->size = (Int) $size;
      $this->sizeMB = !is_null($this->size) ? round(((int)$this->size) / 1024 / 1024, 1) : 0;
    }

    if (!is_null($filename)) {
      $this->filename = $filename;
    }

    $this->fulldate = date_format(date_create($this->merge), "Y-m-d");
    $this->diffdate = getDateDiff($this->merge);

    // All PRs starting 2018-06-02 are hosted on rpcs3/rpcs3-binaries-win
    $this->url = strtotime($this->merge) > 1528416000 ? "https://github.com/RPCS3/rpcs3-binaries-win/releases/download/build-{$this->commit}/{$this->filename}" : "{$c_appveyor}{$this->version}/artifacts";

  }

}
