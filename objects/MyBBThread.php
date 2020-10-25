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


class MyBBThread
{
	public $tid;     // int
	public $fid;     // int
	public $subject; // string

	function __construct(int $tid, int $fid, string $subject)
	{
		$this->tid     = $tid;
		$this->fid     = $fid;
		$this->subject = $subject;
	}

	public function get_sid() : ?int
	{
		switch ($this->fid)
		{
			// Playable
			case 5:
				return 1;

			// Ingame
			case 6:
				return 2;

			// Intro
			case 7:
				return 3;

			// Loadable
			case 8:
				return 4;

			// Nothing
			case 9:
				return 5;

			default:
				return null;
		}
	}

	public function get_game_id() : ?string
	{
		// Thread title is not big enough to have a valid game id
		if (strlen($this->subject) < 14)
		{
			return null;
		}

		// No spacing between game title and game id
		if (substr($this->subject, -12, 1) !== ' ')
		{
			return null;
		}

		$game_id = substr($this->subject, -10, 9);

		// Invalid game id
		if (!isGameID($game_id))
		{
			return null;
		}

		return $game_id;
	}

	public function get_thread_url() : string
	{
		return "https://forums.rpcs3.net/thread-{$this->tid}.html";
	}
}
