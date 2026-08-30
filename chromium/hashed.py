import json, sqlite3, datetime, time, subprocess, re

with open('hashes.json', 'rb') as inpu:
    jsonic = reversed(contental := json.load(inpu))
total = len(contental)
print('start; total =', total)
COMMENT_RE = re.compile(r'^\s*//.*$', re.MULTILINE)
CANDIDATE_PATHS = [
    "net/http/transport_security_state_static.json",
    "net/base/transport_security_state_static.json",
    "net/base/transport_security_state.json",
]


class GitBatchReader:
    def __init__(self, repo_dir="../../../chromium-hsts"):
        self.proc = subprocess.Popen(
            ["git", "cat-file", "--batch"],
            cwd=repo_dir, text=False,
            stdout=subprocess.PIPE,
            stdin=subprocess.PIPE)

    def get_blob(self, sha, candidate_paths):
        for path in candidate_paths:
            spec = f"{sha}:{path}\n".encode('utf-8')
            self.proc.stdin.write(spec)
            self.proc.stdin.flush()

            header = self.proc.stdout.readline().decode('utf-8').strip()
            if "missing" in header or not header:
                continue

            # Header format: <sha> blob <size>
            parts = header.split()
            if len(parts) == 3 and parts[1] == "blob":
                size = int(parts[2])
                content = self.proc.stdout.read(size)
                self.proc.stdout.read(1)  # Read trailing newline
                return content.decode('utf-8', errors='ignore')

        return None


def main():
    present = set()
    policies = dict()
    batch = GitBatchReader()
    conn = sqlite3.connect("../hsts_history.db")
    with conn:
        timer = time.perf_counter()
        print('deleting old data')
        conn.execute('DELETE FROM domain_events WHERE 1;')
        conn.execute('DELETE FROM commits WHERE 1;')
        now = time.perf_counter()
        print(f'deleted old data in {(now - timer):.6f}s')
    conn.execute('VACUUM;')
    print(f'vacuumed for {(time.perf_counter() - now):.6f}s')
    timer = time.perf_counter()
    total_now = timer
    with conn:
        for count, o in enumerate(jsonic, start=1):
            sha_short = (sha := o["sha"])[:8]

            # Construct timestamp
            local = datetime.datetime(
                int(o['Y']), int(o['M']), int(o['D']),
                int(o['H']), int(o['I']), int(o['S']),
                tzinfo=datetime.datetime.strptime(o['O'], "%z").tzinfo
            )

            # 3. Read raw file blob directly from C-bindings (50x-100x faster than subprocess)
            # blob_text = get_hsts_blob(commit_obj := repo.get(sha), CANDIDATE_PATHS)
            blob_text = batch.get_blob(sha, CANDIDATE_PATHS)
            if not blob_text:
                now = time.perf_counter()
                # elapsed = now - timer
                timer = now
                # print(f"{count}/{total} [SKIP: File path not present in commit] {elapsed:.6f}s (sha-{sha_short})",
                #      local.astimezone(datetime.timezone.utc))
                continue

            # 4. Strip C++ single-line comments and parse JSON
            clean_json = COMMENT_RE.sub('', blob_text)
            try:
                data = json.loads(clean_json)
                entries = data.get('entries', list())
            except json.JSONDecodeError:
                now = time.perf_counter()
                # elapsed = now - timer
                timer = now
                # print(f"{count}/{total} [skipped. JSONDecodeError] {elapsed:.6f}s (sha-{sha})",
                #      local.astimezone(datetime.timezone.utc))
                continue

            # Calculate additions and removals
            foundnow = set()
            changed = list()
            for policy in entries:
                domain = policy['name']
                foundnow.add(domain)

                new_policy = {
                    'policy': policy.get('policy'),
                    'subdomains': policy.get('include_subdomains', None)
                }

                # Check if domain already exists and its values changed
                if domain in policies and policies[domain] != new_policy:
                    changed.append(domain)

                policies[domain] = new_policy
            added = foundnow - present
            removed = present - foundnow
            present = foundnow  # Maintain state continuously across sequence

            # 5. Execute DB write within a managed transaction block

            cursor = conn.execute(
                "INSERT OR IGNORE INTO commits (sha, timestamp, message, title) VALUES (?, ?, ?, ?)",
                (sha, local.timestamp(), o['m'], o['t'])
            )
            changed_amount = int()

            # Only log events if this commit sha hadn't been recorded yet
            if cursor.rowcount > 0:
                if added:
                    conn.executemany(
                        "INSERT INTO domain_events (domain, commit_sha, action, policy, subdomains) VALUES (?, ?, ?, ?, ?)",
                        [(name, sha, 'a', policies[name]['policy'], policies[name]['subdomains']) for name in added],
                    )
                if removed:
                    conn.executemany(
                        "INSERT INTO domain_events (domain, commit_sha, action, policy, subdomains) VALUES (?, ?, ?, null, null)",
                        [(name, sha, 'r') for name in removed],
                    )
                if changed:
                    changed_params = [(name, sha, 'm', policies[name]['policy'], policies[name]['subdomains']
                                       ) for name in changed if name not in added and name not in removed]
                    conn.executemany(
                        "INSERT INTO domain_events (domain, commit_sha, action, policy, subdomains) VALUES (?, ?, ?, ?, ?)",
                        changed_params,
                    )
                    changed_amount = len(changed_params)
            now = time.perf_counter()
            elapsed = now - timer
            timer = now
            added_tmp = len(added)
            removed_tmp = len(removed)
            print(
                f"{count:03d}/{total}",
                f"[{added_tmp:04d} added] [{removed_tmp:04d} removed] [{changed_amount:04d} modified]",
                f"{elapsed:.6f}s (sha-{sha_short}) {local.astimezone(datetime.timezone.utc)}"
            )
    conn.close()
    time_taken = time.perf_counter() - total_now
    print(f'totally that took', f"{time_taken:.6f} seconds ({(time_taken / 60):.3} minutes)")
    return contental[0]['sha']


if __name__ == '__main__':
    main()

pass
