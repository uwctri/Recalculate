<?php

try {
    $result = $module->process(true);
    RestUtility::sendResponse(200, $result, 'json');
} catch (Exception $ex) {
    RestUtility::sendResponse(400, $ex->getMessage());
}