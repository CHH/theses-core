<?php

namespace theses;

interface SettingsManagerInterface
{
    function set($setting, $value = null);
    function get($setting, $default = null);
    function has($setting);
    function all();
    function save();
}
