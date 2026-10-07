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
if (!@include_once(__DIR__."/../objects/Game.php"))         throw new Exception("Compat: Failed to include objects/Game.php");
if (!@include_once(__DIR__."/../objects/Build.php"))        throw new Exception("Compat: Failed to include objects/Build.php");
if (!@include_once(__DIR__."/../objects/MyBBThread.php"))   throw new Exception("Compat: Failed to include objects/MyBBThread.php");
if (!@include_once(__DIR__."/../objects/MyBBPost.php"))     throw new Exception("Compat: Failed to include objects/MyBBPost.php");
if (!@include_once(__DIR__."/../services/Compat.php"))      throw new Exception("Compat: Failed to include services/Compat.php");
if (!@include_once(__DIR__."/../services/GitHub.php"))      throw new Exception("Compat: Failed to include services/GitHub.php");
if (!@include_once(__DIR__."/../services/PlayStation.php")) throw new Exception("Compat: Failed to include services/PlayStation.php");
if (!@include_once(__DIR__."/../services/Mediawiki.php"))   throw new Exception("Compat: Failed to include services/Mediawiki.php");
if (!@include_once(__DIR__."/../html/HTML.php"))            throw new Exception("Compat: Failed to include html/HTML.php");


/*
TODO: Login system
TODO: Log commands with run time and datetime
*/

Profiler::start_profiler("Profiler: Panel");

function runFunctions() : void
{
    global $get, $a_panel;

    if (array_key_exists($get['a'], $a_panel))
    {
        Profiler::add_data("Panel: Run Function ({$get['a']})");

        $ret = runFunctionWithCronometer($get['a']);

        if (!empty($a_panel[$get['a']]['success']))
        {
            printf("<p><b>Debug mode:</b> %s (%fs).</p>", $a_panel[$get['a']]['success'], $ret);
        }
    }
}

// TODO: Refactoring
function checkInvalidThreads() : void
{
    global $a_status, $get;

    Profiler::add_data("Panel: Check Threads");

    $invalid = 0;
    $output = "";
    $where = "";
    $a_threads = array();

    // Generate WHERE condition for our query
    // Includes all forum IDs for the game status sections
    $where = '';
    foreach ($a_status as $id => $status)
    {
        foreach ($status["fid"] as $fid)
        {
            if (!empty($where))
                $where .= "||";

            $where .= " `fid` = {$fid} ";
        }
    }

    $db = get_database("compat");
    $db_forums = get_database("forums");

    $q_threads = mysqli_query($db_forums, "SELECT `tid`, `subject`, `fid`
    FROM `rpcs3_forums`.`mybb_threads`
    WHERE ({$where}) AND `visible` > 0 AND `closed` NOT LIKE 'moved%'; ");

    $q_games = mysqli_query($db, "SELECT * FROM `game_list`; ");

    mysqli_close($db_forums);

    if (is_bool($q_games) || is_bool($q_threads))
    {
        mysqli_close($db);
        print("<b>Error while fetching the game or thread list</b>");
        return;
    }

    $a_games = Game::query_to_games($q_games, $db);
    mysqli_close($db);

    while ($row = mysqli_fetch_object($q_threads))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row, "tid") ||
            !property_exists($row, "subject") ||
            !property_exists($row, "fid"))
        {
            print("<b>Error while fetching the thread list</b>");
            return;
        }

        $html_subject = htmlspecialchars($row->subject, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
        $thread = new MyBBThread($row->tid, $row->fid, $row->subject);

        if (is_null($thread->get_game_id()))
        {
            $html_a = new HTMLA($thread->get_thread_url(), "", $html_subject);
            $html_a->set_target("_blank");

            $output .= "<p>Thread {$html_a->to_string()} is incorrectly formatted.</p>";
            continue;
        }

        $a_threads[$row->tid] = $thread;
    }

    foreach ($a_games as $game)
    {
        foreach ($game->game_item as $item)
        {
            $html_title = htmlspecialchars($item->title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

            if (!array_key_exists($item->thread_id, $a_threads))
            {
                $output .= "<p class='debug-tvalidity-list'>";
                $output .= "Thread {$item->thread_id}: [{$item->game_id}] {$html_title} doesn't exist.<br>";
                $output .= "</p>";
                $invalid++;
            }
            elseif ($item->game_id !== $a_threads[$item->thread_id]->get_game_id())
            {
                $html_a = new HTMLA($a_threads[$item->thread_id]->get_thread_url(), "", "{$item->thread_id}: [{$item->game_id}] {$html_title}");
                $html_a->set_target("_blank");


                $output .= "<p class='debug-tvalidity-list'>";
                $output .= "Thread {$html_a->to_string()} is incorrect.<br>";
                $output .= "- Compat: {$html_title} [{$item->game_id}]<br>";
                $output .= "- Forums: {$a_threads[$item->thread_id]->get_game_id()}<br>";
                $output .= "</p>";
                $invalid++;
            }
            elseif ($game->status !== $a_threads[$item->thread_id]->get_sid())
            {
                $html_a = new HTMLA($a_threads[$item->thread_id]->get_thread_url(), "", "{$item->thread_id}: [{$item->game_id}] {$html_title}");
                $html_a->set_target("_blank");

                $output .= "<p class='debug-tvalidity-list'>";
                $output .= "Thread {$html_a->to_string()} is in the wrong section.<br>";
                $output .= "- Compat: {$a_status[$game->status]['name']} <br>";
                $output .= "- Forums: {$a_status[$a_threads[$item->thread_id]->get_sid()]['name']}<br>";
                $output .= "</p>";
                $invalid++;
            }
        }
    }

    if ($invalid > 0)
    {
        printf("<p class='debug-tvalidity-title color-red background-red'>Attention required! %d Invalid threads detected</p>",
               $invalid);

        if ($get['a'] === "checkInvalidThreads")
            print($output);
    }
    else
    {
        print("<p class='debug-tvalidity-title color-green background-green'>No invalid threads detected</p>");
    }
}

// TODO: Refactoring
function compatibilityUpdater() : void
{
    global $a_histdates, $a_status, $get;
    
    Profiler::add_data("Panel: Compatibility Updater");

    set_time_limit(300);
    $db = get_database("compat");
    $db_forums = get_database("forums");

    // Timestamp of the penultimate list update
    end($a_histdates);
    $lastkey = key($a_histdates);
    reset($a_histdates);
    $ts_lastupdate = strtotime("{$a_histdates[$lastkey][0]['y']}-{$a_histdates[$lastkey][0]['m']}-{$a_histdates[$lastkey][0]['d']}");

    if (is_bool($ts_lastupdate))
    {
        return;
    }

    // Generate WHERE condition for our query
    // Includes all forum IDs for the game status sections
    $where = '';
    foreach ($a_status as $id => $status)
    {
        foreach ($status["fid"] as $fid)
        {
            if (!empty($where))
                $where .= "||";

            $where .= " `fid` = {$fid} ";
        }
    }

    // Cache commits
    Profiler::add_data("Panel: Fetch Commits");
    $a_commits = array();
    $q_commits = mysqli_query($db, "SELECT `pr`, `commit`, `version`, `merge_datetime`
                                    FROM `builds`
                                    WHERE `merge_datetime` > CURDATE() - INTERVAL 1 YEAR 
                                    ORDER BY `merge_datetime` DESC;");

    if (is_bool($q_commits))
    {
        print("<b>Error while fetching the builds list</b>");
        return;
    }

    while ($row = mysqli_fetch_object($q_commits))
    {
        // This should be unreachable unless the database structure is damaged
        if (!property_exists($row, "pr") ||
            !property_exists($row, "commit") ||
            !property_exists($row, "version") ||
            !property_exists($row, "merge_datetime"))
        {
            print("<b>Error while fetching the builds list</b>");
            return;
        }

        $a_commits[$row->commit] = array("pr" => $row->pr, 
                                         "version" => $row->version, 
                                         "merge" => date('Y-m-d', strtotime($row->merge_datetime)));
    }

    Profiler::add_data("Panel: Fetch Threads");
    $a_threads = fetch_compatibility_threads($db_forums, $where, $ts_lastupdate);
    if (is_null($a_threads))
    {
        mysqli_close($db);
        mysqli_close($db_forums);
        return;
    }

    Profiler::add_data("Panel: Fetch Posts");
    $a_posts = fetch_compatibility_posts($db_forums, $a_threads, $ts_lastupdate);
    if (is_null($a_posts))
    {
        mysqli_close($db);
        mysqli_close($db_forums);
        return;
    }

    Profiler::add_data("Panel: Fetch Games");
    // Get all games in the database
    $q_games = mysqli_query($db, "SELECT * FROM `game_list`;");

    if (is_bool($q_games))
    {
        print("<b>Error while fetching the games list</b>");
        return;
    }

    $a_games = Game::query_to_games($q_games, $db);

    // Script data
    $a_inserts = array();
    $a_updates = array();
    // Visited Game IDs
    $a_gameIDs = array();
    
    // game_id => array(Game, thread_id)
    $a_games_by_id = array();

    foreach ($a_games as $game)
    {
        foreach ($game->game_item as $item)
        {
            $a_games_by_id[$item->game_id] = array($game, $item->thread_id);
        }
    }

    // Printed after the scan
    $log_warn = "";
    $log_new = "";
    $log_mov = "";

    print("<div class=\"compat-text\">"); // Start log

    Profiler::add_data("Panel: Check Threads");
    foreach ($a_threads as $thread)
    {
        $game_id = $thread->get_game_id();

        // If a thread for this Game ID was already visited, continue to next thread entry
        if ($game_id !== null && isset($a_gameIDs[$game_id]))
        {
            $html_subject = htmlspecialchars($thread->subject, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
            $html_a = new HTMLA($thread->get_thread_url(), "", $html_subject);
            $html_a->set_target("_blank");

            printf("Error! A thread for %s was already visited. %s is a duplicate.<br><br>",
                   $thread->get_game_id(),
                   $html_a->to_string());
            continue;
        }

        if ($game_id !== null)
            $a_gameIDs[$game_id] = true;

        // Thread ID validation
        // If game entry exists, get game data
        $tid = null;
        $cur_game = null;
        if ($game_id !== null && isset($a_games_by_id[$game_id]))
        {
            $cur_game = $a_games_by_id[$game_id][0];
            $tid = $a_games_by_id[$game_id][1];
        }

        // New thread is a duplicate of an existing one
        if (!is_null($tid) && $tid != $thread->tid)
        {
            $html_a_thread1 = new HTMLA($thread->get_thread_url(), "", (string) $thread->tid);
            $html_a_thread2 = new HTMLA("https://forums.rpcs3.net/thread-{$tid}.html", "", (string) $tid);
            $html_a_thread1->set_target("_blank");
            $html_a_thread2->set_target("_blank");

            $html_subject = htmlspecialchars($thread->subject, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

            printf("<span style='color:red'><b>Error!</b> %s (%s) duplicated thread of (%s).</span><br><br>",
                   $html_subject,
                   $html_a_thread1->to_string(),
                   $html_a_thread2->to_string());
            continue;
        }

        // New thread for the Game ID
        if (is_null($tid))
        {
            $a_inserts[$thread->tid] = array(
                'thread' => $thread,
                'commit' => null,
                'pr' => null,
                'last_update' => null
            );

            // Verify posts
            foreach ($a_posts[(int) $thread->tid] as $post)
            {
                MyBBThread::remove_post_quotes($post->message);

                foreach ($a_commits as $commit => $value)
                {
                    // Skip posts with no commits
                    if (!str_contains($post->message, substr((string) $commit, 0, 8)))
                    {
                        continue;
                    }

                    $a_inserts[$thread->tid]['thread']->set_post_id($post->pid);
                    $a_inserts[$thread->tid]['commit'] = (string) $commit;
                    $a_inserts[$thread->tid]['pr'] = $value["pr"];
                    $a_inserts[$thread->tid]['version'] = $value["version"];
                    $a_inserts[$thread->tid]['last_update'] = date('Y-m-d', $post->dateline);
                    $a_inserts[$thread->tid]['author'] = $post->username;
                    break 2;
                }
            }

            $html_a = new HTMLA($a_inserts[$thread->tid]['thread']->get_thread_url(), "", (string) $a_inserts[$thread->tid]['thread']->tid);
            $html_a->set_target("_blank");

            // Invalid report found
            if (is_null($a_inserts[$thread->tid]['commit']) ||
                is_null($a_inserts[$thread->tid]['pr']) ||
                is_null($a_inserts[$thread->tid]['version']) /*||
                !str_starts_with($a_inserts[$thread->tid]['last_update'], "2025-10")*/)
            {
                printf("<b>Error!</b> Invalid report found on tid: %s<br><br>",
                       $html_a->to_string());
                unset($a_inserts[$thread->tid]);
                continue;
            }

            // Attachment checks
            $attachment_warning = check_report_attachments(
                $db_forums,
                (string) $a_inserts[$thread->tid]['thread']->pid,
                $html_a->to_string(),
                $a_status[$thread->get_sid()]['name']
            );

            if (!is_null($attachment_warning))
            {
                $log_warn .= sprintf("<div><b>Warning:</b> %s</div>", $attachment_warning);
                unset($a_inserts[$thread->tid]);
                continue;
            }

            // Valid report found
            $version       = $a_inserts[$thread->tid]['version'];
            $commit        = $a_inserts[$thread->tid]['commit'];
            $date_commit   = $a_commits[$commit]["merge"];

            $log_new .= sprintf("<div><b>New:</b> %s (tid: %s, author: %s, type: %s)<br>",
                                htmlspecialchars($thread->subject, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                                $html_a->to_string(),
                                htmlspecialchars($a_inserts[$thread->tid]['author'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                                $thread->get_game_type_name());
            $log_new .= sprintf("- Status: <span style='color:#%s'>%s (%s)</span><br>",
                                $a_status[$thread->get_sid()]['color'],
                                $a_status[$thread->get_sid()]['name'],
                                $a_inserts[$thread->tid]['last_update']);
            $log_new .= sprintf("- Version: <span class='color-green'>%s</span> (%s)<br>",
                                $version,
                                $date_commit);
            $log_new .= "</div>";

        }
        // TODO: Distinguish different game ID / thread IDs within the update object
        else if ($tid == $thread->tid)
        {
            // This game entry was already checked before in this script
            // Update with the new information
            if (array_key_exists($cur_game->key, $a_updates))
            {
                // Update status
                if ($a_updates[$cur_game->key]['status'] < $thread->get_sid())
                {
                    $html_a = new HTMLA($a_updates[$cur_game->key]['thread']->get_thread_url(), "", (string) $a_updates[$cur_game->key]['thread']->tid);
                    $html_a->set_target("_blank");

                    printf("<b>Error!</b> %s [%s] cannot be downgraded to a lower status (%s) as another report for the same game entry (tid:%s) upgrades it to a newer status (%s)<br><br>",
                           $a_updates[$cur_game->key]['game_title'],
                           $thread->get_game_id(),
                           $a_status[$a_updates[$cur_game->key]['status']]['name'],
                           $html_a->to_string(),
                           $a_status[$thread->get_sid()]['name']);
                    continue;
                }
                elseif (is_null($a_updates[$cur_game->key]['commit']))
                {
                    print("<b>Error!</b> Unreachable code <br><br>");
                    dumpVar($a_updates[$cur_game->key]);
                    continue;
                }
            }
            else
            {
                $a_updates[$cur_game->key] = array(
                    'attachments' => null,
                    'thread' => $thread,
                    'game_title' => $cur_game->title(),
                    'status' => $thread->get_sid(),
                    'commit' => null,
                    'pr' => null,
                    'version' => null,
                    'last_update' => null,
                    'action' => 'mov',
                    'old_date' => $cur_game->date,
                    'old_status' => $cur_game->status,
                    'author' => '',
                    'pid' => 0
                );
            }

            // Verify posts
            foreach ($a_posts[(int) $thread->tid] as $post)
            {
                MyBBThread::remove_post_quotes($post->message);

                foreach ($a_commits as $commit => $value)
                {
                    // Skip posts with no commits
                    if (!str_contains($post->message, substr((string) $commit, 0, 8)))
                    {
                        continue;
                    }

                    // If current commit is newer than the previously recorded one, replace
                    // TODO: Check distance between commit date and post here
                    // TODO: Remove quoted sections from $post->message before checking it
                    if (is_null($a_updates[$cur_game->key]['commit']) ||
                        strtotime($a_commits[$a_updates[$cur_game->key]['commit']]["merge"]) < strtotime($value["merge"]))
                    {
                        $s_pid = mysqli_real_escape_string($db_forums, (string) $post->pid);
                        $a_updates[$cur_game->key]['thread']->set_post_id($post->pid);
                        $a_updates[$cur_game->key]['commit'] = (string) $commit;
                        $a_updates[$cur_game->key]['pr'] = $value["pr"];
                        $a_updates[$cur_game->key]['version'] = $value["version"];
                        $a_updates[$cur_game->key]['last_update'] = date('Y-m-d', $post->dateline);
                        $a_updates[$cur_game->key]['author'] = $post->username;
                        $a_updates[$cur_game->key]['pid'] = $post->pid;
                        break 2;
                    }
                }
            }

            // If the new date is older than the current date (meaning there's no valid report post)
            // Or no new pr was found
            // then ignore this entry and continue
            if (is_null($a_updates[$cur_game->key]['commit']) ||
                is_null($a_updates[$cur_game->key]['pr']) ||
                is_null($a_updates[$cur_game->key]['version']) ||
                /*!str_starts_with(($a_updates[$cur_game->key]['last_update']), "2025-10") ||*/
                strtotime($cur_game->date) >= strtotime($a_updates[$cur_game->key]['last_update']))
            {
                unset($a_updates[$cur_game->key]);
                continue;
            }

            // Link to the forum post
            $html_a = new HTMLA($a_updates[$cur_game->key]['thread']->get_thread_url(), "", (string) $a_updates[$cur_game->key]['pid']);
            $html_a->set_target("_blank");

            // Attachment checks
            $attachment_warning = check_report_attachments(
                $db_forums,
                (string) $a_updates[$cur_game->key]['pid'],
                $html_a->to_string(),
                $a_status[$thread->get_sid()]['name']
            );

            if (!is_null($attachment_warning))
            {
                $log_warn .= sprintf("<div><b>Warning:</b> %s</div>", $attachment_warning);
                unset($a_updates[$cur_game->key]);
                continue;
            }

            // Check if the distance between commit date and post is bigger than 4 weeks
            if (strtotime($a_updates[$cur_game->key]['last_update']) - strtotime($a_commits[$a_updates[$cur_game->key]['commit']]["merge"]) > 4 * 604804)
            {
                $log_warn .= sprintf("<div><b>Warning:</b> Distance between commit and post dates bigger than 4 weeks on post %s</div>",
                                      $html_a->to_string());
            }

            // Green for existing commit, Red for non-existing commit
            $old_status_commit = !is_null($cur_game->pr) ? 'color-green' : 'color-red';
            $version           = $a_updates[$cur_game->key]['version'];
            $commit            = $a_updates[$cur_game->key]['commit'];
            $date_commit       = "({$a_commits[$commit]["merge"]})";
            $old_version        = !is_null($cur_game->version) ? $cur_game->version : "null";

            $log_mov .= sprintf("<div><b>Mov:</b> %s - %s (pid: %s, author: %s, type: %s)<br>",
                                $thread->get_game_id(),
                                htmlspecialchars($cur_game->title(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                                $html_a->to_string(),
                                htmlspecialchars($a_updates[$cur_game->key]['author'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                                $thread->get_game_type_name());
            $log_mov .= sprintf("- Status: <span style='color:#%s'>%s (%s)</span> <-- <span style='color:#%s'>%s (%s)</span><br>",
                                $a_status[$thread->get_sid()]['color'],
                                $a_status[$thread->get_sid()]['name'],
                                $a_updates[$cur_game->key]['last_update'],
                                $a_status[$cur_game->status]['color'],
                                $a_status[$cur_game->status]['name'],
                                $cur_game->date);
            $log_mov .= sprintf("- Version: <span class='color-green'>%s</span> %s <-- <span class='%s'>%s</span> (%s)<br>",
                                $version,
                                $date_commit,
                                $old_status_commit,
                                $old_version,
                                $cur_game->date);
            $log_mov .= "</div>";
        }
    }

    Profiler::add_data("Panel: Print Log");
    if (!empty($log_warn))
    {
        print("<div class=\"compat-profiler\"><div class=\"text-bold\">Warnings</div>");
        print($log_warn);
        print("</div>");
    }
    if (!empty($log_new))
    {
        print("<div class=\"compat-profiler\"><div class=\"text-bold\">New reports</div>");
        print($log_new);
        print("</div>");
    }
    if (!empty($log_mov))
    {
        print("<div class=\"compat-profiler\"><div class=\"text-bold\">Updated reports</div>");
        print($log_mov);
        print("</div>");
    }
    print("</div>"); // End log

    if (isset($_POST['updateCompatibility']))
    {
        // Permissions: Update
        if (array_search("debug.update", $get['w']) === false)
        {
            print("<p><b>Error:</b> You do not have permission to issue database update commands</p>");
            mysqli_close($db);
            mysqli_close($db_forums);
            return;
        }

        $cr = curl_init();

        /*
            Inserts
        */
        foreach ($a_inserts as $tid => $game)
        {
            // Should be unreachable
            if (is_null($game['thread']->get_game_id()) ||
                is_null($game['thread']->get_game_title()) ||
                is_null($game['commit']))
            {
                continue;
            }

            $db_game_id = mysqli_real_escape_string($db, $game['thread']->get_game_id());
            $db_game_title = mysqli_real_escape_string($db, $game['thread']->get_game_title());

            // Insert new entry on the game list
            mysqli_query($db, "INSERT INTO `game_list` (`build_commit`, `pr`, `version`, `last_update`, `status`, `type`) VALUES
            ('".mysqli_real_escape_string($db, $game['commit'])."',
            '".mysqli_real_escape_string($db, $game['pr'])."',
            '".mysqli_real_escape_string($db, $game['version'])."',
            '{$game['last_update']}',
            {$game['thread']->get_sid()},
            {$game['thread']->get_game_type()});");

            $key = mysqli_insert_id($db);
           
            if ($key === 0)
            {
                exit("[COMPAT] Error while trying to fetch key from game list");
            }

            // Insert Game and Thread IDs on the ID table
            mysqli_query($db, "INSERT INTO `game_id` (`key`, `gid`, `tid`, `game_title`) VALUES ({$key}, '{$db_game_id}', {$tid}, '{$db_game_title}'); ");

            // Cache the updates for the new ID
            cache_game_updates($cr, $db, $game['thread']->get_game_id());

            // Log change to game history
            mysqli_query($db, "INSERT INTO `game_history` (`game_key`, `new_gid`, `new_status`, `new_date`) VALUES
            ({$key}, '{$db_game_id}', '{$game['thread']->get_sid()}', '{$game['last_update']}');");
        }

        /*
            Updates
        */
        foreach ($a_updates as $key => $game)
        {
            // Should be unreachable
            if (is_null($game['commit']))
            {
                continue;
            }

            // Update entry parameters on game list
            mysqli_query($db, "UPDATE `game_list` SET
            `build_commit` = '".mysqli_real_escape_string($db, $game['commit'])."',
            `pr`           = '".mysqli_real_escape_string($db, $game['pr'])."',
            `version`      = '".mysqli_real_escape_string($db, $game['version'])."',
            `last_update`  = '{$game['last_update']}',
            `status`       = '{$game['status']}'
            WHERE `key` = {$key};");

            // Log change to game history
            mysqli_query($db, "INSERT INTO `game_history` (`game_key`, `old_status`, `old_date`, `new_status`, `new_date`) VALUES
            ({$key}, '{$game['old_status']}', '{$game['old_date']}', '{$game['status']}', '{$game['last_update']}'); ");
        }

        // Recache initials cache
        cache_initials();
        // Recache game count
        cache_game_count();
    }
    else
    {
        // Display update button
        $form = new HTMLForm("", "POST");
        $form->add_button(new HTMLButton("updateCompatibility", "submit", "Update Compatibility"));
        $form->print();
    }

    mysqli_close($db);
    mysqli_close($db_forums);
}

function refreshBuild() : void
{
    $pr = (isset($_POST["pr"]) && is_numeric($_POST["pr"])) ? (int) $_POST["pr"] : 0;

    $form = new HTMLForm("", "POST");
    $form->add_input(new HTMLInput("pr", "text", "{$pr}", "Pull Request"));
    $form->add_button(new HTMLButton("refreshBuild", "submit", "Refresh"));
    $form->print();

    if (!isset($_POST["refreshBuild"]))
        return;

    cache_build($pr);
}

function mergeGames() : void
{
    global $a_status, $get;

    $gid1 = isset($_POST['gid1']) && is_string($_POST['gid1']) ? trim($_POST['gid1']) : "";
    $gid2 = isset($_POST['gid2']) && is_string($_POST['gid2']) ? trim($_POST['gid2']) : "";

    $form = new HTMLForm("", "POST");
    $form->add_input(new HTMLInput("gid1", "text", $gid1, "Game ID 1"));
    $form->add_input(new HTMLInput("gid2", "text", $gid2, "Game ID 2"));
    $form->add_button(new HTMLButton("mergeRequest", "submit", "Merge Request"));
    $form->add_button(new HTMLButton("mergeConfirm", "submit", "Merge Confirm"));
    $form->print();

    if (!isset($_POST['mergeRequest']) && !isset($_POST['mergeConfirm']))
        return;

    if (!isGameID($gid1))
    {
        print("<p><b>Error:</b> Game ID 1 is not a valid Game ID</p>");
        return;
    }
    if (!isGameID($gid2))
    {
        print("<p><b>Error:</b> Game ID 2 is not a valid Game ID</p>");
        return;
    }

    $db = get_database("compat");

    $s_gid1 = mysqli_real_escape_string($db, $gid1);
    $s_gid2 = mysqli_real_escape_string($db, $gid2);

    $q_game1 = mysqli_query($db, "SELECT * 
                                  FROM `game_list` 
                                  WHERE `key` IN(SELECT `key` FROM `game_id` WHERE `gid` = '{$s_gid1}') 
                                  LIMIT 1;");
    if (is_bool($q_game1) || mysqli_num_rows($q_game1) === 0)
    {
        print("<p><b>Error:</b> Game ID 1 could not be found</p>");
        return;
    }
    $game1 = Game::query_to_games($q_game1, $db)[0];

    $q_game2 = mysqli_query($db, "SELECT * 
                                  FROM `game_list` 
                                  WHERE `key` IN(SELECT `key` FROM `game_id` WHERE `gid` = '{$s_gid2}') 
                                  LIMIT 1;");
    if (is_bool($q_game2) || mysqli_num_rows($q_game2) === 0)
    {
        print("<p><b>Error:</b> Game ID 2 could not be found</p>");
        return;
    }
    $game2 = Game::query_to_games($q_game2, $db)[0];

    if ($game1->key === $game2->key)
    {
        print("<p><b>Error:</b> Both Game IDs belong to the same Game Entry</p>");
        return;
    }

    if (substr($game1->game_item[0]->game_id, 0, 1) !== substr($game2->game_item[0]->game_id, 0, 1))
    {
        print("<p><b>Error:</b> Cannot merge entries of different Game Media</p>");
        return;
    }

    print("<p>"); // Start paragraph

    $others1 = $game1->other_titles();
    $others2 = $game2->other_titles();
    $alternative1 = $others1 !== array() ? '(other: '.implode(', ', $others1).')' : '';
    $alternative2 = $others2 !== array() ? '(other: '.implode(', ', $others2).')' : '';

    $pr1 = !is_null($game1->pr) ? $game1->pr : "null";
    $pr2 = !is_null($game2->pr) ? $game2->pr : "null";

    printf("<b>Game 1: %s %s (status: <span style='color:#%s'>%s</span>, pr: %s, date: %s, type: %s)</b><br>",
           htmlspecialchars($game1->title(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
           htmlspecialchars($alternative1, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
           $a_status[$game1->status]['color'],
           $a_status[$game1->status]['name'],
           $pr1,
           $game1->date,
           $game1->type);

    foreach ($game1->game_item as $item)
    {
        printf("- %s (tid: %d)<br>",
               $item->game_id,
               $item->thread_id);
    }

    print("<br>");

    printf("<b>Game 2: %s %s (status: <span style='color:#%s'>%s</span>, pr: %s, date: %s, type: %s)</b><br>",
           htmlspecialchars($game2->title(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
           htmlspecialchars($alternative2, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
           $a_status[$game2->status]['color'],
           $a_status[$game2->status]['name'],
           $pr2,
           $game2->date,
           $game2->type);

    foreach ($game2->game_item as $item)
    {
        printf("- %s (tid: %d)<br>",
               $item->game_id,
               $item->thread_id);
    }

    print("<br>");

    $time1 = strtotime($game1->date);
    $time2 = strtotime($game2->date);

    // If the most recent entry doesn't have a PR and the oldest one has
    // allow for 1 month tolerance to use the older key if the difference between them is 1 month at max
    if (is_null($game1->pr) && !is_null($game2->pr))
        $time1 -= 2678400;
    if (!is_null($game1->pr) && is_null($game2->pr))
        $time2 -= 2678400;

    if ($time1 === $time2 && $game1->pr !== $game2->pr)
    {
        // If the update date is the same, pick the one with the most recent PR
        // TODO: Check for null cases
        $new = $game1->pr > $game2->pr ? $game1 : $game2;
        $old = $game1->pr > $game2->pr ? $game2 : $game1;
    }
    else if ($game1->pr === $game2->pr)
    {
        // If PRs are the same, pick the one with the oldest update date
        $new = $time1 < $time2 ? $game1 : $game2;
        $old = $time1 < $time2 ? $game2 : $game1;
    }
    else
    {
        // If the update date differs, pick the one with the most recent update date
        $new = $time1 > $time2 ? $game1 : $game2;
        $old = $time1 > $time2 ? $game2 : $game1;
    }

    // Update: Set both game keys to the same previous picked key
    if (isset($_POST['mergeConfirm']))
    {
        // Permissions: debug.update
        if (array_search("debug.update", $get['w']) === false)
        {
            print("<p><b>Error:</b> You do not have permission to issue database update commands</p>");
            mysqli_close($db);
            return;
        }

        // Copy network flag to new entry if necessary
        if ($game1->network !== $game2->network)
        {
            $network = (string) ($game1->network === 0 ? $game2->network : $game1->network);
            mysqli_query($db, "UPDATE `game_list` SET `network` = '".mysqli_real_escape_string($db, $network)."' WHERE `key`='{$new->key}';");
        }

        // Copy 3d flag to new entry if necessary
        if ($game1->stereo_3d !== $game2->stereo_3d)
        {
            $stereo_3d = (string) ($game1->stereo_3d === 0 ? $game2->stereo_3d : $game1->stereo_3d);
            mysqli_query($db, "UPDATE `game_list` SET `3d` = '".mysqli_real_escape_string($db, $stereo_3d)."' WHERE `key`='{$new->key}';");
        }

        // Copy move flag to new entry if necessary
        if ($game1->move !== $game2->move)
        {
            $move = (string) ($game1->move === 0 ? $game2->move : $game1->move);
            mysqli_query($db, "UPDATE `game_list` SET `move` = '".mysqli_real_escape_string($db, $move)."' WHERE `key`='{$new->key}';");
        }

        // Move IDs from the older entry to the newer entry
        mysqli_query($db, "UPDATE `game_id` SET `key`='{$new->key}' WHERE (`key`='{$old->key}');");
        // Reassociate old entry history updates to the newer entry
        mysqli_query($db, "UPDATE `game_history` SET `game_key`='{$new->key}' WHERE (`game_key`='{$old->key}');");
        // Delete older entry
        mysqli_query($db, "DELETE FROM `game_list` WHERE (`key`='{$old->key}');");

        // Recache game count
        cache_game_count();

        print("<b>Games successfully merged!</b><br>");
    }

    print("</p>"); // End paragraph
}

function flag_build_as_broken() : void
{
    global $get;

    $pr = (isset($_POST["pr"]) && is_numeric($_POST["pr"])) ? (int) $_POST["pr"] : 0;

    $form = new HTMLForm("", "POST");
    $form->add_input(new HTMLInput("pr", "text", "{$pr}", "Pull Request"));
    $form->add_button(new HTMLButton("flag_build_as_broken", "submit", "Flag as broken"));
    $form->add_button(new HTMLButton("unflag_build_as_broken", "submit", "Unflag as broken"));
    $form->print();

    if ($pr === 0)
        return;

    if (!isset($_POST["flag_build_as_broken"]) && !isset($_POST["unflag_build_as_broken"]))
        return;

    $db = get_database("compat");
    $q_build = mysqli_query($db, "SELECT * 
                                  FROM `builds` 
                                  WHERE `pr` = '{$pr}' 
                                  LIMIT 1; ");

    if (is_bool($q_build) || mysqli_num_rows($q_build) === 0)
        return;

    $build = Build::query_to_builds($q_build)[0];

    $build_time = strtotime($build->merge);

    // Cannot flag builds older than a week as broken
    if (time() - $build_time > 7 * 24 * 60 * 60)
    {
        print("<b>The build is older than a week. Cannot flag as broken.</b><br>");
        mysqli_close($db);
        return;
    }

    // Permissions: Update
    if (array_search("debug.update", $get['w']) === false)
    {
        print("<p><b>Error:</b> You do not have permission to issue database update commands</p>");
        mysqli_close($db);
        return;
    }

    if (isset($_POST["flag_build_as_broken"]))
    {
        mysqli_query($db, "UPDATE `builds` SET `broken` = 1 WHERE `pr` = {$pr}; ");
        printf("<p>Flagged <b>%d</b> as broken.</p>",
               $pr);
    }
    else if (isset($_POST["unflag_build_as_broken"]))
    {
        mysqli_query($db, "UPDATE `builds` SET `broken` = 0 WHERE `pr` = {$pr}; ");
        printf("<p>Unflagged <b>%d</b> as broken.</p>",
               $pr);
    }

    mysqli_close($db);
}

function export_build_backup() : void
{
    $form = new HTMLForm("", "POST");
    $select = new HTMLSelect("os");
    $select->add_option(new HTMLOption("win",   "Windows (x64)"));
    $select->add_option(new HTMLOption("linux", "Linux (x64)"));
    $select->add_option(new HTMLOption("mac",   "macOS (x64)"));
    $select->add_option(new HTMLOption("win-arm64", "Windows (arm64)"));
    $select->add_option(new HTMLOption("linux-arm64", "Linux (arm64)"));
    $select->add_option(new HTMLOption("mac-arm64", "macOS (arm64)"));
    $form->add_select($select);

    $db = get_database("compat");

    $q_version_tags = mysqli_query($db, "SELECT DISTINCT(SUBSTRING_INDEX(`version`, '-', 1)) as `version_tag` 
                                         FROM `builds` 
                                         ORDER BY `merge_datetime` DESC");

    if (is_bool($q_version_tags))
    {
        print("<b>Error while fetching the builds tag list</b>");
        mysqli_close($db);
        return;
    }

    $select_tag = new HTMLSelect("tag");
    $select_tag->add_option(new HTMLOption("all", "All"));
    while ($row = mysqli_fetch_object($q_version_tags))
    {
        $select_tag->add_option(new HTMLOption($row->version_tag, $row->version_tag));
    }

    $form->add_select($select_tag);
    $form->add_button(new HTMLButton("backupRequest", "submit", "Backup Request"));
    $form->print();

    if (!isset($_POST['os']) || !is_string($_POST['os']) || !in_array($_POST['os'], array("win", "linux", "mac", "win-arm64", "linux-arm64", "mac-arm64")))
    {
        return;
    }

    $s_os = mysqli_real_escape_string($db, $_POST['os']);
    $s_url_prefix = mysqli_escape_string($db, "https://github.com/RPCS3/rpcs3-binaries-{$_POST['os']}/releases/download/build-");

    $s_rowname = mysqli_real_escape_string($db, "filename_".str_replace('-', '_', $_POST['os']));

    $optional_tag = "";
    if (isset($_POST['tag']) && is_string($_POST['tag']) && $_POST['tag'] != 'all')
    {
        $s_tag = mysqli_real_escape_string($db, $_POST['tag']);
        $optional_tag = " AND `version` LIKE '{$s_tag}%' ";
    }

    $q_builds = mysqli_query($db, "SELECT CONCAT('{$s_url_prefix}', `commit`, '/', `{$s_rowname}`) AS `url`
                                   FROM `builds`
                                   WHERE `{$s_rowname}` IS NOT NULL AND `{$s_rowname}` <> '' {$optional_tag}
                                   ORDER BY `merge_datetime` DESC;");

    mysqli_close($db);

    if (is_bool($q_builds))
    {
        print("<b>Error while fetching the builds list</b>");
        return;
    }

    print("<p>");
    print("Save the following builds list to a text file and run command<br>");
    print("<b>cat builds.txt | parallel --gnu \"wget -nc -nv --content-disposition --trust-server-names {}\"</b><br><br>");
    print("</p>");

    print("<p>");
    while ($row = mysqli_fetch_object($q_builds))
    {
        print($row->url."<br>");
    }
    print("</p>");
}

function check_duplicated_entries() : void
{
    global $get;

    Profiler::add_data("Panel: Check Duplicates");

    $db = get_database("compat");

    // Digital (N) and Disc (B) are compared separately
    $q_entries = mysqli_query($db, "SELECT i.`key`,
                                           SUBSTR(i.`gid`, 1, 1) AS `gid_type`,
                                           (SELECT i2.`game_title` FROM `game_id` i2
                                                WHERE i2.`key` = i.`key`
                                                ORDER BY i2.`gid` ASC LIMIT 1) AS `game_title`
                                    FROM `game_id` i
                                    WHERE SUBSTR(i.`gid`, 1, 1) IN ('N', 'B')
                                    GROUP BY i.`key`, `gid_type`");

    mysqli_close($db);

    if (is_bool($q_entries))
    {
        return;
    }

    // gid_type => normalized title => titles
    $groups = array();

    while ($row = mysqli_fetch_object($q_entries))
    {
        if (!property_exists($row, "gid_type") || !property_exists($row, "game_title") || !is_string($row->game_title))
            continue;

        $normalized = mb_strtolower(normalize_search($row->game_title));

        if ($normalized === "")
            continue;

        $groups[$row->gid_type][$normalized][] = $row->game_title;
    }

    $output = "";
    $count = 0;

    foreach ($groups as $gid_type => $titles)
    {
        foreach ($titles as $entries)
        {
            if (count($entries) < 2)
                continue;

            $count++;

            $other = "";
            $unique = array_values(array_unique($entries));
            $title = $unique[0];
            $search = urlencode($title);
            $html_title = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

            $html_a = new HTMLA("compatibility?g={$search}&type=0#jump", $title, $html_title);
            $html_a->set_target("_blank");

            if (count($unique) > 1)
            {
                $others = array();
                foreach (array_slice($unique, 1) as $other)
                    $others[] = htmlspecialchars($other, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

                $other = " (".implode(", ", $others).")";
            }

            $output .= sprintf("<p>- [%s] %s%s</p>",
                               $gid_type,
                               $html_a->to_string(),
                               $other);
        }
    }


    if ($count === 0)
    {
        print("<p class='debug-tvalidity-title color-green background-green'>No duplicated threads detected</p>");
    }
    else
    {
        printf("<p class='debug-tvalidity-title color-red background-red'>Attention required! %d Duplicated entries detected</p>", $count);

        if ($get['a'] === "check_duplicated_entries")
        {
            print($output);
        }
    }
}

function check_report_attachments(mysqli $db_forums, string $pid, string $post_url, string $status_name) : ?string
{
    $s_pid = mysqli_real_escape_string($db_forums, $pid);
    $q_attachments = mysqli_query($db_forums, "SELECT `filename`
                                               FROM `rpcs3_forums`.`mybb_attachments`
                                               WHERE `pid` = '{$s_pid}'");

    if (is_bool($q_attachments))
        return "Error while fetching attachments list";

    $attachment_count = 0;
    $log_detected = false;

    while ($attachment = mysqli_fetch_object($q_attachments))
    {
        if (!property_exists($attachment, "filename"))
            return "Error while fetching attachments list";

        $attachment_count++;

        if (str_ends_with($attachment->filename, ".gz") || str_ends_with($attachment->filename, ".7z"))
            $log_detected = true;
    }

    if ($attachment_count === 0)
        return "No attachments found on post {$post_url}, skipping";

    if (!$log_detected)
        return "No log file attachments found on post {$post_url}, skipping";

    if ($status_name == "Playable" && $attachment_count < 4)
        return "Attempted update to Playable with less than 4 attachments on post {$post_url}, only {$attachment_count} uploaded";

    if ($status_name != "Playable" && $attachment_count < 2)
        return "Attempted update to {$status_name} with less than 2 attachments on post {$post_url}, only {$attachment_count} uploaded";

    return null;
}

/**
 * @return array<MyBBThread>|null
 */
function fetch_compatibility_threads(mysqli $db_forums, string $where, int $ts_lastupdate) : ?array
{
    $a_threads = array();
    $q_threads = mysqli_query($db_forums, "SELECT `tid`, `fid`, `subject`, `lastpost`, `visible`
    FROM `rpcs3_forums`.`mybb_threads`
    WHERE ({$where}) AND
    `lastpost` > {$ts_lastupdate} AND
    `visible` > 0 AND
    `closed` NOT LIKE 'moved%';");

    if (is_bool($q_threads))
    {
        print("<b>Error while fetching the threads list</b>");
        return null;
    }

    while ($row = mysqli_fetch_object($q_threads))
    {
        if (!property_exists($row, "tid") ||
            !property_exists($row, "fid") ||
            !property_exists($row, "subject") ||
            !property_exists($row, "lastpost") ||
            !property_exists($row, "visible"))
        {
            continue;
        }

        $thread = new MyBBThread($row->tid, $row->fid, $row->subject);
        $html_subject = htmlspecialchars($row->subject, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

        if (is_null($thread->get_game_id()) || is_null($thread->get_game_title()))
        {
            $html_a = new HTMLA($thread->get_thread_url(), "", $html_subject);
            $html_a->set_target("_blank");

            printf("<span style='color:red'>Error! Invalid new thread found. See: %s.<br><br></span>",
                   $html_a->to_string());
            continue;
        }
        else if ($row->visible <= 0)
        {
            $html_a = new HTMLA($thread->get_thread_url(), "", $html_subject);
            $html_a->set_target("_blank");

            printf("<span style='color:red'>Error! The new thread for %s is not visible (%s). See: %s.<br><br></span>",
                   $thread->get_game_id(),
                   $row->visible,
                   $html_a->to_string());
            continue;
        }

        $a_threads[] = $thread;
    }

    return $a_threads;
}

/**
 * @param array<int, MyBBThread> $a_threads
 * @return array<int, array<MyBBPost>>|null
 */
function fetch_compatibility_posts(mysqli $db_forums, array $a_threads, int $ts_lastupdate) : ?array
{
    $posts_by_tid = array();

    if ($a_threads === array())
        return $posts_by_tid;

    $tids = array();
    foreach ($a_threads as $thread)
    {
        $tids[] = (int) $thread->tid;
    }
    $tids = implode(",", array_values(array_unique($tids)));

    $q_posts = mysqli_query($db_forums, "SELECT `tid`, `pid`, `dateline`, `message`, `username`
    FROM `rpcs3_forums`.`mybb_posts`
    WHERE `tid` IN ({$tids})
        AND `dateline` > {$ts_lastupdate}
    ORDER BY `tid` ASC, `pid` DESC;");

    if (is_bool($q_posts))
    {
        print("<b>Error while fetching posts list</b>");
        return null;
    }

    while ($post = mysqli_fetch_object($q_posts))
    {
        if (!property_exists($post, "tid") ||
            !property_exists($post, "pid") ||
            !property_exists($post, "dateline") ||
            !property_exists($post, "message") ||
            !property_exists($post, "username"))
        {
            print("<b>Error while fetching posts list</b>");
            return null;
        }

        $posts_by_tid[(int) $post->tid][] = new MyBBPost(
            (int) $post->tid,
            (int) $post->pid,
            (int) $post->dateline,
            (string) $post->message,
            (string) $post->username
        );
    }

    return $posts_by_tid;
}