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
    $db = get_database("compat");

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

    mysqli_close($db);
}