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


class HTMLA
{
    public  string $href;
    public  string $title;
    public  string $content;
    public ?string $target;
    public ?string $rel;

    function __construct(string $href, string $title, string $content)
    {
        $this->href    = $href;
        $this->title   = $title;
        $this->content = $content;
        $this->target  = null;
        $this->rel     = null;
    }

    public function set_target(string $target) : void
    {
        $this->target = $target;
    }

    public function set_rel(string $rel) : void
    {
        $this->rel = $rel;
    }

    public function to_string() : string
    {
        $ret = sprintf("<a href=\"%s\" ", 
                       htmlspecialchars($this->href, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5));

        if (!is_null($this->target))
        {
            $ret .= sprintf("target=\"%s\" ", 
                            htmlspecialchars($this->target, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5));
        }

        if (!is_null($this->rel))
        {
            $ret .= sprintf("rel=\"%s\" ",
                            htmlspecialchars($this->rel, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5));
        }

        $ret .= sprintf("title=\"%s\">%s</a>".PHP_EOL, 
                        htmlspecialchars($this->title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                        $this->content);

        return $ret;
    }

    public function print() : void
    {
        echo $this->to_string();
    }
}
