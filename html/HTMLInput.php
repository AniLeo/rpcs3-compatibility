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


class HTMLInput
{
    public string $name;
    public string $type;
    public string $value;
    public string $placeholder;
    public string $label;
    public bool   $checked;

    function __construct(string $name, string $type, string $value, string $placeholder)
    {
        $this->name        = $name;
        $this->type        = $type;
        $this->value       = $value;
        $this->placeholder = $placeholder;
        $this->label       = "";
        $this->checked     = false;
    }

    public function set_label(string $label) : void
    {
        $this->label = $label;
    }

    public function set_checked(bool $checked) : void
    {
        $this->checked = $checked;
    }

    public function to_string() : string
    {
        $placeholder = $this->placeholder === "" ? "" : sprintf(" placeholder=\"%s\"", htmlspecialchars($this->placeholder, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5));
        $checked = $this->checked ? " checked" : "";
        $input = sprintf("<input name=\"%s\" type=\"%s\" value=\"%s\"%s%s>".PHP_EOL,
                        htmlspecialchars($this->name, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                        htmlspecialchars($this->type, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                        htmlspecialchars($this->value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                        $placeholder,
                        $checked);

        if ($this->label === "")
            return $input;

        return sprintf("<label class=\"debug-entry-flag\">%s%s</label>".PHP_EOL,
                    htmlspecialchars($this->label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
                    $input);
    }
}
