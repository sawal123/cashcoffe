<?php
$log = file(__DIR__ . '/storage/logs/laravel.log');
$errors = array_values(preg_grep('/local\.ERROR:/', $log));
echo end($errors);
