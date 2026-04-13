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
if (!@include_once(__DIR__."/../functions.php"))    throw new Exception("Compat: Failed to include functions.php");
if (!@include_once(__DIR__."/../objects/Game.php")) throw new Exception("Compat: Failed to include objects/Game.php");


function cache_wiki_ids() : void
{
    $db = get_database("compat");
    $db_wiki = get_database("wiki");
    $a_wiki = array();
    $break = false;

    // Run this in batches of 250 pages
    for ($count = 0; !$break; $count += 250)
    {
        // Fetch all wiki pages that contain a Game ID
        $q_wiki = mysqli_query($db_wiki, "SELECT `page_id`, CONVERT(`old_text` USING utf8mb4) AS `text`
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

function cache_patches() : void
{
    $db = get_database("compat");
    $db_wiki = get_database("wiki");

    // ID for the SPU Patches page, containing the general use SPU patches
    $id_patches_spu = 1090;

    // Select all page IDs present on game list
    $q_wiki = mysqli_query($db_wiki, "SELECT `page_id`,
                                        `page_title`,
                                        `page_touched`,
                                         CONVERT(`old_text` USING utf8mb4) AS `text`
                                      FROM `rpcs3_wiki`.`page`
                                      LEFT JOIN `rpcs3_wiki`.`slots`
                                             ON `page`.`page_latest` = `slots`.`slot_revision_id`
                                      LEFT JOIN `rpcs3_wiki`.`content`
                                             ON `slots`.`slot_content_id` = `content`.`content_id`
                                      LEFT JOIN `rpcs3_wiki`.`text`
                                             ON SUBSTR(`content`.`content_address`, 4) = `text`.`old_id`
                                      WHERE (`page`.`page_namespace` = 0)
                                         OR `page`.`page_id` = {$id_patches_spu}
                                      HAVING `text` LIKE '%{{patch%'; ");

    // No wiki pages, return here
    if (is_bool($q_wiki) || mysqli_num_rows($q_wiki) === 0)
        return;

    // Select all wiki pages that are linked to a game entry
    $q_compat = mysqli_query($db, "SELECT UNIQUE `wiki`
                                   FROM `rpcs3_compatibility`.`game_list`
                                   WHERE `wiki` IS NOT NULL");

    // No compat entries, return here
    if (is_bool($q_compat) || mysqli_num_rows($q_compat) === 0)
        return;

    // List of wiki IDs currently linked to the compatibility list
    $a_compat_wiki_ids = array();
    $a_compat_wiki_ids[] = $id_patches_spu;

    while ($row = mysqli_fetch_object($q_compat))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row, "wiki"))
        {
            continue;
        }

        if (!in_array((int) $row->wiki, $a_compat_wiki_ids))
            $a_compat_wiki_ids[] = (int) $row->wiki;
    }

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

        // Do not cache page IDs that are not linked on the compatibility list
        if (!in_array((int) $row->page_id, $a_compat_wiki_ids))
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

function cache_game_settings() : void
{
    global $get;

    $db_wiki   = get_database("wiki");
    $db_compat = get_database("compat");
     
    // Setting to category and subcategory
    $q_categories = mysqli_query($db_wiki, "SELECT cl_to AS category, REPLACE(REPLACE(REPLACE(REPLACE(page_title, \"_(Config)\", \"\"), \"_\", \" \"), \": On\", \": true\"), \": Off\", \": false\") AS setting
                                            FROM `categorylinks`
                                            LEFT JOIN `page` 
                                            ON `page`.`page_id` = `categorylinks`.`cl_from` 
                                            WHERE page_title LIKE '%:%' AND categorylinks.cl_to IN
                                            (SELECT page_title FROM page WHERE page_id IN 
                                                (SELECT cl_from FROM categorylinks WHERE cl_to = \"Config_file\")
                                                OR 
                                                (page_title NOT LIKE '%(Config)%' and page_id IN
                                                    (SELECT cl_from FROM categorylinks WHERE cl_to IN
                                                        (SELECT page_title FROM page WHERE page_id IN 
                                                            (SELECT cl_from FROM categorylinks WHERE cl_to = \"Config_file\")
                                                        )
                                                    )
                                                )
                                            );");

    // Invalid query or database
    if (is_bool($q_categories) || mysqli_num_rows($q_categories) == 0)
    {
        return;
    }

    // Setting name to setting category
    $a_settings = array();

    // Store setting name to setting category links
    while ($row = mysqli_fetch_object($q_categories))
    {
        $a_settings[$row->setting] = $row->category;
    }

    // Subcategories to category
    $q_subcategories = mysqli_query($db_wiki, "SELECT category_page.page_title AS category, page.page_title AS subcategory
                                     FROM page
                                     JOIN categorylinks cl_subcategory 
                                         ON cl_subcategory.cl_from = page.page_id
                                     JOIN page category_page 
                                         ON category_page.page_title = cl_subcategory.cl_to
                                     JOIN categorylinks cl_category 
                                         ON cl_category.cl_from = category_page.page_id
                                     WHERE page.page_title NOT LIKE \"%(Config)%\"
                                     AND cl_category.cl_to = \"Config_file\";");

    // Invalid query or database
    if (is_bool($q_subcategories) || mysqli_num_rows($q_subcategories) == 0)
    {
        return;
    }

    // Subcategory to category
    $a_subcategories = array();

    // Store subcategory to category links
    while ($row = mysqli_fetch_object($q_subcategories))
    {
        $a_subcategories[$row->subcategory] = $row->category;
    }

    // Wiki page to setting
    $q_settings = mysqli_query($db_wiki, "SELECT cl_from AS wiki, replace(replace(replace(replace(cl_to, \"_(Config)\", \"\"), \"_\", \" \"), \": On\", \": true\"), \": Off\", \": false\") AS setting, cl_timestamp AS `timestamp`
                                          FROM categorylinks
                                          WHERE cl_to LIKE '%(Config)%' AND cl_to LIKE '%:%' 
                                          ORDER BY cl_from;");

    // Invalid query or database
    if (is_bool($q_settings) || mysqli_num_rows($q_settings) == 0)
    {
        return;
    }

    // Wiki page to game id
    $q_game = mysqli_query($db_compat, "SELECT wiki, gid 
                                        FROM game_list
                                        LEFT JOIN game_id ON game_list.`key` = game_id.`key`
                                        WHERE wiki IS NOT NULL
                                        ORDER BY wiki ASC ");

    // Invalid query or database
    if (is_bool($q_game) || mysqli_num_rows($q_game) == 0)
    {
        return;
    }

    // Wiki page to array of game ids
    $a_wiki_to_gid = array();
    $a_gid_to_wiki = array();
    // Game id to array of settings
    $a_games = array();
    // Wiki id to timestamp
    $a_timestamp = array();

    // Store wiki page to game id links
    while ($row = mysqli_fetch_object($q_game))
    {
        $a_wiki_to_gid[$row->wiki][] = $row->gid;
        $a_gid_to_wiki[$row->gid] = $row->wiki;
    }

    while ($row = mysqli_fetch_object($q_settings))
    {
        // Skip wiki pages that have settings but are not linked to compat db
        if (!array_key_exists($row->wiki, $a_wiki_to_gid))
            continue;

        $gid_list = $a_wiki_to_gid[$row->wiki];

        foreach ($gid_list as $gid)
        {
            // Skip settings without a category link (Debug)
            if (!array_key_exists($row->setting, $a_settings))
                continue;

            // Skip malformed setting values
            if (!preg_match("/[^A-Za-z0-9 ()\-_]/", $row->setting))
                continue;

            $category = $a_settings[$row->setting];

            // Skip network settings as netplay requires manual user setup
            if ($category === "Net")
                continue;

            // Update last update timestamp for the current game
            if (!array_key_exists($row->wiki, $a_timestamp) || $a_timestamp[$row->wiki] < $row->timestamp)
            {
                $a_timestamp[$row->wiki] = $row->timestamp;
            }

            // Subcategories (only one level of depth supported)
            if (array_key_exists($category, $a_subcategories))
            {
                $subcategory = $category;
                $category = $a_subcategories[$subcategory];

                // Initialise subcategory array as we're going to use it
                if (!isset($a_games[$gid][$category][$subcategory]) || !is_array($a_games[$gid][$category][$subcategory]))
                    $a_games[$gid][$category][$subcategory] = array();

                $a_games[$gid][$category][$subcategory][] = $row->setting;
                continue;
            }

            // Unfold this setting as the UI dropdown changes two different config.yml settings
            if (str_starts_with($row->setting, "ZCULL accuracy"))
            {
                $accurate_stats = str_ends_with($row->setting, "Precise") ? "true" : "false";
                $relaxed_sync   = str_ends_with($row->setting, "Relaxed") ? "true" : "false";

                $a_games[$gid][$category][] = "Accurate ZCull stats: {$accurate_stats}";
                $a_games[$gid][$category][] = "Relaxed ZCull Sync: {$relaxed_sync}";
                continue;
            }

            // This setting is a list in the configuration file
            if (str_starts_with($row->setting, "Firmware libraries"))
            {
                $lib = explode(': ', $row->setting);

                // Malformed category
                if (count($lib) != 2 || !str_ends_with($lib[1], ".sprx"))
                    continue;

                $lib = $lib[1];
                $setting = "Libraries Control";

                // Initialise firmware libraries array as we're going to use it
                if (!isset($a_games[$gid][$category][$setting]) || !is_array($a_games[$gid][$category][$setting]))
                    $a_games[$gid][$category][$setting] = array();

                $a_games[$gid][$category][$setting][] = sprintf("- %s:lle", $lib);
                continue;
            }

            // Normalise AF frontend values to their config value setting
            if (str_starts_with($row->setting, "Anisotropic Filter Override"))
            {
                $row->setting = "Anisotropic Filter Override: " . (int) preg_replace("/\D+/", "", $row->setting);
            }
            
            $a_games[$gid][$category][] = $row->setting;
        }
    }

    $api_result = array();

    foreach ($a_games as $gid => $settings)
    {
        $yaml = yaml_emit($settings);

        // Remove leading dashes that have a space in front
        $yaml = str_replace("\n  - ", "\n    ", $yaml);
        $yaml = str_replace("\n- ", "\n  ", $yaml);
        // Remove quotes
        $yaml = str_replace("'", "", $yaml);
        // Remove beginning and ending yaml delimiters
        $yaml = str_replace("---\n", "", $yaml);
        $yaml = str_replace("\n...\n", "", $yaml);
        // Remove category default array key
        $yaml = preg_replace('/\d+: /', '', $yaml);

        $api_result[$gid] = $yaml;
    }

    // Add to database
    $db = get_database("compat");

    // Get the timestamps from all game configs currently on database
    $q_config = mysqli_query($db, "SELECT `game_id`, `timestamp`
                                   FROM `rpcs3_compatibility`.`game_settings`; ");

    // This should be unreachable unless the database structure is damaged
    if (is_bool($q_config))
        return;

    // Results
    $a_config = array();
    if (mysqli_num_rows($q_config) !== 0)
    {
        while ($row = mysqli_fetch_object($q_config))
        {
            // This should be unreachable unless the database structure is damaged
            if (!property_exists($row, "game_id") ||
                !property_exists($row, "timestamp"))
            {
                continue;
            }

            $a_config[$row->game_id] = $row->timestamp;
        }
    }

    foreach ($api_result as $gid => $config)
    {
        if (!is_string($config))
            continue;
        
        $wiki_id   = $a_gid_to_wiki[$gid];
        $timestamp = $a_timestamp[$wiki_id];

        $db_game_id   = mysqli_real_escape_string($db, $gid);
        $db_wiki_id   = mysqli_real_escape_string($db, $wiki_id);
        $db_timestamp = mysqli_real_escape_string($db, $timestamp);
        $db_config    = mysqli_real_escape_string($db, $config);

        // No existing config found, insert new config
        if (!isset($a_config[$gid]))
        {
            mysqli_query($db, "INSERT INTO `rpcs3_compatibility`.`game_settings`
                               (`game_id`,
                                `wiki_id`,
                                `timestamp`,
                                `config`)
                               VALUES ('{$db_game_id}',
                                       '{$db_wiki_id}',
                                       '{$db_timestamp}',
                                       '{$db_config}'); ");     
        }
        // Existing config found with a different timestamp, update it
        else if ($db_timestamp !== $a_config[$gid])
        {
            mysqli_query($db, "UPDATE `rpcs3_compatibility`.`game_settings`
                               SET `timestamp` = '{$db_timestamp}',
                                   `config`    = '{$db_config}'
                               WHERE `game_id` = '{$db_game_id}'
                                 AND `wiki_id` = '{$db_wiki_id}'; ");
        }
        
    }

    mysqli_close($db);
}