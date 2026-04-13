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


function cache_initials() : void
{
    $db = get_database("compat");

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
            $q_check = mysqli_query($db, "SELECT `initials` 
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

function cache_game_count() : void
{
    // count_game_entry_all
    file_put_contents(__DIR__."/../cache/count_game_entry_all.txt", (string) count_game_entry_all(true));

    // count_game_id_all
    file_put_contents(__DIR__."/../cache/count_game_id_all.txt", (string) count_game_id_all(true));
}