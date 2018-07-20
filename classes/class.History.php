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
if (!@include_once(__DIR__."/../objects/HistoryEntry.php")) throw new Exception("Compat: HistoryEntry.php is missing. Failed to include HistoryEntry.php");


class History {


public static function getHistoryDescription() {
	global $get, $a_histdates, $a_currenthist;

	$s_desc = "You're now watching the updates that altered a game's status for RPCS3's Compatibility List ";

	if ($get['h'] == $a_currenthist[0]) {
		$s_desc .= "since <b>{$a_currenthist[1]}</b>.";
	} else {
		$v = $a_histdates[$get['h']];
		$m1 = monthNumberToName($v[0]['m']);
		$m2 = monthNumberToName($v[1]['m']);
		$s_desc .= "from <b>{$m1} {$v[0]['d']}, {$v[0]['y']}</b> to <b>{$m2} {$v[1]['d']}, {$v[1]['y']}</b>.";
	}

	return "<p id='compat-history-description'>{$s_desc}</p>";
}


public static function getHistoryMonths() {
	global $get, $a_histdates, $a_currenthist;

	$s_months = "<strong>Month Selection</strong><br>";
	$spacer = "&nbsp;&#8226;&nbsp;";

	$watchdog = '';

	foreach($a_histdates as $k => $v) {

		$month = monthNumberToName(substr($k, -2));
		$year  = substr($k, 0, 4);

		if ($watchdog != $year) {
			if ($watchdog != '') { $s_months .= "<br>"; }
			$s_months .= "<strong>{$year}:</strong>&nbsp;";
			$watchdog = $year;
		}

		$m = "<a href=\"?h={$k}\">{$month}</a>";

		$s_months .= ($get['h'] == $k) ? highlightText($m) : $m;
		if ($month != "December" && $v != end($a_histdates)) { $s_months .= $spacer; }
	}

	$s_months .= "<br><strong>Current:</strong>&nbsp;";

	$month = monthNumberToName(substr($a_currenthist[0], -2));
	$year = substr($a_currenthist[0], 0, 4);

	$m = "<a href=\"?h\">{$month} {$year}</a>";

	$s_months .= ($get['h'] == $a_currenthist[0]) ? highlightText($m) : $m;

	return "<p id='compat-history-months'>{$s_months}</p>";
}


public static function getHistoryOptions() {
	global $get, $a_currenthist;

	if ($get['h'] != $a_currenthist[0]) { $h = "={$get['h']}"; }
	else                                { $h = ""; }

	$o1 = "<a href=\"?h{$h}\">Show all entries</a>";
	$o2 = "<a href=\"?h{$h}&m=c\">Show only previously existent entries</a>";
	$o3 = "<a href=\"?h{$h}&m=n\">Show only new entries</a>";
	$spacer = "&nbsp;&#8226;&nbsp;";

	$s_options = ($get['m'] == "") ? highlightText($o1) : $o1;
	$s_options .= " <a href=\"?h{$h}&rss\">(RSS)</a>{$spacer}";

	$s_options .= ($get['m'] == "c") ? highlightText($o2) : $o2;
	$s_options .= " <a href=\"?h{$h}&m=c&rss\">(RSS)</a>{$spacer}";

	$s_options .= ($get['m'] == "n") ? highlightText($o3) : $o3;
	$s_options .= " <a href=\"?h{$h}&m=n&rss\">(RSS)</a>";

	return "<p id='compat-history-options'>{$s_options}</p>";
}


public static function getTableHeaders($full = true) {
	if ($full) {
		$headers = array(
			'Game Regions' => 0,
			'Game Title' => 0,
			'New Status' => 0,
			'New Date' => 0,
			'Old Status' => 0,
			'Old Date' => 0
		);
	} else {
		$headers = array(
			'Game Regions' => 0,
			'Game Title' => 0,
			'Status' => 0,
			'Date' => 0
		);
	}

	return getTableHeaders($headers, 'h');
}


public static function getTableContent($array) {
	$s_content = "";

	$s_content .= "<div class='divTableBody'>";

	foreach ($array as $entry) {
		$s_content .= "<div class='divTableRow'>";

		// Cell 1: Regions
		$cell = "";
		foreach ($entry->IDs as $id) {
			$cell .= getThread(getGameRegion($id[0], false), $id[1]);
			$media = getGameMedia($id[0], false);
		}
		$s_content .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 2: Media and Titles
		$cell = "{$media}{$entry->title}";
		if (!is_null($entry->title2)) {
			$cell .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;({$entry->title2})";
		}
		$s_content .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 3: New Status
		$cell = getColoredStatus($entry->new_status);
		$s_content .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 4: New Date
		$cell = $entry->new_date;
		$s_content .= "<div class=\"divTableCell\">{$cell}</div>";

		// Cell 5: Old Status (If existent)
		if (!is_null($entry->old_status)) {
			$cell = getColoredStatus($entry->old_status);
			$s_content .= "<div class=\"divTableCell\">{$cell}</div>";
		}

		// Cell 6: Old Date (If existent)
		if (!is_null($entry->old_date)) {
			$cell = $entry->old_date;
			$s_content .= "<div class=\"divTableCell\">{$cell}</div>";
		}

		$s_content .= "</div>";
	}

	$s_content .= "</div>";

	return $s_content;
}


public static function getHistoryContent() {
	global $get, $a_existing, $a_new, $error_existing, $error_new;

	// Initialize string
	$s_content = "";

	// Existing entries table
	if ($error_existing != "") {
		$s_content .= "<p class=\"compat-tx1-criteria\">{$error_existing}</p>";
	} elseif (!is_null($a_existing)) {
		$s_content .= "<div class='divTable history-table'>";
		$s_content .= self::getTableHeaders();
		$s_content .= self::getTableContent($a_existing);
		$s_content .= "</div><br>";
	}

	// New entries table
	if ($error_new != "") {
		$s_content .= "<p class=\"compat-tx1-criteria\">{$error_new}</p>";
	} elseif (!is_null($a_new)) {
		$s_content .= "<p class=\"compat-tx1-criteria\"><strong>Newly reported games (includes new regions for existing games)</strong></p>";
		$s_content .= "<div class='divTable history-table'>";
		$s_content .= self::getTableHeaders(false);
		$s_content .= self::getTableContent($a_new);
		$s_content .= "</div><br>";
	}

	return $s_content;
}


// TODO: Refactor RSS
public static function getHistoryRSS(){
	global $c_forum, $get, $a_histdates, $a_currenthist;

	$db = getDatabase();

	if ($get['h'] == $a_currenthist[0]) {
		$dateQuery = " AND new_date >= CAST('{$a_currenthist[2]}' AS DATE) ";
	} else {
		$dateQuery = " AND new_date BETWEEN
		CAST('{$a_histdates[$get['h']][0]['y']}-{$a_histdates[$get['h']][0]['m']}-{$a_histdates[$get['h']][0]['d']}' AS DATE)
		AND CAST('{$a_histdates[$get['h']][1]['y']}-{$a_histdates[$get['h']][1]['m']}-{$a_histdates[$get['h']][1]['d']}' AS DATE) ";
	}

	$rssCmd = "SELECT id, old_status, old_date, new_status, new_date,
	game_list.tid_EU, game_list.tid_US, game_list.tid_JP, game_list.tid_AS, game_list.tid_KR, game_list.tid_HK,
	game_title, alternative_title, game_history.* FROM game_history
	LEFT JOIN game_list ON
	game_history.gid_EU = game_list.gid_EU OR
	game_history.gid_US = game_list.gid_US OR
	game_history.gid_JP = game_list.gid_JP OR
	game_history.gid_AS = game_list.gid_AS OR
	game_history.gid_KR = game_list.gid_KR OR
	game_history.gid_HK = game_list.gid_HK
	WHERE game_title IS NOT NULL ";
	if ($get['m'] == "c") {
		$rssCmd .= " AND old_status IS NOT NULL ";
	} elseif ($get['m'] == "n") {
		$rssCmd .= " AND old_status IS NULL ";
	}
	$rssCmd .= " {$dateQuery}
	ORDER BY new_date DESC, new_status ASC, -old_status DESC, game_title ASC; ";

	$rssQuery = mysqli_query($db, $rssCmd);

	mysqli_close($db);

	if (!$rssQuery) {
		return "An error occurred. Please try again. If the issue persists contact RPCS3 team.";
	}

	$url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	$url = str_replace('&', '&amp;', $url);

		$rssfeed = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
				<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
				<channel>
				<title>RPCS3 Compatibility List History's RSS feed</title>
				<link>https://rpcs3.net/compatibility?h</link>
				<description>For more information about RPCS3 visit https://rpcs3.net</description>
				<language>en-uk</language>
				<atom:link href=\"{$url}\" rel=\"self\" type=\"application/rss+xml\" />";

		while($row = mysqli_fetch_object($rssQuery)) {

				$rssfeed .= "<item>
					<title><![CDATA[{$row->game_title}]]></title>
					<guid isPermaLink=\"false\">rpcs3-compatibility-history-{$row->id}</guid>";

		if ($row->old_status !== NULL) {
			$rssfeed .= "<description>Updated from {$row->old_status} ({$row->old_date}) to {$row->new_status} ({$row->new_date})</description>";
		} else {
			$rssfeed .= "<description>New entry for {$row->new_status} ({$row->new_date})</description>";
		}

		$rssfeed .= "<pubDate>".date('r', strtotime($row->new_date))."</pubDate>
					</item>";
		}

		$rssfeed .= "</channel>
				</rss>";


		return $rssfeed;
}

} // End of Class
