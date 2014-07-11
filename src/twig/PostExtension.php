<?php

namespace theses\twig;

use Twig_SimpleFunction as SimpleFunction;
use theses\Post;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use theses\PostRepository;

class PostExtension extends \Twig_Extension
{
    private $posts;

    function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    function getName()
    {
        return 'post';
    }

    function getFunctions()
    {
        return [
            new SimpleFunction('post_permalink', [$this, 'permalink']),
            new SimpleFunction('post_content', [$this, 'content'], ['is_safe' => ['html']]),
            new SimpleFunction('post_excerpt', [$this, 'excerpt'], ['is_safe' => ['html']]),
        ];
    }

    function content(Post $post)
    {
        return $this->posts->render($post);
    }

    function excerpt(Post $post)
    {
        return $post->getExcerpt();
    }

    function permalink(Post $post)
    {
        $date = $post->getPublishedAt();

        $permalink = sprintf("/%d/%d/%d/%s",
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            $post->getSlug()
        );

        return $permalink;
    }
}
