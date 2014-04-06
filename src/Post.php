<?php

namespace theses;

use PHPCR\NodeInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Post
{
    public $id;
    public $slug;
    public $title;
    public $rawContent;
    public $createdAt;
    public $lastModified;
    public $publishedAt;
    public $userProperties = [];

    protected $dispatcher;
    protected $urlGenerator;

    function __construct(
        EventDispatcherInterface $dispatcher,
        callable $urlGenerator = null
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->dispatcher = $dispatcher;
    }

    function __get($property)
    {
        return $this->getCustomProperty($property);
    }

    function getId()
    {
        return $this->id;
    }

    function getTitle()
    {
        return $this->title;
    }

    function getRawContent()
    {
        return $this->rawContent;
    }

    function getContent()
    {
        $event = new event\ConvertPostEvent($this);
        $html = $this->dispatcher->dispatch(Events::POST_CONVERT, $event)->getContent();

        return $html;
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

    function getCustomProperty($property, $default = null)
    {
        $custom = $this->getCustom();

        if (array_key_exists($property, $custom)) {
            return $custom[$property];
        }

        return $default;
    }

    function publish()
    {
        $this->publishedAt = new \DateTime;
        $this->dispatcher->dispatch(Events::POST_PUBLISH, new event\PostEvent($this));
    }

    function unpublish()
    {
        $this->publishedAt = null;
        $this->dispatcher->dispatch(Events::POST_UNPUBLISH, new event\PostEvent($this));
    }

    function getUrl()
    {
        $date = $this->getPublishedAt();

        $permalink = sprintf("/%d/%d/%d/%s",
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            $this->getSlug()
        );

        return $permalink;
    }

    /**
     * Returns the first paragraph of the content
     * @return string
     */
    function getExcerpt()
    {
        $content = $this->getRawContent();
        $content = ltrim($content);

        return substr($content, 0, strpos($content, "\n"));
    }

    function on($event, callable $listener)
    {
        $this->dispatcher->addListener($event, $listener);
        return $this;
    }
}
