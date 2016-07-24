<?php

require __DIR__ . '/../vendor/autoload.php';

$errors = new \Rx\Subject\Subject();

$process = new \Rx\React\ProcessSubject('somebadcommand', $errors);

$process->subscribe(new \Rx\Observer\CallbackObserver(function ($x) {
    echo $x;
}));

$errors->subscribe(new \Rx\Observer\CallbackObserver(function (Exception $ex) {
    echo $ex->getMessage();
}));

//sh: somebadcommand: command not found
