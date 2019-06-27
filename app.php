#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\Command\UnfollowNonFollowersCommand;
use App\Command\NotificationsCommand;
use App\MyApp;

$app = new MyApp("Instagram Cli Tool by <Besemuna Samuel Adjei>", "1.0");
$app->add(new UnfollowNonFollowersCommand);
$app->add(new NotificationsCommand);
$app->run();