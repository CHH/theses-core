<?php

namespace theses\plugin;

interface Plugin
{
    static function getPluginInfo();

    function register(\theses\Theses $core);
}
