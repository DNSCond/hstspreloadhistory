<?php use function ANTHeader\create_head3;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
header('cache-control: public, max-age=432000');
require_once "{$_SERVER['DOCUMENT_ROOT']}/require/header3/head3.php";
create_head3($title = 'HSTSPreload List Query tool', [
        'stylelinks' => ['/gallery/ddDL-table.css', '/hstspreloadhistory/styles.css'],
        'base' => '/hstspreloadhistory/', 'bread' => [
                ['text' => 'HSTS Preload History', 'href' => '/hstspreloadhistory/'],
                ['text' => 'Query Queue Stats', 'href' => '/hstspreloadhistory/stats.php'],
        ], 'desc' => 'View the Stats of the HSTSPreload List Query tool.',
]);
function constructTimeTag(string|int $time): string
{
    if (is_string($time)) {
        $time = strtotime($time);
    }
    $rfc3339Date = gmdate('Y-m-d\\TH:i:s\\Z', $time);
    $formattedDate = gmdate('D M, Y-m-d H:i:s', $time);
    return "<time datetime=$rfc3339Date>$formattedDate UTC</time>";
}

[$manualUpdatedAt, $manualSha] = explode(',',
        file_get_contents(__DIR__ . '/manual-update-at.txt'));
?>
<main class=divs>
    <dl>
        <dt>Manual Updated At
        <dd><?= constructTimeTag($manualUpdatedAt) ?></dd>
        <dt>Manual Updated Last Sha
        <dd><?= $manualSha ?></dd>
        <dt>Automatically Updated At
        <dd>N/A</dd>
        <dt>Automatically Updated Last Sha
        <dd>N/A</dd>
    </dl>
</main>