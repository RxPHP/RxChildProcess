<?php

use Rx\Observable;
use Rx\React\ProcessSubject;
use Rx\Thruway\Client;

require __DIR__ . '/../vendor/autoload.php';

$top = (new ProcessSubject('top -l 1'))->repeatWhen(function (Observable $attempts) {
    return $attempts->delay(3000);
});


(new Client('ws://127.0.0.1:9090', 'realm1'))->publish('top.of.the.morning', $top);

$top->subscribe('print_r');
