<?php

namespace theses;

use PHPCR\NodeInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Post
{
    protected $id;
    protected $slug;
    protected $title;
    protected $content;
    protected $createdAt;
    protected $lastModified;
    protected $publishedAt;
    protected $userProperties = [];

    protected $dispatcher;
    protected $urlGenerator;

    // Todo: remove dependency on Event Dispatcher by shifting responsibility to
    //       PostRepository class, or a possible additional helper class. Only publish/unpublish
    //       are currently left using the Event Dispatcher.
    function __construct(
        array $attributes = [],
        EventDispatcherInterface $dispatcher
    )
    {
        if ($attributes) {
            $this->modify($attributes);
        }

        $this->dispatcher = $dispatcher;
    }

    function __get($property)
    {
        return $this->getCustomProperty($property);
    }

    function modify(array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            if (property_exists($this, $attribute)) {
                $this->$attribute = $value;
            }
        }
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

    /**
     * @deprecated
     */
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
        $content = $this->getContent();
        $content = ltrim($content);

        return substr($content, 0, strpos($content, "\n"));
    }

    function on($event, callable $listener)
    {
        $this->dispatcher->addListener($event, $listener);
        return $this;
    }
}
