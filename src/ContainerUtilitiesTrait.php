<?php

namespace theses;

trait ContainerUtilitiesTrait
{
    function extendShared($id, callable $factory)
    {
        $this[$id] = $this->share($this->extend($id, $factory));
    }
}
