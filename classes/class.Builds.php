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
if (!@include_once(__DIR__."/../HTTPQuery.php")) throw new Exception("Compat: HTTPQuery.php is missing. Failed to include HTTPQuery.php");


class Builds {


/***************************
 * Print: Results per Page *
 ***************************/
public static function printResultsPerPage() : void
{
	global $get;

	$http_query = new HTTPQuery($get);

	echo resultsPerPage($http_query->get_except($http_query::to_exclusions(array("order"))));
}


/*****************
 * Print: Table  *
 *****************/
public static function printTable() : void
{
	global $builds, $error;

	if (!is_null($error))
	{
	 	echo "<p class=\"compat-tx1-criteria\">{$error}</p>";
		return;
	}

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
	echo getTableHeaders($headers, 'b');

	// Prepare images that will be used
	$html_img_win = new HTMLImg("builds-icon", "/img/icons/compat/windows.png");
	$html_img_linux = new HTMLImg("builds-icon", "/img/icons/compat/linux.png");
	$html_img_pr = new HTMLImg("builds-icon", "/img/icons/compat/github.png");

	// Print table body
	foreach ($builds as $build)
	{
		// Padding formula to apply in order to align deletions in all rows
		$padding = (7 - strlen($build->additions)) * 7;

		// Formatted version with metadata
		$version = "";

		// Tooltip
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
		$html_div_cell = new HTMLDiv("compat-table-cell");

		$html_a_pr = new HTMLA($build->get_url_pr(), "Pull Request #{$build->pr}", "{$html_img_pr->to_string()}#{$build->pr}");
		$html_a_pr->set_target("_blank");

		$html_div_cell->add_content($html_a_pr->to_string());
		$html_div_cell->print();


		/* Cell 2: Author */
		$html_div_cell = new HTMLDiv("compat-table-cell");

		$html_img_author = new HTMLImg("builds-icon", $build->get_url_author_avatar());
		$html_a_author = new HTMLA($build->get_url_author(), $build->author, "{$html_img_author->to_string()}{$build->author}");
		$html_a_author->set_target("_blank");

		$html_div_cell->add_content($html_a_author->to_string());
		$html_div_cell->print();


		/* Cell 3: Lines of Code */
		$html_div_cell = new HTMLDiv("compat-table-cell");

		$additions = !is_null($build->additions) ? $build->additions : "?";
		$deletions = !is_null($build->deletions) ? $build->deletions : "?";

	 	$html_div_cell->add_content("<span style='color:#4cd137;'>+{$additions}</span>");
		$html_div_cell->add_content("<span style='color:#e84118; padding-left: {$padding}px;'>-{$deletions}</span>");
		$html_div_cell->print();


		/* Cell 4: Diffdate and Fulldate */
		$html_div_cell = new HTMLDiv("compat-table-cell");

		$html_div_cell->add_content("{$build->diffdate} ({$build->fulldate})");
		$html_div_cell->print();


		/* Cell 5: URL, Version, Size (MB) and Checksum */
		$html_div_cell = new HTMLDiv("compat-table-cell");

		if ($build->broken)
			$html_div_cell->add_content("<s>");

		$html_div_cell->add_content($version);

		if (!is_null($build->get_url_windows()))
		{
			$html_a_win = new HTMLA($build->get_url_windows(), "Download for Windows", $html_img_win->to_string());
			$html_div_cell->add_content($html_a_win->to_string());
		}

		if (!is_null($build->get_url_linux()))
		{
			$html_a_linux = new HTMLA($build->get_url_linux(), "Download for Linux", $html_img_linux->to_string());
			$html_div_cell->add_content($html_a_linux->to_string());
		}

		if ($build->broken)
			$html_div_cell->add_content("</s>");

		$html_div_cell->print();


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
	global $pages, $currentPage, $get;

	$http_query = new HTTPQuery($get);
	$extra = $http_query->get_except($http_query::to_exclusions(array("order", "results")));

	$html_div = new HTMLDiv("compat-con-pages");
	$html_div->add_content(getPagesCounter($pages, $currentPage, $extra));
	$html_div->print();
}


public static function getBuildsRSS() : string
{
	global $info, $builds;

	if (!is_null($info))
		return $info;

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
