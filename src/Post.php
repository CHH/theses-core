<?php

namespace theses;

use PHPCR\NodeInterface;

class Post
{
    public $id;
    public $slug;
    public $title;
    public $content;
    public $createdAt;
    public $lastModified;
    public $publishedAt;
    public $userProperties = [];

    function __construct()
    {
    }

    function getId()
    {
        return $this->id;
    }

    function getTitle()
    {
        return $this->title;
    }

    function getContent()
    {
        return $this->content;
    }

    function getSlug()
    {
        return $this->slug;
    }

    function getLastModified()
    {
        return $this->lastModified;
    }

    function getCreatedAt()
    {
        return $this->createdAt;
    }

    function getPublishedAt()
    {
        return $this->publishedAt;
    }

    function getCustom()
    {
        return $this->userProperties;
    }

    function publish()
    {
        $this->publishedAt = new \DateTime;
    }

    function unpublish()
    {
        $this->publishedAt = null;
    }
}
