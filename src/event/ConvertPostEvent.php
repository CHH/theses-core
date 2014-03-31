<?php

namespace theses\event;

use Symfony\Component\EventDispatcher\Event;
use theses\Post;

class ConvertPostEvent extends Event
{
    protected $post;
    protected $content;

    function __construct(Post $post)
    {
        $this->post = $post;
        $this->content = $post->getRawContent();
    }

    function setContent($content)
    {
        $this->content = $content;
    }

    function getContent()
    {
        return $this->content;
    }

    function getPost()
    {
        return $this->post;
    }
}
