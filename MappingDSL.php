<?php

class Post
{
    protected $id;
    protected $title;
    protected $content;

    function __construct(array $attrs = [])
    {
        $this->id = $attrs['id'];
        $this->title = $attrs['title'];
        $this->content = $attrs['content'];
    }
}

$core->defineType('post', function($type) {
    $type->title('Post', 'Posts');
    $type->model(Post::class);
});
