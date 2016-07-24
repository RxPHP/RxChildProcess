<?php

require __DIR__ . '/../vendor/autoload.php';

$process = new \Rx\React\ProcessSubject('echo foo');

$process->subscribe(new \Rx\Observer\CallbackObserver(function ($x) {
    echo $x;
}));
