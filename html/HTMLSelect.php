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


class HTMLSelect
{
    public string $name;
  /** @var array<HTMLOption> $options **/
    public array  $options;

    function __construct(string $name)
    {
        $this->name = $name;
    $this->options = array();
    }

  public function add_option(HTMLOption $option) : void
    {
        $this->options[] = $option;
    }

    public function to_string() : string
    {
        $ret = "<select name=\"{$this->name}\">".PHP_EOL;

    foreach ($this->options as $option)
    {
      $ret .= $option->to_string();
    }

    $ret .= "</select>".PHP_EOL;

    return $ret;
    }

    public function print() : void
    {
        echo $this->to_string();
    }
}
