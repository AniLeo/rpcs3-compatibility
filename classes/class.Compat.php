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
if (!@include_once(__DIR__."/../objects/Profiler.php")) throw new Exception("Compat: Profiler.php is missing. Failed to include Profiler.php");
if (!@include_once(__DIR__."/../html/HTML.php")) throw new Exception("Compat: HTML.php is missing. Failed to include HTML.php");
if (!@include_once(__DIR__."/../HTTPQuery.php")) throw new Exception("Compat: HTTPQuery.php is missing. Failed to include HTTPQuery.php");


class Compat {


// Generates query from given GET parameters
/**
* @param array<string, string|int|bool> $get
*/
public static function generate_query(array $get, mysqli &$db) : string
{
	$genquery = "";

	// QUERYGEN: Character
	if (isset($get['c']))
	{
		if ($get['c'] === '09')
		{
			// Regular expression: Starts with a number
			$genquery .= " (`game_title` REGEXP '^[0-9]' OR `alternative_title` REGEXP '^[0-9]') ";
		}
		elseif ($get['c'] === 'sym')
		{
			// Allowed characters: ' .
			$genquery .= " (`game_title` LIKE '.%' OR `game_title` LIKE '\'%' OR `alternative_title` LIKE '.%' OR `alternative_title` LIKE '\'%') ";
		}
		else
		{
			$s_c = mysqli_real_escape_string($db, (string) $get['c']);
			$genquery .= " (`game_title` LIKE '{$s_c}%' OR `alternative_title` LIKE '{$s_c}%') ";
		}
	}

	// QUERYGEN: Searchbox
	if (isset($get['g']))
	{
		if (!empty($genquery)) { $genquery .= " AND "; }

		$s_g = mysqli_real_escape_string($db, (string) $get['g']);
		$searchbox = " `game_title` LIKE '%{$s_g}%' OR `alternative_title` LIKE '%{$s_g}%' OR `key` = ANY (SELECT `key` FROM `game_id` WHERE `gid` LIKE '%{$s_g}%') ";

		// Initials cache search
		if (strlen((string) $get['g']) >= 2)
		{
			$searchbox .= " OR `game_title` = ANY (SELECT `game_title` FROM `initials_cache` WHERE `initials` LIKE '%{$s_g}%')
			OR `alternative_title` = ANY (SELECT `game_title` FROM `initials_cache` WHERE `initials` LIKE '%{$s_g}%') ";
		}

		$genquery .= " ({$searchbox}) ";
	}

	// QUERYGEN: Search by media type
	if (isset($get['t']))
	{
		if (!empty($genquery)) { $genquery .= " AND "; }

		$s_t = mysqli_real_escape_string($db, (string) $get['t']);
		$genquery .= " ( `key` = ANY (SELECT `key` FROM `game_id` WHERE SUBSTR(`gid`, 1, 1) = '{$s_t}') ) ";
	}

	// QUERYGEN: Search by date
	if (isset($get['d']))
	{
		if (!empty($genquery)) { $genquery .= " AND "; }

		$s_d = mysqli_real_escape_string($db, "{$get['d']}");
		$genquery .= " `last_update` = '{$s_d}' ";
	}

	// QUERYGEN: Search by move support
	if (isset($get['move']))
	{
		if (!empty($genquery)) { $genquery .= " AND "; }

		if ($get['move'] === 0)
			$genquery .= " ( `move` = 0 ) ";
		else
			$genquery .= " ( `move` <> 0 ) ";
	}

	// QUERYGEN: Search by 3D support
	if ($get['3D'] !== 0)
	{
		if (!empty($genquery)) { $genquery .= " AND "; }

		$genquery .= " ( `3d` <> 0 ) ";
	}

	// QUERYGEN: Search by game type
	if ($get['type'] !== 0)
	{
		if (!empty($genquery)) { $genquery .= " AND "; }

		$genquery .= " ( `type` = {$get['type']} ) ";
	}

	// QUERYGEN: Search by network requirement
	if (isset($get['network']))
	{
		if (!empty($genquery)) { $genquery .= " AND "; }

		if ($get['network'] === 0)
			$genquery .= " ( `network` = 0 ) ";
		else
			$genquery .= " ( `network` <> 0 ) ";
	}

	return $genquery;
}


public static function printNetworkSort() : void
{
	global $get;

	$http_query = new HTTPQuery($get);
	$s_query = $http_query->get_except(array("network"));

	$defined = array_key_exists("network", $get);

	// All statuses
	$html_a = new HTMLA("?{$s_query}", "Show all applications", highlightText("All", !$defined));
	$html_a->print();

	echo "•&nbsp;";

	if (!empty($s_query))
		$s_query .= "&";

	$html_a = new HTMLA("?{$s_query}network=1", "Only show entries that require network", highlightText("Yes", $defined && $get['network'] === 1));
	$html_a->print();

	echo "•&nbsp;";

	$html_a = new HTMLA("?{$s_query}network=0", "Only show entries that do not require network", highlightText("No", $defined && $get['network'] === 0));
	$html_a->print();
}


public static function printMoveSort() : void
{
	global $get;

	$http_query = new HTTPQuery($get);
	$s_query = $http_query->get_except(array("move"));

	$defined = array_key_exists("move", $get);

	// All statuses
	$html_a = new HTMLA("?{$s_query}", "Show all applications", highlightText("All", !$defined));
	$html_a->print();

	echo "•&nbsp;";

	if (!empty($s_query))
		$s_query .= "&";

	$html_a = new HTMLA("?{$s_query}move=1", "Only show games with PS Move support", highlightText("Yes", $defined && $get['move'] === 1));
	$html_a->print();

	echo "•&nbsp;";

	$html_a = new HTMLA("?{$s_query}move=0", "Only show games without PS Move support", highlightText("No", $defined && $get['move'] === 0));
	$html_a->print();
}


public static function printTypeSort() : void
{
	global $get;

	$http_query = new HTTPQuery($get);
	$s_query = $http_query->get_except(array("status", "type", "3d"));

	// All statuses
	$html_a = new HTMLA("?{$s_query}", "Show applications from all types", highlightText("All", $get['type'] === 0));
	$html_a->print();

	echo "•&nbsp;";

	if (!empty($s_query))
		$s_query .= "&";

	$html_a = new HTMLA("?{$s_query}type=1", "Only show PS3 Games", highlightText("PS3 Games", $get['type'] === 1));
	$html_a->print();

	echo "•&nbsp;";

	$html_a = new HTMLA("?{$s_query}type=2", "Only show PS3 Apps", highlightText("PS3 Apps", $get['type'] === 2));
	$html_a->print();
}


/**********************
 * Print: Status Sort *
 **********************/
public static function printStatusSort() : void
{
	global $a_status, $scount, $get;

	$http_query = new HTTPQuery($get);

	// Get combined search parameters
	$s_query = $http_query->get_except(array("status"));

	if (!empty($s_query))
		$s_query .= "&";

	// All statuses
	$html_a = new HTMLA("?{$s_query}s=0", "Show games from all statuses", highlightText("All ({$scount["nostatus"][0]})", $get['s'] === 0));
	$html_a->print();

	// Individual statuses
	foreach ($a_status as $id => $status)
	{
		$html_a = new HTMLA("?{$s_query}s={$id}", $status["desc"], highlightText("{$status["name"]} ({$scount["nostatus"][$id]})", $get['s'] === $id));
		$html_a->print();
	}
}


/***************************
 * Print: Results per page *
 ***************************/
public static function printResultsPerPage() : void
{
	global $get;

	$http_query = new HTTPQuery($get);

	echo resultsPerPage($http_query->get_except(array("results")));
}


/***************************
 * Print: Character search *
 ***************************/
public static function printCharSearch() : void
{
	global $get;

	$http_query = new HTTPQuery($get);

	// Get combined search parameters
	$s_query = $http_query->get_except(array("character", "search", "order"));

	if (!empty($s_query))
		$s_query .= '&';

	// Build characters array
	$a_chars[''] = 'All';
	$a_chars['09'] = '0-9';
	foreach (range('a', 'z') as $ch)
		$a_chars[$ch] = strtoupper($ch);
	$a_chars['sym'] = '#';


	$html_div_outer = new HTMLDiv("compat-search-outer");

	foreach ($a_chars as $key => $value)
	{
		$html_div_inner = new HTMLDiv("compat-search-inner");

		$html_div_char = new HTMLDiv("compat-search-character");
		$html_div_char->add_content(highlightText($value, isset($get['c']) && $get['c'] === $key));

		$html_a = new HTMLA("?{$s_query}c={$key}", $value, $html_div_char->to_string());

		$html_div_inner->add_content($html_a->to_string());
		$html_div_outer->add_content($html_div_inner->to_string());
	}

	$html_div_outer->print();
}


/*****************
 * Print: Table  *
 *****************/
public static function printTable() : void
{
	global $games, $error, $a_status, $a_media, $a_flags, $get, $l_orig, $l_title;

	if (!is_null($error))
	{
		switch ($error)
		{
			case "ERROR_QUERY_FAIL":
				echo "<p class=\"compat-tx1-criteria\">Could not fetch contents (e=1), please report to a developer.</p>";
				return;
			case "ERROR_QUERY_EMPTY":
				echo "<p class=\"compat-tx1-criteria\">The Game ID you searched for doesn't exist in our database.</p>";
				return;
			case "ERROR_STATUS_EMPTY":
				echo "<p class=\"compat-tx1-criteria\">No results found for the specified search on the indicated status.</p>";
				return;
			case "ERROR_QUERY_FAIL_2":
				echo "<p class=\"compat-tx1-criteria\">Could not fetch contents (e=2), please report to a developer.</p>";
				return;
		}
	}
	else if (isset($l_orig) && isset($l_title))
	{
		$html_a = new HTMLA("?g=".urlencode($l_title), $l_title, $l_title);

		echo "<p class=\"compat-tx1-criteria\">No results found for <i>{$l_orig}</i>.";
		echo "<br>";
		echo "Displaying results for <b>{$html_a->to_string()}</b></p>";
	}

	if (is_null($games))
		return;

	$http_query = new HTTPQuery($get);

	// Start table
	echo "<div class=\"compat-table-outside\">";
	echo "<div class=\"compat-table-inside\">";

	// Print table headers
	$extra = $http_query->get_except(array("order"));
	$headers = array(
		array(
			'name' => 'Game IDs',
			'class' => 'compat-table-cell compat-table-cell-gameid',
			'sort' => '0'
		),
		array(
			'name' => 'Game Title',
			'class' => 'compat-table-cell',
			'sort' => '2'
		),
		array(
			'name' => 'Status',
			'class' => 'compat-table-cell compat-table-cell-status',
			'sort' => '3'
		),
		array(
			'name' => 'Updated',
			'class' => 'compat-table-cell compat-table-cell-updated',
			'sort' => '4'
		)
	);
	echo getTableHeaders($headers, $extra);

	// Prepare images that will be used
	$html_img_network = new HTMLImg("compat-icon", "/img/icons/compat/onlineonly.png");
	$html_img_network->set_title("Online-only");

	$html_img_move = new HTMLImg("compat-icon", "/img/icons/compat/psmove.png");
	$html_img_move->set_title("PS Move");

	$extra = $http_query->get_except(array("move"));

	// Allow for filter resetting by clicking the icon again
	if (isset($get['move']) && $get['move'] !== 0)
	{
		$html_a_move = new HTMLA("?{$extra}", "", $html_img_move->to_string());
	}
	// Returns clickable icon for move search
	else
	{
		if (!empty($extra))
			$extra .= '&';

		$html_a_move = new HTMLA("?{$extra}move=1", "", $html_img_move->to_string());
	}

	$html_img_3d = new HTMLImg("compat-icon", "/img/icons/compat/3d.png");
	$html_img_3d->set_title("Stereoscopic 3D");

	$extra = $http_query->get_except(array("3D"));

	// Allow for filter resetting by clicking the icon again
	if ($get['3D'] !== 0)
	{
		$html_a_3d = new HTMLA("?{$extra}", "", $html_img_3d->to_string());
	}
	// Returns clickable icon for 3D search
	else
	{
		if (!empty($extra))
			$extra .= '&';

		$html_a_3d = new HTMLA("?{$extra}3D=1", "", $html_img_3d->to_string());
	}

	// Print table body
	foreach ($games as $game)
	{
		// Game media image
		$html_img_media = new HTMLImg("compat-icon-media", $a_media[$game->get_media_id()]["icon"]);
		$html_img_media->set_title($a_media[$game->get_media_id()]["name"]);

		// Allow for filter resetting by clicking the icon again
		if (isset($get['t']) && $get['t'] === strtolower($game->get_media_id()))
		{
			$html_a_media = new HTMLA("?", $a_media[$game->get_media_id()]["name"], $html_img_media->to_string());
		}
		// Returns clickable icon for type (media) search
		else
		{
			$html_a_media = new HTMLA("?t=".strtolower($game->get_media_id()), $a_media[$game->get_media_id()]["name"], $html_img_media->to_string());
		}


		echo "<label for=\"compat-table-checkbox-{$game->key}\" class=\"compat-table-row\">";


		// Cell 1: Regions and GameIDs
		$html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-gameid");
		foreach ($game->game_item as $id => $item)
		{
			if ($id !== 0)
				$html_div_cell->add_content("<br>".PHP_EOL);

			// Game region flag image
			$html_img_region = new HTMLImg("compat-icon-flag", $a_flags[$item->get_region_id()]);
			$html_img_region->set_title($item->game_id);
			$html_div_cell->add_content($html_img_region->to_string());

			// Game ID string
			$html_a_gameid = new HTMLA($item->get_thread_url(), "", $item->game_id);
			$html_a_gameid->set_target("_blank");
			$html_div_cell->add_content($html_a_gameid->to_string());
		}
		$html_div_cell->print();


		// Cell 2: Game Media, Titles and Network
		$html_div_cell = new HTMLDiv("compat-table-cell");

		// Game media image
		$html_div_cell->add_content($html_a_media->to_string());

		if (!is_null($game->get_url_wiki()))
		{
			$html_a_title = new HTMLA($game->get_url_wiki(), $game->wiki_title, $game->title);
			$html_a_title->set_target("_blank");

			$html_div_cell->add_content($html_a_title->to_string());
		}
		else
		{
			$html_div_cell->add_content($game->title);
		}
		if ($game->network === 1)
		{
			$html_div_cell->add_content($html_img_network->to_string());
		}
		if ($game->move === 1)
		{
			$html_div_cell->add_content($html_a_move->to_string());
		}
		if ($game->stereo_3d !== 0)
		{
			$html_div_cell->add_content($html_a_3d->to_string());
		}
		if (!is_null($game->title2))
		{
			$html_div_cell->add_content("<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;({$game->title2})");
		}

		$html_div_cell->print();


		// Cell 3: Status
		$html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-status");

		$html_div_status = new HTMLDiv("txt-compat-status background-status-{$game->status}");
		$html_div_status->add_content($a_status[$game->status]["name"]);

		$html_div_cell->add_content($html_div_status->to_string());

		$html_div_cell->print();


		// Cell 4: Last Test
		$html_div_cell = new HTMLDiv("compat-table-cell compat-table-cell-updated");

		$html_a_date = new HTMLA("?d=".str_replace('-', '', $game->date), "Updated on {$game->date}", $game->date);

		$html_div_cell->add_content($html_a_date->to_string());

		if (!is_null($game->pr))
		{
			$html_a_pr = new HTMLA($game->get_url_pr(), "Tested on PR #{$game->pr}", "#{$game->pr}");
			$html_a_pr->set_target("_blank");

			$html_div_cell->add_content("&nbsp;&nbsp;{$html_a_pr->to_string()}");
		}

		$html_div_cell->print();


		echo "</label>";

		// Dropdown
		echo "<input type=\"checkbox\" id=\"compat-table-checkbox-{$game->key}\">";
		echo "<div class=\"compat-table-row compat-table-dropdown\">";


		// TODO: Better printing of dropdown contents
		$has_updates = false;

		foreach ($game->game_item as $item)
		{
			foreach ($item->tags as $tag)
			{
				if ($has_updates)
					echo "<hr>";

				$has_updates = true;
				$patchset = substr($tag->tag_id, 10);

				echo "<p>Available updates for <b>{$item->game_id}</b>, latest patchset {$patchset}:<br>";

				foreach ($tag->packages as $package)
				{
					$size_mb = round($package->size / 1024 / 1024, 2);
					echo "- <b>Update v{$package->version}</b> ({$size_mb} MB)<br>";

					$changelog = $package->get_main_changelog();

					if (!is_null($changelog))
					{
						echo "<br>";
						echo "<i>";

						$changelog = mb_ereg_replace("\r?\n|\r", '<br>', $changelog);

						if (!$changelog)
						{
							continue;
						}

						if (str_contains($changelog, "<br><br><br>"))
						{
							$changelog = mb_ereg_replace("<br><br><br>", "<br><br>", $changelog);

							if (!$changelog)
							{
								continue;
							}
						}

						if (substr($changelog, -4) === "<br>")
						{
							$changelog = substr($changelog, 0, -4);
						}

						echo $changelog;
						echo "</i>";
					}
				}

				echo "</p>";
			}
		}

		if (!$has_updates)
		{
			echo "<p>This game entry contains no available game updates</p>";
		}

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

	$extra = $http_query->get_except(array());

	$html_div = new HTMLDiv("compat-con-pages");
	$html_div->add_content(getPagesCounter($pages, $currentPage, $extra));
	$html_div->print();
}

/*
return_code
0  - Normal return with results found
1  - Normal return with no results found
2  - Normal return with results found via Levenshtein
-1 - Internal error
-2 - Maintenance
-3 - Illegal search

gameID
	commit
		0 - Unknown / Invalid commit
	status
		Playable/Ingame/Intro/Loadable/Nothing
	date
		yyyy-mm-dd
*/
/**
* @return array<string, mixed> $results
*/
public static function APIv1() : array
{
	global $c_maintenance, $games, $error, $l_title, $a_status, $get;

	// Array to returned, then encoded in JSON
	$results = array();
	$results['return_code'] = 0;

	if ($c_maintenance)
	{
		$results['return_code'] = -2;
		return $results;
	}

	if ($error === "ERROR_QUERY_FAIL" || $error === "ERROR_QUERY_FAIL_2")
	{
		$results['return_code'] = -1;
		return $results;
	}

	if (!isset($get['g']) && isset($_GET['g']))
	{
		$results['return_code'] = -3;
		return $results;
	}

	if (is_null($games))
	{
		$results['return_code'] = 1;
		return $results;
	}

	// No results found for {$l_orig}. Displaying results for {$l_title}.
	if (isset($l_title))
	{
		$results['return_code'] = 2;
		$results['search_term'] = $l_title;
	}

	foreach ($games as $game)
	{
		foreach ($game->game_item as $item)
		{
			$results['results'][$item->game_id] = array(
			'title' => $game->title,
			'alternative-title' => $game->title2,
			'wiki-title' => $game->wiki_title,
			'status' => $a_status[$game->status]['name'],
			'date' => $game->date,
			'thread' => $item->thread_id,
			'commit' => $game->commit,
			'pr' => $game->pr,
			'network' => $game->network
			);
		}
	}

	return $results;
}

} // End of Class
