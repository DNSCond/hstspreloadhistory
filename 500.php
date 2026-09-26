<?php use function ANTHeader\create_head3;

http_response_code(500);
header('cache-control: public, max-age=0');
require_once "{$_SERVER['DOCUMENT_ROOT']}/require/header3/head3.php";
create_head3($title = '500 Internal Server Error' . "\x20HSTS Preload History", [
        'base' => '/hstspreloadhistory/',
]) ?>
<div class=divs>
    <h1><?= $title ?></h1>
</div>
