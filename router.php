<?php

try {
    $result = $module->process();
} catch (Exception $ex) {
    RestUtility::sendResponse(400, $ex->getMessage());
}
RestUtility::sendResponse(200, $result);