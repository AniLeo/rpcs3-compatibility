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

// Productcode info: PSDevWiki (http://www.psdevwiki.com/ps3/Productcode)

// Check the first character of the Game ID to obtain media
function getGameMedia($gid) {
	global $a_media;
	global $a_css;
	
	if     (substr($gid, 0, 1) == "N")  { return "<img alt=\"Digital\" src=\"{$a_media["PSN"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PSN Retail
	elseif (substr($gid, 0, 1) == "B")  { return "<img alt=\"Blu-Ray\" src=\"{$a_media["BLR"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PS3 Blu-Ray
	elseif (substr($gid, 0, 1) == "X")  { return "<img alt=\"Blu-Ray\" src=\"{$a_media["BLR"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PS3 Blu-Ray + Extras
	else                                { return ""; }
}

// Check the third character of the Game ID to obtain region
function getGameRegion($gid) {
	global $a_flags;
	
	// Icons from: https://www.iconfinder.com/iconsets/famfamfam_flag_icons
	if     (substr($gid, 2, 1) == "A")  { return $a_flags["CN"]; }   // Asia 
	elseif (substr($gid, 2, 1) == "E")  { return $a_flags["EU"]; }   // Europe
	elseif (substr($gid, 2, 1) == "H")  { return $a_flags["CN"]; }   // Southern Asia
	elseif (substr($gid, 2, 1) == "K")  { return $a_flags["HK"]; }   // Hong Kong
	elseif (substr($gid, 2, 1) == "J")  { return $a_flags["JP"]; }   // Japan
	elseif (substr($gid, 2, 1) == "U")  { return $a_flags["US"]; }   // USA
	// We don't care about those two as they won't be listed
	elseif (substr($gid, 2, 1) == "I")  { return ""; }               // Internal (Sony)
	elseif (substr($gid, 2, 1) == "X")  { return ""; }               // Firmware/SDK Sample
	// One shouldn't be able to reach here, unless there are missing regions
	else                                { return ""; }
}

// Check the fourth character of the Game ID to obtain type
function getGameType($gid) {
	// Physical
	if (substr($gid, 0, 1) == "B" || substr($gid, 0, 1) == "X") {
		if     (substr($gid, 3, 1) == "D")  { return "Demo"; }             // Demo
		elseif (substr($gid, 3, 1) == "M")  { return "Malayan Release"; }  // Malayan Release
		elseif (substr($gid, 3, 1) == "S")  { return "Retail Release"; }   // Retail Release
		// We don't care about the other types as they won't be listed
		else                                { return ""; }
	}
	// Digital
	if (substr($gid, 0, 1) == "N") {
		if     (substr($gid, 3, 1) == "A")  { return "First Party PS3"; }  // First Party PS3 (Demo/Retail)
		elseif (substr($gid, 3, 1) == "B")  { return "Licensed PS3"; }     // Licensed PS3 (Demo/Retail)
		elseif (substr($gid, 3, 1) == "C")  { return "First Party PS2"; }  // First Party PS2 Classic (Demo/Retail)
		elseif (substr($gid, 3, 1) == "D")  { return "Licensed PS3"; }     // Licensed PS3 (Demo/Retail)
		// We don't care about the other types as they won't be listed
		else                                { return ""; }
	}
}

// Get the thread link and return it as a hyperlink wrapped around the provided text
function getThread($text, $tid) {
	global $c_forum;
	
	if ($tid != "None") { return "<a href=\"{$c_forum}{$tid}\">{$text}</a>"; } 
	else                { return $text; }
}

// Get the GitHub commit link and return it as a hyperlink
function getCommit($cid) {
	global $c_github;
	
	if ($cid != "0")    { return "<a href=\"{$c_github}{$cid}\">".mb_substr($cid, 0, 8)."</a>"; } 
	else                { return "<i>Unknown</i>"; }
}

// Get the status color and return it as font color wrapped around the status name
function getColoredStatus($sn) {
	global $a_title;
	global $a_color;
	
	// This should be unreachable unless someone wrongly inputs status in the database
	if ($sn == "") { return "<i>Invalid</i>"; }
	
	foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $i) { 
		if ($sn == $a_title[$i]) { return "<font color=\"$a_color[$i]\">$a_title[$i]</font>"; }
	}
}

// Validate searchbox user input
function isValid($str) {
    return !preg_match('/[^A-Za-z0-9.#& \\-]/', $str);
}

?>
