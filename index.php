<?php use function Helpers\htmlspecialchars12;
use function ANTHeader\create_head3;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
header('cache-control: public, max-age=30');
require_once "{$_SERVER['DOCUMENT_ROOT']}/require/header3/head3.php";
create_head3($title = 'HSTS Preload History', [
        'stylelinks' => ['/gallery/ddDL-table.css', '/hstspreloadhistory/styles.css'],
        'base' => '/hstspreloadhistory/', 'bread' => [
                ['text' => 'HSTS Preload History', 'href' => '/hstspreloadhistory/'],
        ],
]) ?>
<main class=divs>
    <h1><?= $title ?></h1>
    <h2>Search</h2>
    <form method=get action=search.php>
        <label>Domain: <input type=text name=query pattern="^[a-z0-9.\-A-Z]+$" size=25></label>
        <input type=hidden name=type value=domain>
        <button>Find</button>
        please know that a proper search is on it's way.
    </form>
    <h2>Legend</h2>
    <a href=legend.php>How to Read This website</a>
    <h2>Latest Commits</h2>
    <div class=overflow-x>
        <table><?= '<thead><tr><th scope=col>Commit Hash<th scope=col>Timestamp<th scope=col>Message' .
            '<th scope=col>Added<th scope=col>Removed<th scope=col>Modified<tbody>';
            require_once __DIR__ . '/phpmodule/opendb.php';
            $stmt = getPDO()->query("SELECT c.*, COUNT(CASE WHEN domain_events.action = 'a' THEN 1 END) AS"
                    . " additions_count, COUNT(CASE WHEN domain_events.action = 'r' THEN 1 END) AS removals_count, " .
                    " COUNT(CASE WHEN domain_events.action = 'm' THEN 1 END) AS modifications_count FROM commits c "
                    . "LEFT JOIN domain_events ON domain_events.commit_sha = c.sha GROUP BY c.sha, c.timestamp ORDER"
                    . " BY c.timestamp DESC");// LIMIT 20
            foreach ($all = $stmt->fetchAll() as $item) {
                $message = htmlspecialchars12("{$item['message']}");
                $htmlname = htmlspecialchars12(substr("{$item['sha']}", 0, 8));
                $rfc3339Date = gmdate('Y-m-d\\TH:i:s\\Z', $item['timestamp']);
                $formattedDate = gmdate('D M, Y-m-d H:i:s', $item['timestamp']);
                echo "<tr><td><a href=/hstspreloadhistory/commit/{$item['sha']}/>$htmlname</a><td><time" .
                        " datetime=$rfc3339Date>$formattedDate UTC</time><td>$message";
                echo "<td>{$item['additions_count']}<td>{$item['removals_count']}<td>{$item['modifications_count']}";
            } ?></table>
    </div>
</main>
