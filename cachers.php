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

// Calls for the file that contains the needed functions
if(!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/objects/Game.php")) throw new Exception("Compat: Game.php is missing. Failed to include Game.php");


function cache_builds(bool $full = false) : void
{
    $db = getDatabase();
    $cr = curl_init();

    if (!$full)
    {
        set_time_limit(60*5); // 5 minute limit
        // Get date from last merged PR. Subtract 1 day to it and check new merged PRs since then.
        // Note: If master builds are disabled we need to remove WHERE type = 'branch'
        $q_mergedate = mysqli_query($db, "SELECT DATE_SUB(`merge_datetime`, INTERVAL 1 DAY) AS `date`
                                          FROM `builds`
                                          WHERE `type` = 'branch'
                                          ORDER BY `merge_datetime` DESC
                                          LIMIT 1;");

        if (is_bool($q_mergedate))
        {
            return;
        }

        $row = mysqli_fetch_object($q_mergedate);

        // This should be unreachable unless the database structure is damaged
        if (!$row || !property_exists($row, "date"))
        {
            return;
        }

        $date = date_create($row->date);

        if (!$date)
        {
            return;
        }

        $date = date_format($date, 'Y-m-d');
    }
    else
    {
        // This can take a while to do...
        set_time_limit(60*60); // 1 hour limit
        // Start from indicated date (2015-08-10 for first PR with AppVeyor CI)
        $date = '2018-06-02';
    }

    // Get number of PRs (GitHub Search API)
    // repo:rpcs3/rpcs3, is:pr, is:merged, merged:>$date, sort=updated (asc)
    // TODO: Sort by merged date whenever it's available on the GitHub API
    $url = "https://api.github.com/search/issues?q=repo:rpcs3/rpcs3+is:pr+is:merged+sort:updated-asc+merged:%3E{$date}";
    $search = curl_json($url, $cr);

    // API Call Failed or no PRs to cache, end here
    // TODO: Log and handle API call fails differently depending on the fail
    if (is_null($search) ||
        !property_exists($search, "total_count") ||
        !property_exists($search, "items") ||
        $search->total_count == 0)
    {
        mysqli_close($db);
        curl_close($cr);
        return;
    }

    $page_limit = 30; // Search API page limit: 30
    $pages = (int) (ceil($search->total_count / $page_limit));
    $a_PR = array();	// List of iterated PRs
    $i = 1;	// Current page

    // Loop through all pages and get PR information
    while ($i <= $pages)
    {
        $a = 0; // Current PR (page relative)

        // Define PR limit for current page
        $pr_limit = ($i == $pages) ? ($search->total_count - (($pages-1)*$page_limit)) : $page_limit;

        $i++; // Prepare for next page

        while ($a < $pr_limit)
        {
            $pr = (int) $search->items[$a]->number;
            $a++;	// Prepare for next PR

            // If PR was already checked in this run, skip it
            if (in_array($pr, $a_PR))
            {
                continue;
            }
            $a_PR[]  = $pr;

            // Check if PR is already cached
            $q_pr = mysqli_query($db, "SELECT *
                                          FROM `builds`
                                          WHERE `pr` = {$pr}
                                            AND `title` IS NOT NULL
                                          LIMIT 1; ");

            // This should be unreachable
            if (is_bool($q_pr))
            {
                continue;
            }

            // If PR is already cached and we're not in full mode, skip
            if (mysqli_num_rows($q_pr) > 0 && !$full)
            {
                continue;
            }

            cache_build($pr);
        }

        if ($i <= $pages)
        {
            $search = curl_json("{$url}&page={$i}", $cr);

            // API call failed
            if (is_null($search) ||
                !property_exists($search, "total_count") ||
                !property_exists($search, "items") ||
                $search->total_count == 0)
            {
                mysqli_close($db);
                curl_close($cr);
                return;
            }
        }
    }
    mysqli_close($db);
    curl_close($cr);
}

/**
* @return array<string, string>|null $ret
*/
function parse_build_properties(object $info) : ?array
{
    $ret = array();

    /*** Version name ***/
    // API Sanity Check
    if (!isset($info->name))
        return null;

    // Assign
    $ret["version"] = (string) $info->name;

    // Verify: If version name doesn't contain a slash
    //         then the current entry is invalid
    if (!str_contains($ret["version"], '-'))
        return null;

    // Truncate apostrophes on version name if they exist
    if (str_contains($ret["version"], '\''))
        $ret["version"] = str_replace('\'', '', $ret["version"]);

    // API Sanity Check
    if (empty($ret["version"]))
        return null;

    /*** Filename ***/
    // API Sanity Check
    if (!isset($info->assets))
        return null;

    // Assign
    foreach ($info->assets as $asset)
    {
        // Skip checksum files
        if (str_contains($asset->name, ".sha256"))
            continue;

        if (str_contains($asset->name, "win64.7z") ||
            str_contains($asset->name, "win64_msvc.7z") ||
            str_contains($asset->name, "linux64.AppImage") ||
            str_contains($asset->name, "linux_aarch64.AppImage") ||
            str_contains($asset->name, "macos.7z") ||
            str_contains($asset->name, "macos_arm64.7z"))
        {
            $ret["filename"] = $asset->name;
        }
    }

    // API Sanity Check
    if (!array_key_exists("filename", $ret) || is_null($ret["filename"]) || empty($ret["filename"]))
        return null;

    /*** Checksum and size ***/
    // API Sanity Check
    if (!isset($info->body) || empty($info->body))
        return null;

    // Assign
    $fileinfo = explode(';', $info->body);
    $ret["checksum"] = (string) strtoupper($fileinfo[0]);
    $ret["size"] = floatval(preg_replace("/[^0-9.,]/", "", $fileinfo[1]));

    // Convert size to bytes if needed
    if      (str_contains($fileinfo[1], "MB"))
        $ret["size"] = (string) ($ret["size"] * 1024 * 1024);
    else if (str_contains($fileinfo[1], "KB"))
        $ret["size"] = (string) ($ret["size"] * 1024);
    else
        $ret["size"] = (string) $ret["size"];

    // API Sanity Checks
    if (empty($ret["checksum"]))
        return null;
    if (empty($ret["size"]))
        return null;

    return $ret;
}


function cache_build(int $pr) : void
{
    // Malformed ID
    if ($pr <= 0)
    {
        return;
    }

    $cr = curl_init();

    // Grab pull request information from GitHub REST API (v3)
    $pr_info = curl_json("https://api.github.com/repos/rpcs3/rpcs3/pulls/{$pr}", $cr);

    if (is_null($pr_info))
        return;

    // Check if we aren't rate limited
    if (!isset($pr_info->merge_commit_sha))
    {
        echo "cache_build({$pr}): Rate limited".PHP_EOL;
        curl_close($cr);
        return;
    }

    if (!isset($pr_info->merged_at)        ||
        !isset($pr_info->created_at)       ||
        !isset($pr_info->merge_commit_sha) ||
        !isset($pr_info->user)             ||
        !isset($pr_info->user->login)      ||
        !isset($pr_info->additions)        ||
        !isset($pr_info->deletions)        ||
        !isset($pr_info->changed_files)    ||
        !isset($pr_info->title))
    {
        echo "cache_build({$pr}): API error".PHP_EOL;
        curl_close($cr);
        return;
    }

    $merge_datetime = $pr_info->merged_at;
    $start_datetime = $pr_info->created_at;
    $commit         = $pr_info->merge_commit_sha;
    $author         = $pr_info->user->login;
    $additions      = $pr_info->additions;
    $deletions      = $pr_info->deletions;
    $changed_files  = $pr_info->changed_files;
    $title          = $pr_info->title;

    if (!isset($pr_info->body))
    {
        $body = "";
    }
    else
    {
        $body = $pr_info->body;
    }

    // Currently unused
    $type = "branch";

    $aid = cacheContributor($author);
    // Checking author information failed
    // TODO: This should probably be logged, as other API call fails
    if ($aid == 0)
    {
        echo "Error: Checking author information failed";
        curl_close($cr);
        return;
    }

    // Windows build metadata
    $info_release_win = curl_json("https://api.github.com/repos/rpcs3/rpcs3-binaries-win/releases/tags/build-{$commit}", $cr);
    if (is_null($info_release_win))
        return;

    // Linux build metadata
    $info_release_linux = curl_json("https://api.github.com/repos/rpcs3/rpcs3-binaries-linux/releases/tags/build-{$commit}", $cr);
    if (is_null($info_release_linux))
        return;

    // macOS build metadata
    $info_release_mac = curl_json("https://api.github.com/repos/rpcs3/rpcs3-binaries-mac/releases/tags/build-{$commit}", $cr);
    if (is_null($info_release_mac))
        return;

    // Linux arm64 build metadata
    $info_release_linux_arm64 = curl_json("https://api.github.com/repos/rpcs3/rpcs3-binaries-linux-arm64/releases/tags/build-{$commit}", $cr);
    if (is_null($info_release_linux_arm64))
        return;

    // macOS arm64 build metadata
    $info_release_mac_arm64 = curl_json("https://api.github.com/repos/rpcs3/rpcs3-binaries-mac-arm64/releases/tags/build-{$commit}", $cr);
    if (is_null($info_release_mac_arm64))
        return;

    $is_missing = isset($info_release_win->message) ||
                  isset($info_release_linux->message) ||
                  isset($info_release_linux_arm64->message) ||
                  isset($info_release_mac->message) ||
                  isset($info_release_mac_arm64->message);

    $is_missing_platform = $is_missing && time() - strtotime($merge_datetime) >= (3600 * 2);

    // Error message found: Build doesn't exist in one of the repos
    // Do not ignore if the build was merged over two hours ago, to cache as broken
    // TODO: Ignore macOS if date is prior to the first macOS build
    if ($is_missing && !$is_missing_platform)
    {
        printf("Error: Checking author information failed, current=%s, merge=%s", time(), strtotime($merge_datetime));
        curl_close($cr);
        return;
    }

    $info_win   = parse_build_properties($info_release_win);
    $info_linux = parse_build_properties($info_release_linux);
    $info_mac   = parse_build_properties($info_release_mac);
    $info_linux_arm64 = parse_build_properties($info_release_linux_arm64);
    $info_mac_arm64 = parse_build_properties($info_release_mac_arm64);

    // Fail if one of the builds is not available until a grace period has passed
    if (!is_null($info_win))
    {
        $version = $info_win["version"];
    }
    else if (!$is_missing_platform)
    {
        curl_close($cr);
        return;
    }

    if (!isset($version) && !is_null($info_linux))
    {
        $version = $info_linux["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        curl_close($cr);
        return;
    }

    if (!isset($version) && !is_null($info_mac))
    {
        $version = $info_mac["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        curl_close($cr);
        return;
    }

    if (!isset($version) && !is_null($info_linux_arm64))
    {
        $version = $info_linux_arm64["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        curl_close($cr);
        return;
    }

    if (!isset($version) && !is_null($info_mac_arm64))
    {
        $version = $info_mac_arm64["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        curl_close($cr);
        return;
    }

    // No builds are available
    // TODO: Get $version when all the builds are missing and allow caching the entry
    if (!isset($version))
    {
        curl_close($cr);
        return;
    }

    $is_broken_build = false;

    // Broken pipeline, did a shallow clone and ended up without a commit count
    if (str_ends_with($version, "-1"))
    {
        $is_broken_build = true;
    }

    if ($is_missing_platform)
    {
        echo "A build is broken for Pull Request #{$pr}".PHP_EOL;
        printf("Build status: Windows: %s, Linux: %s, macOS: %s, Linux arm64: %s, macOS arm64: %s",
               isset($info_release_win->message)   ? $info_release_win->message : "OK",
               isset($info_release_linux->message) ? $info_release_linux->message : "OK",
               isset($info_release_mac->message)   ? $info_release_mac->message : "OK",
               isset($info_release_linux_arm64->message) ? $info_release_linux_arm64->message : "OK",
               isset($info_release_mac_arm64->message) ? $info_release_mac_arm64->message : "OK");
    }

    if ($is_broken_build)
        $is_broken = "1";
    else if ($is_missing_platform)
        $is_broken = "2";

    $db = getDatabase();

    $q_build = mysqli_query($db, "SELECT * FROM `builds` WHERE `pr` = {$pr} LIMIT 1; ");

    if (is_bool($q_build))
    {
        mysqli_close($db);
        return;
    }

    if (mysqli_num_rows($q_build) === 1)
    {
        mysqli_query($db, "UPDATE `builds` SET
        `commit`         = '".mysqli_real_escape_string($db, $commit)."',
        `type`           = '".mysqli_real_escape_string($db, $type)."',
        `author`         = '".mysqli_real_escape_string($db, (string) $aid)."',
        `start_datetime` = '".mysqli_real_escape_string($db, $start_datetime)."',
        `merge_datetime` = '".mysqli_real_escape_string($db, $merge_datetime)."',
        `version`        = '".mysqli_real_escape_string($db, $version)."',
        `additions`      = '".mysqli_real_escape_string($db, (string) $additions)."',
        `deletions`      = '".mysqli_real_escape_string($db, (string) $deletions)."',
        `changed_files`  = '".mysqli_real_escape_string($db, (string) $changed_files)."',
        `size_win`       = ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["size"])."'" : "NULL").",
        `checksum_win`   = ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["checksum"])."'" : "NULL").",
        `filename_win`   = ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["filename"])."'" : "NULL").",
        `size_linux`     = ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["size"])."'" : "NULL").",
        `checksum_linux` = ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["checksum"])."'" : "NULL").",
        `filename_linux` = ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["filename"])."'" : "NULL").",
        `size_mac`       = ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["size"])."'" : "NULL").",
        `checksum_mac`   = ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["checksum"])."'" : "NULL").",
        `filename_mac`   = ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["filename"])."'" : "NULL").",
        `size_linux_arm64`     = ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["size"])."'" : "NULL").",
        `checksum_linux_arm64` = ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["checksum"])."'" : "NULL").",
        `filename_linux_arm64` = ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["filename"])."'" : "NULL").",
        `size_mac_arm64`       = ".(isset($info_mac_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["size"])."'" : "NULL").",
        `checksum_mac_arm64`   = ".(isset($info_mac_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["checksum"])."'" : "NULL").",
        `filename_mac_arm64`   = ".(isset($info_mac_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["filename"])."'" : "NULL").",
        `broken`         = ".(isset($is_broken) ? "'".mysqli_real_escape_string($db, $is_broken)."'" : "NULL").",
        `title`          = '".mysqli_real_escape_string($db, $title)."',
        `body`           = '".mysqli_real_escape_string($db, $body)."'
        WHERE `pr` = '{$pr}'
        LIMIT 1;");
    }
    else
    {
        mysqli_query($db, "INSERT INTO `builds`
        (`pr`,
         `commit`,
         `type`,
         `author`,
         `start_datetime`,
         `merge_datetime`,
         `version`,
         `additions`,
         `deletions`,
         `changed_files`,
         `size_win`,
         `checksum_win`,
         `filename_win`,
         `size_linux`,
         `checksum_linux`,
         `filename_linux`,
         `size_mac`,
         `checksum_mac`,
         `filename_mac`,
         `size_linux_arm64`,
         `checksum_linux_arm64`,
         `filename_linux_arm64`,
         `size_mac_arm64`,
         `checksum_mac_arm64`,
         `filename_mac_arm64`,
         `broken`,
         `title`,
         `body`)
        VALUES ('{$pr}',
        '".mysqli_real_escape_string($db, $commit)."',
        '".mysqli_real_escape_string($db, $type)."',
        '".mysqli_real_escape_string($db, (string) $aid)."',
        '".mysqli_real_escape_string($db, $start_datetime)."',
        '".mysqli_real_escape_string($db, $merge_datetime)."',
        '".mysqli_real_escape_string($db, $version)."',
        '".mysqli_real_escape_string($db, (string) $additions)."',
        '".mysqli_real_escape_string($db, (string) $deletions)."',
        '".mysqli_real_escape_string($db, (string) $changed_files)."',
        ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["size"])."'" : "NULL").",
        ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["checksum"])."'" : "NULL").",
        ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["filename"])."'" : "NULL").",
        ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["size"])."'" : "NULL").",
        ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["checksum"])."'" : "NULL").",
        ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["filename"])."'" : "NULL").",
        ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["size"])."'" : "NULL").",
        ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["checksum"])."'" : "NULL").",
        ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["filename"])."'" : "NULL").",
        ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["size"])."'" : "NULL").",
        ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["checksum"])."'" : "NULL").",
        ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["filename"])."'" : "NULL").",
        ".(isset($info_mac_arm64)   ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["size"])."'" : "NULL").",
        ".(isset($info_mac_arm64)   ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["checksum"])."'" : "NULL").",
        ".(isset($info_mac_arm64)   ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["filename"])."'" : "NULL").",
        ".(isset($is_broken)        ? "'".mysqli_real_escape_string($db, $is_broken)."'" : "NULL").",
        '".mysqli_real_escape_string($db, $title)."',
        '".mysqli_real_escape_string($db, $body)."'); ");
    }

    mysqli_close($db);
}


function cacheInitials() : void
{
    $db = getDatabase();

    // Pack and Vol.: Idolmaster
    // GOTY: Batman
    $words_blacklisted = array("demo", "pack", "vol.", "goty");
    $words_whitelisted = array("hd");

    $q_initials = mysqli_query($db, "SELECT DISTINCT(`game_title`), `alternative_title`
                                     FROM `game_list`;");

    // Query failed or no games present in the database
    if (is_bool($q_initials) || mysqli_num_rows($q_initials) < 1)
    {
        return;
    }

    $a_titles = array();

    while ($row = mysqli_fetch_object($q_initials))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row, "game_title") ||
                !property_exists($row, "alternative_title"))
        {
            return;
        }

        $a_titles[] = (string) $row->game_title;

        if (!is_null($row->alternative_title))
            $a_titles[] = (string) $row->alternative_title;
    }

    foreach ($a_titles as $title)
    {
        // Original title
        $original = $title;

        // For games with semi-colons: replace those with spaces
        // Science Adventure Games (Steins;Gate/Chaos;Head/Robotics;Notes...)
        $title = str_replace(';', ' ', $title);

        // For games with double dots: replace those with spaces
        $title = str_replace(':', ' ', $title);

        // For games with double slashes: replace those with spaces
        $title = str_replace('//', ' ', $title);

        // For games with single slashes: replace those with spaces
        $title = str_replace('/', ' ', $title);

        // For games with hyphen: replace those with spaces
        $title = str_replace('-', ' ', $title);

        // For games starting with a dot: remove it (.detuned and .hack//Versus)
        if (strpos($title, '.') === 0)
            $title = substr($title, 1);

        // Divide game title by spaces between words
        $words = explode(' ', $title);

        // Variable to store initials result
        $initials = "";

        foreach ($words as $word)
        {
            // Skip empty words
            if (empty($word))
                continue;

            // Include whitelisted words and skip
            if (in_array(strtolower($word), $words_whitelisted))
            {
                $initials .= $word;
                continue;
            }

            // Skip blacklisted words without including
            if (in_array(strtolower($word), $words_blacklisted))
                continue;

            // Handle roman numerals
            // Note: This catches some false positives, but the result is better than without this step
            if (preg_match("/^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/", $word))
            {
                $initials .= $word;
                continue;
            }

            // If the first character is alphanumeric then add it to the initials, else ignore
            if (ctype_alnum($word[0]))
            {
                $initials .= $word[0];

                // If the next character is a digit, add next characters to initials
                // until an non-alphanumeric character is hit
                // For games like Disgaea D2 and Idolmaster G4U!
                if (strlen($word) > 1 && ctype_digit($word[1]))
                {
                    $len = strlen($word);
                    for ($i = 1; $i < $len; $i++)
                        if (ctype_alnum($word[$i]))
                            $initials .= $word[$i];
                        else
                            break;
                }
            }
            // Any word that doesn't have a-z A-Z
            // For games with numbers like 15 or 1942
            elseif (!ctype_alpha($word))
            {
                $len = strlen($word);
                // While character is a number, add it to initials
                for ($i = 0; $i < $len; $i++)
                    if (ctype_digit($word[$i]))
                        $initials .= $word[$i];
                    else
                        break;
            }
        }

        // We don't care about games with less than 2 initials
        if (strlen($initials) > 1)
        {
            $original = mysqli_real_escape_string($db, $original);
            $s_initials = mysqli_real_escape_string($db, $initials);

            // Check if value is already cached (two games can have the same initials so we use game_title)
            $q_check = mysqli_query($db, "SELECT *
                                          FROM `initials_cache`
                                          WHERE `game_title` = '{$original}'
                                          LIMIT 1; ");

            if (is_bool($q_check))
                return;

            if (mysqli_num_rows($q_check) === 0)
            {
                // If value isn't cached, then cache it
                mysqli_query($db, "INSERT INTO `initials_cache` (`game_title`, `initials`)
                VALUES ('{$original}', '{$s_initials}'); ");
            }
            else
            {
                $row = mysqli_fetch_object($q_check);

                // This should be unreachable unless the database structure is damaged
                if (!$row || !property_exists($row, "initials"))
                {
                    return;
                }

                if ($row->initials != $initials)
                {
                    // If value is cached but differs from newly calculated initials, update it
                    mysqli_query($db, "UPDATE `initials_cache`
                                       SET `initials` = '{$s_initials}'
                                       WHERE `game_title` = '{$original}' LIMIT 1;");
                }
            }
        }
    }
    mysqli_close($db);
}


function cacheGameCount() : void
{
    // count_game_entry_all
    file_put_contents(__DIR__.'/cache/count_game_entry_all.txt', (string) count_game_entry_all(true));

    // count_game_id_all
    file_put_contents(__DIR__.'/cache/count_game_id_all.txt', (string) count_game_id_all(true));
}


function cacheContributor(string $username) : int
{
    $info_contributor = curl_json("https://api.github.com/users/{$username}", null);

    if (is_null($info_contributor))
        return 0;

    // If message is set, API call did not go well. Ignore caching.
    if (isset($info_contributor->message) || !isset($info_contributor->id))
    {
        return 0;
    }

    $db = getDatabase();

    $s_id       = mysqli_real_escape_string($db, $info_contributor->id);
    $s_username = mysqli_real_escape_string($db, $username);

    $q_contributor = mysqli_query($db, "SELECT *
                                        FROM `contributors`
                                        WHERE `id` = {$s_id}
                                        LIMIT 1; ");

    if (is_bool($q_contributor))
    {
        return 0;
    }

    if (mysqli_num_rows($q_contributor) === 0)
    {
        // Contributor not yet cached on contributors table.
        mysqli_query($db, "INSERT INTO `contributors` (`id`, `username`)
                           VALUES ({$s_id}, '{$s_username}');");
    }
    else
    {
        $contributor = mysqli_fetch_object($q_contributor);

        // This should be unreachable unless the database structure is damaged
        if (!$contributor || !property_exists($contributor, "username"))
        {
            return 0;
        }

        if ($contributor->username != $username)
        {
            // Contributor on contributors table but changed GitHub username.
            mysqli_query($db, "UPDATE `contributors`
                               SET `username` = '{$s_username}'
                               WHERE `id` = {$s_id};");
        }
    }

    mysqli_close($db);

    return $info_contributor->id;
}


function cacheWikiIDs() : void
{
    $db = getDatabase();
    $a_wiki = array();
    $break = false;

    // Run this in batches of 250 pages
    for ($count = 0; !$break; $count += 250)
    {
        // Fetch all wiki pages that contain a Game ID
        $q_wiki = mysqli_query($db, "SELECT `page_id`, CONVERT(`old_text` USING utf8mb4) AS `text`
                                     FROM `rpcs3_wiki`.`page`
                                     INNER JOIN `rpcs3_wiki`.`slots`
                                             ON `page`.`page_latest` = `slots`.`slot_revision_id`
                                     INNER JOIN `rpcs3_wiki`.`content`
                                             ON `slots`.`slot_content_id` = `content`.`content_id`
                                     INNER JOIN `rpcs3_wiki`.`text`
                                             ON SUBSTR(`content`.`content_address`, 4) = `text`.`old_id`
                                     WHERE `page`.`page_namespace` = 0
                                     HAVING `text` RLIKE '[A-Z]{4}[0-9]{5}'
                                     LIMIT {$count}, 250; ");

        if (is_bool($q_wiki))
            return;

        // As long as we have results
        if (mysqli_num_rows($q_wiki) > 0)
        {
            while ($row = mysqli_fetch_object($q_wiki))
            {
                // This should be unreachable unless the database structure is damaged
                if (!property_exists($row, "page_id") ||
                        !property_exists($row, "text"))
                {
                    return;
                }

                $matches = array();
                preg_match_all("/[A-Z]{4}[0-9]{5}/", $row->text, $matches);

                foreach ($matches[0] as $match)
                {
                    $a_wiki[$match] = $row->page_id;
                }
            }
        }
        // End the cycle after the unset
        else
        {
            $break = true;
        }

        // Unload memory heavy object from memory after we've used it
        unset($q_wiki);
    }

    $q_games = mysqli_query($db, "SELECT * FROM `game_list`;");

    if (is_bool($q_games))
        return;

    $a_games = Game::query_to_games($q_games);

    // Cached game keys
    $a_cached  = array();
    $q_updates = "";

    // For every Game
    // For every GameItem
    foreach ($a_games as $game)
    {
        foreach ($game->game_item as $item)
        {
            // Didn't find Game ID on any wiki pages or already cached this key in this run
            if (!isset($a_wiki[$item->game_id]) || in_array($game->key, $a_cached))
            {
                continue;
            }

            // Update compatibility list entries with the found Wiki IDs
            // Maybe delete all pages beforehand?
            // Probably not needed as Wiki pages shouldn't be changing IDs.
            // Different games can have the same game title, thus use key here.
            $db_id  = mysqli_real_escape_string($db, $a_wiki[$item->game_id]);
            $db_key = mysqli_real_escape_string($db, (string) $game->key);

            $q_updates .= "UPDATE `game_list`
                           SET `wiki` = '{$db_id}'
                           WHERE `key` = '{$db_key}'; ";

            $a_cached[] = $game->key;
            break;
        }
    }

    if (!empty($q_updates))
    {
        mysqli_multi_query($db, $q_updates);
        // No need to flush here since we're not issuing other queries before closing
    }

    mysqli_close($db);
}


function cache_game_updates(CurlHandle $cr, mysqli $db, string $gid) : bool
{
    set_time_limit(60*60); // 1 hour

    // Reset current cURL resource to use default values before using it
    curl_reset($cr);

    // Set the required cURL flags
    curl_setopt($cr, CURLOPT_RETURNTRANSFER, true);  // Return result as raw output
    curl_setopt($cr, CURLOPT_SSL_VERIFYPEER, false); // Do not verify SSL certs (PS3 Update API uses Private CA)
    curl_setopt($cr, CURLOPT_URL, "https://a0.ww.np.dl.playstation.net/tpl/np/{$gid}/{$gid}-ver.xml");

    // Get the response
    $api = curl_exec($cr);

    if (is_bool($api))
        return false;

    // Get cURL response related information
    $httpcode = curl_getinfo($cr, CURLINFO_HTTP_CODE);

    // Reset current cURL resource to use default values before returning it
    curl_reset($cr);

    $db_gid = mysqli_real_escape_string($db, $gid);

    // Handle not found cases
    if ($httpcode == 404)
    {
        // Game ID does not exist on the Update API (but a game with it may exist)
        // Note: The API appears to have been updated and now always returns a proper XML reply for 404
        // Keeping the old check for plaintext 'Not found' reply just in case
        if (str_starts_with($api, "Not found") || str_contains($api, "NoSuchKey"))
        {
            mysqli_query($db, "INSERT INTO `game_update_titlepatch` (`titleid`, `status`)
                               VALUES ('{$db_gid}', '404'); ");
            // Legacy field
            mysqli_query($db, "UPDATE `game_id`
                               SET `latest_ver` = ''
                               WHERE `gid` = '{$gid}'; ");
            return true;
        }

        echo "Unknown return type! gid:{$gid}, httpcode:{$httpcode}, api:{$api}".PHP_EOL;
        return false;
    }
    else if ($httpcode == 200)
    {
        // Game ID exists but has no updates
        if ($api === "")
        {
            mysqli_query($db, "INSERT INTO `game_update_titlepatch` (`titleid`, `status`)
                               VALUES ('{$db_gid}', ''); ");
            // Legacy field
            mysqli_query($db, "UPDATE `game_id`
                               SET `latest_ver` = ''
                               WHERE `gid` = '{$gid}'; ");
            return true;
        }
    }
    else
    {
        // Unknown HTTP return code
        echo "Unknown return code! gid:{$gid}, httpcode:{$httpcode}, api:{$api}".PHP_EOL;
        return false;
    }

    // Convert from XML to JSON
    $api = simplexml_load_string($api);
    $api = json_encode($api);
    if (!$api)
        return false;
    $api = json_decode($api, false);
    if (!is_object($api))
        return false;

    // Sanity check the API results
    if (!isset($api->{"@attributes"}) || !isset($api->{"@attributes"}->status) || !isset($api->{"@attributes"}->titleid))
    {
        echo "Missing titlepatch attributes! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
        return false;
    }
    if (count(get_object_vars($api->{"@attributes"})) !== 2)
    {
        echo "Unexpected titlepatch attributes count! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
        return false;
    }
    if ($api->{"@attributes"}->titleid !== $gid)
    {
        echo "Mismatching game IDs?! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
        return false;
    }
    if (!isset($api->tag->{"@attributes"}->name) || !isset($api->tag->{"@attributes"}->popup) || !isset($api->tag->{"@attributes"}->signoff))
    {
        echo "Missing tag core attributes! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
        return false;
    }
    if ($api->tag->{"@attributes"}->popup !== "true" && $api->tag->{"@attributes"}->popup !== "false")
    {
        echo "Unexpected tag popup value! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
        return false;
    }
    if ($api->tag->{"@attributes"}->signoff !== "true" && $api->tag->{"@attributes"}->signoff !== "false")
    {
        echo "Unexpected tag signoff value! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
        return false;
    }

    // Verify tag attributes
    $count_tag_attributes = count(get_object_vars($api->tag->{"@attributes"}));
    $a_tag_attributes = array("name", "popup", "signoff", "hash", "popup_delay", "min_system_ver");

    foreach ((array) $api->tag->{"@attributes"} as $tag_attribute => $value)
    {
        if (!in_array($tag_attribute, $a_tag_attributes))
        {
            echo "Unexpected tag attribute {$tag_attribute}! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
            return false;
        }
    }

    // Titlepatch
    $db_status = mysqli_real_escape_string($db, $api->{"@attributes"}->status);
    $q_insert = "INSERT INTO `game_update_titlepatch` (`titleid`, `status`) VALUES ('{$db_gid}', '{$db_status}'); ";

    // Tag
    $db_tag_name = mysqli_real_escape_string($db, $api->tag->{"@attributes"}->name);
    $db_tag_popup = mysqli_real_escape_string($db, $api->tag->{"@attributes"}->popup);
    $db_tag_signoff = mysqli_real_escape_string($db, $api->tag->{"@attributes"}->signoff);
    $tag_hash = NULL;

    $db_package_version_latest = NULL;

    // Has multiple updates
    $packages = is_array($api->tag->package) ? $api->tag->package : array($api->tag->package);

    // Packages
    foreach ($packages as $package)
    {
        // Split URL to extract tag hash
        $url_split = explode('/', $package->{"@attributes"}->url);

        if (count($url_split) !== 9)
        {
            echo "Unexpected package URL! gid:{$gid}, httpcode:{$httpcode}, url:{$package->{"@attributes"}->url}".PHP_EOL;
            return false;
        }
        if (!is_null($tag_hash) && $tag_hash !== $url_split[7])
        {
            echo "Unexpected package hash! gid:{$gid}, httpcode:{$httpcode}, url:{$package->{"@attributes"}->url}".PHP_EOL;
            return false;
        }
        if (!isset($package->{"@attributes"}->version) || !isset($package->{"@attributes"}->size) || !isset($package->{"@attributes"}->sha1sum) || !isset($package->{"@attributes"}->url))
        {
            echo "Missing package core attributes! gid:{$gid}, httpcode:{$httpcode}, url:{$package->{"@attributes"}->url}".PHP_EOL;
            return false;
        }

        // Verify package attributes
        // $count_package_attributes = count(get_object_vars($package->{"@attributes"}));
        $a_package_attributes = array("version", "size", "sha1sum", "url", "ps3_system_ver", "drm_type");

        foreach ((array) $package->{"@attributes"} as $tag_package => $value)
        {
            if (!in_array($tag_package, $a_package_attributes))
            {
                echo "Unexpected package attribute {$tag_package}! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
                return false;
            }
        }

        $tag_hash = $url_split[7];

        $db_package_version = mysqli_real_escape_string($db, $package->{"@attributes"}->version);
        $db_package_size = mysqli_real_escape_string($db, $package->{"@attributes"}->size);
        $db_package_sha1sum = mysqli_real_escape_string($db, $package->{"@attributes"}->sha1sum);
        $db_package_url = mysqli_real_escape_string($db, $package->{"@attributes"}->url);

        $db_package_version_latest = $db_package_version;

        // Optional field: ps3_system_ver
        if (isset($package->{"@attributes"}->ps3_system_ver))
        {
            $db_package_ps3_system_ver = ", '".mysqli_real_escape_string($db, $package->{"@attributes"}->ps3_system_ver)."'";
        }
        else
        {
            $db_package_ps3_system_ver = ", NULL";
        }

        // Optional field: drm_type
        if (isset($package->{"@attributes"}->drm_type))
        {
            $db_package_drm_type = ", '".mysqli_real_escape_string($db, $package->{"@attributes"}->drm_type)."'";
        }
        else
        {
            $db_package_drm_type = ", NULL";
        }

        $q_insert .= "INSERT INTO `game_update_package`
                      (`tag`,
                       `version`,
                       `size`,
                       `sha1sum`,
                       `url`,
                       `ps3_system_ver`,
                       `drm_type`)
                      VALUES ('{$db_tag_name}',
                              '{$db_package_version}',
                              '{$db_package_size}',
                              '{$db_package_sha1sum}',
                              '{$db_package_url}'
                              {$db_package_ps3_system_ver}{$db_package_drm_type}); \n";

        // Extra URL (usually used with different drm_type)
        if (isset($package->url))
        {
            // Has multiple extra URLs
            $urls = is_array($package->url) ? $package->url : array($package->url);

            foreach ($urls as $url)
            {
                if (isset($url->{"@attributes"}->version))
                    $db_package_version = mysqli_real_escape_string($db, $url->{"@attributes"}->version);

                if (isset($url->{"@attributes"}->size))
                    $db_package_size = mysqli_real_escape_string($db, $url->{"@attributes"}->size);

                if (isset($url->{"@attributes"}->sha1sum))
                    $db_package_sha1sum = mysqli_real_escape_string($db, $url->{"@attributes"}->sha1sum);

                if (isset($url->{"@attributes"}->url))
                    $db_package_url = mysqli_real_escape_string($db, $url->{"@attributes"}->url);

                // Optional field: ps3_system_ver
                if (isset($url->{"@attributes"}->ps3_system_ver))
                {
                    $db_package_ps3_system_ver = ", '".mysqli_real_escape_string($db, $url->{"@attributes"}->ps3_system_ver)."'";
                }
                else
                {
                    $db_package_ps3_system_ver = ", NULL";
                }

                // Optional field: drm_type
                if (isset($url->{"@attributes"}->drm_type))
                {
                    $db_package_drm_type = ", '".mysqli_real_escape_string($db, $url->{"@attributes"}->drm_type)."'";
                }
                else
                {
                    $db_package_drm_type = ", NULL";
                }

                $q_insert .= "INSERT INTO `game_update_package`
                              (`tag`,
                               `version`,
                               `size`,
                               `sha1sum`,
                               `url`,
                               `ps3_system_ver`,
                               `drm_type`)
                              VALUES ('{$db_tag_name}',
                                      '{$db_package_version}',
                                      '{$db_package_size}',
                                      '{$db_package_sha1sum}',
                                      '{$db_package_url}'
                                      {$db_package_ps3_system_ver}{$db_package_drm_type}); \n";
            }
        }

        // PARAM.SFO data
        if (isset($package->paramsfo))
        {
            foreach ($package->paramsfo as $type => $title)
            {
                $db_paramsfo_type = mysqli_real_escape_string($db, $type);
                $db_paramsfo_title = mysqli_real_escape_string($db, $title);

                $q_insert .= "INSERT INTO `game_update_paramsfo`
                              (`tag`,
                               `package_version`,
                               `paramsfo_type`,
                               `paramsfo_title`)
                              VALUES ('{$db_tag_name}',
                                      '{$db_package_version}',
                                      '{$db_paramsfo_type}',
                                      '{$db_paramsfo_title}'); \n";
            }
        }

        // PARAM.HIP data
        foreach ($package as $key => $value)
        {
            if (!str_contains($key, "paramhip"))
            {
                continue;
            }

            $db_paramhip_type = mysqli_real_escape_string($db, $key);
            $db_paramhip_url = mysqli_real_escape_string($db, $value->{"@attributes"}->url);

            // Fetch PARAM.HIP contents
            curl_setopt($cr, CURLOPT_RETURNTRANSFER, true);  // Return result as raw output
            curl_setopt($cr, CURLOPT_SSL_VERIFYPEER, false); // Do not verify SSL certs (PS3 Update API uses Private CA)
            curl_setopt($cr, CURLOPT_URL, $value->{"@attributes"}->url);

            $paramhip_content = curl_exec($cr);
            $httpcode = curl_getinfo($cr, CURLINFO_HTTP_CODE);
            curl_reset($cr);

            if ($httpcode !== 200 || is_bool($paramhip_content))
            {
                echo "Failed to fetch PARAM.HIP! httpcode:{$httpcode}, return:{$paramhip_content}, url:{$value->{"@attributes"}->url}".PHP_EOL;
                return false;
            }

            $db_paramhip_content = mysqli_real_escape_string($db, $paramhip_content);

            $q_insert .= "INSERT INTO `game_update_paramhip`
                          (`tag`,
                           `package_version`,
                           `paramhip_type`,
                           `paramhip_url`,
                           `paramhip_content`)
                          VALUES ('{$db_tag_name}',
                                  '{$db_package_version}',
                                  '{$db_paramhip_type}',
                                  '{$db_paramhip_url}',
                                  '{$db_paramhip_content}'); \n";
        }

        // Check if there are any child nodes we're not handling
        foreach ($package as $key => $value)
        {
            if ($key !== "@attributes" && $key !== "url" && $key !== "paramsfo" && !str_contains($key, "paramhip"))
            {
                echo "Unhandled package child node! key:{$key}, gid:{$gid}".PHP_EOL;
                return false;
            }
        }
    }

    if (is_null($tag_hash))
    {
        echo "Missing tag hash value! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
        return false;
    }
    else if (is_null($db_package_version_latest))
    {
        echo "Missing package version latest! gid:{$gid}, httpcode:{$httpcode}".PHP_EOL;
        return false;
    }


    $db_tag_hash = mysqli_real_escape_string($db, $tag_hash);

    // Optional field: popup_delay
    if (isset($api->tag->{"@attributes"}->popup_delay))
    {
        $db_tag_popup_delay = ", '".mysqli_real_escape_string($db, $api->tag->{"@attributes"}->popup_delay)."'";
    }
    else
    {
        $db_tag_popup_delay = ", NULL";
    }
    // Optional field: min_system_ver
    if (isset($api->tag->{"@attributes"}->min_system_ver))
    {
        $db_tag_min_system_ver = ", '".mysqli_real_escape_string($db, $api->tag->{"@attributes"}->min_system_ver)."'";
    }
    else
    {
        $db_tag_min_system_ver = ", NULL";
    }

    $q_insert .= "INSERT INTO `game_update_tag`
                  (`name`,
                   `popup`,
                   `signoff`,
                   `hash`,
                   `popup_delay`,
                   `min_system_ver`)
                  VALUES ('{$db_tag_name}',
                          '{$db_tag_popup}',
                          '{$db_tag_signoff}',
                          '{$db_tag_hash}'
                          {$db_tag_popup_delay}{$db_tag_min_system_ver}); \n";

    // Legacy field
    $q_insert .= "UPDATE `game_id`
                  SET `latest_ver` = '{$db_package_version_latest}'
                  WHERE `gid` = '{$db_gid}'; \n";

    // Run all queries
    mysqli_multi_query($db, $q_insert);

    // Flush mysqli object after mysqli_multi_query
    while ($db->next_result()) {;}

    return true;
}


function cache_games_updates() : void
{
    $db = getDatabase();

    $q_ids = mysqli_query($db, "SELECT `gid`
                                FROM `game_id`
                                LEFT JOIN `game_update_titlepatch`
                                ON `game_id`.`gid` = `game_update_titlepatch`.`titleid`
                                WHERE `titleid` IS NULL;");

    if (is_bool($q_ids))
        return;

    $cr = curl_init();

    while ($row = mysqli_fetch_object($q_ids))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row, "gid"))
        {
            continue;
        }

        cache_game_updates($cr, $db, $row->gid);
    }

    curl_close($cr);
    mysqli_close($db);
}


function cachePatches() : void
{
    $db = getDatabase();

    // ID for the SPU Patches page, containing the general use SPU patches
    $id_patches_spu = 1090;

    // Select all page IDs present on game list
    $q_wiki = mysqli_query($db, "SELECT `page_id`,
                                        `page_title`,
                                        `page_touched`,
                                         CONVERT(`old_text` USING utf8mb4) AS `text`
                                 FROM `rpcs3_wiki`.`page`
                                 LEFT JOIN `rpcs3_compatibility`.`game_list`
                                        ON `page`.`page_id` = `game_list`.`wiki`
                                 LEFT JOIN `rpcs3_wiki`.`slots`
                                        ON `page`.`page_latest` = `slots`.`slot_revision_id`
                                 LEFT JOIN `rpcs3_wiki`.`content`
                                        ON `slots`.`slot_content_id` = `content`.`content_id`
                                 LEFT JOIN `rpcs3_wiki`.`text`
                                        ON SUBSTR(`content`.`content_address`, 4) = `text`.`old_id`
                                 WHERE (`page`.`page_namespace` = 0 AND
                                        `game_list`.`wiki` IS NOT NULL)
                                    OR `page`.`page_id` = {$id_patches_spu}
                                 HAVING `text` LIKE '%{{patch%'; ");

    // No wiki pages, return here
    if (is_bool($q_wiki) || mysqli_num_rows($q_wiki) === 0)
        return;

    // Disabled by default, but it's disabled here again in case it's enabled
    ini_set("yaml.decode_php", '0');

    // Select all game patches currently on database
    $q_patch = mysqli_query($db, "SELECT `wiki_id`, `version`, `touched`
                                  FROM `rpcs3_compatibility`.`game_patch`; ");

    // This should be unreachable unless the database structure is damaged
    if (is_bool($q_patch))
        return;

    // Results array [version, touched]
    $a_patch = array();
    if (mysqli_num_rows($q_patch) !== 0)
    {
        while ($row = mysqli_fetch_object($q_patch))
        {
            // This should be unreachable unless the database structure is damaged
            if (!property_exists($row, "wiki_id") ||
                    !property_exists($row, "version") ||
                    !property_exists($row, "touched"))
            {
                continue;
            }

            $a_patch[$row->wiki_id] = array("version" => $row->version,
                                            "touched" => $row->touched);
        }
    }

    // Results array [id, title, text, date]
    $a_wiki = array();
    while ($row = mysqli_fetch_object($q_wiki))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row, "page_id") ||
                !property_exists($row, "page_title") ||
                !property_exists($row, "text") ||
                !property_exists($row, "page_touched"))
        {
            continue;
        }

        $a_wiki[] = array("id"    => (int)    $row->page_id,
                          "title" => (string) $row->page_title,
                          "text"  => (string) $row->text,
                          "date"  => (int)    $row->page_touched);
    }

    // Delete cached data for the now patchless pages if cache exists
    foreach ($a_patch as $id => $patch)
    {
        $exists = false;

        foreach ($a_wiki as $i => $result)
        {
            if ($id == $result["id"])
            {
                $exists = true;
                break;
            }
        }

        if (!$exists)
        {
            mysqli_query($db, "DELETE FROM `rpcs3_compatibility`.`game_patch`
                                                 WHERE `wiki_id` = {$id}; ");
        }
    }

    foreach ($a_wiki as $i => $result)
    {
        // Get patch header information
        $header = get_string_between($result["text"], "{{patch", "|content");

        // Invalid information header
        if (!is_string($header) || empty($header))
        {
            echo "Invalid patch header syntax on Wiki Page {$result["id"]}: {$result["title"]} <br>";
            unset($a_wiki[$i]);
            continue;
        }

        // Get patch type
        $type = null;
        $line = strtok($header, "\r\n");

        while ($line !== false)
        {
            if (!str_starts_with($line, "|type"))
            {
                $line = strtok("\r\n");
                continue;
            }

            // Get the three characters representing the patch type after " = "
            $type = substr($line, strpos($line, " = ") + strlen(" = "), 3);
            break;
        }

        // Check if patch version syntax is valid (number, underscore, number)
        if (!is_string($type) || strlen($type) !== 3 || !ctype_alpha($type))
        {
            echo "Invalid patch type syntax on Wiki Page {$result["id"]}: {$result["title"]} <br>";
            unset($a_wiki[$i]);
            continue;
        }

        // Only accept PPU, SPU and OVL type patches
        if ($type !== "PPU" && $type !== "SPU" && $type !== "OVL")
        {
            unset($a_wiki[$i]);
            continue;
        }

        // Only accept SPU patches from the SPU page
        if ($result["id"] === $id_patches_spu && $type !== "SPU")
        {
            unset($a_wiki[$i]);
            continue;
        }

        // Get patch version
        $version = null;
        $line = strtok($header, "\r\n");

        while ($line !== false)
        {
            if (!str_starts_with($line, "|version"))
            {
                $line = strtok("\r\n");
                continue;
            }

            // Get the three characters representing the patch version after " = "
            $version = substr($line, strpos($line, " = ") + strlen(" = "), 3);
            break;
        }

        // Check if patch version syntax is valid (number, underscore, number)
        if (!is_string($version) || strlen($version) !== 3 || !ctype_digit($version[0]) || $version[1] !== '.' || !ctype_digit($version[2]))
        {
            echo "Invalid patch version syntax on Wiki Page {$result["id"]}: {$result["title"]} <br>";
            unset($a_wiki[$i]);
            continue;
        }

        if ($version !== "1.2")
        {
            echo "Invalid patch version as it's not on the latest patch version on Wiki Page {$result["id"]}: {$result["title"]} <br>";
            unset($a_wiki[$i]);
            continue;
        }

        // Remove the header before extracting the YAML code
        $txt_patch = substr($result["text"], strpos($result["text"], "|content") + strlen("|content"));
        // Extract the YAML code
        $txt_patch = get_string_between($txt_patch, "=", "}}");

        if (is_null($txt_patch))
        {
            echo "Invalid YAML syntax on Wiki Page {$result["id"]}: {$result["title"]} <br>";
            unset($a_wiki[$i]);
            continue;
        }

        // Remove any spacing and newlines before and after the patch
        $txt_patch = trim($txt_patch);

        // Validate whether the YAML code we fetched has valid YAML syntax
        $yml_patch = yaml_parse($txt_patch);

        // Discard patches with invalid YAML syntax
        if ($yml_patch === false)
        {
            echo "Invalid YAML syntax on Wiki Page {$result["id"]}: {$result["title"]} <br>";
            unset($a_wiki[$i]);
            continue;
        }

        $db_id      = mysqli_real_escape_string($db, (string) $result["id"]);
        $db_date    = mysqli_real_escape_string($db, (string) $result["date"]);
        $db_version = mysqli_real_escape_string($db, $version);
        $db_patch   = mysqli_real_escape_string($db, $txt_patch);

        // No existing patch found, insert new patch
        if (!isset($a_patch[$result["id"]]))
        {
            mysqli_query($db, "INSERT INTO `rpcs3_compatibility`.`game_patch`
                               (`wiki_id`,
                                `version`,
                                `touched`,
                                `patch`)
                               VALUES ('{$db_id}',
                                       '{$db_version}',
                                       '{$db_date}',
                                       '{$db_patch}'); ");
        }

        // Existing patch found with older touch date, update it
        else if ($db_date !== $a_patch[$result["id"]]["touched"])
        {
            mysqli_query($db, "UPDATE `rpcs3_compatibility`.`game_patch`
                               SET `touched` = '{$db_date}',
                                   `patch`   = '{$db_patch}'
                               WHERE `wiki_id` = '{$db_id}'
                                 AND `version` = '{$db_version}'; ");
        }
    }
}


function cache_netplay_statistics() : bool
{
    $q_updates = "";

    // Reset current cURL resource to use default values before using it
    $np_stats = curl_json(np_api, null);

    if (is_null($np_stats))
    {
        echo "cache_netplay_statistics(): Failed to poll the NP API".PHP_EOL;
        return false;
    }

    // Global players data
    if (!property_exists($np_stats, "num_users"))
    {
        echo "cache_netplay_statistics(): NP API does not contain the num_users property".PHP_EOL;
        return false;
    }

    $db = getDatabase();

    $s_players = mysqli_real_escape_string($db, (string) $np_stats->num_users);
    $q_updates .= "INSERT INTO `np_players` (`timestamp`, `players`) VALUES (CONVERT_TZ(NOW(),'SYSTEM','+00:00'), '{$s_players}'); ";

    // PSN games data
    if (!property_exists($np_stats, "psn_games"))
    {
        echo "cache_netplay_statistics(): NP API does not contain the psn_games property".PHP_EOL;
        mysqli_close($db);
        return false;
    }

    foreach ($np_stats->psn_games as $comm_id => $np_data)
    {
        if (!isset($np_data[0]))
        {
            echo "cache_netplay_statistics(): NP API does not contain the player count for {$comm_id}".PHP_EOL;
            mysqli_close($db);
            return false;
        }

        $s_comm_id  = mysqli_real_escape_string($db, (string) $comm_id);
        $s_players  = mysqli_real_escape_string($db, (string) $np_data[0]);
        $q_updates .= "INSERT INTO `np_psn_games` (`timestamp`, `comm_id`, `players`) ";
        $q_updates .= "VALUES (CONVERT_TZ(NOW(),'SYSTEM','+00:00'), '{$s_comm_id}', '{$s_players}'); ";
    }

    // Ticket games data
    if (!property_exists($np_stats, "ticket_games"))
    {
        echo "cache_netplay_statistics(): NP API does not contain the ticket_games property".PHP_EOL;
        mysqli_close($db);
        return false;
    }

    foreach ($np_stats->ticket_games as $content_id => $players)
    {
        $s_content_id = mysqli_real_escape_string($db, (string) $content_id);
        $s_players    = mysqli_real_escape_string($db, (string) $players);
        $q_updates   .= "INSERT INTO `np_ticket_games` (`timestamp`, `content_id`, `players`) ";
        $q_updates   .= "VALUES (CONVERT_TZ(NOW(),'SYSTEM','+00:00'), '{$s_content_id}', '{$s_players}'); ";
    }

    mysqli_multi_query($db, $q_updates);
    mysqli_close($db);
    return true;
}
