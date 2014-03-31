<?php

namespace theses;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class Converter implements EventSubscriberInterface
{
    private $post;

    abstract function convert($content);

    function getPost()
    {
        return $this->post;
    }

    function onConvert(event\ConvertPostEvent $event)
    {
        $this->post = $event->getPost();

        $event->setContent($this->convert($event->getContent()));
    }

    static function getSubscribedEvents()
    {
        return [
            Events::POST_CONVERT => 'onConvert'
        ];
    }
}
