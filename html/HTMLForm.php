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


class HTMLForm
{
    public string $action;
    public string $method;
    /** @var array<HTMLInput> $inputs **/
    public array  $inputs;
    /** @var array<HTMLSelect> $selects **/
    public array  $selects;
    /** @var array<HTMLButton> $buttons **/
    public array  $buttons;

    function __construct(string $action, string $method)
    {
        $this->action  = $action;
        $this->method  = $method;
        $this->inputs  = array();
        $this->selects = array();
        $this->buttons = array();
    }

    public function add_input(HTMLInput $input) : void
    {
        $this->inputs[] = $input;
    }

    public function add_select(HTMLSelect $select) : void
    {
        $this->selects[] = $select;
    }

    public function add_button(HTMLButton $button) : void
    {
        $this->buttons[] = $button;
    }

    public function to_string() : ?string
    {
        // Unsupported methods
        if ($this->method !== "POST" && $this->method !== "GET")
            return null;

        $ret = "<form action=\"{$this->action}\" method=\"{$this->method}\">".PHP_EOL;

        foreach ($this->inputs as $input)
        {
            $ret .= $input->to_string();
            $ret .= "<br>".PHP_EOL;
        }

        foreach ($this->selects as $select)
        {
            $ret .= $select->to_string();
            $ret .= "<br>".PHP_EOL;
        }

        foreach ($this->buttons as $button)
        {
            $ret .= $button->to_string();
            $ret .= "<br>".PHP_EOL;
        }

        $ret .= "</form>".PHP_EOL;

        return $ret;
    }

    public function print() : void
    {
        echo $this->to_string();
    }
}
