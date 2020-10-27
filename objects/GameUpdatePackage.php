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


class GameUpdatePackage
{
	public $version;        // string
	public $size;           // int
	public $sha1sum;        // string
	public $url;            // string
	public $ps3_system_ver; // string
	public $drm_type;       // string

	function __construct(string $version,
	                     int    $size,
	                     string $sha1sum,
	                     string $url,
	                     string $ps3_system_ver,
	                     string $drm_type)
	{
		$this->version        = $version;
		$this->size           = $size;
		$this->sha1sum        = $sha1sum;
		$this->url            = $url;
		$this->ps3_system_ver = $ps3_system_ver;
		$this->drm_type       = $drm_type;
	}
}
