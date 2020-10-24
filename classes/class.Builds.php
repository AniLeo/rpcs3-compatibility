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
if (!@include_once(__DIR__."/../html/HTML.php")) throw new Exception("Compat: HTML.php is missing. Failed to include HTML.php");


class Builds {


/***************************
 * Print: Results per Page *
 ***************************/
public static function printResultsPerPage() : void
{
	echo resultsPerPage(combinedSearch(false, false, false, false, false, false, false, true));
}


/*******************
 * Print: Messages *
 *******************/
public static function printMessages() : void
{
	global $error;

	if (!is_null($error))
	 	echo "<p class=\"compat-tx1-criteria\">{$error}</p>";
}


/*****************
 * Print: Table  *
 *****************/
public static function printTable() : void
{
	global $builds, $error;

	if (!is_null($error))
		return;

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
	echo getTableHeaders($headers);

	// Print table body
	foreach ($builds as $build)
	{
		// Length of additions text
		$len = strlen($build->additions) + 1;
		// Padding formula to apply in order to align deletions in all rows
		$padding = (8 - $len) * 7;
		// Formatted version with metadata
		$version = "";

		if (!is_null($build->checksum_win))
		{
			$version .= "Windows SHA-256: {$build->checksum_win}";
		}
		if (!is_null($build->get_size_mb_windows()))
		{
			if (!empty($version))
				$version .= "\n";
			$version .= "Windows Size: {$build->get_size_mb_windows()} MB";
		}
		if (!empty($version))
			$version .= "\n";
		if (!is_null($build->checksum_linux))
		{
			if (!empty($version))
				$version .= "\n";
			$version .= "Linux SHA-256: {$build->checksum_linux}";
		}
		if (!is_null($build->get_size_mb_linux()))
		{
			if (!empty($version))
				$version .= "\n";
			$version .= "Linux Size: {$build->get_size_mb_linux()} MB";
		}
		$version = !empty($version) ? "<span class=\"compat-builds-version\" title=\"{$version}\">{$build->version}</span>" : $build->version;

	 	echo "<div class=\"compat-table-row\">";

		/* Cell 1: PR */
		$html_a_pr = new HTMLA($build->get_url_pr(), "Pull Request #{$build->pr}", "<img class='builds-icon' alt='GitHub' src=\"/img/icons/compat/github.png\">#{$build->pr}");
		$html_a_pr->set_target("_blank");
		$cell = $html_a_pr->to_string();
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		/* Cell 2: Author */
		$html_a_author = new HTMLA($build->get_url_author(), $build->author, "<img class='builds-icon' alt='{$build->author}' src=\"{$build->get_url_author_avatar()}\">{$build->author}");
		$html_a_author->set_target("_blank");
		$cell = $html_a_author->to_string();
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		/* Cell 3: Lines of Code */
		$additions = !is_null($build->additions) ? $build->additions : "?";
		$deletions = !is_null($build->deletions) ? $build->deletions : "?";

		$cell = "<span style='color:#4cd137;'>+{$additions}</span>";
		$cell .= "<span style='color:#e84118; padding-left: {$padding}px;'>-{$deletions}</span>";
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		/* Cell 4: Diffdate and Fulldate */
		$cell = "{$build->diffdate} ({$build->fulldate})";
		echo "<div class=\"compat-table-cell\">{$cell}</div>";

		/* Cell 5: URL, Version, Size (MB) and Checksum */
		$url_windows = $build->get_url_windows();
		$url_linux   = $build->get_url_linux();
		$cell = $version;
		if (!is_null($url_windows))
		{
			$html_a_win = new HTMLA($url_windows, "Download for Windows", "<img class='builds-icon' title='Download for Windows' alt='Windows' src=\"/img/icons/compat/windows.png\">");
			$cell .= $html_a_win->to_string();
		}
		if (!is_null($url_linux))
		{
			$html_a_linux = new HTMLA($url_linux, "Download for Linux", "<img class='builds-icon' title='Download for Linux' alt='Linux' src=\"/img/icons/compat/linux.png\">");
			$cell .= $html_a_linux->to_string();
		}
		if ($build->broken)
			$cell = "<s>{$cell}</s>";
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
public static function printPagesCounter() : void
{
	global $pages, $currentPage;

	$extra = combinedSearch(true, false, false, false, false, false, false, true);

	echo "<div class=\"compat-con-pages\">";
	echo getPagesCounter($pages, $currentPage, $extra);
	echo "</div>";
}


public static function getBuildsRSS() : string
{
	global $info, $builds;

	if (!is_null($info)) { return $info; }

	// Initialize string
	$rssfeed = "";

	foreach ($builds as $build)
	{
		// Skip broken builds
		if ($build->broken)
			continue;

		$rssfeed .= "
				<item>
					<title><![CDATA[{$build->version} (#{$build->pr})]]></title>
					<description><![CDATA[Pull Request #{$build->pr} by {$build->author} was merged {$build->diffdate}]]></description>
					<guid>{$build->get_url_pr()}</guid>
					<pubDate>".date('r', strtotime($build->merge))."</pubDate>
					<link>{$build->get_url_pr()}</link>
					<comments>{$build->get_url_pr()}</comments>
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
