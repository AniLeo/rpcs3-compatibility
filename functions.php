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

// Check the third character of the Game ID to obtain the region
// Icons from: https://www.iconfinder.com/iconsets/famfamfam_flag_icons
// Region info: PSDevWiki (http://www.psdevwiki.com/ps3/Productcode)
function getRegion($gid){
	if (substr($gid, 2, 1) == "A")      { return "<img src='/img/icons/flags/CN.png'>"; }   // Asia 
	elseif (substr($gid, 2, 1) == "E")  { return "<img src='/img/icons/flags/EU.png'>"; }   // Europe
	elseif (substr($gid, 2, 1) == "H")  { return "<img src='/img/icons/flags/CN.png'>"; }   // Southern Asia
	elseif (substr($gid, 2, 1) == "K")  { return "<img src='/img/icons/flags/HK.png'>"; }   // Hong Kong
	elseif (substr($gid, 2, 1) == "J")  { return "<img src='/img/icons/flags/JP.png'>"; }   // Japan
	elseif (substr($gid, 2, 1) == "U")  { return "<img src='/img/icons/flags/US.png'>"; }   // USA
	// We don't care about those two as they won't be listed
	elseif (substr($gid, 2, 1) == "I")  { return ""; }                                      // Internal (Sony)
	elseif (substr($gid, 2, 1) == "X")  { return ""; }                                      // Firmware/SDK Sample
	// One shouldn't be able to reach here, unless there are missing regions
	else                                { return ""; }
}

// Check the first character of the Game ID to obtain the type of game
function getGameType($gid){
	if (substr($gid, 0, 1) == "N") { return "<img alt='Digital' src='/img/icons/list/psn.png' class='div-compat-fmat'>"; } 
	elseif (substr($gid, 0, 1) == "B") { return "<img alt='Blu-Ray' src='/img/icons/list/rom.png' class='div-compat-fmat'>"; } 
	else { return ""; }
}

// Get the thread link and return it as a hyperlink wrapped around the provided text
function getThread($text, $tid){
	if ($tid != "None") { return '<a href="'.c_forum.$tid.'"><font color ="#002556">'.$text.'</font></a>'; } 
	else { return $text; }
}

// Get the GitHub commit link and return it as a hyperlink
function getCommit($cid){
	if ($cid != "0") { return '<a href="'.c_github.$cid.'">'.mb_substr($cid, 0, 8).'</a>'; } 
	else { return '<i>Unknown</i>'; }
}

// Returns status as colored text ready to be displayed on the list [Requires: Status Name]
function getColoredStatus($sn){
	// TODO: Use a_title and a_colors
    if ($sn == 'Playable'){ return "<font color='#2ecc71'>Playable</font>"; } 
	elseif ($sn == 'Ingame'){ return "<font color='#f1c40f'>Ingame</font>"; } 
	elseif ($sn == 'Intro'){ return "<font color='#f39c12'>Intro</font>"; } 
	elseif ($sn == 'Loadable'){ return "<font color='#e74c3c'>Loadable</font>"; } 
	elseif ($sn == 'Nothing'){ return "<font color='#2c3e50'>Nothing</font>"; } 
	// This should be unreachable unless someone wrongly inputs status in the database
	else { return '<i>Invalid</i>'; }
}

// Validate searchbox user input
function isValid($str) {
    return !preg_match('/[^A-Za-z0-9.#& \\-]/', $str);
}

?>
