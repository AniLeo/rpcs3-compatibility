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


class HTTPQuery
{
	// Compat, Builds, Debug
	public $module;     // string
	// Results per page
	public $results;    // int
	// Game status
	public $status;     // int
	// Character search
	public $character;  // string
	// Game search
	public $search;     // string
	// Game media
	public $media;      // string
	// Game report date
	public $date;       // string
	// Search order by
	public $order;      // string
	// Move support
	public $move;       // int
	// 3D support
	public $stereo_3D;  // int
	// Application type
	public $type;       // string

	function __construct(array $get)
	{
		if      (isset($get['b']))
			$this->module = "builds";
		else if (isset($get['h']))
			$this->module = "history";
		else if (isset($get['a']))
			$this->module = "panel";
		else
			$this->module = "compat";

		if (isset($get['r']))
			$this->results = (int) $get['r'];

		if (isset($get['s']))
			$this->status = (int) $get['s'];
		else
			$this->status = 0;

		if (isset($get['c']))
			$this->character = (string) $get['c'];

		if (isset($get['g']))
			$this->search = (string) $get['g'];

		if (isset($get['t']))
			$this->media = (string) $get['t'];

		if (isset($get['d']))
			$this->date = (int) $get['d'];

		if (isset($get['o']))
			$this->order = (string) $get['o'];
	}

	function get_except(array $exclusions) : string
	{
		$query = array();
		$ret = "";

		// Module
		if (isset($this->module) && !in_array("module", $exclusions))
		{
			switch ($this->module)
			{
				case "builds":
					$ret = 'b';
					break;
				case "history":
					$ret = 'h';
					break;
				case "panel":
					$ret = 'a';
					break;
			}
		}

		// Results
		if (isset($this->results) && !in_array("results", $exclusions))
		{
			if ($this->results !== 50)
				$query['r'] = $this->results;
		}

		// Status
		if (isset($this->status) && !in_array("status", $exclusions))
		{
			if ($this->status !== 0)
				$query['s'] = $this->status;
		}

		// Character
		if (isset($this->character) && !in_array("character", $exclusions))
		{
			$query['c'] = $this->character;
		}

		// Search
		if (isset($this->search) && !in_array("search", $exclusions))
		{
			if (!empty($this->search))
				$query['g'] = $this->search;
		}

		// Media
		if (isset($this->media) && !in_array("media", $exclusions))
		{
			if (!empty($this->media))
				$query['t'] = $this->media;
		}

		// Date
		if (isset($this->date) && !in_array("date", $exclusions))
		{
			$query['d'] = $this->date;
		}

		// Order
		if (isset($this->order) && !in_array("order", $exclusions))
		{
			$query['o'] = $this->order;
		}

		// Move Support
		if (isset($this->move) && !in_array("move", $exclusions))
		{
			if ($this->move !== 0)
				$query["move"] = $this->move;
		}

		// 3D Support
		if (isset($this->stereo_3D) && !in_array("3D", $exclusions))
		{
			if ($this->stereo_3D !== 0)
				$query["3D"] = $this->stereo_3D;
		}

		// Application Type
		if (isset($this->type) && !in_array("type", $exclusions))
		{
			if ($this->type !== 0)
				$query["type"] = $this->type;
		}

		if (!empty($query))
		{
			if (!empty($ret))
				$ret .= '&';

			$ret .= http_build_query($query);
		}

		return $ret;
	}

	public static function to_exclusions(array $inclusions) : array
	{
		$exclusions = array();

		if (!in_array("results", $inclusions))
			$exclusions[] = "results";

		if (!in_array("status", $inclusions))
			$exclusions[] = "status";

		if (!in_array("character", $inclusions))
			$exclusions[] = "character";

		if (!in_array("search", $inclusions))
			$exclusions[] = "search";

		if (!in_array("media", $inclusions))
			$exclusions[] = "media";

		if (!in_array("date", $inclusions))
			$exclusions[] = "date";

		if (!in_array("order", $inclusions))
			$exclusions[] = "order";

		if (!in_array("move", $inclusions))
			$exclusions[] = "move";

		if (!in_array("3D", $inclusions))
			$exclusions[] = "3D";

		if (!in_array("type", $inclusions))
			$exclusions[] = "type";

		return $exclusions;
	}
}
