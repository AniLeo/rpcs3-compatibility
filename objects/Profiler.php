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


class Profiler {

  public static $title;     // String
  public static $data;      // [(String, Int, Float)]
  public static $size = 0;  // Int
  public static $mem_start; // Int


  public static function setTitle($title) {
    global $get, $c_profiler;

    if (!$get['w'] || !$c_profiler)
      return;

    if (!isset(self::$mem_start))
      self::$mem_start = memory_get_usage(false);

    self::$title = $title;
  }

  public static function addData($description) {
    global $get, $c_profiler;

    if (!$get['w'] || !$c_profiler)
      return;

    if (!isset(self::$mem_start))
      self::$mem_start = memory_get_usage(false);

    self::$data[] = array(
      'desc' => $description,
      'time' => microtime(true) * 10000000,               // Microseconds
      'mem'  => round(memory_get_usage(false)/1024, 2));  // KBs
    self::$size++;
  }

  public static function getDataHTML() {
    global $get, $c_profiler;

    if (!$get['w'] || !$c_profiler || is_null(self::$data) || empty(self::$data))
      return "";

    if (isset(self::$mem_start)) {
      $ret = "<p style='line-height:20px; padding-bottom:15px;'>";
      $ret .= "Start Memory: ".round(self::$mem_start/1024, 2)."kB<br>";
      $ret .= "End Memory: ".round(memory_get_usage(false)/1024, 2)."kB<br>";
      $ret .= "Peak Memory: ".round(memory_get_peak_usage(false)/1024, 2)."kB<br>";
      $ret .= "</p>";
    }

    $ret .= "<p style='line-height:20px; padding-bottom:15px;'><b>".self::$title."</b><br>";
		for ($i = 0; $i < self::$size - 1; $i++)
			$ret .= sprintf("%05dμs &nbsp;-&nbsp; %.02fKBs &nbsp;-&nbsp; %s<br>", self::$data[$i+1]['time'] - self::$data[$i]['time'], self::$data[$i]['mem'], self::$data[$i]['desc']);
    $ret .= "</p>";

    return $ret;
  }

}
