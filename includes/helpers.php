<?php
function json_error($message) {
    header('Content-Type: application/json');
    return json_encode([
        'status' => 'error',
        'message' => $message
    ]);
}

function json_success($message) {
    header('Content-Type: application/json');
    return json_encode([
        'status' => 'success',
        'message' => $message
    ]);
}
?> 