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
if (!@include_once(__DIR__."/../functions.php"))
    throw new Exception("Compat: functions.php is missing. Failed to include functions.php");


class MyBBThread
{
    public int    $tid;
    public int    $fid;
    public ?int   $pid;
    public string $subject;

    function __construct(int $tid, int $fid, string $subject)
    {
        $this->tid     = $tid;
        $this->fid     = $fid;
        $this->pid     = null;
        $this->subject = $subject;
    }

    public function get_sid() : ?int
    {
        switch ($this->fid)
        {
            // Playable
            case 5:
            case 26:
                return 1;

            // Ingame
            case 6:
            case 27:
                return 2;

            // Intro
            case 7:
            case 28:
                return 3;

            // Loadable
            case 8:
            case 29:
                return 4;

            // Nothing
            case 9:
            case 30:
                return 5;

            default:
                return null;
        }
    }

    public function get_game_type() : ?int
    {
        switch ($this->fid)
        {
            // PS3 Game
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
                return 1;

            // PS3 App
            case 26:
            case 27:
            case 28:
            case 29:
            case 30:
                return 2;

            default:
                return null;
        }
    }

    public function get_game_type_name() : ?string
    {
        switch ($this->get_game_type())
        {
            case 1:
                return "PS3 Game";
            case 2:
                return "PS3 App";
            default:
                return "Invalid";
        }
    }

    public function get_game_id() : ?string
    {
        // Thread title is not big enough to have a valid game id
        if (strlen($this->subject) < 14)
        {
            return null;
        }

        // Dash between game title and game id
        if (substr($this->subject, -13, 1) === '-' && substr($this->subject, -14, 1) === ' ')
        {
            return null;
        }

        // No spacing between game title and game id
        if (substr($this->subject, -12, 1) !== ' ')
        {
            return null;
        }

        // No square brackets around game id
        if (substr($this->subject, -11, 1) !== '[' || substr($this->subject, -1, 1) !== ']')
        {
            return null;
        }

        $game_id = substr($this->subject, -10, 9);

        // Invalid game id
        if (!isGameID($game_id))
        {
            return null;
        }

        return $game_id;
    }

    public function get_game_title() : ?string
    {
        // Extract game title from thread title
        $ret = substr($this->subject, 0, -12);

        // Check if thread title is invalid
        if (substr($ret, -1) === ' ' || substr($ret, -1) === '-')
        {
            return null;
        }

        return $ret;
    }

    public function get_thread_url() : string
    {
        if (!is_null($this->pid))
        {
            return "https://forums.rpcs3.net/thread-{$this->tid}-post-{$this->pid}.html#pid{$this->pid}";
        }

        return "https://forums.rpcs3.net/thread-{$this->tid}.html";
    }

    public function set_post_id(int $pid) : void
    {
        $this->pid = $pid;
    }

    public static function remove_post_quotes(string &$message) : void
    {
        $result = "";
        $stack = array();
        $length = strlen($message);

        $quote_start = "[quote=";
        $quote_end   = "[/quote]";

        for ($pos = 0; $pos < $length; $pos++)
        {
            if (substr($message, $pos, strlen($quote_start)) == $quote_start)
            {
                $stack[] = $pos;
                $pos += 6;
            }
            else if (substr($message, $pos, strlen($quote_end)) == $quote_end)
            {
                if (!empty($stack))
                {
                    array_pop($stack);
                }
                $pos += 7;
            }
            else if (empty($stack))
            {
                $result .= $message[$pos];
            }
        }

        $message = $result;
    }
}