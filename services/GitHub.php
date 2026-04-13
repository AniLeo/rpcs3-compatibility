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


function cache_builds(bool $full = false) : void
{
    $db = get_database("compat");
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
            $q_pr = mysqli_query($db,  "SELECT `pr` 
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
                return;
            }
        }
    }
    mysqli_close($db);
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
        return;
    }

    $merge_datetime = strtotime($pr_info->merged_at);
    $start_datetime = strtotime($pr_info->created_at);
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

    $aid = cache_contributor($author);
    // Checking author information failed
    // TODO: This should probably be logged, as other API call fails
    if ($aid == 0)
    {
        echo "Error: Checking author information failed";
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

    // Windows arm64 build metadata
    $info_release_win_arm64 = curl_json("https://api.github.com/repos/rpcs3/rpcs3-binaries-win-arm64/releases/tags/build-{$commit}", $cr);
    if (is_null($info_release_win_arm64))
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
                  isset($info_release_win_arm64->message) ||
                  isset($info_release_linux->message) ||
                  isset($info_release_linux_arm64->message) ||
                  isset($info_release_mac->message) ||
                  isset($info_release_mac_arm64->message);

    $hour_limit = 1;
    $is_missing_platform = $is_missing && time() - $merge_datetime >= (3600 * $hour_limit);

    // Error message found: Build doesn't exist in one of the repos
    // Do not ignore if the build was merged over two hours ago, to cache as broken
    // TODO: Ignore macOS if date is prior to the first macOS build
    if ($is_missing && !$is_missing_platform)
    {
        printf("Error: One of the platforms is still not available, waiting up to %dh before assuming as missing, %d seconds remaining", 
               $hour_limit, time() - $merge_datetime);
        return;
    }

    $info_win   = parse_build_properties($info_release_win);
    $info_linux = parse_build_properties($info_release_linux);
    $info_mac   = parse_build_properties($info_release_mac);
    $info_win_arm64 = parse_build_properties($info_release_win_arm64);
    $info_linux_arm64 = parse_build_properties($info_release_linux_arm64);
    $info_mac_arm64 = parse_build_properties($info_release_mac_arm64);

    // Fail if one of the builds is not available until a grace period has passed
    if (!is_null($info_win))
    {
        $version = $info_win["version"];
    }
    else if (!$is_missing_platform)
    {
        return;
    }

    if (!isset($version) && !is_null($info_linux))
    {
        $version = $info_linux["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        return;
    }

    if (!isset($version) && !is_null($info_mac))
    {
        $version = $info_mac["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        return;
    }

    if (!isset($version) && !is_null($info_win_arm64))
    {
        $version = $info_win_arm64["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        return;
    }

    if (!isset($version) && !is_null($info_linux_arm64))
    {
        $version = $info_linux_arm64["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        return;
    }

    if (!isset($version) && !is_null($info_mac_arm64))
    {
        $version = $info_mac_arm64["version"];
    }
    else if (!isset($version) && !$is_missing_platform)
    {
        return;
    }

    // No builds are available
    // TODO: Get $version when all the builds are missing and allow caching the entry
    if (!isset($version))
    {
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
        printf("Build status: Windows: %s, Linux: %s, macOS: %s, Windows arm64: %s, Linux arm64: %s, macOS arm64: %s",
               isset($info_release_win->message)         ? $info_release_win->message : "OK",
               isset($info_release_linux->message)       ? $info_release_linux->message : "OK",
               isset($info_release_mac->message)         ? $info_release_mac->message : "OK",
               isset($info_release_win_arm64->message)   ? $info_release_win_arm64->message : "OK",
               isset($info_release_linux_arm64->message) ? $info_release_linux_arm64->message : "OK",
               isset($info_release_mac_arm64->message)   ? $info_release_mac_arm64->message : "OK");
    }

    if ($is_broken_build)
        $is_broken = "1";
    else if ($is_missing_platform)
        $is_broken = "2";

    $db = get_database("compat");

    $q_build = mysqli_query($db, "SELECT `pr` FROM `builds` WHERE `pr` = {$pr} LIMIT 1; ");

    if (is_bool($q_build))
    {
        mysqli_close($db);
        return;
    }

    if (mysqli_num_rows($q_build) === 1)
    {
        mysqli_query($db, "UPDATE `builds` SET
        `commit`               = '".mysqli_real_escape_string($db, $commit)."',
        `type`                 = '".mysqli_real_escape_string($db, $type)."',
        `author`               = '".mysqli_real_escape_string($db, (string) $aid)."',
        `start_datetime`       = FROM_UNIXTIME('".mysqli_real_escape_string($db, (string) $start_datetime)."'),
        `merge_datetime`       = FROM_UNIXTIME('".mysqli_real_escape_string($db, (string) $merge_datetime)."'),
        `version`              = '".mysqli_real_escape_string($db, $version)."',
        `additions`            = '".mysqli_real_escape_string($db, (string) $additions)."',
        `deletions`            = '".mysqli_real_escape_string($db, (string) $deletions)."',
        `changed_files`        = '".mysqli_real_escape_string($db, (string) $changed_files)."',
        `size_win`             = ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["size"])."'" : "NULL").",
        `checksum_win`         = ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["checksum"])."'" : "NULL").",
        `filename_win`         = ".(isset($info_win)   ? "'".mysqli_real_escape_string($db, (string) $info_win["filename"])."'" : "NULL").",
        `size_linux`           = ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["size"])."'" : "NULL").",
        `checksum_linux`       = ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["checksum"])."'" : "NULL").",
        `filename_linux`       = ".(isset($info_linux) ? "'".mysqli_real_escape_string($db, (string) $info_linux["filename"])."'" : "NULL").",
        `size_mac`             = ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["size"])."'" : "NULL").",
        `checksum_mac`         = ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["checksum"])."'" : "NULL").",
        `filename_mac`         = ".(isset($info_mac)   ? "'".mysqli_real_escape_string($db, (string) $info_mac["filename"])."'" : "NULL").",
        `size_win_arm64`       = ".(isset($info_win_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_win_arm64["size"])."'" : "NULL").",
        `checksum_win_arm64`   = ".(isset($info_win_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_win_arm64["checksum"])."'" : "NULL").",
        `filename_win_arm64`   = ".(isset($info_win_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_win_arm64["filename"])."'" : "NULL").",
        `size_linux_arm64`     = ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["size"])."'" : "NULL").",
        `checksum_linux_arm64` = ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["checksum"])."'" : "NULL").",
        `filename_linux_arm64` = ".(isset($info_linux_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_linux_arm64["filename"])."'" : "NULL").",
        `size_mac_arm64`       = ".(isset($info_mac_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["size"])."'" : "NULL").",
        `checksum_mac_arm64`   = ".(isset($info_mac_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["checksum"])."'" : "NULL").",
        `filename_mac_arm64`   = ".(isset($info_mac_arm64) ? "'".mysqli_real_escape_string($db, (string) $info_mac_arm64["filename"])."'" : "NULL").",
        `broken`               = ".(isset($is_broken) ? "'".mysqli_real_escape_string($db, $is_broken)."'" : "NULL").",
        `title`                = '".mysqli_real_escape_string($db, $title)."',
        `body`                 = '".mysqli_real_escape_string($db, $body)."'
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
         `size_win_arm64`,
         `checksum_win_arm64`,
         `filename_win_arm64`,
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
        FROM_UNIXTIME('".mysqli_real_escape_string($db, (string) $start_datetime)."'),
        FROM_UNIXTIME('".mysqli_real_escape_string($db, (string) $merge_datetime)."'),
        '".mysqli_real_escape_string($db, $version)."',
        '".mysqli_real_escape_string($db, (string) $additions)."',
        '".mysqli_real_escape_string($db, (string) $deletions)."',
        '".mysqli_real_escape_string($db, (string) $changed_files)."',
        ".(isset($info_win)         ? "'".mysqli_real_escape_string($db, (string) $info_win["size"])."'" : "NULL").",
        ".(isset($info_win)         ? "'".mysqli_real_escape_string($db, (string) $info_win["checksum"])."'" : "NULL").",
        ".(isset($info_win)         ? "'".mysqli_real_escape_string($db, (string) $info_win["filename"])."'" : "NULL").",
        ".(isset($info_linux)       ? "'".mysqli_real_escape_string($db, (string) $info_linux["size"])."'" : "NULL").",
        ".(isset($info_linux)       ? "'".mysqli_real_escape_string($db, (string) $info_linux["checksum"])."'" : "NULL").",
        ".(isset($info_linux)       ? "'".mysqli_real_escape_string($db, (string) $info_linux["filename"])."'" : "NULL").",
        ".(isset($info_mac)         ? "'".mysqli_real_escape_string($db, (string) $info_mac["size"])."'" : "NULL").",
        ".(isset($info_mac)         ? "'".mysqli_real_escape_string($db, (string) $info_mac["checksum"])."'" : "NULL").",
        ".(isset($info_mac)         ? "'".mysqli_real_escape_string($db, (string) $info_mac["filename"])."'" : "NULL").",
        ".(isset($info_win_arm64)   ? "'".mysqli_real_escape_string($db, (string) $info_win_arm64["size"])."'" : "NULL").",
        ".(isset($info_win_arm64)   ? "'".mysqli_real_escape_string($db, (string) $info_win_arm64["checksum"])."'" : "NULL").",
        ".(isset($info_win_arm64)   ? "'".mysqli_real_escape_string($db, (string) $info_win_arm64["filename"])."'" : "NULL").",
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
            str_contains($asset->name, "aarch64_clang.7z") ||
            str_contains($asset->name, "linux64.AppImage") ||
            str_contains($asset->name, "linux_aarch64.AppImage") ||
            str_contains($asset->name, "macos.7z") ||
            str_contains($asset->name, "macos_arm64.7z") ||
            str_contains($asset->name, "macos_aarch64.7z"))
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

function cache_contributor(string $username) : int
{
    $info_contributor = curl_json("https://api.github.com/users/{$username}", null);

    if (is_null($info_contributor))
        return 0;

    // If message is set, API call did not go well. Ignore caching.
    if (isset($info_contributor->message) || !isset($info_contributor->id))
    {
        return 0;
    }

    $db = get_database("compat");

    $s_id       = mysqli_real_escape_string($db, $info_contributor->id);
    $s_username = mysqli_real_escape_string($db, $username);

    $q_contributor = mysqli_query($db, "SELECT `username`
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