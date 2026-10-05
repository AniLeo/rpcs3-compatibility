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
if (!@include_once(__DIR__."/../functions.php")) throw new Exception("Compat: Failed to include functions.php");


class Profiler
{
    public static string $title = "Profiler";

    // Memory usage at the start
    public static int    $mem_start;

    // Data entries
    /** @var array<string> $desc **/
    public static array  $desc;
    /** @var array<float> $time **/
    public static array  $time;
    /** @var array<float> $mem **/
    public static array  $mem;


    public static function start_profiler(string $title) : void
    {
        global $get, $c_profiler;

        if ($get['w'] == NULL || !$c_profiler)
            return;

        if (!isset(self::$mem_start))
            self::$mem_start = memory_get_usage(false);

        self::$title = $title;
    }

    public static function add_data(string $description) : void
    {
        global $get, $c_profiler;

        if ($get['w'] == NULL || !$c_profiler)
            return;

        if (!isset(self::$mem_start))
            self::$mem_start = memory_get_usage(false);

        self::$desc[] = $description;
        self::$time[] = microtime(true) * 1000;                 // Milliseconds
        self::$mem[]  = round(memory_get_usage(false)/1024, 2); // KBs
    }

    public static function get_data_html() : string
    {
        global $get, $c_profiler;

        if (is_null($get['w']) || !$c_profiler || empty(self::$desc) ||
                empty(self::$time) || empty(self::$mem))
            return "";

        $ret = "<div><b>".self::$title."</b>";

        if (isset(self::$mem_start))
        {
            $ret .= "<div>".PHP_EOL;
            $ret .= "<b>Start Memory:</b> ".round(self::$mem_start/1024, 2)." KB<br>".PHP_EOL;
            $ret .= "<b>End Memory:</b> ".round(memory_get_usage(false)/1024, 2)." KB<br>".PHP_EOL;
            $ret .= "<b>Peak Memory:</b> ".round(memory_get_peak_usage(false)/1024, 2)." KB<br>".PHP_EOL;
            $ret .= "</div>";
        }

        if (PHP_OS_FAMILY !== "Windows")
        {        
            $load = sys_getloadavg();

            $cpuinfo = file_get_contents("/proc/cpuinfo");
            if ($cpuinfo !== false && $load !== false)
            {
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                $cpu_count = count($matches[0]);

                $ret .= "<div>";
                $ret .= sprintf("<b>CPU Load (1m):</b>  %.2f%%<br>", $load[0] / $cpu_count * 100);
                $ret .= sprintf("<b>CPU Load (5m):</b>  %.2f%%<br>", $load[1] / $cpu_count * 100);
                $ret .= sprintf("<b>CPU Load (15m):</b> %.2f%%<br>", $load[2] / $cpu_count * 100);
                $ret .= "</div>";
            }

            // Include total/available meminfo if readable
            $meminfo = file_get_contents("/proc/meminfo");
            if ($meminfo !== false)
            {
                $lines = explode(PHP_EOL, $meminfo);
                $mem_total = 0;
                $mem_available = 0;
                
                foreach ($lines as $line)
                {
                    $pieces = array();

                    if (preg_match("/^MemTotal:\s+(\d+)\skB$/", $line, $pieces))
                        $mem_total = $pieces[1];
                    else if (preg_match("/^MemAvailable:\s+(\d+)\skB$/", $line, $pieces))
                        $mem_available = $pieces[1];

                    if ($mem_total > 0 && $mem_available > 0)
                        break;
                }

                $ret .= "<div>";
                $ret .= "<b>Available Memory:</b> ".round($mem_available/1024, 2)." MB<br>".PHP_EOL;
                $ret .= "<b>Total Memory:</b> ".round($mem_total/1024, 2)." MB<br>".PHP_EOL;
                $ret .= "</div>";
            }
        }

        $size = count(self::$desc);

        // This should be unreachable
        if (count(self::$time) != $size || count(self::$mem) != $size)
            return "";

        if ($size > 1)
        {
            $ret .= "<div>".PHP_EOL;
            $now = microtime(true) * 1000;

            for ($i = 0; $i < $size; $i++)
            {
                $next = ($i + 1 < $size) ? self::$time[$i + 1] : $now;

                $ret .= sprintf("%.3f ms &nbsp;| &nbsp; %s<br>".PHP_EOL,
                                $next - self::$time[$i],
                                /*self::$mem[$i+1] - self::$mem[$i],*/
                                self::$desc[$i]);
            }
            $ret .= "</div>";
        }

        return $ret;
    }
}
