<?php

namespace theses;

use cebe\markdown\Markdown;

class MarkdownConverter extends Converter
{
    protected $markdown;

    function __construct(Markdown $markdown)
    {
        $this->markdown = $markdown;
    }

    function convert($content)
    {
        return $this->markdown->parse($content);
    }
}
