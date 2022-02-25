<?php

if ($_POST['route'] == "recalculate") {
    $module->recalculate($_POST['fields'], $_POST['events'], $_POST['records'], $_GET['pid']);
} else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json; charset=UTF-8');
    die(json_encode("This route does not exist."));
}
