<?php

namespace theses;

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('UTC');

\Dotenv::load(__DIR__ . '/../');

return Bootstrap::buildApplication(__DIR__);
