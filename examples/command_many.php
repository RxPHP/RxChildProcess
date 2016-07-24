<?php

require __DIR__ . '/../vendor/autoload.php';

$ls1 = new \Rx\React\ProcessSubject('ls ' . __DIR__);
$ls2 = new \Rx\React\ProcessSubject('ls ' . __DIR__ . '/../');

$ls1
    ->merge($ls2)
    ->subscribe(new \Rx\Observer\CallbackObserver(function ($x) {
        echo $x;
    }));
