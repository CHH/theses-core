<?php

namespace theses;

class Site
{
    protected $app;

    function __construct(Theses $app)
    {
        $this->app = $app;
    }

    function getPosts()
    {
        return $this->app['posts'];
    }
}
