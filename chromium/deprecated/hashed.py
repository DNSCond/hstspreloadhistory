import json, subprocess, re, sqlite3, datetime, time

with open('hashes.json', 'rb') as file:
    jsonic = reversed(contental := json.load(file))
total = len(contental)
print('start; total=', total)
step = time.perf_counter()
with sqlite3.connect("hsts_history.db") as conn:
    cursor = conn.cursor()
    present = set()
    count = int()
    for o in jsonic:
        count += 1
        local = datetime.datetime(
            int(o['Y']), int(o['M']), int(o['D']), int(o['H']), int(o['I']), int(o['S']),
            tzinfo=datetime.datetime.strptime(o['O'], "%z").tzinfo)
        cursor.execute(
            "INSERT OR IGNORE INTO commits (sha, timestamp) VALUES (?, ?)",
            (o["sha"], local.timestamp()))
        result = subprocess.run(
            [
                "git", "--no-pager", "show",
                f"{o['sha']}:net/http/transport_security_state_static.json",
            ],
            cwd="chromium-hsts",
            capture_output=True,
            check=True, text=True)
        value = re.sub('^\\s*//.*', '', result.stdout, flags=re.MULTILINE)
        const = json.loads(value)['entries']
        foundnow = set(i['name'] for i in const)
        added = foundnow - present
        removed = present - foundnow
        present = foundnow
        # skip the commit
        if cursor.rowcount != 0:
            conn.executemany(
                "INSERT INTO domain_events (domain, commit_sha, action) VALUES (?, ?, ?)",
                [(name, o['sha'], 'added') for name in added],
            )
            conn.executemany(
                "INSERT INTO domain_events (domain, commit_sha, action) VALUES (?, ?, ?)",
                [(name, o['sha'], 'removed') for name in removed],
            )
        timer = time.perf_counter() - step
        print(f"{count}/{total} [{str(len(added)).rjust(3, '0')} added]\x20",
              f"[{str(len(removed)).rjust(3, '0')} removed] {timer:.6f}s\x20"
              , f"(sha-{o["sha"]})", local.astimezone(datetime.timezone.utc))
pass
