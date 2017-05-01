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

if (!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");


// Define mime-type (png image)
header("Content-Type: image/png"); 

// Get width and height args
if ( !isset($_GET['width']) ) { $width = 1100; } else { $width = $_GET['width']; }
if ( !isset($_GET['height']) || $_GET['height'] < 30 ) { $height = 30; } else { $height = $_GET['height']; }

// Get games count per status
$count = countGames("", 0);

// Prepare true color for given width, height
$im = imagecreatetruecolor($width, $height);  

$c_bg = imagecolorallocatealpha($im, 255, 255, 255, 0.0);


// If count = 0 then database connection failed. Return "empty" image.
if ($count == 0) {

	// Main rectangle
	imagefilledrectangle($im, 0, 0, $width, $height, $c_bg);  
	
} else {
	
	// Convert colors from HEX to RGB
	foreach (range(min(array_keys($a_color)), max(array_keys($a_color))) as $s) { 
		$rgb = colorHEXtoRGB($a_color[$s]);
		$scolor[$s] = imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], 0.0);
	} 

	// Set image misc colors
	$c_white = imagecolorallocatealpha($im, 236, 240, 241, 0.0);
	$c_black = imagecolorallocatealpha($im, 0, 0, 0, 0.0);

	// Calculate percentage with database information fetched beforehand
	$p_playable = round(($count[1]/$count[0])*100, 2, PHP_ROUND_HALF_UP);
	$p_ingame = round(($count[2]/$count[0])*100, 2, PHP_ROUND_HALF_UP);
	$p_intro = round(($count[3]/$count[0])*100, 2, PHP_ROUND_HALF_UP);
	$p_loadable = round(($count[4]/$count[0])*100, 2, PHP_ROUND_HALF_UP);
	$p_nothing = round(($count[5]/$count[0])*100, 2, PHP_ROUND_HALF_UP);

	// Calculate width per status (add the previous status width as well) 
	// I don't think it really matters if we round here...
	$val_playable = round( (($p_playable * $width-3) / 100), 2, PHP_ROUND_HALF_UP);  
	$val_ingame = round( ($val_playable + (($p_ingame * $width-3) / 100)), 2, PHP_ROUND_HALF_UP); 
	$val_intro = round( ($val_ingame + (($p_intro * $width-3) / 100)), 2, PHP_ROUND_HALF_UP); 
	$val_loadable = round( ($val_intro + (($p_loadable * $width-3) / 100)), 2, PHP_ROUND_HALF_UP); 
	$val_nothing = round( ($val_loadable + (($p_nothing * $width-3) / 100)), 2, PHP_ROUND_HALF_UP); 

	// Main rectangle
	imagefilledrectangle($im, 0, 0, $width, $height, $c_bg);  

	// Progress bar inside rectangles
	imagefilledrectangle($im, 0, 1, $val_playable-1, $height-2, $scolor[1]);  
	imagefilledrectangle($im, $val_playable, 1, $val_ingame-1, $height-2, $scolor[2]);  
	imagefilledrectangle($im, $val_ingame, 1, $val_intro-1, $height-2, $scolor[3]);  
	imagefilledrectangle($im, $val_intro, 1, $val_loadable-1, $height-2, $scolor[4]);  
	imagefilledrectangle($im, $val_loadable, 1, $val_nothing, $height-2, $scolor[5]);

	// Strings with borders for text
	imagestringstroketext($im, 2, 6, 2, $c_white, $c_black, "Playable", 1);
	imagestringstroketext($im, 2, 6, 14, $c_white, $c_black, "{$p_playable}%", 1);

	imagestringstroketext($im, 2, $val_playable+4, 2, $c_white, $c_black, "Ingame", 1);
	imagestringstroketext($im, 2, $val_playable+4, 14, $c_white, $c_black, "{$p_ingame}%", 1);

	imagestringstroketext($im, 2, $val_ingame+4, 2, $c_white, $c_black, "Intro", 1);
	imagestringstroketext($im, 2, $val_ingame+4, 14, $c_white, $c_black, "{$p_intro}%", 1);

	imagestringstroketext($im, 2, $val_intro+4, 2, $c_white, $c_black, "Loadable", 1);
	imagestringstroketext($im, 2, $val_intro+4, 14, $c_white, $c_black, "{$p_loadable}%", 1);

	imagestringstroketext($im, 2, $val_loadable+4, 2, $c_white, $c_black, "Nothing", 1);
	imagestringstroketext($im, 2, $val_loadable+4, 14, $c_white, $c_black, "{$p_nothing}%", 1);
	
}

// Render image as PNG 
imagepng($im);  

// Destroy used resources to free up memory 
imagedestroy($im);  

?>