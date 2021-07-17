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


class GameItem
{
	public $game_id;   // string
	public $thread_id; // int
	public $update;    // ?string

	public $tags;      // GameUpdateTag[]

	function __construct(string $game_id, int $thread_id, ?string $update)
	{
		$this->game_id   = $game_id;
		$this->thread_id = $thread_id;
		$this->update    = $update;
		$this->tags      = array();
	}

	function get_media_id() : string
	{
		return substr($this->game_id, 0, 1);
	}

	function get_region_id() : string
	{
		return substr($this->game_id, 2, 1);
	}

	function get_thread_url() : string
	{
		return "https://forums.rpcs3.net/thread-{$this->thread_id}.html";
	}
}
