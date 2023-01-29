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
if (!@include_once(__DIR__."/../html/HTML.php")) throw new Exception("Compat: HTML.php is missing. Failed to include HTML.php");


class History {


/**********************
 * Print: Description *
 **********************/
public static function printDescription() : void
{
	global $get, $a_histdates, $a_currenthist;

	echo "<p>";
	echo "You're now watching the updates that altered a game's status for RPCS3's Compatibility List ";

	if ($get['h'] === $a_currenthist[0])
	{
		echo "since <b>{$a_currenthist[1]}</b>.";
	}
	else
	{
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

	echo "<p class=\"compat-history-months\">";

	foreach ($a_histdates as $k => $v)
	{
		$month = monthNumberToName((int) substr($k, -2));
		$year  = substr($k, 0, 4);

		if ($watchdog != $year)
		{
			if (!empty($watchdog))
				echo "<br>";

			echo "<strong>{$year}:</strong>&nbsp;";
			$watchdog = $year;
		}

		$html_a_month = new HTMLA("?h={$k}", "{$month} {$year}", $month);
		echo highlightText($html_a_month->to_string(), $get['h'] === $k);

		if ($month != "December" && $v != end($a_histdates))
			echo $spacer;
	}

	echo "<br><strong>Current:</strong>&nbsp;";

	$month = monthNumberToName((int) substr($a_currenthist[0], -2));
	$year = substr($a_currenthist[0], 0, 4);

	$html_a_month = new HTMLA("?h", "{$month} {$year}", "{$month} {$year}");
	echo highlightText($html_a_month->to_string(), $get['h'] === $a_currenthist[0]);

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

	echo "<p>";

	$html_a = new HTMLA("?h{$h}", "Show all entries", "Show all entries");
	echo highlightText($html_a->to_string(), !isset($get['m']));
 	echo $spacer;

	$html_a = new HTMLA("?h{$h}&m=c", "Show only previously existent entries", "Show only previously existent entries");
	echo highlightText($html_a->to_string(), isset($get['m']) && $get['m'] === 'c');

	$html_a = new HTMLA("?h{$h}&m=c&rss", "RSS Feed", "(RSS)");
	$html_a->print();
	echo $spacer;

	$html_a = new HTMLA("?h{$h}&m=n", "Show only new entries", "Show only new entries");
	echo highlightText($html_a->to_string(), isset($get['m']) && $get['m'] === 'n');

	$html_a = new HTMLA("?h{$h}&m=n&rss", "RSS Feed", "(RSS)");
	$html_a->print();

	echo "</p>";
}


/***********************
 * Print: Table Header *
 ***********************/
public static function printTableHeader(bool $full = true) : void
{
	if ($full)
	{
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
	}
	else
	{
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

	echo getTableHeaders($headers);
}


/************************
 * Print: Table Content *
 ************************/
/**
* @param array<HistoryEntry> $array
*/
public static function printTableContent(array $array) : void
{
	global $a_status, $a_media, $a_flags;

	foreach ($array as $entry)
	{
		$html_img_media = new HTMLImg("compat-icon-media", $a_media[$entry->game_item->get_media_id()]["icon"]);
		$html_img_media->set_title($a_media[$entry->game_item->get_media_id()]["name"]);

		$html_img_region = new HTMLImg("compat-icon-flag", $a_flags[$entry->game_item->get_region_id()]);
		$html_img_region->set_title($entry->game_item->game_id);

		echo "<div class=\"compat-table-row\">";


		// Cell 1: Regions
		$html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-gameid");

		$html_a_thread = new HTMLA($entry->game_item->get_thread_url(), "", "{$html_img_region->to_string()}{$entry->game_item->game_id}");

		$html_div_cell->add_content($html_a_thread->to_string());
		$html_div_cell->print();


		// Cell 2: Media and Titles
		$html_div_cell = new HTMLDiv("compat-table-cell");

		$html_div_cell->add_content($html_img_media->to_string());
		$html_div_cell->add_content($entry->title);

		if (!is_null($entry->title2))
		{
			$html_div_cell->add_content("<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;({$entry->title2})");
		}

		$html_div_cell->print();


		// Cell 3: New Status
		$html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-status");

		$html_div_status = new HTMLDiv("txt-compat-status background-status-{$entry->new_status}");
		$html_div_status->add_content($a_status[$entry->new_status]["name"]);

		$html_div_cell->add_content($html_div_status->to_string());
		$html_div_cell->print();


		// Cell 4: New Date
		$html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-date");
		$html_div_cell->add_content($entry->new_date);
		$html_div_cell->print();


		// Cell 5: Old Status (If existent)
		if (!is_null($entry->old_status))
		{
			$html_div_status = new HTMLDiv("txt-compat-status background-status-{$entry->old_status}");
			$html_div_status->add_content($a_status[$entry->old_status]["name"]);

			$html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-status");
			$html_div_cell->add_content($html_div_status->to_string());
			$html_div_cell->print();
		}


		// Cell 6: Old Date (If existent)
		if (!is_null($entry->old_date))
		{
			$html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-date");
			$html_div_cell->add_content($entry->old_date);
			$html_div_cell->print();
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
	if (!empty($error_existing))
	{
		echo "<p class=\"compat-tx1-criteria\">{$error_existing}</p>";
	}
	elseif (!empty($a_existing))
	{
		echo "<div class=\"compat-table-outside\">";
		echo "<div class=\"compat-table-inside\">";
		self::printTableHeader();
		self::printTableContent($a_existing);
		echo "</div>";
		echo "</div>";
	}

	// New entries table
	if (!empty($error_new))
	{
		echo "<p class=\"compat-tx1-criteria\">{$error_new}</p>";
	}
	elseif (!empty($a_new))
	{
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
	if (empty($a_new) && empty($a_existing)) return;

	$error = !empty($error_new) ? $error_new : $error_existing;
	$title = !empty($a_new) ? "New additions" : "Updates";

	$url = str_replace('&', '&amp;', "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
	<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
	<channel>
	<title>RPCS3 Compatibility List History - {$title}</title>
	<link>https://rpcs3.net/compatibility?h</link>
	<description>For more information about RPCS3 visit https://rpcs3.net</description>
	<language>en-uk</language>
	<atom:link href=\"{$url}\" rel=\"self\" type=\"application/rss+xml\" />";

	if (!empty($error))
	{
		echo "<item>
						<title><![CDATA[{$error}]]></title>
						<description>{$error}</description>
						<pubDate>".date('r', time())."</pubDate>
					</item>";
	}
	elseif (!empty($a_new))
	{
		foreach ($a_new as $key => $entry)
		{
			echo "<item>
						<title><![CDATA[{$entry->title}]]></title>
						<guid isPermaLink=\"false\">rpcs3-compatibility-history-{$entry->game_item->game_id}_{$entry->new_date}</guid>
						<description>New entry for {$a_status[$entry->new_status]["name"]} ({$entry->new_date})</description>
						<pubDate>".date('r', strtotime($entry->new_date))."</pubDate>
						</item>";
		}
	}
	else /*if (!empty($a_existing)) */
	{
		foreach ($a_existing as $key => $entry)
		{
			echo "<item>
						<title><![CDATA[{$entry->title}]]></title>
						<guid isPermaLink=\"false\">rpcs3-compatibility-history-{$entry->game_item->game_id}_{$entry->new_date}</guid>
						<description>Updated from {$a_status[$entry->old_status]["name"]} ({$entry->old_date}) to {$a_status[$entry->new_status]["name"]} ({$entry->new_date})</description>
						<pubDate>".date('r', strtotime($entry->new_date))."</pubDate>
						</item>";
		}
	}

	echo "</channel>
	</rss>";
}

} // End of Class
