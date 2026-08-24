<?php if (array_key_exists('type', $_GET)) {
    // switch ("{$_GET['type']}") { case 'domain':}
    if ("{$_GET['type']}" == 'domain') {
        if (array_key_exists('query', $_GET)) {
            if (preg_match('/^[a-z0-9.\\-A-Z]+$/D', "{$_GET['query']}")) {
                http_response_code(307);
                header("Location: /hstspreloadhistory/domain/{$_GET['query']}/");
                exit;
            }
        }
    }
}
http_response_code(404);
