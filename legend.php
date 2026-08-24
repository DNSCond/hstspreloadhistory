<?php use function Helpers\htmlspecialchars12;
use function ANTHeader\create_head3;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
header('cache-control: public, max-age=30');
require_once "{$_SERVER['DOCUMENT_ROOT']}/require/header3/head3.php";
create_head3($title = 'HSTS Preload History Legend', [
        'stylelinks' => ['/hstspreloadhistory/styles.css'],
        'bread' => [
                ['text' => 'HSTS Preload History', 'href' => '/hstspreloadhistory/'],
                ['text' => 'Legend', 'href' => '/hstspreloadhistory/legend.php'],
        ],
]) ?>
<main class=divs>
    <h1><?= $title ?></h1>
    <dl>
        <dt>Policy
        <dd><p>the policy under which the domain is part of the preload list. This field is used for list maintenance.
            <dl>
                <dt>"<code>test</code>"
                <dd>test domains
                <dt>"<code>google</code>"
                <dd>Google-owned sites.
                <dt>"<code>custom</code>"
                <dd>entries without includeSubdomains or with HPKP.
                <dt>"<code>bulk-legacy</code>"
                <dd>bulk entries preloaded before Chrome 50.
                <dt>"<code>bulk-18-weeks</code>"
                <dd>bulk entries with max-age >= 18 weeks (Chrome 50-63).
                <dt>"<code>bulk-1-year</code>"
                <dd>bulk entries with max-age >= 1 year (after Chrome 63).
                <dt>"<code>public-suffix</code>"
                <dd>public suffixes (e.g. TLDs or other public suffix list entries) preloaded at the owner's request.
                <dt>"<code>public-suffix-requested</code>"
                <dd>domains under a public suffix that have been preloaded at the request of the the public suffix owner
                    (e.g. the registry for the TLD).
            </dl>
</main>
