<?php

require __DIR__ . '/../vendor/autoload.php';

$process = new \Rx\React\ProcessSubject('echo "name:"; read name; echo $name');

$process->subscribe(new \Rx\Observer\CallbackObserver(function ($x) use ($process){
    echo $x;

    //write to the process
    $process->onNext("Bob\n");
}));
