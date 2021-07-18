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
	public string $module;    // Compat, Builds, Debug
	public int    $results;   // Results per page
	public int    $status;    // Game status
	public string $character; // Character search
	public string $search;    // Game search
	public string $media;     // Game media
	public string $date;      // Game report date
	public string $order;     // Search order by
	public int    $move;      // Move support
	public int    $stereo_3D; // 3D support
	public string $type;      // Application type

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
			if ($this->results !== 25)
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
		$parameters = array("results", "status", "character", "search",
		                    "media", "date", "order", "move", "3D", "type");

		foreach ($parameters as $parameter)
		{
			if (!in_array($parameter, $inclusions))
				$exclusions[] = $parameter;
		}

		return $exclusions;
	}
}
