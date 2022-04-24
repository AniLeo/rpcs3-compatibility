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


class Profiler
{
	public static string $title = "Profiler";
	/** @var array<array<string, string|int|float>> $data **/
	public static array  $data;
	public static int    $size = 0;
	public static int    $mem_start;

	public static function setTitle(string $title) : void
	{
		global $get, $c_profiler;

		if ($get['w'] == NULL || !$c_profiler)
			return;

		if (!isset(self::$mem_start))
			self::$mem_start = memory_get_usage(false);

		self::$title = $title;
	}

	public static function addData(string $description) : void
	{
		global $get, $c_profiler;

		if ($get['w'] == NULL || !$c_profiler)
			return;

		if (!isset(self::$mem_start))
			self::$mem_start = memory_get_usage(false);

		self::$data[] = array(
			'desc' => $description,
			'time' => microtime(true) * 1000,                   // Milliseconds
			'mem'  => round(memory_get_usage(false)/1024, 2)    // KBs
		);
		self::$size++;
	}

	public static function getDataHTML() : string
	{
		global $get, $c_profiler;

		if (is_null($get['w']) || !$c_profiler || empty(self::$data))
			return "";

		$ret = "<p><b>".self::$title."</b><br>";

		if (PHP_OS_FAMILY !== "Windows")
		{
			$load = sys_getloadavg();
			$ret .= "<p>";
			$ret .= "Load (1m): {$load[0]}<br>";
			$ret .= "Load (5m): {$load[1]}<br>";
			$ret .= "Load (15m): {$load[2]}<br>";
			$ret .= "</p>";
		}

		if (isset(self::$mem_start))
		{
			$ret .= "<p>".PHP_EOL;
			$ret .= "Start Memory: ".round(self::$mem_start/1024, 2)." KB<br>".PHP_EOL;
			$ret .= "End Memory: ".round(memory_get_usage(false)/1024, 2)." KB<br>".PHP_EOL;
			$ret .= "Peak Memory: ".round(memory_get_peak_usage(false)/1024, 2)." KB<br>".PHP_EOL;
			$ret .= "</p>";
		}

		if (self::$size > 1)
		{
			$ret .= "<p>".PHP_EOL;
			for ($i = 0; $i < self::$size - 1; $i++)
			{
				$ret .= sprintf("%.5f ms &nbsp;|&nbsp; %06.2f KB &nbsp;-&nbsp; %s<br>".PHP_EOL,
				                self::$data[$i+1]['time'] - self::$data[$i]['time'],
				                self::$data[$i+1]['mem'] - self::$data[$i]['mem'],
				                self::$data[$i]['desc']);
			}
			$ret .= "</p>";
		}

		return $ret;
	}
}
