<?php

namespace theses\event;

use theses\Post;

class ConvertPostEvent extends PostEvent
{
    protected $content;

    function __construct(Post $post)
    {
        parent::__construct($post);
        $this->content = $post->getContent();
    }

    function setContent($content)
    {
        $this->content = $content;
    }

    function getContent()
    {
        return $this->content;
    }
}
