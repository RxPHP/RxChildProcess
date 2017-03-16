<?php

require __DIR__ . '/../vendor/autoload.php';

$process = new \Rx\React\ProcessSubject('echo foo');

$process->subscribe(function ($x) {
    echo $x;
});
