<?php
$lines = file('storage/logs/laravel.log');
$lastLines = array_slice($lines, -100);
file_put_contents('public/last_logs.json', json_encode(array_values($lastLines)));
