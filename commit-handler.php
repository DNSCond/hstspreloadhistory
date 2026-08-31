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
$max = 400;
$rawOffset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0]
]);
$offset = ($rawOffset !== false && $rawOffset !== null) ? $rawOffset : 0;
if ($offset % $max !== 0) {
    $offset = $offset - ($offset % $max);
    http_response_code(307);
    header("Location: ?offset=$offset");
    exit;
}
require_once 'opendb.php';
$stmt = getPDO()->prepare("SELECT * FROM commits WHERE sha=:commit;");
$stmt->bindParam(':commit', $commit);
$stmt->execute();
$data = $stmt->fetch();
$rfc3339Date = gmdate('Y-m-d\\TH:i:s\\Z', $data['timestamp']);
$formattedDate = gmdate('D M, Y-m-d H:i:s', $data['timestamp']);

$humanCommit = substr($commit, 0, 8);
header('cache-control: public, max-age=432000');
create_head3($title = "{$data['title']} (HSTS Preload History)", [
        'stylelinks' => ['/gallery/ddDL-table.css', '/hstspreloadhistory/styles.css'],
        'canonical' => "https://antrequest.nl/hstspreloadhistory/commit/$commit/",
        'base' => '/hstspreloadhistory/', 'bread' => [
                ['text' => 'HSTS Preload History', 'href' => '/hstspreloadhistory/'],
                ['text' => "Chromium Commit \"$humanCommit\"", 'href' => "/hstspreloadhistory/commit/$commit/"],
        ],
]);
$maximum = $max;
$max += $offset + 1;
$stmt = getPDO()->prepare("SELECT COUNT(CASE WHEN domain_events.action = 'a' THEN 1 END) AS additions, COUNT" .
        "(CASE WHEN domain_events.action = 'r' THEN 1 END) AS removals, COUNT(CASE WHEN domain_events.action = 'm' " .
        "THEN 1 END) AS modifications FROM domain_events WHERE commit_sha=:commit;");
$stmt->bindParam(':commit', $commit);
$stmt->execute();
['additions' => $additions, 'removals' => $removals, 'modifications' => $modifications] = $stmt->fetch() ?>
<main class=divs>
    <h1><?= $title ?></h1>
    <h2>Commit Details</h2>
    <dl>
        <dt>Commit Subject
        <dd><?= htmlspecialchars12("{$data['title']}") ?></dd>
        <dt>Commit Message
        <dd>
            <blockquote cite="<?= "https://chromium.googlesource.com/chromium/src/+/$commit" ?>">
                <pre><?= htmlspecialchars12("{$data['message']}") ?></pre>
            </blockquote>
        </dd>
        <dt>Commit Timestamp
        <dd><?= "<time datetime=$rfc3339Date>$formattedDate UTC</time>" ?></dd>
        <dt>Commit Full Hash
        <dd><code><?= "$commit" ?></code>
        <dt>Commit Additions
        <dd><span><?= "$additions" ?></span>
        <dt>Commit Removals
        <dd><span><?= "$removals" ?></span>
        <dt>Commit Modifications
        <dd><span><?= "$modifications" ?></span>
        <dt>Commit Total changes
        <dd><span><?= $additions + $modifications + $removals ?></span>
    </dl>
    <a href="<?= "https://github.com/chromium/chromium/commit/$commit" ?>" target='_blank'
       referrerpolicy=origin>View Commit on Chromium's GitHub Mirror (might be slow)</a>
    <a href="<?= "https://chromium.googlesource.com/chromium/src/+/$commit" ?>"
       target='_blank' referrerpolicy=origin>View Commit on Chromium's Googlesource</a>
    <h2>Preload List Changes</h2>
    <div class=overflow-x><?= '<table><thead><tr><th scope=col>Int<th scope=col>Domain' .
        '<th scope=col>Action<th scope=col>Policy<th scope=col>Includes SubDomains<tbody>';
        $stmt = getPDO()->prepare("SELECT * FROM domain_events WHERE commit_sha=:commit LIMIT :limit OFFSET :offset;");
        $stmt->bindParam(':commit', $commit);
        $stmt->bindParam(':offset', $offset);
        $stmt->bindParam(':limit', $max);
        $stmt->execute();
        $breakpast = false;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) if (processRow($row)) break;
        function processRow($item): bool
        {
            global $offset, $max, $breakpast;
            $offset += 1;
            $htmldnname = htmlspecialchars12("{$item['domain']}");
            $htmlaction = match ("{$item['action']}") {
                'm' => 'Modified',
                'r' => 'Removed',
                'a' => 'Added',
                default => '/*Error*/',
            };
            $rem = $offset % $max;
            if ($rem === 0) {
                $breakpast = true;
                return true;
            }
            $hOffset = str_pad("$offset", 4, '0', STR_PAD_LEFT);
            $subs_class = ($subs = (bool)$item['subdomains']) ? 't' : 'f';
            echo "<tr><td>$hOffset<td><a href='/hstspreloadhistory/domain/$htmldnname"
                    . "/'>$htmldnname</a><td class={$item['action']}>$htmlaction<td>" .
                    "{$item['policy']}<td class=$subs_class>" . ($subs ? 'Yes' : 'No');
            return false;
        }

        echo '</table>';
        if ($breakpast) {
            echo "<a href=?offset=$offset>View More</a>";
        } ?></div>
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
