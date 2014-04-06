<?php

namespace theses\event;

use Symfony\Component\EventDispatcher\Event;
use theses\Post;

class PostEvent extends Event
{
    protected $post;

    function __construct(Post $post)
    {
        $this->post = $post;
    }

    function getPost()
    {
        return $this->post;
    }
}
