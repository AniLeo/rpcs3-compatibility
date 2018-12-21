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


class HistoryEntry {

	public $title;				// String
	public $title2;				// String
	public $old_status;		// Int
	public $new_status;		// Int
	public $old_date;			// Date
	public $new_date;			// Date
	public $IDs;					// (String, Int)

	function __construct($maintitle, $alternativetitle, $oldstatus, $newstatus, $olddate, $newdate, $gid, $tid) {

		$this->title = $maintitle;
		if (!is_null($alternativetitle))
			$this->title2 = $alternativetitle;

		if (!is_null($oldstatus))
			$this->old_status = getStatusID($oldstatus);
		if (!is_null($olddate))
			$this->old_date = $olddate;

		$this->new_status = getStatusID($newstatus);
		$this->new_date = $newdate;

		$this->IDs = array($gid, $tid);

	}


	/**
		* rowToHistoryEntry
		* Obtains a HistoryEntry from given MySQL Row.
		*
		* @param object  $row								The MySQL Row (returned by mysqli_fetch_object($query))
		*
		* @return object $historyentry			HistoryEntry fetched from given Row
		*/
	public static function rowToHistoryEntry($row) {
		return new HistoryEntry($row->game_title, $row->alternative_title, $row->old_status, $row->new_status, $row->old_date, $row->new_date, $row->gid, $row->tid);
	}

}
