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
if (!@include_once(__DIR__."/../objects/WindowsBuild.php")) throw new Exception("Compat: WindowsBuild.php is missing. Failed to include WindowsBuild.php");


class Builds {


function getResultsPerPage() {
	return resultsPerPage(combinedSearch(false, false, false, false, false, false, false, true), "b&");
}


function getTableMessages() {
	global $info;
	if (!is_null($info)) { return "<p class=\"compat-tx1-criteria\">{$info}</p>"; }
}


function getTableHeaders() {
	$headers = array(
		'Pull Request' => 1,
		'Author' => 2,
		'Lines of Code' => 0,
		'Build Date' => 4,
		'Download' => 0
	);
	return getTableHeaders($headers, 'b&');
}


function getTableContent() {
	global $c_github, $builds;

	// Initialize string
	$s_tablecontent = "";

	foreach($builds as $build) {

		// Length of additions text
		$len = strlen($build->additions) + 1;
		// Padding formula to apply in order to align deletions in all rows
		$padding = (8 - $len) * 7;
		// Formatted checksum
		$checksum = !is_null($build->checksum) ? "<span style=\"font-size=10px; border-bottom: 1px dotted #3198ff;\" title=\"{$build->checksum}\">sha256</span>" : NULL;

		$s_tablecontent .= "<div class=\"divTableRow\">";

		/* Cell 1: PR */
		$cell = "<a href=\"{$c_github}/pull/{$build->pr}\"><img class='builds-icon' alt='GitHub' src=\"/img/icons/compat/github.png\">&nbsp;&nbsp;#{$build->pr}</a>";
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		/* Cell 2: Author */
		$cell = "<a href=\"https://github.com/{$build->author}\">{$build->author}</a>";
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		/* Cell 3: Lines of Code */
		$cell = "<span style='color:#4cd137;'>+{$build->additions}</span>";
		$cell .= "<span style='color:#e84118; padding-left: {$padding}px;'>-{$build->deletions}</span>";
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		/* Cell 4: Diffdate and Fulldate */
		$cell = "{$build->diffdate} ({$build->fulldate})";
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		/* Cell 5: URL, Version, Size (MB) and Checksum */
		$cell = "<a href=\"{$build->url}\"><img class='builds-icon' alt='Download' src=\"/img/icons/compat/download.png\">&nbsp;&nbsp;{$build->version}</a>";
		if (!is_null($build->sizeMB))	{ $cell .= "&nbsp;&nbsp;{$build->sizeMB}MB"; }
		if (!is_null($checksum)) 			{ $cell .= "&nbsp;&nbsp;{$checksum}"; }
		$s_tablecontent .= "<div class=\"divTableCell\">{$cell}</div>";

		$s_tablecontent .= "</div>";

	}

	return "<div class=\"divTableBody\">{$s_tablecontent}</div>";
}


function getPagesCounter() {
	global $pages, $currentPage, $get;

	$extra = combinedSearch(true, false, false, false, false, false, false, true);

	return getPagesCounter($pages, $currentPage, "b&{$extra}");
}


function getBuildsRSS() {
	global $info, $builds;

	if (!is_null($info)) { return $info; }

	// Initialize string
	$rssfeed = "";

	foreach($builds as $build) {
			$rssfeed .= "
					<item>
						<title><![CDATA[{$build->version} (PR #{$build->pr})]]></title>
						<link>{$build->url}</link>
						<guid>{$build->url}</guid>
						<description>Pull Request #{$build->pr} by {$build->author} was merged {$build->diffdate}.</description>
						<pubDate>".date('r', strtotime($build->merge))."</pubDate>
					</item>
			";
	}

	$url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	$url = str_replace('&', '&amp;', $url);

	return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
	<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
		<channel>
			<title>RPCS3 Builds History's RSS feed</title>
			<link>https://rpcs3.net/compatibility?b</link>
			<description>For more information about RPCS3 visit https://rpcs3.net</description>
			<language>en-uk</language>
			<atom:link href=\"{$url}\" rel=\"self\" type=\"application/rss+xml\" />
				{$rssfeed}
			</channel>
	</rss>";
}

} // End of Class
