<?php

namespace theses;

abstract class Events
{
    const POST_CONVERT = 'post.convert';
    const POST_PUBLISH = 'post.publish';
    const POST_UNPUBLISH = 'post.unpublish';
    const POST_BEFORE_INSERT = 'post.before_insert';
    const POST_INSERT = 'post.insert';
    const POST_BEFORE_SAVE = 'post.before_save';
    const POST_AFTER_SAVE = 'post.after_save';
}
