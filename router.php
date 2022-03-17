<?php

if ($_POST['route'] == "recalculate" || $_POST['route'] == "preview") {
    $module->recalculate($_POST['fields'], $_POST['events'], $_POST['records'], $_POST['route'] == "preview");
    exit;
}

header("HTTP/1.1 400 Bad Request");
header('Content-Type: application/json; charset=UTF-8');
die(json_encode("This route does not exist."));
