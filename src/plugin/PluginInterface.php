<?php

namespace theses\plugin;

interface PluginInterface
{
    static function getPluginInfo();

    function register(\theses\Theses $core);
}
