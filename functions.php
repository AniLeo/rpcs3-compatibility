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

if(!@include_once("config.php")) throw new Exception("Compat: config.php is missing. Failed to include config.php");
if(!@include_once(__DIR__."/html/HTML.php")) throw new Exception("Compat: HTML.php is missing. Failed to include HTML.php");


// Productcode info: PSDevWiki (http://www.psdevwiki.com/ps3/Productcode)


/**
    * getDatabase
    *
    * Establishes a database connection and sets utf8mb4 charset
    *
    * @return mysqli Connection to MySQL Server
    */
function getDatabase() : mysqli
{
    if (!defined("db_host") || !defined("db_user") || !defined("db_pass") ||
        !defined("db_name") || !defined("db_port"))
        exit("[COMPAT] Database: Missing connection data");

    $db = mysqli_connect(db_host, db_user, db_pass, db_name, (int) db_port);

    if (!$db)
        trigger_error("[COMPAT] Database: Connection could not be established", E_USER_ERROR);

    mysqli_set_charset($db, "utf8mb4");
    return $db;
}


/**
    * getGameType
    *
    * Obtains Game Type by checking Game ID's fourth character.
    * Returns Game Type as a string, empty if Game Type is invalid/unknown.
    *
    * @param string $gid GameID: 9 character ID that identifies a game
    *
    * @return string
    */
function getGameType(string $gid) : string
{
    // Physical
    if (substr($gid, 0, 1) === "B" || substr($gid, 0, 1) === "X")
    {
        if (substr($gid, 3, 1) === "D")  { return "Demo"; }             // Demo
        if (substr($gid, 3, 1) === "M")  { return "Malayan Release"; }  // Malayan Release
        if (substr($gid, 3, 1) === "S")  { return "Retail Release"; }   // Retail Release
    }
    // Digital
    else if (substr($gid, 0, 1) === "N")
    {
        if (substr($gid, 3, 1) === "A")  { return "First Party PS3"; }  // First Party PS3 (Demo/Retail)
        if (substr($gid, 3, 1) === "B")  { return "Licensed PS3"; }     // Licensed PS3 (Demo/Retail)
        if (substr($gid, 3, 1) === "C")  { return "First Party PS2"; }  // First Party PS2 Classic (Demo/Retail)
        if (substr($gid, 3, 1) === "D")  { return "Licensed PS2"; }     // Licensed PS2 (Demo/Retail)
        if (substr($gid, 3, 1) === "E")  { return "First Party PS1"; }  // First Party PS1 Classic (Demo/Retail)
        if (substr($gid, 3, 1) === "F")  { return "Licensed PS1"; }     // Licensed PS1 (Demo/Retail)
    }

    // We don't care about the other types as they won't be listed
    return "";
}


/**
    * isValid
    *
    * Checks if string only has allowed characters.
    * Returns true if valid or false if invalid.
    * Used for the searchbox.
    *
    * @param string $str Some text
    *
    * @return bool
    */
function isValid(string $str) : bool
{
    return !preg_match("/[^\p{L}0-9.#&~;:\* \/\'\-,!]/", $str);
}


/**
    * highlightText
    *
    * Returns provided string with increased size and font-weight
    *
    * @param string $str  Some text
    * @param bool   $cond Condition to be met for text to be highlighted
    *
    * @return string
    */
function highlightText(string $str, bool $cond = true)
{
    return $cond ? "<span class=\"highlightedText\">{$str}</span>" : $str;
}


/**
* @return array<string, array<string>|bool|int|string|null> $get
*/
function validateGet() : array
{
    global $a_pageresults, $c_pageresults, $a_status, $a_histdates, $a_currenthist, $a_media;

    // Start new $get array for sanitized input
    $get = array();

    // Unexpected, UTF-8 is the default for PHP 7
    if (mb_internal_encoding() !== "UTF-8")
    {
        // Ensure internal encoding is UTF-8 even if it's changed on server config
        mb_internal_encoding("UTF-8");
    }

    // Sanitize and store boolean values
    if (isset($_GET['rss']))
    {
        // RSS feed
        $get['rss'] = true;
    }
    if (isset($_GET['b']))
    {
        // Builds page
        $get['b'] = true;
    }
    else if (isset($_GET['h']))
    {
        // History page
        $get['h'] = true;
    }

    // First pass of sanitization on user input from $_GET
    foreach ($_GET as $key => $value)
    {
        // Remove non-string arguments (f.ex: arrays)
        if (!is_string($value))
        {
            unset($_GET[$key]);
        }
        // Remove non UTF-8 strings
        else if (!mb_check_encoding($value))
        {
            unset($_GET[$key]);
        }
    }

    // Set default values
    $get['r'] = (int) $c_pageresults;
    $get['s'] = 0; // All
    // $get['o'] = "";
    // $get['c'] = '';
    // $get['g'] = "";
    // $get['d'] = "";
    // $get['t'] = '';
    // $get['m'] = '';
    // $get["move"] = 0;
    $get["3D"] = 0;
    $get["type"] = 0;
    // $get["network"] = 0;

    // API version
    if (isset($_GET['api']) && is_string($_GET['api']))
    {
        $get['api'] = (string) $_GET['api'];
    }

    // Page counter
    if (isset($_GET['p']) && is_string($_GET['p']))
    {
        $get['p'] = (int) $_GET['p'];
    }

    // Results per page
    if (isset($_GET['r']) && is_string($_GET['r']) && in_array($_GET['r'], $a_pageresults))
    {
        $get['r'] = (int) $_GET['r'];
    }

    // Status
    if (isset($_GET['s']) && is_string($_GET['s']) && ((int) $_GET['s'] === 0 || array_key_exists($_GET['s'], $a_status)))
    {
        $get['s'] = (int) $_GET['s'];
    }

    // Order by
    if (isset($_GET['o']) && is_string($_GET['o']) && strlen($_GET['o']) == 2 && is_numeric(substr($_GET['o'], 0, 1)) && (substr($_GET['o'], 1, 1) == 'a' || substr($_GET['o'], 1, 1) == 'd'))
    {
        $get['o'] = (string) $_GET['o'];
    }

    // Character
    if (isset($_GET['c']) && is_string($_GET['c']))
    {
        // If it is a single alphabetic character
        if (ctype_alpha($_GET['c']) && strlen($_GET['c']) === 1)
        {
            $get['c'] = strtolower($_GET['c']);
        }
        else if ($_GET['c'] == '09')  { $get['c'] = '09';  } // Numbers
        else if ($_GET['c'] == 'sym') { $get['c'] = 'sym'; } // Symbols
    }

    // Searchbox
    if (isset($_GET['g']) && is_string($_GET['g']) && !empty($_GET['g']) && mb_strlen($_GET['g']) <= 128 && isValid($_GET['g']))
    {
        $get['g'] = (string) $_GET['g'];

        // Trim all unnecessary double spaces
        while (str_contains($get['g'], "  "))
            $get['g'] = str_replace("  ", " ", $get['g']);
    }

    // Date
    if (isset($_GET['d']) && is_numeric($_GET['d']) && strlen((string) $_GET['d']) === 8 && strpos((string) $_GET['d'], '20') === 0)
    {
        $get['d'] = (int) $_GET['d'];
    }

    // Media type
    if (isset($_GET['t']) && is_string($_GET['t']) && array_key_exists(strtoupper($_GET['t']), $a_media))
    {
        $get['t'] = strtolower($_GET['t']);
    }

    // Move support
    if (isset($_GET['move']) && is_string($_GET['move']))
    {
        // No move support
        if ((int) $_GET['move'] === 0)
        {
            $get['move'] = 0;
        }
        // Has move support
        else if ((int) $_GET['move'] === 1)
        {
            $get['move'] = 1;
        }
    }

    // Stereoscopic 3D support
    if (isset($_GET['3D']) && is_string($_GET['3D']) && (int) $_GET['3D'] !== 0)
    {
        $get['3D'] = 1;
    }

    // Game type
    if (isset($_GET['type']) && is_string($_GET['type']) && $_GET['type'] != 0)
    {
        // PS3 Game
        if ((int) $_GET['type'] === 1)
        {
            $get['type'] = 1;
        }
        // PS3 App
        else if ((int) $_GET['type'] === 2)
        {
            $get['type'] = 2;
        }
    }

    // Network requirement
    if (isset($_GET['network']) && is_string($_GET['network']))
    {
        // No move support
        if ((int) $_GET['network'] === 0)
        {
            $get['network'] = 0;
        }
        // Has move support
        else if ((int) $_GET['network'] === 1)
        {
            $get['network'] = 1;
        }
    }

    // History
    if (isset($_GET['h']) && is_string($_GET['h']) && array_key_exists($_GET['h'], $a_histdates))
    {
        $get['h'] = (string) $_GET['h'];
    }

    // History mode
    if (isset($_GET['m']) && ($_GET['m'] === 'c' || $_GET['m'] === 'n'))
    {
        $get['m'] = strtolower($_GET['m']);
    }

    // Patch system: Version
    if (isset($_GET['v']) && is_string($_GET['v']) && strlen($_GET['v']) === 3 && ctype_digit($_GET['v'][0]) && $_GET['v'][1] === '.' && ctype_digit($_GET['v'][2]))
    {
        $get['v'] = (string) $_GET['v'];
    }

    // Patch system: Sha256
    if (isset($_GET['sha256']) && is_string($_GET['sha256']) && strlen($_GET['sha256']) === 64 && ctype_alnum($_GET['sha256']))
    {
        $get['sha256'] = (string) $_GET['sha256'];
    }

    // Get debug permissions, if any
    $get['w'] = getDebugPermissions();

    // Enable error reporting for admins
    if (!is_null($get['w']))
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        // Admin debug mode
        if (isset($_GET['a']) && is_string($_GET['a']) && array_search("debug.view", $get['w']) !== false)
        {
            $get['a'] = (string) $_GET['a'];
        }
    }

    return $get;
}


// Select the count of games in each status, subjective to query restrictions
/**
* @return array<string, array<int, int>> $count
*/
function countGames(mysqli $db, string $query = "") : array
{
    global $get, $a_status;

    $scount = array();
    $and = empty($query) ? "" : " AND ({$query}) ";

    // Without network only games
    $q_gen1 = mysqli_query($db, "SELECT `status`+0 AS `statusID`, count(*) AS `c`
                                 FROM `game_list`
                                 WHERE (`network` = 0
                                    OR (`network` = 1 && `status` <= 2)) {$and}
                                 GROUP BY `status`;");

    // With network only games
    $q_gen2 = mysqli_query($db, "SELECT `status`+0 AS `statusID`, count(*) AS `c`
                                 FROM `game_list`
                                 WHERE (`network` = 0
                                    OR  `network` = 1) {$and}
                                 GROUP BY `status`;");

    // Zero-fill the array keys that are going to be used
    for ($id = 0; $id <= count($a_status); $id++)
    {
        $scount["status"][$id]   = 0;
        $scount["nostatus"][$id] = 0;
        // Derivative of status mode but with network games
        $scount["network"][$id]  = 0;
    }

    if (is_bool($q_gen1) || is_bool($q_gen2))
        return $scount;

    while ($row1 = mysqli_fetch_object($q_gen1))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row1, "statusID") ||
                !property_exists($row1, "c"))
        {
            continue;
        }

        $sid   = (int) $row1->statusID;
        $count = (int) $row1->c;

        $scount["nostatus"][$sid] =  $count;
        $scount["nostatus"][0]    += $count;

        // For count with specified status, include only results for the specified status
        // If there is no specified status, replicate nostatus mode
        if ($get['s'] === 0 || $sid === $get['s'])
        {
            $scount["status"][$sid]  =  $count;
            $scount["status"][0]     += $count;
        }
    }

    while ($row2 = mysqli_fetch_object($q_gen2))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row2, "statusID") ||
                !property_exists($row2, "c"))
        {
            continue;
        }

        $sid   = (int) $row2->statusID;
        $count = (int) $row2->c;

        if ($get['s'] === 0 || $sid === $get['s'])
        {
            $scount["network"][$sid] =  $count;
            $scount["network"][0]    += $count;
        }
    }

    return $scount;
}


function count_game_entry_all(bool $ignore_cache = false) : int
{
    // If we don't ignore cache, try to retrieve the value from cache
    if (!$ignore_cache)
    {
        $count = file_get_contents(__DIR__.'/cache/count_game_entry_all.txt');

        // Return value from cache only if available
        if ($count !== false)
            return (int) $count;
    }

    $db = getDatabase();
    $ret = 0;

    // Total game count (without network games)
    $q_unique = mysqli_query($db, "SELECT count(*) AS `c`
                                   FROM `game_list`
                                   WHERE (`network` = 0
                                      OR (`network` = 1 && `status` <= 2))
                                      AND `type` = 'PS3 Game'; ");

    if (!is_bool($q_unique) && mysqli_num_rows($q_unique) === 1)
    {
        $row = mysqli_fetch_object($q_unique);

        // This should be unreachable unless the database structure is damaged
        if (!$row || !property_exists($row, "c"))
        {
            return 0;
        }

        $ret = (int) $row->c;
    }

    mysqli_close($db);
    return $ret;
}


function count_game_id_all(bool $ignore_cache = false) : int
{
    // If we don't ignore cache, try to retrieve the value from cache
    if (!$ignore_cache)
    {
        $count = file_get_contents(__DIR__.'/cache/count_game_id_all.txt');

        // Return value from cache only if available
        if ($count !== false)
            return (int) $count;
    }

    $db = getDatabase();
    $ret = 0;

    // Total game count (without network games)
    $q_unique = mysqli_query($db, "SELECT count(*) AS `c`
                                   FROM `game_list`
                                   LEFT JOIN `game_id`
                                   ON `game_id`.`key` = `game_list`.`key`
                                   WHERE (`network` = 0
                                      OR (`network` = 1 && `status` <= 2))
                                      AND `type` = 'PS3 Game'; ");

    if (!is_bool($q_unique) && mysqli_num_rows($q_unique) === 1)
    {
        $row = mysqli_fetch_object($q_unique);

        // This should be unreachable unless the database structure is damaged
        if (!$row || !property_exists($row, "c"))
        {
            return 0;
        }

        $ret = (int) $row->c;
    }

    mysqli_close($db);
    return $ret;
}


// TODO: Cleanup
function getPagesCounter(int $pages, int $currentPage, string $extra) : string
{
    global $c_pagelimit;

    // Initialize string
    $s_pagescounter = "";

    if (!empty($extra))
        $extra .= "&";

    // IF no results are found then the amount of pages is 0
    // Returns no results found message
    if ($pages === 0)
        return "No results found using the selected search criteria.";

    // Shows current page and total pages
    $s_pagescounter .= "Page {$currentPage} of {$pages} - ";

    // If there's less pages to the left than current limit it loads the excess amount to the right for balance
    if ($c_pagelimit > $currentPage)
        $c_pagelimit += $c_pagelimit - $currentPage + 1;

    // If there's less pages to the right than current limit it loads the excess amount to the left for balance
    if ($c_pagelimit > $pages - $currentPage)
        $c_pagelimit += $c_pagelimit - ($pages - $currentPage) + 1;

    // Loop for each page link and make it properly clickable until there are no more pages left
    for ($i = 1; $i <= $pages; $i++)
    {
        if ( ($i >= $currentPage-$c_pagelimit && $i <= $currentPage) || ($i+$c_pagelimit >= $currentPage && $i <= $currentPage+$c_pagelimit) )
        {
            // Highlights the page if it's the one we're currently in
            $content = highlightText(str_pad((string) $i, 2, "0", STR_PAD_LEFT), $i === $currentPage);
            $html_a = new HTMLA("?{$extra}p={$i}", "Page {$i}", $content);

            $s_pagescounter .= $html_a->to_string();
            $s_pagescounter .= "&nbsp;&#32;";
        }
        // First page
        elseif ($i === 1)
        {
            $html_a = new HTMLA("?{$extra}p={$i}", "Page {$i}", "01");

            $s_pagescounter .= $html_a->to_string();
            $s_pagescounter .= "&nbsp;&#32;";

            if ($currentPage != $c_pagelimit + 2)
            {
                $s_pagescounter .= "...&nbsp;&#32;";
            }
        }
        // Last page
        elseif ($pages === $i)
        {
            $s_pagescounter .= "...&nbsp;&#32;";

            $html_a = new HTMLA("?{$extra}p={$i}", "Page {$i}", (string) $i);
            $s_pagescounter .= $html_a->to_string();
        }
    }

    return $s_pagescounter;
}


/**
* @param array<array<string, string>> $headers
*/
function getTableHeaders(array $headers, string $extra = "") : string
{
    global $get;

    if (!empty($extra))
        $extra .= "&";

    $html_div_ret = new HTMLDiv("compat-table-header");

    foreach ($headers as $i => $header)
    {
        $html_div = new HTMLDiv($header["class"]);

        if ($header['sort'] === '0')
        {
            $html_div->add_content($header["name"]);
        }
        else if (isset($get['o']) && $get['o'] === "{$header['sort']}a")
        {
            $html_a = new HTMLA("?{$extra}o={$header['sort']}d", $header["name"], "{$header['name']} &nbsp; &#8593;");
            $html_div->add_content($html_a->to_string());
        }
        elseif (isset($get['o']) && $get['o'] === "{$header['sort']}d")
        {
            $html_a = new HTMLA("?{$extra}", $header["name"], "{$header['name']} &nbsp; &#8595;");
            $html_div->add_content($html_a->to_string());
        }
        else
        {
            $html_a = new HTMLA("?{$extra}o={$header['sort']}a", $header["name"], $header["name"]);
            $html_div->add_content($html_a->to_string());
        }

        $html_div_ret->add_content($html_div->to_string());
    }

    return $html_div_ret->to_string();
}


function getFooter() : string
{
    global $c_maintenance, $get, $start_time, $c_footer_before, $c_footer_after;

    // Total time in milliseconds
    $total_time = round((microtime(true) - $start_time) * 1000, 2);

    $html_a = new HTMLA("https://github.com/AniLeo", "AniLeo", "AniLeo");
    $html_a->set_target("_blank");

    $html_div = new HTMLDiv("compat-footer");
    $html_div->add_content("<p>Compatibility list developed and maintained by {$html_a->to_string()} &nbsp;-&nbsp; Page loaded in {$total_time}ms</p>");

    $s = $html_div->to_string();

    // Debug output
    if (!is_null($get['w']))
    {
        $html_div = new HTMLDiv("compat-profiler");

        // Maintenance mode information
        if ($c_maintenance)
        {
            $html_div->add_content("<p>Maintenance mode: <span class=\"color-green\"><b>ON</b></span></p>");
        }
        else
        {
            $html_div->add_content("<p>Maintenance mode: <span class=\"color-red\"><b>OFF</b></span></p>");
        }

        $html_div->add_content(Profiler::get_data_html());

        $s .= $html_div->to_string();
    }

    $s = $c_footer_before . $s . $c_footer_after;

    return $s;
}


// File path where the menu was called from
function getMenu(string $file) : string
{
    global $get;

    $html_div = new HTMLDiv("compat-menu");

    $file = basename($file, '.php');

    if ($file !== "compat")
    {
        $html_a = new HTMLA("?", "Compatibility List", "Compatibility List");
        $html_div->add_content($html_a->to_string());
    }
    if ($file !== "history")
    {
        $html_a = new HTMLA("?h", "Compatibility List History", "Compatibility List History");
        $html_div->add_content($html_a->to_string());
    }
    if ($file !== "builds")
    {
        $html_a = new HTMLA("?b", "Builds History", "Builds History");
        $html_div->add_content($html_a->to_string());
    }
    if (!is_null($get['w']) && $file !== "panel")
    {
        $html_a = new HTMLA("?a", "Debug Panel", "Debug Panel");
        $html_div->add_content($html_a->to_string());
    }

    return $html_div->to_string();
}


// Get current page user is on
function getCurrentPage(int $pages) : int
{
    global $get;

    // No specific page set or page bigger or smaller than what's possible
    if (!isset($get['p']) || $get['p'] > $pages || $get['p'] < 1)
        return 1;

    return $get['p'];
}


// Calculate the number of pages according selected status and results per page
function countPages(int $results, int $count) : int
{
    return (int) ceil($count / $results);
}


// Checks if user has debug permissions
/**
* @return array<string> $permissions
*/
function getDebugPermissions() : ?array
{
    if (!isset($_COOKIE["debug"]) || !is_string($_COOKIE["debug"]) || !ctype_alnum($_COOKIE["debug"]))
        return null;

    $db = getDatabase();
    $s_token = mysqli_real_escape_string($db, $_COOKIE["debug"]);

    $q_debug = mysqli_query($db, "SELECT *
                                  FROM `debug_whitelist`
                                  WHERE `token` = '{$s_token}'
                                  LIMIT 1; ");

    if (is_bool($q_debug) || mysqli_num_rows($q_debug) === 0)
        return null;

    $row = mysqli_fetch_object($q_debug);

    // This should be unreachable unless the database structure is damaged
    if (!$row || !property_exists($row, "permissions"))
    {
        return null;
    }

    $permissions = array();

    if (!str_contains($row->permissions, ','))
    {
        $permissions[0] = $row->permissions;
    }
    else
    {
        $permissions = explode(',', $row->permissions);
    }

    return $permissions;
}


function getDateDiff(string $datetime) : string
{
    $diff = time() - strtotime($datetime);
    $days = (int) floor($diff / 86400);

    if ($days === 0)
    {
        $hours = (int) floor($diff / 3600);
        if ($hours === 0)
        {
            $minutes = (int) floor($diff / 60);
            $diff = $minutes === 1 ? "{$minutes} minute" : "{$minutes} minutes";
        }
        else
        {
            $diff = $hours === 1 ? "{$hours} hour" : "{$hours} hours";
        }
    }
    else
    {
        $diff = $days === 1 ? "{$days} day" : "{$days} days";
    }

    return "{$diff} ago";
}


function monthNumberToName(int $month) : string
{
    $datetime = DateTime::createFromFormat('!m', (string) $month);
    return $datetime ? $datetime->format('F') : "";
}


function dumpVar(mixed $var) : void
{
    echo "<br>";
    highlight_string("<?php\n\$data =\n".var_export($var, true).";\n?>");
}


function resultsPerPage(string $combined_search) : string
{
    global $a_pageresults, $get;

    $s_pageresults = "";

    if (!empty($combined_search))
        $combined_search .= '&';

    foreach ($a_pageresults as $pageresult)
    {
        // If it's the current selected item, highlight
        $content = highlightText($pageresult, $get['r'] === $pageresult);
        $html_a = new HTMLA("?{$combined_search}r={$pageresult}", $pageresult, $content);
        $s_pageresults .= $html_a->to_string();

        // If not the last value then add a separator for the next value
        if ($pageresult !== end($a_pageresults))
        {
            $s_pageresults .= "•&nbsp;";
        }
    }

    return $s_pageresults;
}


// Checks whether indicated string is a Game ID or not
// Game ID validation: is alphanumeric, len = 9, last 5 characters are digits,
// 3rd character represents a valid region and 1st character represents a valid media
function isGameID(string $string) : bool
{
    global $a_flags, $a_media;

    return ctype_alnum($string) &&
           strlen($string) == 9 &&
           is_numeric(substr($string, 4, 5)) &&
           array_key_exists(strtoupper(substr($string, 2, 1)), $a_flags) &&
           array_key_exists(strtoupper(substr($string, 0, 1)), $a_media);
}


// Runs a function while keeping track of the time it takes to run
// Returns amount of time in seconds
function runFunctionWithCronometer(string $function) : float
{
    if (!is_callable($function))
        return -1.0;

    $start = microtime(true);
    $function();
    $finish = microtime(true);
    return round(($finish - $start), 4); // Seconds
}


// Gets status ID for a respective status title
function getStatusID(string $name) : ?int
{
    global $a_status;

    foreach ($a_status as $id => $status)
    {
        if ($name === $status['name'])
            return $id;
    }

    return null;
}


// cURL JSON document and return the result as an object
function curl_json(string $url, ?CurlHandle $cr) : ?object
{
    if (!defined("gh_token"))
        exit("[COMPAT] API: Missing connection data");

    // Use existing cURL resource or create a temporary one
    $ch = (!is_null($cr)) ? $cr : curl_init();

    if (empty($url))
        return null;

    // Set the required cURL flags
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return result as raw output
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "RPCS3 - Compatibility");

    // We're cURLing the GitHub API, set GitHub Auth Token on headers
    if (strlen($url) >= 23 && substr($url, 0, 23) === "https://api.github.com/")
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: token ".gh_token));
    }

    // Get the response and httpcode of that response
    $result = curl_exec($ch);

    // Close the temporary cURL resource or reset the given cURL resource
    if (is_null($cr))
        curl_close($ch);
    else
        curl_reset($cr);

    if (is_bool($result))
        return null;

    // Decode JSON
    $result = json_decode($result, false);

    if (is_bool($result) || is_null($result) || !is_object($result))
        return null;

    return $result;
}


// Based on https://stackoverflow.com/a/9826656
function get_string_between(string $string, string $start, string $end) : ?string
{
    // Return position of initial limit in our string
    // If position doesn't exist, then return false as string doesn't contain our start limit
    if (!($inipos = strpos($string, $start)))
        return null;

    // Add length of start limit, so our start position is the character AFTER the start limit
    $inipos += strlen($start);

    // Look for end string position starting on initial position (offset)
    // If position doesn't exist, then return false as string doesn't contain our end limit
    if (!($endpos = strpos($string, $end, $inipos)))
        return null;

    // Start on 'start limit position' and return string with substring length
    return substr($string, $inipos, $endpos - $inipos /*substring length*/);
}
