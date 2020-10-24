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


class HTMLInput
{
	public $name;        // string
	public $type;        // string
	public $value;       // string
	public $placeholder; // string

	function __construct(string $name, string $type, string $value, string $placeholder)
	{
		$this->name        = $name;
		$this->type        = $type;
		$this->value       = $value;
		$this->placeholder = $placeholder;
	}

	public function to_string() : string
	{
		return "<input name=\"{$this->name}\" type=\"{$this->type}\" value=\"{$this->value}\" placeholder=\"{$this->placeholder}\">".PHP_EOL;
	}

	public function print() : void
	{
		echo $this->to_string();
	}
}
