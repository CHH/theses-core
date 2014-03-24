<?php

$app = new \Pimple;

$app['db'] = $app->share(function() {
    $options = parse_url($_SERVER['DATABASE_URL']);

    if ($options['scheme'] === 'postgres') {
        $driver = 'pdo_pgsql';
    } else {
        $driver = 'pdo_' . $options['scheme'];
    }

    return \Doctrine\DBAL\DriverManager::getConnection(array(
        'driver'    => $driver,
        'host'      => $options['host'],
        'user'      => @$options['user'],
        'password'  => @$options['pass'],
        'dbname'    => trim($options['path'], '/'),
    ));
});

$app['phpcr.workspace'] = 'default';

$app['phpcr.repository'] = $app->share(function () use ($app) {
    $factory = new \Jackalope\RepositoryFactoryDoctrineDBAL();
    $repository = $factory->getRepository(array('jackalope.doctrine_dbal_connection' => $app['db']));

    return $repository;
});

$app['phpcr.session'] = function () use ($app) {
    return $app['phpcr.repository']->login(new \PHPCR\SimpleCredentials(null, null), $app['phpcr.workspace']);
};

$app['site'] = $app->share(function () use ($app) {
    return new theses\Site($app);
});

return $app;
