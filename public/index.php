<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$helloWorld = new \App\HelloWorld();
$helloWorld->announce();