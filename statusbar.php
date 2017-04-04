<?php
// Draw image with compatibility progress bar
// https://github.com/AniLeo
// 2017.03.27

// TODO: Retrieve colors from config and convert from hex to rgba

// Adapted from: http://www.johnciacia.com/2010/01/04/using-php-and-gd-to-add-border-to-text/ to imagestring by me
function imagestringstroketext(&$image, $size, $x, $y, &$textcolor, &$strokecolor, $text, $px) {
    for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
        for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
            $bg = imagestring($image, $size, $c1, $c2, $text, $strokecolor);
   return imagestring($image, $size, $x, $y, $text, $textcolor);
}

// File containing database connection information
require "lib/compat/config.php";

// Get width and height args
if ( !isset($_GET['width']) ) { $width = 1100; } else { $width = $_GET['width']; }
if ( !isset($_GET['height']) || $_GET['height'] < 30 ) { $height = 30; } else { $height = $_GET['height']; }

// Connect to database
$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
mysqli_set_charset($db, 'utf8');

// Fetch quantity of games per status and add to an array
foreach (range((min(array_keys($a_title))+1), max(array_keys($a_title))) as $s) { 
	$count[$s] = mysqli_fetch_object(mysqli_query($db, "SELECT count(*) AS c FROM ".db_table." WHERE status = {$s}"))->c;
	$count[0] += $count[$s];
} 

// Close database connection
mysqli_close($db);

// Prepare true color for given width, height
$im = imagecreatetruecolor($width, $height);  

// Set image global background color 
$c_bg = imagecolorallocatealpha($im, 149, 165, 166, 1.0);

// Colors for statuses
$c_playable = imagecolorallocatealpha($im, 39, 174, 96, 0.0); 
$c_ingame = imagecolorallocatealpha($im, 245, 171, 53, 0.0);
$c_intro = imagecolorallocatealpha($im, 230, 126, 34, 0.0);
$c_loadable = imagecolorallocatealpha($im, 231, 76, 60, 0.0);
$c_nothing = imagecolorallocatealpha($im, 52, 73, 94, 0.0);
$c_white = imagecolorallocatealpha($im, 236, 240, 241, 1.0);
$c_black = imagecolorallocatealpha($im, 0, 0, 0, 0.5);

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
imagefilledrectangle($im, 1, 1, $val_playable-3, $height-2, $c_playable);  
imagefilledrectangle($im, $val_playable, 1, $val_ingame-2, $height-2, $c_ingame);  
imagefilledrectangle($im, $val_ingame, 1, $val_intro-2, $height-2, $c_intro);  
imagefilledrectangle($im, $val_intro, 1, $val_loadable-2, $height-2, $c_loadable);  
imagefilledrectangle($im, $val_loadable, 1, $val_nothing-1, $height-2, $c_nothing);

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

// Define mime-type (png image)
header("Content-Type: image/png"); 

// Render image as PNG 
imagepng($im);  

// Destroy used resources to free up memory 
imagedestroy($im);  
?>
