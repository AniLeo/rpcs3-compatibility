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


/**
  * getGameMedia
  *
  * Obtains Game Media by checking Game ID's first character. 
  * Returns Game Media as an image with MEDIA_ICON CSS class,
  *  empty string if Game Media is invalid.
  *
  * @param string $gid GameID: 9 character ID that identifies a game
  *
  * @return string
  */
function getGameMedia($gid) {
	global $a_media, $a_css;
	
	if     (substr($gid, 0, 1) == "N")  { return "<img alt=\"Digital\" src=\"{$a_media["PSN"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PSN Retail
	elseif (substr($gid, 0, 1) == "B")  { return "<img alt=\"Blu-Ray\" src=\"{$a_media["BLR"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PS3 Blu-Ray
	elseif (substr($gid, 0, 1) == "X")  { return "<img alt=\"Blu-Ray\" src=\"{$a_media["BLR"]}\" class=\"{$a_css["MEDIA_ICON"]}\">"; }  // PS3 Blu-Ray + Extras
	else                                { return ""; }
}


/**
  * getGameRegion
  *
  * Obtains Game Region by checking Game ID's third character. 
  * Returns Game Region as a clickable or non-clickable flag image,
  *  empty string if Game Region is invalid/unknown.
  * Icon flags from https://www.iconfinder.com/iconsets/famfamfam_flag_icons
  *
  * @param string $gid GameID: 9 character ID that identifies a game
  * @param bool   $url Whether to return Game Region as a clickable(1) or non-clickable(0) flag
  *
  * @return string
  */
function getGameRegion($gid, $url) {
	global $a_flags;
	
	$l = substr($gid, 2, 1);
	
	// If it's not a valid / known region then we return an empty string
	if (!array_key_exists($l, $a_flags)) {
		return "";
	}
	
	if ($url) {
		// Returns clickable flag for region (flag) search
		return "<a href=\"?f=".strtolower($l)."\"><img src=\"{$a_flags[$l]}\"></a>";
	} else {
		// Returns unclickable flag
		return "<img src=\"$a_flags[$l]\">";
	}
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
		elseif (substr($gid, 3, 1) == "D")  { return "Licensed PS2"; }     // Licensed PS2 (Demo/Retail)
		// We don't care about the other types as they won't be listed
		else                                { return ""; }
	}
}


/**
  * getThread
  *
  * Obtains thread URL for a game by adding thread ID to the forum showthread URL prefix
  * Returns provided text wrapped around a hyperlink for the thread
  *
  * @param string $text
  * @param string $tid ThreadID
  *
  * @return string
  */
function getThread($text, $tid) {
	global $c_forum;
	
	// The thread should never be 0. All games MUST have a thread.
	if ($tid != "0") { return "<a href=\"{$c_forum}{$tid}\">{$text}</a>"; } 
	else             { return $text; }
}


/**
  * getCommit
  *
  * Obtains commit URL for a commit by adding commit ID to the github commit URL prefix
  * Returns commit ID wrapped around a hyperlink with BUILD CSS class
  * for the commit or "Unknown" with NOBUILD CSS class if the commit ID is 0 (Unknown)
  *
  * @param string $cid CommitID
  *
  * @return string
  */
function getCommit($cid) {
	global $c_github, $a_css, $c_unkcommit;
	
	// If the commit is unknown we input 0.
	if ($cid != "0") { return "<a class='{$a_css["BUILD"]}' href=\"{$c_github}{$cid}\">".mb_substr($cid, 0, 8)."</a>"; } 
	else             { return "<div class='{$a_css["NOBUILD"]}' style='background: #{$c_unkcommit};'>Unknown</div>"; }
}


/**
  * getColoredStatus
  *
  * Obtains colored status using the color from configuration a_colors array
  * Returns status wrapped around a background colored div with STATUS CSS class 
  * or italic "Invalid" if someone messes up inputting statuses.
  *
  * @param string $sn StatusName
  *
  * @return string
  */
function getColoredStatus($sn) {
	global $a_title, $a_color, $a_css;
	
	foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $i) { 
		if ($sn == $a_title[$i]) { return "<div class='{$a_css["STATUS"]}' style='background: #{$a_color[$i]};'>{$a_title[$i]}</div>"; }
	}
	
	// This should be unreachable unless someone wrongly inputs status in the database
	return "<i>Invalid</i>";
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
function isValid($str) {
    return !preg_match("/[^A-Za-z0-9.#&~ \/\'-]/", $str);
}


/**
  * highlightBold
  *
  * Returns provided string wrapped in bold html tags
  *
  * @param string $str Some text
  *
  * @return string
  */
function highlightBold($str) {
	return "<b>$str</b>";
}

?>
