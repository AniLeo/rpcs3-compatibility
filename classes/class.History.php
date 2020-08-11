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


/**********************
 * Print: Description *
 **********************/
public static function printDescription() : void
{
	global $get, $a_histdates, $a_currenthist;

	echo "<p id=\"compat-history-description\">";
	echo "You're now watching the updates that altered a game's status for RPCS3's Compatibility List ";

	if ($get['h'] === $a_currenthist[0]) {
		echo "since <b>{$a_currenthist[1]}</b>.";
	} else {
		$v = $a_histdates[$get['h']];
		$m1 = monthNumberToName($v[0]['m']);
		$m2 = monthNumberToName($v[1]['m']);
		echo "from <b>{$m1} {$v[0]['d']}, {$v[0]['y']}</b> to <b>{$m2} {$v[1]['d']}, {$v[1]['y']}</b>.";
	}

	echo "</p>";
}


/*****************
 * Print: Months *
 *****************/
public static function printMonths() : void
{
	global $get, $a_histdates, $a_currenthist;

	$spacer = "&nbsp;&#8226;&nbsp;";
	$watchdog = '';

	echo "<p id=\"compat-history-months\">";

	foreach($a_histdates as $k => $v) {
		$month = monthNumberToName(substr($k, -2));
		$year  = substr($k, 0, 4);

		if ($watchdog != $year) {
			if ($watchdog != '')
				echo "<br>";
			echo "<strong>{$year}:</strong>&nbsp;";
			$watchdog = $year;
		}

		echo highlightText("<a href=\"?h={$k}\">{$month}</a>", $get['h'] === $k);
		if ($month != "December" && $v != end($a_histdates))
			echo $spacer;
	}

	echo "<br><strong>Current:</strong>&nbsp;";

	$month = monthNumberToName(substr($a_currenthist[0], -2));
	$year = substr($a_currenthist[0], 0, 4);

	echo highlightText("<a href=\"?h\">{$month} {$year}</a>", $get['h'] === $a_currenthist[0]);

	echo "</p>";
}


/******************
 * Print: Options *
 ******************/
public static function printOptions() : void
{
	global $get, $a_currenthist;

	$h = $get['h'] !== $a_currenthist[0] ? "={$get['h']}" : "";
	$spacer = "&nbsp;&#8226;&nbsp;";

	echo "<p id=\"compat-history-options\">";

	echo highlightText("<a href=\"?h{$h}\">Show all entries</a>", $get['m'] == "");
 	echo "{$spacer}";

	echo highlightText("<a href=\"?h{$h}&m=c\">Show only previously existent entries</a>", $get['m'] == "c");
	echo " <a href=\"?h{$h}&m=c&rss\">(RSS)</a>{$spacer}";

	echo highlightText("<a href=\"?h{$h}&m=n\">Show only new entries</a>", $get['m'] == "n");
	echo " <a href=\"?h{$h}&m=n&rss\">(RSS)</a>";

	echo "</p>";
}


/***********************
 * Print: Table Header *
 ***********************/
public static function printTableHeader(bool $full = true) : void
{
	if ($full) {
		$headers = array(
			array(
				'name' => 'Game Regions',
				'class' => 'compat-table-cell compat-table-cell-gameid',
				'sort' => 0
			),
			array(
				'name' => 'Game Title',
				'class' => 'compat-table-cell',
				'sort' => 0
			),
			array(
				'name' => 'New Status',
				'class' => 'compat-table-cell compat-table-cell-status',
				'sort' => 0
			),
			array(
				'name' => 'New Date',
				'class' => 'compat-table-cell compat-table-cell-date',
				'sort' => 0
			),
			array(
				'name' => 'Old Status',
				'class' => 'compat-table-cell compat-table-cell-status',
				'sort' => 0
			),
			array(
				'name' => 'Old Date',
				'class' => 'compat-table-cell compat-table-cell-date',
				'sort' => 0
			)
		);
	} else {
		$headers = array(
			array(
				'name' => 'Game Regions',
				'class' => 'compat-table-cell compat-table-cell-gameid',
				'sort' => 0
			),
			array(
				'name' => 'Game Title',
				'class' => 'compat-table-cell',
				'sort' => 0
			),
			array(
				'name' => 'Status',
				'class' => 'compat-table-cell compat-table-cell-status',
				'sort' => 0
			),
			array(
				'name' => 'Date',
				'class' => 'compat-table-cell compat-table-cell-date',
				'sort' => 0
			)
		);
	}

	echo getTableHeaders($headers, 'h');
}


/************************
 * Print: Table Content *
 ************************/
public static function printTableContent(array $array) : void
{
	global $a_status;

	foreach ($array as $entry) {
		echo "<div class=\"compat-table-row\">";

		// Cell 1: Regions
		$cell = getThread(getGameRegion($entry->IDs[0], false).$entry->IDs[0], $entry->IDs[1]);
		$media = getGameMediaIcon($entry->IDs[0], false);
		echo "<div class=\"compat-table-cell compat-table-cell-gameid\">{$cell}</div>";

		// Cell 2: Media and Titles
		$cell = "{$media}{$entry->title}";
		if (!is_null($entry->title2)) {
			$cell .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;({$entry->title2})";
		}
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		// Cell 3: New Status
		$cell = '';
		if (!is_null($entry->new_status))
			$cell = "<div class=\"txt-compat-status\" style=\"background: #{$a_status[$entry->new_status]['color']};\">{$a_status[$entry->new_status]['name']}</div>";
		echo "<div class=\"compat-table-cell compat-table-cell-status\">{$cell}</div>";

		// Cell 4: New Date
		$cell = $entry->new_date;
		echo "<div class=\"compat-table-cell compat-table-cell-date\">{$cell}</div>";

		// Cell 5: Old Status (If existent)
		if (!is_null($entry->old_status)) {
			$cell = '';
			if (!is_null($entry->old_status))
				$cell = "<div class=\"txt-compat-status\" style=\"background: #{$a_status[$entry->old_status]['color']};\">{$a_status[$entry->old_status]['name']}</div>";
			echo "<div class=\"compat-table-cell compat-table-cell-status\">{$cell}</div>";
		}

		// Cell 6: Old Date (If existent)
		if (!is_null($entry->old_date)) {
			$cell = $entry->old_date;
			echo "<div class=\"compat-table-cell compat-table-cell-date\">{$cell}</div>";
		}

		echo "</div>";
	}
}


/******************
 * Print: Content *
 ******************/
public static function printContent() : void
{
	global $a_existing, $a_new, $error_existing, $error_new;

	// Existing entries table
	if ($error_existing != "") {
		echo "<p class=\"compat-tx1-criteria\">{$error_existing}</p>";
	} elseif (!is_null($a_existing)) {
		echo "<div class=\"compat-table-outside\">";
		echo "<div class=\"compat-table-inside\">";
		self::printTableHeader();
		self::printTableContent($a_existing);
		echo "</div>";
		echo "</div>";
	}

	// New entries table
	if ($error_new != "") {
		echo "<p class=\"compat-tx1-criteria\">{$error_new}</p>";
	} elseif (!is_null($a_new)) {
		echo "<p class=\"compat-tx1-criteria\"><strong>Newly reported games (includes new regions for existing games)</strong></p>";
		echo "<div class=\"compat-table-outside\">";
		echo "<div class=\"compat-table-inside\">";
		self::printTableHeader(false);
		self::printTableContent($a_new);
		echo "</div>";
		echo "</div>";
	}
}


public static function printHistoryRSS() : void
{
	global $a_status, $a_new, $a_existing, $error_new, $error_existing;

	// Should be unreachable, function is always called when one of the modes is set
	if (is_null($a_new) && is_null($a_existing)) return;

	$error = $error_new != "" ? $error_new : $error_existing;
	$title = !is_null($a_new) ? "New additions" : "Updates";
	$url = str_replace('&', '&amp;', "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
	<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
	<channel>
	<title>RPCS3 Compatibility List History - {$title}</title>
	<link>https://rpcs3.net/compatibility?h</link>
	<description>For more information about RPCS3 visit https://rpcs3.net</description>
	<language>en-uk</language>
	<atom:link href=\"{$url}\" rel=\"self\" type=\"application/rss+xml\" />";

	if ($error != "") {
		echo "<item>
						<title><![CDATA[{$error}]]></title>
						<description>{$error}</description>
						<pubDate>".date('r', time())."</pubDate>
					</item>";
	} elseif (!is_null($a_new)) {
		foreach ($a_new as $key => $entry) {
			echo "<item>
						<title><![CDATA[{$entry->title}]]></title>
						<guid isPermaLink=\"false\">rpcs3-compatibility-history-{$entry->IDs[0]}_{$entry->new_date}</guid>
						<description>New entry for {$a_status[$entry->new_status]["name"]} ({$entry->new_date})</description>
						<pubDate>".date('r', strtotime($entry->new_date))."</pubDate>
						</item>";
		}
	} elseif (!is_null($a_existing)) {
		foreach ($a_existing as $key => $entry) {
			echo "<item>
						<title><![CDATA[{$entry->title}]]></title>
						<guid isPermaLink=\"false\">rpcs3-compatibility-history-{$entry->IDs[0]}_{$entry->new_date}</guid>
						<description>Updated from {$a_status[$entry->old_status]["name"]} ({$entry->old_date}) to {$a_status[$entry->new_status]["name"]} ({$entry->new_date})</description>
						<pubDate>".date('r', strtotime($entry->new_date))."</pubDate>
						</item>";
		}
	}

	echo "</channel>
	</rss>";
}

} // End of Class
