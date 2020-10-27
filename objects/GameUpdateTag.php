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


class GameUpdateTag
{
	public $tag_id;         // string
	public $popup;          // bool
	public $signoff;        // bool
	public $popup_delay;    // int
	public $min_system_ver; // string

	function __construct(string $tag_id,
	                     bool   $popup,
	                     bool   $signoff,
	                     int    $popup_delay,
	                     string $min_system_ver)
	{
		$this->tag_id         = $tag_id;
		$this->popup          = $popup;
		$this->signoff        = $signoff;
		$this->popup_delay    = $popup_delay;
		$this->min_system_ver = $min_system_ver;
	}
}
