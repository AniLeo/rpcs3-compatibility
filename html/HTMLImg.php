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


class HTMLImg
{
    public  string $class;
    public  string $src;
    public ?string $title;

    function __construct(string $class, string $src)
    {
        $this->class = $class;
        $this->src   = $src;
        $this->title = null;
    }

    public function set_title(string $title) : void
    {
        $this->title = $title;
    }

    public function to_string() : string
    {
        $ret = sprintf("<img class=\"%s\" ", 
                       htmlspecialchars($this->class, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5));

        if (!is_null($this->title))
        {
            $ret .= sprintf(" title=\"%s\" ",
                            htmlspecialchars($this->title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5));
        }

        $ret .= sprintf("src=\"%s\">".PHP_EOL,
                        htmlspecialchars($this->src, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5));

        return $ret;
    }

    public function print() : void
    {
        echo $this->to_string();
    }
}
