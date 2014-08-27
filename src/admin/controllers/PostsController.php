<?php

namespace theses\admin\controllers;

use Silex\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostsController implements ControllerProviderInterface
{
    function connect(Application $app)
    {
        $routes = $app['controllers_factory'];

        $routes->get('/posts', static::class.'::indexAction')->bind('posts');
        $routes->match('/posts/new', static::class.'::newAction')->bind('posts_create');
        $routes->match('/posts/{slug}/edit', static::class.'::editAction')->bind('posts_edit');
        $routes->get('/posts/{id}/delete', static::class.'::deleteAction')->bind('posts_delete');

        return $routes;
    }

    function indexAction(Application $app)
    {
        $posts = $app['posts']->findAll();

        return $app['twig']->render('posts/index.html', [
            'posts' => $posts
        ]);
    }

    function newAction(Application $app, Request $req)
    {
        $post = $app['posts']->create();

        if ($req->isMethod("POST")) {
            $data = $req->get('post');

            $post->modify([
                'title' => $data['title'],
                'content' => $data['content'],
            ]);

            if (isset($data['custom'])) {
                $custom = [];
                foreach ($data['custom'] as $prop) {
                    if (!empty($prop['property'])) {
                        $custom[ $prop['property'] ] = $prop['value'];
                    }
                }

                $post->modify([
                    'userProperties' => $custom
                ]);
            }

            if (empty($data['title'])) {
                $app['session']->getFlashBag()->add('error', 'Post title cannot be empty');
                goto render;
            }

            $app['posts']->insert($post);

            return $app->redirect($app->path('posts_edit', ['slug' => $post->getSlug()]));
        }

        render:
            return $app['twig']->render('posts/create.html', ['post' => $post]);
    }

    function editAction(Application $app, Request $req, $slug)
    {
        $post = $app['posts']->findBySlug($slug);;

        if ($req->isMethod('POST')) {
            $data = $req->get('post');
            $attributes = [];

            if ($req->get('publish') !== null) {
                $post->publish();
            }

            if ($req->get('unpublish') !== null) {
                $post->unpublish();
            }

            if (isset($data['custom'])) {
                $custom = [];
                foreach ($data['custom'] as $prop) {
                    if (!empty($prop['property'])) {
                        $custom[ $prop['property'] ] = $prop['value'];
                    }
                }

                $attributes['userProperties'] = $custom;
            }

            if (empty($data['title'])) {
                $app['session']->getFlashBag()->add('error', 'Post title cannot be empty');
                goto redirect;
            }

            $attributes['title'] = $data['title'];
            $attributes['content'] = $data['content'];
            $attributes['slug'] = (new \Cocur\Slugify\Slugify)->slugify($attributes['title']);

            $post->modify($attributes);

            $app['posts']->update($post);

            redirect:
                return $app->redirect($app->path('posts_edit', ['slug' => $post->getSlug()]));
        }

        return $app['twig']->render('posts/edit.html', [
            'post' => $post
        ]);
    }

    function deleteAction(Application $app, $id)
    {
        $session = $app['theses']['phpcr.session'];

        $post = $session->getNodeByIdentifier($id);
        $post->remove();

        $session->save();

        return $app->redirect($app->path('posts'));
    }
}
