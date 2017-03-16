<?php

require __DIR__ . '/../vendor/autoload.php';

$errors = new \Rx\Subject\Subject();

$process = new \Rx\React\ProcessSubject('somebadcommand', $errors);

$process->subscribe(function ($x) {
    echo $x;
});

$errors->subscribe(function (Exception $ex) {
    echo $ex->getMessage();
});

//sh: somebadcommand: command not found
