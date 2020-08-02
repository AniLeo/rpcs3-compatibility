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
if (!@include_once(__DIR__."/../objects/Build.php")) throw new Exception("Compat: Build.php is missing. Failed to include Build.php");
if (!@include_once(__DIR__."/../objects/Profiler.php")) throw new Exception("Compat: Profiler.php is missing. Failed to include Profiler.php");


class Builds {


/***************************
 * Print: Results per Page *
 ***************************/
public static function printResultsPerPage() {
	echo resultsPerPage(combinedSearch(false, false, false, false, false, false, false, true), "b&");
}


/*******************
 * Print: Messages *
 *******************/
public static function printMessages() {
	global $error;

	if (!is_null($error))
	 	echo "<p class=\"compat-tx1-criteria\">{$error}</p>";
}


/*****************
 * Print: Table  *
 *****************/
public static function printTable() {
	global $c_github, $builds, $error;

	if (!is_null($error))
		return "";

	// Start table
	echo "<div class=\"compat-table-outside\">";
	echo "<div class=\"compat-table-inside\">";

	// Print table headers
	$headers = array(
		array(
			'name' => 'Pull Request',
			'class' => 'compat-table-cell',
			'sort' => 1
		),
		array(
			'name' => 'Author',
			'class' => 'compat-table-cell',
			'sort' => 0
		),
		array(
			'name' => 'Lines of Code',
			'class' => 'compat-table-cell',
			'sort' => 0
		),
		array(
			'name' => 'Build Date',
			'class' => 'compat-table-cell',
			'sort' => 4
		),
		array(
			'name' => 'Download',
			'class' => 'compat-table-cell',
			'sort' => 0
		)
	);
	echo getTableHeaders($headers, 'b&');

	// Print table body
	foreach($builds as $build) {

		// Length of additions text
		$len = strlen($build->additions) + 1;
		// Padding formula to apply in order to align deletions in all rows
		$padding = (8 - $len) * 7;
		// Formatted version with metadata
		$version = "";
		if (!is_null($build->checksum_win)) {
			$version .= "Windows SHA-256: {$build->checksum_win}";
		}
		if (!is_null($build->sizeMB_win)) {
			if (!empty($version)) $version .= "\n";
			$version .= "Windows Size: {$build->sizeMB_win} MB";
		}
		if (!empty($version)) $version .= "\n";
		if (!is_null($build->checksum_linux)) {
			if (!empty($version)) $version .= "\n";
			$version .= "Linux SHA-256: {$build->checksum_linux}";
		}
		if (!is_null($build->sizeMB_linux)) {
			if (!empty($version)) $version .= "\n";
			$version .= "Linux Size: {$build->sizeMB_linux} MB";
		}
		$version = !empty($version) ? "<span class=\"compat-builds-version\" title=\"{$version}\">$build->version</span>" : $build->version;

	 	echo "<div class=\"compat-table-row\">";

		/* Cell 1: PR */
		$cell = "<a href=\"{$c_github}/pull/{$build->pr}\"><img class='builds-icon' alt='GitHub' src=\"/img/icons/compat/github.png\">#{$build->pr}</a>";
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		/* Cell 2: Author */
		$cell = "<a href=\"https://github.com/{$build->author}\"><img class='builds-icon' alt='{$build->author}' src=\"https://avatars.githubusercontent.com/u/{$build->authorID}\">{$build->author}</a>";
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		/* Cell 3: Lines of Code */
		$cell = "<span style='color:#4cd137;'>+{$build->additions}</span>";
		$cell .= "<span style='color:#e84118; padding-left: {$padding}px;'>-{$build->deletions}</span>";
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		/* Cell 4: Diffdate and Fulldate */
		$cell = "{$build->diffdate} ({$build->fulldate})";
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		/* Cell 5: URL, Version, Size (MB) and Checksum */
		$cell = $version;
		if (!is_null($build->url_win))
			$cell .= "<a href=\"{$build->url_win}\"><img class='builds-icon' title='Download for Windows' alt='Windows' src=\"/img/icons/compat/windows.png\"></a>";
		if (!is_null($build->url_linux))
			$cell .= "<a href=\"{$build->url_linux}\"><img class='builds-icon' title='Download for Linux' alt='Linux' src=\"/img/icons/compat/linux.png\"></a>";
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		echo "</div>";
	}

	// End table
	echo "</div>";
	echo "</div>";
}


/************************
 * Print: Pages Counter *
 ************************/
public static function printPagesCounter() {
	global $pages, $currentPage;

	$extra = combinedSearch(true, false, false, false, false, false, false, true);

	echo "<div class=\"compat-con-pages\">";
	echo getPagesCounter($pages, $currentPage, "b&{$extra}");
	echo "</div>";
}


public static function getBuildsRSS() {
	global $info, $builds, $c_github;

	if (!is_null($info)) { return $info; }

	// Initialize string
	$rssfeed = "";

	foreach ($builds as $build) {
		$rssfeed .= "
				<item>
					<title><![CDATA[{$build->version} (#{$build->pr})]]></title>
					<description><![CDATA[<a href=\"{$c_github}/pull/{$build->pr}\">Pull Request #{$build->pr}</a> by {$build->author} was merged {$build->diffdate}]]></description>
					<guid>{$c_github}/pull/{$build->pr}</guid>
					<pubDate>".date('r', strtotime($build->merge))."</pubDate>
					<link>{$c_github}/pull/{$build->pr}</link>
					<comments>{$c_github}/pull/{$build->pr}</comments>
					<dc:creator>{$build->author}</dc:creator>
				</item>
		";
	}

	$url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	$url = str_replace('&', '&amp;', $url);

	return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
	<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">
		<channel>
			<title>RPCS3 Builds History's RSS feed</title>
			<link>https://rpcs3.net/compatibility?b</link>
			<description>For more information about RPCS3 visit https://rpcs3.net</description>
			<language>en-uk</language>
			<category>Emulation</category>
			<atom:link href=\"{$url}\" rel=\"self\" type=\"application/atom+xml\" />
				{$rssfeed}
			</channel>
	</rss>";
}

} // End of Class
