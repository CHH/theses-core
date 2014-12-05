<?php

namespace theses\event;

use Symfony\Component\EventDispatcher\Event;
use Silex\Application;

class ApplicationBootEvent extends Event
{
    private $app;

    function __construct(Application $app)
    {
        $this->app = $app;
    }

    function getApplication()
    {
        return $this->app;
    }
}
