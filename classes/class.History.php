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
if (!@include_once(__DIR__."/../functions.php"))            throw new Exception("Compat: Failed to include functions.php");
if (!@include_once(__DIR__."/../objects/HistoryEntry.php")) throw new Exception("Compat: Failed to include objects/HistoryEntry.php");
if (!@include_once(__DIR__."/../html/HTML.php"))            throw new Exception("Compat: Failed to include html/HTML.php");


class History {


/**********************
 * Print: Description *
 **********************/
public static function printDescription() : void
{
    global $get, $a_histdates, $a_currenthist;

    print("<p>");
    print("You're now watching the updates that altered a game's status for RPCS3's Compatibility List ");

    if ($get['h'] === $a_currenthist[0])
    {
        print("since <b>{$a_currenthist[1]}</b>.");
    }
    else
    {
        $v = $a_histdates[$get['h']];
        $m1 = monthNumberToName($v[0]['m']);
        $m2 = monthNumberToName($v[1]['m']);
        printf("from <b>%s %s, %s</b> to <b>%s %s, %s</b>.", $m1, $v[0]['d'], $v[0]['y'], $m2, $v[1]['d'], $v[1]['y']);
    }

    print("</p>");
}


/*****************
 * Print: Months *
 *****************/
public static function printMonths() : void
{
    global $get, $a_histdates, $a_currenthist;

    $spacer = "&nbsp;&#8226;&nbsp;&nbsp;";
    $watchdog = '';

    print("<p class=\"compat-history-months\">");

    foreach ($a_histdates as $k => $v)
    {
        $month = monthNumberToName((int) substr($k, -2));
        $year  = substr($k, 0, 4);

        if ($watchdog != $year)
        {
            if (!empty($watchdog))
                print("<br>");

            printf("<strong>%s:</strong>&nbsp;", $year);
            $watchdog = $year;
        }

        $html_a_month = new HTMLA("?h={$k}", "{$month} {$year}", $month);
        if ($get['h'] === $k)
            $html_a_month->set_class("compat-text text-bold text-underline");
        $html_a_month->print();

        if ($month != "December" && $v != end($a_histdates))
            print($spacer);
    }

    print("<br><strong>Current:</strong>&nbsp;");

    $month = monthNumberToName((int) substr($a_currenthist[0], -2));
    $year = substr($a_currenthist[0], 0, 4);

    $html_a_month = new HTMLA("?h", "{$month} {$year}", "{$month} {$year}");
    if ($get['h'] === $a_currenthist[0])
        $html_a_month->set_class("compat-text text-bold text-underline");
    $html_a_month->print();

    print("</p>");
}


/******************
 * Print: Options *
 ******************/
public static function printOptions() : void
{
    global $get, $a_currenthist;

    $h = $get['h'] !== $a_currenthist[0] ? "={$get['h']}" : "";
    $spacer = "&nbsp;&#8226;&nbsp;";

    print("<p>");

    $html_a = new HTMLA("?h{$h}", "Show all entries", "Show all entries");
    if (!isset($get['m']))
        $html_a->set_class("compat-text text-bold text-underline");
    $html_a->print();
    print($spacer);

    $html_a = new HTMLA("?h{$h}&m=c", "Show only previously existent entries", "Show only previously existent entries");
    if (isset($get['m']) && $get['m'] === 'c')
        $html_a->set_class("compat-text text-bold text-underline");
    $html_a->print();

    $html_a = new HTMLA("?h{$h}&m=c&rss&api=v1", "RSS Feed", "(RSS)");
    $html_a->set_target("_blank");
    $html_a->print();
    print($spacer);

    $html_a = new HTMLA("?h{$h}&m=n", "Show only new entries", "Show only new entries");
    if (isset($get['m']) && $get['m'] === 'n')
        $html_a->set_class("compat-text text-bold text-underline");
    $html_a->print();

    $html_a = new HTMLA("?h{$h}&m=n&rss&api=v1", "RSS Feed", "(RSS)");
    $html_a->set_target("_blank");
    $html_a->print();

    print("</p>");
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
                'sort' => '0'
            ),
            array(
                'name' => 'Game Title',
                'class' => 'compat-table-cell',
                'sort' => '0'
            ),
            array(
                'name' => 'New Status',
                'class' => 'compat-table-cell compat-table-cell-status',
                'sort' => '0'
            ),
            array(
                'name' => 'New Date',
                'class' => 'compat-table-cell compat-table-cell-date',
                'sort' => '0'
            ),
            array(
                'name' => 'Old Status',
                'class' => 'compat-table-cell compat-table-cell-status',
                'sort' => '0'
            ),
            array(
                'name' => 'Old Date',
                'class' => 'compat-table-cell compat-table-cell-date',
                'sort' => '0'
            )
        );
    }
    else
    {
        $headers = array(
            array(
                'name' => 'Game Regions',
                'class' => 'compat-table-cell compat-table-cell-gameid',
                'sort' => '0'
            ),
            array(
                'name' => 'Game Title',
                'class' => 'compat-table-cell',
                'sort' => '0'
            ),
            array(
                'name' => 'Status',
                'class' => 'compat-table-cell compat-table-cell-status',
                'sort' => '0'
            ),
            array(
                'name' => 'Date',
                'class' => 'compat-table-cell compat-table-cell-date',
                'sort' => '0'
            )
        );
    }

    print(getTableHeaders($headers));
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

        $html_img_move = new HTMLImg("compat-icon", "/img/icons/compat/psmove.png");
        $html_img_move->set_title("PS Move");

        print("<div class=\"compat-table-row\">");


        // Cell 1: Regions
        $html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-gameid");

        $html_a_thread = new HTMLA($entry->game_item->get_thread_url(), "", $entry->game_item->game_id);

        $html_div_cell->add_content($html_img_region->to_string());
        $html_div_cell->add_content($html_a_thread->to_string());
        $html_div_cell->print();


        // Cell 2: Media and Titles
        $html_div_cell = new HTMLDiv("compat-table-cell");

        $html_div_cell->add_content($html_img_media->to_string());
        
        $html_div_cell->add_content($entry->title.PHP_EOL);

        if ($entry->move === 1)
        {
            $html_div_cell->add_content($html_img_move->to_string());
        }

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


        print("</div>");
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
        printf("<p class=\"compat-tx1-criteria\">%s</p>", $error_existing);
    }
    elseif (!empty($a_existing))
    {
        print("<div class=\"compat-table-outside\">");
        print("<div class=\"compat-table-inside\">");
        self::printTableHeader();
        self::printTableContent($a_existing);
        print("</div>");
        print("</div>");
    }

    // New entries table
    if (!empty($error_new))
    {
        printf("<p class=\"compat-tx1-criteria\">%s</p>", $error_new);
    }
    elseif (!empty($a_new))
    {
        print("<p class=\"compat-tx1-criteria\"><strong>Newly reported games (includes new regions for existing games)</strong></p>");
        print("<div class=\"compat-table-outside\">");
        print("<div class=\"compat-table-inside\">");
        self::printTableHeader(false);
        self::printTableContent($a_new);
        print("</div>");
        print("</div>");
    }
}


/************************
 * Print: Status Module *
 ************************/
public static function printStatusModule() : void
{
    global $a_status;

    $html_div = new HTMLDiv("compat-status-container");

    // Pretty output for readability
    foreach ($a_status as $id => $status)
    {
        // Initialise current status parent div
        $html_div_main = new HTMLDiv("compat-status-main");

        // Status icon
        $html_div_icon = new HTMLDiv("compat-status-icon background-status-{$id}");
        $html_div_main->add_content($html_div_icon->to_string());

        // Status, description
        $html_div_text = new HTMLDiv("compat-status-text");
        $html_div_text->add_content("<span style='color:#{$status['color']}'>");
        $html_div_text->add_content("<strong>{$status['name']}: </strong>");
        $html_div_text->add_content("</span>");
        $html_div_text->add_content($status['desc']);
        $html_div_main->add_content($html_div_text->to_string());

        // Add current status parent div to the root div
        $html_div->add_content($html_div_main->to_string());
    }

    $html_div->print();
}


public static function printHistoryRSS() : void
{
    global $a_status, $a_new, $a_existing, $error_new, $error_existing;

    // Should be unreachable, function is always called when one of the modes is set
    if (empty($a_new) && empty($a_existing)) return;

    $error = !empty($error_new) ? $error_new : $error_existing;
    $title = !empty($a_new) ? "New additions" : "Updates";

    // Should be unreachable, these server globals are always strings
    if (!is_string($_SERVER['HTTP_HOST']) || !is_string($_SERVER['REQUEST_URI'])) return;

    $url = str_replace('&', '&amp;', "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

    printf(
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
        <channel>
        <title>RPCS3 Compatibility List History - %s</title>
        <link>https://rpcs3.net/compatibility?h</link>
        <description>For more information about RPCS3 visit https://rpcs3.net</description>
        <language>en-uk</language>
        <atom:link href=\"%s\" rel=\"self\" type=\"application/rss+xml\" />",
        $title, $url);

    if (!empty($error))
    {
        printf(
            "<item>
                <title><![CDATA[%s]]></title>
                <description>%s</description>
                <pubDate>%s</pubDate>
            </item>",
            $error, $error, date('r', time()));
    }
    elseif (!empty($a_new))
    {
        foreach ($a_new as $key => $entry)
        {
            printf(
                "<item>
                    <title><![CDATA[%s]]></title>
                    <guid isPermaLink=\"false\">rpcs3-compatibility-history-%s_%s</guid>
                    <description>New entry for %s (%s)</description>
                    <pubDate>%s</pubDate>
                </item>",
                $entry->title, $entry->game_item->game_id, $entry->new_date, $a_status[$entry->new_status]["name"], $entry->new_date, date('r', strtotime($entry->new_date)));
        }
    }
    else /*if (!empty($a_existing)) */
    {
        foreach ($a_existing as $key => $entry)
        {
            printf(
                "<item>
                    <title><![CDATA[%s]]></title>
                    <guid isPermaLink=\"false\">rpcs3-compatibility-history-%s_%s</guid>
                    <description>Updated from %s (%s) to %s (%s)</description>
                    <pubDate>%s</pubDate>
                </item>",
                $entry->title, $entry->game_item->game_id, $entry->new_date, $a_status[$entry->old_status]["name"], $entry->old_date, $a_status[$entry->new_status]["name"], $entry->new_date, date('r', strtotime($entry->new_date)));
        }
    }

    print("</channel>");
    print("</rss>");
}

} // End of Class
