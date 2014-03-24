<?php

namespace theses;

use Stack\UrlMap;
use Stack\Builder;

class Bootstrap
{
    static function buildApplication($appRoot)
    {
        $shared = require($appRoot . '/shared/services.php');

        $stack = new Builder;
        $stack->push('\Stack\UrlMap', [
            '/admin' => \Stack\lazy(function() use ($appRoot, $shared) {
                $admin = require($appRoot . '/admin/app.php');
                $admin['shared'] = $admin->share(function() use ($shared) {
                    return $shared;
                });

                $admin->boot();

               return $admin;
            })
        ]);

        $frontend = require($appRoot . '/frontend/app.php');
        $frontend['shared'] = $frontend->share(function() use ($shared) {
            return $shared;
        });
        $frontend->boot();

        return $stack->resolve($frontend);
    }
}
