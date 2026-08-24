<?php use function ANTHeader\create_head3;
use function Helpers\htmlspecialchars12;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
$commit = null;
require_once "{$_SERVER['DOCUMENT_ROOT']}/require/header3/head3.php";
if (array_key_exists('commit', $_GET)) {
    if (preg_match('/^([a-f0-9]+)$/D', "{$_GET['commit']}")) $commit = "{$_GET['commit']}";
}
if (!$commit) {
    http_response_code(404);
    exit;
}
$humanCommit = substr($commit, 0, 8);
create_head3($title = "Chromium Commit \"$humanCommit\" (HSTS Preload History)", [
        'stylelinks' => ['/gallery/ddDL-table.css', '/hstspreloadhistory/styles.css'],
        'canonical' => "https://antrequest.nl/hstspreloadhistory/commit/$commit/",
        'bread' => [
                ['text' => 'HSTS Preload History', 'href' => '/hstspreloadhistory/'],
                ['text' => "Chromium Commit \"$humanCommit\"", 'href' => "/hstspreloadhistory/commit/$commit/"],
        ],
]);
require_once __DIR__ . '/phpmodule/opendb.php';
$stmt = getPDO()->prepare("SELECT * FROM commits WHERE sha=:commit;");
$stmt->bindParam(':commit', $commit);
$stmt->execute();
$data = $stmt->fetch();
$rfc3339Date = gmdate('Y-m-d\\TH:i:s\\Z', $data['timestamp']);
$formattedDate = gmdate('D M, Y-m-d H:i:s', $data['timestamp']) ?>
<main class=divs>
    <h1><?= $title ?></h1>
    <h2>Commit Details</h2>
    <dl>
        <dt>Commit Message
        <dd><?= htmlspecialchars12("{$data['message']}") ?></dd>
        <dt>Commit Timestamp
        <dd>
            <time datetime="<?= $rfc3339Date ?>"><?= "$formattedDate UTC" ?></time>
        <dt>Commit Full Hash
        <dd><code><?= "$commit" ?></code>
    </dl>
    <a href="<?= "https://github.com/chromium/chromium/commit/$commit" ?>">View Commit on Chromium's GitHub Mirror</a>
    <h2>Preload List Changes</h2>
    <div class=overflow-x>
        <table><?= '<thead><tr><th scope=col>Domain<th scope=col>Action' .
            '<th scope=col>Policy<th scope=col>Includes SubDomains<tbody>';
            $stmt = getPDO()->prepare("SELECT * FROM domain_events WHERE commit_sha=:commit;");
            $stmt->bindParam(':commit', $commit);
            $stmt->execute();
            foreach ($all = $stmt->fetchAll() as $item) {
                $htmldnname = htmlspecialchars12("{$item['domain']}");
                $htmlaction = match ("{$item['action']}") {
                    'm' => 'Modified',
                    'r' => 'Removed',
                    'a' => 'Added',
                    default => '/*Error*/',
                };
                $subs_class = ($subs = (bool)$item['subdomains']) ? 't' : 'f';
                echo "<tr><td><a href=/hstspreloadhistory/domain/$htmldnname/>$htmldnname</a><td class={$item['action']}"
                        . ">$htmlaction<td>{$item['policy']}<td class=$subs_class>" . ($subs ? 'Yes' : 'No');
            } ?></table>
    </div>
</main>

