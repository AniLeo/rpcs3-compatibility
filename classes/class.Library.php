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
if (!@include_once(__DIR__."/../functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");
if (!@include_once(__DIR__."/../objects/Game.php")) throw new Exception("Compat: Game.php is missing. Failed to include Game.php");


class Library {


public static function getResultsPerPage() : string
{
	return resultsPerPage(combinedSearch(false, false, false, false, true, true, false, false), "l&");
}


public static function getTestedContents() : void
{
	global $get, $pages, $currentPage, $a_db, $c_github, $a_games;

	if (!$a_db) {
		echo "<p class=\"compat-tx1-criteria\">There are no games present in the selected categories.</p>";
		return;
	}

	$start = $get['r']*$currentPage-$get['r']+1;
	$end = ($pages === $currentPage) ? max(array_keys($a_db)) : $get['r']*$currentPage;

	echo "<div class=\"compat-table-outside\">
	<div class=\"compat-table-inside\">";

	echo "<div class=\"compat-table-header\">
		<div class=\"compat-table-cell compat-table-cell-gameid\">ID</div>
		<div class=\"compat-table-cell\">Title</div>
		<div class=\"compat-table-cell compat-table-cell-updated\">Last Tested</div>
	</div>";

	foreach (range($start, $end) as $i) {
		$gameID = key($a_db[$i]);
		$gameTitle = $a_db[$i][$gameID];

		if (!array_key_exists($gameID, $a_games)) {
			echo "
			<div class=\"compat-table-row\">
				<div class=\"compat-table-cell compat-table-cell-gameid\" style='color:#e74c3c;'>"
				.getGameRegion($gameID, true, 'l&'.combinedSearch(false, false, false, false, false, true, false, false))."
				<a style='color:#e74c3c;' href='http://www.gametdb.com/PS3/{$gameID}' target='_blank'>{$gameID}</a>
				</div>
				<div class=\"compat-table-cell\"  style='color:#e74c3c'>"
				.getGameMediaIcon($gameID, true, 'l&'.combinedSearch(false, false, false, false, true, false, false, false))."
				<a style='color:#e74c3c;' href='http://www.gametdb.com/PS3/{$gameID}' target='_blank'>{$gameTitle}</a>
				</div>
				<div class=\"compat-table-cell compat-table-cell-updated\" style='color:#e74c3c;'>Untested</div>
			</div>";
		} else {

			// If the game hasn't been tested for more than 6 months color = yellow, otherwise color = green
			$color = (time() - strtotime($a_games[$gameID]['last_update']) > 60*60*24*30*6) ? '#f39c12' : '#27ae60';

			echo "
			<div class=\"compat-table-row\">
				<div class=\"compat-table-cell compat-table-cell-gameid\" style='color:{$color};'>"
				.getGameRegion($gameID, true, 'l&'.combinedSearch(false, false, false, false, false, true, false, false))."
				".getThread($gameID, $a_games[$gameID]['thread'])."
				</div>
				<div class=\"compat-table-cell\" style='color:{$color}'>"
				.getGameMediaIcon($gameID, true, 'l&'.combinedSearch(false, false, false, false, true, false, false, false))."
				".getThread($a_games[$gameID]['title'], $a_games[$gameID]['thread'])."
				</div>
				<div class=\"compat-table-cell compat-table-cell-updated\"style='color:{$color};'>
				{$a_games[$gameID]['last_update']}&nbsp;&nbsp;&nbsp;";
				echo is_null($a_games[$gameID]['pr']) ? "(<i>Unknown</i>)" : "(<a href='{$c_github}/pull/{$a_games[$gameID]['pr']}'>#{$a_games[$gameID]['pr']}</a>)";

				echo "</div>
			</div>";
		}
	}

	echo "</div>
	</div>";
}


public static function getPagesCounter() : string
{
	global $pages, $currentPage;

	$extra = combinedSearch(true, false, false, false, true, true, false, false);

	return getPagesCounter($pages, $currentPage, "l&{$extra}");
}


public static function getGameCount(string $type) : int
{
	// Only allow access to all, tested and untested files
	if (!($type == 'all' || $type == 'tested' || $type == 'untested'))
		return 0;

	// Open handle to requested file
	$handle = fopen(__DIR__."/../cache/{$type}.txt", 'r');

	// Opening the requested file failed
	if (!$handle)
		return 0;

	// Assign file content to a temporary variable before returning, so we can close the handle first
	$count = (int) fgets($handle);

	// Close opened handle before returning
	fclose($handle);

	return $count;
}

} // End of Class
