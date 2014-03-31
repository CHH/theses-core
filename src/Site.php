<?php

namespace theses;

class Site
{
    protected $app;
    protected $posts;

    function __construct(Theses $app, PostRepository $posts)
    {
        $this->app = $app;
        $this->posts = $posts;
    }

    function getPosts()
    {
        return $this->posts;
    }
}
