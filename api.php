<?php

$pid = $_POST['pid'];
$client_token = $_POST['token'];
$server_token = $module->getProjectSetting('api_token', $pid);

if (!empty($client_token) && !empty($server_token) && ($client_token == $server_token)) {
    if (!defined('PROJECT_ID')) {
        define('PROJECT_ID', $pid);
    }
    $module->recalculate($_POST['fields'], $_POST['events'], $_POST['records'], $pid);
    exit;
}

header("HTTP/1.1 400 Bad Request");
header('Content-Type: application/json; charset=UTF-8');
die(json_encode(isset($pid) ? "Incorrect or missing api token" : "Missing project id"));
