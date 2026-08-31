<?php use function ANTHeader\create_head3;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
$host = null;
require_once "{$_SERVER['DOCUMENT_ROOT']}/require/header3/head3.php";
if (array_key_exists('host', $_GET)) {
    if (preg_match('/^([a-z0-9.\-A-Z]+)$/D', "{$_GET['host']}")) {
        $host = strtolower("{$_GET['host']}");
    }
}
if (!$host) {
    http_response_code(404);
    exit;
}
header('cache-control: public, max-age=432000');
create_head3($title = "$host's HSTS Preload History (HSTS Preload History)", [
        'stylelinks' => ['/gallery/ddDL-table.css', '/hstspreloadhistory/styles.css'],
        'canonical' => "https://antrequest.nl/hstspreloadhistory/domain/$host/",
        'base' => '/hstspreloadhistory/', 'bread' => [
                ['text' => 'HSTS Preload History', 'href' => '/hstspreloadhistory/'],
                ['text' => "$host's HSTS Preload History", 'href' => "/hstspreloadhistory/domain/$host/"],
        ],
]);
require_once 'opendb.php';
$stmt = getPDO()->prepare("SELECT de.*, c.timestamp AS timestamp FROM domain_events de JOIN " .
        "commits c ON c.sha = de.commit_sha WHERE de.domain = :domain ORDER BY c.timestamp DESC;");
$stmt->bindParam(':domain', $host);
$success = $stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
function processRow($item): void
{
    $commit = "{$item['commit_sha']}";
    $humanCommit = substr($commit, 0, 8);
    $htmlaction = match ("{$item['action']}") {
        'm' => 'Modified',
        'r' => 'Removed',
        'a' => 'Added',
        default => '/*Error*/',
    };
    $subs_class = ($subs = (bool)$item['subdomains']) ? 't' : 'f';
    $rfc3339Date = gmdate('Y-m-d\\TH:i:s\\Z', $item['timestamp']);
    $formattedDate = gmdate('D M, Y-m-d H:i:s', $item['timestamp']);
    echo "<tr><td><a href=/hstspreloadhistory/commit/$commit/>$humanCommit</a><td class"
            . "={$item['action']}>$htmlaction<td>{$item['policy']}<td class=$subs_class>" .
            ($subs ? 'Yes' : 'No') . "<td><time datetime=$rfc3339Date>$formattedDate UTC</time>";
} ?>
<main class=divs>
    <h1><?= $title ?></h1>
    <h2>Current Status</h2>
    <a href="<?= "https://hstspreload.org/?domain=$host#submission-form" ?>"
       target='_blank' referrerpolicy=origin>View Current Status on hstspreload.org</a>
    <h2>History</h2>
    <div class=overflow-x>
        <!--<?= 'TABLE-IF';
        if ($row): ?>-->
        <table><?= '<thead><tr><th scope=col>Commit<th scope=col>Action<th scope=col>' .
            'Policy<th scope=col>Includes SubDomains<th scope=col>Timestamp<tbody>';
            processRow($row);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) processRow($row) ?></table>
        <!--<?= 'ELSE';
        else: echo "--><p>No History seems to be present for this domain.</p>\x3c!--DATAWAY";
        endif ?>-->
    </div>
</main>
<footer class=divs>
    <p>ANTRequest is an unofficial mirror of Chromium's HSTS Preload List source code.
        ANTRequest is not affiliated with Google or Chromium.
    <nav>
        <ul>
            <li><a href=stats.php>View Query Stats</a>
        </ul>
    </nav>
</footer>
