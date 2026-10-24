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
if (!@include_once(__DIR__."/../functions.php"))        throw new Exception("Compat: Failed to include functions.php");
if (!@include_once(__DIR__."/../objects/Build.php"))    throw new Exception("Compat: Failed to include objects/Build.php");
if (!@include_once(__DIR__."/../objects/Profiler.php")) throw new Exception("Compat: Failed to include objects/Profiler.php");
if (!@include_once(__DIR__."/../html/HTML.php"))        throw new Exception("Compat: Failed to include html/HTML.php");
if (!@include_once(__DIR__."/../http/HTTPQuery.php"))   throw new Exception("Compat: Failed to include http/HTTPQuery.php");


class Builds {


/***************************
 * Print: Results per Page *
 ***************************/
public static function printResultsPerPage() : void
{
    global $get;

    $http_query = new HTTPQuery($get);

    print(resultsPerPage($http_query->get_except($http_query::to_exclusions(array("order", "search")))));
}


/*****************
 * Print: Table  *
 *****************/
public static function printTable() : void
{
    global $builds, $error;

    if (!is_null($error))
    {
        printf("<div class=\"compat-tx1-criteria\">%s</div>", $error);
        return;
    }

    // Start table
    print("<div class=\"compat-table-outside\">");
    print("<div class=\"compat-table-inside\">");

    // Print table headers
    $headers = array(
        array(
            'name' => 'Pull Request',
            'class' => 'compat-table-cell builds-table-cell-pr',
            'sort' => '1'
        ),
        array(
            'name' => 'Author',
            'class' => 'compat-table-cell builds-table-cell-author',
            'sort' => '0'
        ),
        array(
            'name' => 'Lines of Code',
            'class' => 'compat-table-cell builds-table-cell-loc',
            'sort' => '0'
        ),
        array(
            'name' => 'Build Date',
            'class' => 'compat-table-cell builds-table-cell-date',
            'sort' => '4'
        ),
        array(
            'name' => 'Download',
            'class' => 'compat-table-cell',
            'sort' => '0'
        ),
        array(
            'name' => 'Version',
            'class' => 'compat-table-cell builds-table-cell-version',
            'sort' => '6'
        )
    );
    print(getTableHeaders($headers, 'b'));

    // Prepare images that will be used
    $html_img_win = new HTMLImg("builds-icon", "/img/icons/compat/windows.png", "Windows x64");
    $html_img_win_disabled = new HTMLImg("builds-icon icon-disabled", "/img/icons/compat/windows.png", "Windows x64 (unavailable)");
    $html_img_win_disabled->set_title("This Windows x64 build is not available");

    $html_img_win_arm64 = new HTMLImg("builds-icon", "/img/icons/compat/windows-arm64.png", "Windows arm64");
    $html_img_win_arm64_disabled = new HTMLImg("builds-icon icon-disabled", "/img/icons/compat/windows-arm64.png", "Windows arm64 (unavailable)");
    $html_img_win_arm64_disabled->set_title("This Windows arm64 build is not available");

    $html_img_linux = new HTMLImg("builds-icon", "/img/icons/compat/linux.png", "Linux x64");
    $html_img_linux_disabled = new HTMLImg("builds-icon icon-disabled", "/img/icons/compat/linux.png", "Linux x64 (unavailable)");
    $html_img_linux_disabled->set_title("This Linux x64 build is not available");

    $html_img_linux_arm64 = new HTMLImg("builds-icon", "/img/icons/compat/linux-arm64.png", "Linux arm64");
    $html_img_linux_arm64_disabled = new HTMLImg("builds-icon icon-disabled", "/img/icons/compat/linux-arm64.png", "Linux arm64 (unavailable)");
    $html_img_linux_arm64_disabled->set_title("This Linux arm64 build is not available");

    $html_img_mac = new HTMLImg("builds-icon", "/img/icons/compat/macos.png", "macOS x64");
    $html_img_mac_disabled = new HTMLImg("builds-icon icon-disabled", "/img/icons/compat/macos.png", "macOS x64 (unavailable)");
    $html_img_mac_disabled->set_title("This macOS x64 build is not available");

    $html_img_mac_arm64 = new HTMLImg("builds-icon", "/img/icons/compat/macos-arm64.png", "macOS arm64");
    $html_img_mac_arm64_disabled = new HTMLImg("builds-icon icon-disabled", "/img/icons/compat/macos-arm64.png", "macOS arm64 (unavailable)");
    $html_img_mac_arm64_disabled->set_title("This macOS arm64 build is not available");

    $html_img_pr = new HTMLImg("builds-icon darkmode-invert", "/img/icons/compat/github.png", "Pull Request");

    // Print table body
    foreach ($builds as $build)
    {
        // Padding formula to apply in order to align deletions in all rows
        $length = !is_null($build->additions) ? strlen($build->additions) : 1;
        $padding = (7 - $length) * 7;

        print("<details class=\"compat-entry\">");
        print("<summary class=\"compat-table-row builds-table-row\">");

        /* Cell 1: PR */
        $html_div_cell = new HTMLDiv("compat-table-cell builds-table-cell-pr");

        $title = is_null($build->title) ? "Pull Request #{$build->pr}" : $build->title;

        $html_a_pr = new HTMLA($build->get_url_pr(), $title, "#{$build->pr}");
        $html_a_pr->set_target("_blank");

        $html_div_cell->add_content($html_img_pr->to_string());
        $html_div_cell->add_content($html_a_pr->to_string());
        $html_div_cell->print();


        /* Cell 2: Author */
        $html_div_cell = new HTMLDiv("compat-table-cell builds-table-cell-author");

        $html_img_author = new HTMLImg("builds-icon", $build->get_url_author_avatar(), "Author");
        $author = htmlspecialchars($build->author, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
        $html_a_author = new HTMLA($build->get_url_author(), $build->author, "{$author}");
        $html_a_author->set_target("_blank");

        $html_div_cell->add_content($html_img_author->to_string());
        $html_div_cell->add_content($html_a_author->to_string());
        $html_div_cell->print();


        /* Cell 3: Lines of Code */
        $html_div_cell = new HTMLDiv("compat-table-cell builds-table-cell-loc");

        $additions = !is_null($build->additions) ? $build->additions : "?";
        $deletions = !is_null($build->deletions) ? $build->deletions : "?";

        $html_div_cell->add_content("<span class=\"color-green\">+{$additions}</span>");
        $html_div_cell->add_content("<span class=\"color-red\" style='padding-left: {$padding}px;'>-{$deletions}</span>");
        $html_div_cell->print();


        /* Cell 4: Diffdate and Fulldate */
        $html_div_cell = new HTMLDiv("compat-table-cell builds-table-cell-date");

        $html_div_cell->add_content("{$build->diffdate} ({$build->fulldate})");
        $html_div_cell->print();


        /* Cell 5: URL */
        $html_div_cell = new HTMLDiv("compat-table-cell");

        $is_only_archived = strtotime($build->fulldate) < strtotime("2018-06-07");

        if (!$is_only_archived)
        {
            if (!is_null($build->get_url_windows()))
            {
                $html_a_win = new HTMLA($build->get_url_windows(), "Download for Windows x64", "");
                $html_a_win->set_img($html_img_win);
                $html_div_cell->add_content($html_a_win->to_string());
            }
            else
            {
                $html_div_cell->add_content($html_img_win_disabled->to_string());
            }

            if (!is_null($build->get_url_windows_arm64()))
            {
                $html_a_win_arm64 = new HTMLA($build->get_url_windows_arm64(), "Download for Windows arm64", "");
                $html_a_win_arm64->set_img($html_img_win_arm64);
                $html_div_cell->add_content($html_a_win_arm64->to_string());
            }
            else
            {
                $html_div_cell->add_content($html_img_win_arm64_disabled->to_string());
            }

            if (!is_null($build->get_url_linux()))
            {
                $html_a_linux = new HTMLA($build->get_url_linux(), "Download for Linux x64", "");
                $html_a_linux->set_img($html_img_linux);
                $html_div_cell->add_content($html_a_linux->to_string());
            }
            else
            {
                $html_div_cell->add_content($html_img_linux_disabled->to_string());
            }

            if (!is_null($build->get_url_linux_arm64()))
            {
                $html_a_linux_arm64 = new HTMLA($build->get_url_linux_arm64(), "Download for Linux arm64", "");
                $html_a_linux_arm64->set_img($html_img_linux_arm64);
                $html_div_cell->add_content($html_a_linux_arm64->to_string());
            }
            else
            {
                $html_div_cell->add_content($html_img_linux_arm64_disabled->to_string());
            }

            if (!is_null($build->get_url_mac()))
            {
                $html_a_mac = new HTMLA($build->get_url_mac(), "Download for macOS x64", "");
                $html_a_mac->set_img($html_img_mac);
                $html_div_cell->add_content($html_a_mac->to_string());
            }
            else
            {
                $html_div_cell->add_content($html_img_mac_disabled->to_string());
            }

            if (!is_null($build->get_url_mac_arm64()))
            {
                $html_a_mac_arm64 = new HTMLA($build->get_url_mac_arm64(), "Download for macOS arm64", "");
                $html_a_mac_arm64->set_img($html_img_mac_arm64);
                $html_div_cell->add_content($html_a_mac_arm64->to_string());
            }
            else
            {
                $html_div_cell->add_content($html_img_mac_arm64_disabled->to_string());
            }
        }
        else
        {
            $html_a_archive = new HTMLA("https://archive.org/details/@rpcs3?sort=date", "Get on The Internet Archive", "Get on The Internet Archive");
            $html_a_archive->set_target("_blank");
            $html_div_cell->add_content($html_a_archive->to_string());
        }

        $html_div_cell->print();


        /* Cell 6: Version, Size (MB) and Checksum */
        $html_div_cell = new HTMLDiv("compat-table-cell builds-table-cell-version");

        if ($build->broken)
            $html_div_cell->add_content("<s>");

        $html_version = htmlspecialchars($build->version, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
        $html_div_cell->add_content($html_version);

        if ($build->broken)
            $html_div_cell->add_content("</s>");

        $html_div_cell->print();

        print("</summary>");

        // Dropdown with size / checksum data
        print("<div class=\"compat-table-dropdown compat-text\">");

        $has_info = false;

        if (!is_null($build->checksum_win) && !is_null($build->get_size_mb_windows()))
        {
            $has_info = true;
            printf("<div><b>Windows x64 (%s MB)</b><br> - SHA-256: <code>%s</code></div>",
                   $build->get_size_mb_windows(), htmlspecialchars($build->checksum_win));
        }
        if (!is_null($build->checksum_win_arm64) && !is_null($build->get_size_mb_windows_arm64()))
        {
            $has_info = true;
            printf("<div><b>Windows arm64 (%s MB)</b><br> - SHA-256: <code>%s</code></div>",
                   $build->get_size_mb_windows_arm64(), htmlspecialchars($build->checksum_win_arm64));
        }
        if (!is_null($build->checksum_linux) && !is_null($build->get_size_mb_linux()))
        {
            $has_info = true;
            printf("<div><b>Linux x64 (%s MB)</b><br> - SHA-256: <code>%s</code></div>",
                   $build->get_size_mb_linux(), htmlspecialchars($build->checksum_linux));
        }
        if (!is_null($build->checksum_linux_arm64) && !is_null($build->get_size_mb_linux_arm64()))
        {
            $has_info = true;
            printf("<div><b>Linux arm64 (%s MB)</b><br> - SHA-256: <code>%s</code></div>",
                   $build->get_size_mb_linux_arm64(), htmlspecialchars($build->checksum_linux_arm64));
        }
        if (!is_null($build->checksum_mac) && !is_null($build->get_size_mb_mac()))
        {
            $has_info = true;
            printf("<div><b>macOS x64 (%s MB)</b><br> - SHA-256: <code>%s</code></div>",
                   $build->get_size_mb_mac(), htmlspecialchars($build->checksum_mac));
        }
        if (!is_null($build->checksum_mac_arm64) && !is_null($build->get_size_mb_mac_arm64()))
        {
            $has_info = true;
            printf("<div><b>macOS arm64 (%s MB)</b><br> - SHA-256: <code>%s</code></div>",
                   $build->get_size_mb_mac_arm64(), htmlspecialchars($build->checksum_mac_arm64));
        }

        if (!$has_info)
            print("<div>No size or checksum information available for this build.</div>");

        print("</div>");
        print("</details>");
    }

    // End table
    print("</div>");
    print("</div>");
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
    $html_div->add_content(get_pages_counter($pages, $currentPage, $extra));
    $html_div->print();
}


public static function getBuildsRSS() : string
{
    global $info, $builds;

    if (!is_null($info))
        return $info;

    if (!is_array($builds))
        return "";

    $items = "";

    foreach ($builds as $build)
    {
        if (!$build instanceof Build)
            continue;

        if ($build->broken)
            continue;

        $merge_ts = strtotime($build->merge);
        
        if ($merge_ts === false)
            continue;

        $pr      = (string) $build->pr;
        $version = xml_escape($build->version);
        $author  = xml_escape($build->author);
        $when    = xml_escape($build->diffdate);
        $pr_xml  = xml_escape($pr);
        $url_pr  = xml_escape($build->get_url_pr());
        $pub     = xml_escape(date("r", $merge_ts));

        $items .=
            "<item>".
                "<title>{$version} (#{$pr_xml})</title>".
                "<description>Pull Request #{$pr_xml} by {$author} was merged {$when}</description>".
                "<guid isPermaLink=\"true\">{$url_pr}</guid>".
                "<pubDate>{$pub}</pubDate>".
                "<link>{$url_pr}</link>".
                "<comments>{$url_pr}</comments>".
                "<dc:creator>{$author}</dc:creator>".
            "</item>";
    }

    $self = xml_escape("https://rpcs3.net/builds?rss&api=v1");

    return
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
        "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">".
            "<channel>".
                "<title>RPCS3 Builds History's RSS feed</title>".
                "<link>https://rpcs3.net/builds</link>".
                "<description>For more information about RPCS3 visit https://rpcs3.net</description>".
                "<language>en-uk</language>".
                "<category>Emulation</category>".
                "<atom:link href=\"{$self}\" rel=\"self\" type=\"application/rss+xml\" />".
                $items.
            "</channel>".
        "</rss>";
}

} // End of Class
