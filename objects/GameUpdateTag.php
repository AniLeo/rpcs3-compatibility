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
if (!@include_once(__DIR__."/GameUpdatePackage.php")) throw new Exception("Compat: Failed to include objects/GameUpdatePackage.php");


class GameUpdateTag
{
    public  string $tag_id;
    public  string $popup;
    public  string $signoff;
    public ?int    $popup_delay;
    public ?string $min_system_ver;
    /** @var array<GameUpdatePackage> $packages **/
    public  array  $packages;

    function __construct( string  $tag_id,
                          string  $popup,
                          string  $signoff,
                         ?int     $popup_delay,
                         ?string  $min_system_ver)
    {
        $this->tag_id         = $tag_id;
        $this->popup          = $popup;
        $this->signoff        = $signoff;
        $this->popup_delay    = $popup_delay;
        $this->min_system_ver = $min_system_ver;
        $this->packages       = array();
    }

    /**
    * @param array<GameUpdateTag> $tags
    */
    public static function import_update_titles(array &$tags, mysqli $db) : void
    {
        if (empty($tags))
            return;

        $a_tag_ids = array();
        
        foreach ($tags as $tag)
            $a_tag_ids[$tag->tag_id] = true;

        $cmd_in = sql_string_in($db, array_keys($a_tag_ids));
        $q_titles = mysqli_query($db, "SELECT `tag`,
                                              `package_version`,
                                              `paramsfo_type`,
                                              `paramsfo_title`
                                       FROM `game_update_paramsfo`
                                       WHERE `tag` IN ({$cmd_in}); ");

        if (is_bool($q_titles))
            return;
 
        $a_titles = array();

        while ($row = mysqli_fetch_object($q_titles))
        {
            // This should be unreachable unless the database structure is damaged
            if (!property_exists($row, "tag") ||
                !property_exists($row, "package_version") ||
                !property_exists($row, "paramsfo_type") ||
                !property_exists($row, "paramsfo_title"))
            {
                return;
            }
            
            $a_titles[$row->tag][$row->package_version][] = new GameUpdateTitle($row->paramsfo_type,
                                                                                $row->paramsfo_title);
        }

        foreach ($tags as $tag)
        {
            foreach ($tag->packages as $package)
            {
                if (isset($a_titles[$tag->tag_id][$package->version]))
                {
                    $package->titles = $a_titles[$tag->tag_id][$package->version];
                }
            }
        }
    }

    /**
    * @param array<GameUpdateTag> $tags
    */
    public static function import_update_changelogs(array &$tags, mysqli $db) : void
    {
        if (empty($tags))
            return;

        $a_tag_ids = array();
        foreach ($tags as $tag)
            $a_tag_ids[$tag->tag_id] = true;

        $cmd_in = sql_string_in($db, array_keys($a_tag_ids));
        $q_changelogs = mysqli_query($db, "SELECT `tag`,
                                                  `package_version`,
                                                  `paramhip_type`,
                                                  `paramhip_content`
                                           FROM `game_update_paramhip`
                                           WHERE `tag` IN ({$cmd_in})
                                             AND `paramhip_type` = 'paramhip'; ");

        if (is_bool($q_changelogs))
            return;
 
        $a_changelogs = array();

        while ($row = mysqli_fetch_object($q_changelogs))
        {
            // This should be unreachable unless the database structure is damaged
            if (!property_exists($row, "tag") ||
                !property_exists($row, "package_version") ||
                !property_exists($row, "paramhip_type") ||
                !property_exists($row, "paramhip_content"))
            {
                return;
            }

            $a_changelogs[$row->tag][$row->package_version][] = new GameUpdateChangelog($row->paramhip_type,
                                                                                        $row->paramhip_content);
        }

        foreach ($tags as $tag)
        {
            foreach ($tag->packages as $package)
            {
                if (isset($a_changelogs[$tag->tag_id][$package->version]))
                {
                    $package->changelogs = $a_changelogs[$tag->tag_id][$package->version];
                }
            }
        }
    }

    /**
    * @param array<GameUpdateTag> $tags
    */
    public static function import_update_packages(array &$tags, mysqli $db) : void
    {
        if (empty($tags))
            return;

        $a_tag_ids = array();
        foreach ($tags as $tag)
            $a_tag_ids[$tag->tag_id] = true;

        $cmd_in = sql_string_in($db, array_keys($a_tag_ids));
        $q_packages = mysqli_query($db, "SELECT `tag`, `version`, `size`, `sha1sum`, `ps3_system_ver`, `drm_type`
                                         FROM `game_update_package`
                                         WHERE `tag` IN ({$cmd_in}); ");

        if (is_bool($q_packages))
            return;

        $a_packages = array();

        while ($row = mysqli_fetch_object($q_packages))
        {
            // This should be unreachable unless the database structure is damaged
            if (!property_exists($row, "tag") ||
                !property_exists($row, "version") ||
                !property_exists($row, "size") ||
                !property_exists($row, "sha1sum") ||
                !property_exists($row, "ps3_system_ver") ||
                !property_exists($row, "drm_type"))
            {
                return;
            }

            $a_packages[$row->tag][] = new GameUpdatePackage($row->version,
                                                             $row->size,
                                                             $row->sha1sum,
                                                             $row->ps3_system_ver,
                                                             $row->drm_type);
        }

        foreach ($tags as $tag)
        {
            $tag->packages = $a_packages[$tag->tag_id];
        }
    }
}
