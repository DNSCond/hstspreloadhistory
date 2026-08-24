import json, sqlite3, datetime, time, pygit2, re

with open('hashes.json', 'rb') as file:
    jsonic = reversed(contental := json.load(file))
total = len(contental)
present = set()
print('start; total=', total)
step = time.perf_counter()
COMMENT_RE = re.compile(r'^\s*//.*$', re.MULTILINE)
CANDIDATE_PATHS = [
    "net/http/transport_security_state_static.json",
    "net/base/transport_security_state_static.json",
    "net/base/transport_security_state.json",
]


def get_hsts_blob(commit, candidate_paths):
    for path in candidate_paths:
        try:
            entry = commit.tree[path]
            return repo[entry.id].data.decode('utf-8', errors='ignore')
        except KeyError:
            continue
    return None


conn = sqlite3.connect("hsts_history.db")
repo = pygit2.Repository("chromium-hsts")
for count, o in enumerate(jsonic, start=1):
    sha_short = (sha := o["sha"])[:8]

    # Construct timestamp
    local = datetime.datetime(
        int(o['Y']), int(o['M']), int(o['D']),
        int(o['H']), int(o['I']), int(o['S']),
        tzinfo=datetime.datetime.strptime(o['O'], "%z").tzinfo
    )

    # 3. Read raw file blob directly from C-bindings (50x-100x faster than subprocess)
    blob_text = get_hsts_blob(commit_obj := repo.get(sha), CANDIDATE_PATHS)
    if not blob_text:
        print(f"{count}/{total} [SKIP: File path not present in commit] (sha-{sha_short})",
              local.astimezone(datetime.timezone.utc))
        continue

    # 4. Strip C++ single-line comments and parse JSON
    clean_json = COMMENT_RE.sub('', blob_text)
    try:
        data = json.loads(clean_json)
        entries = data.get('entries', [])
    except json.JSONDecodeError:
        print(f"{count}/{total}: skipped. JSONDecodeError; (sha-{sha})",
              local.astimezone(datetime.timezone.utc))
        continue

    # Calculate additions and removals
    foundnow = set(i['name'] for i in entries if 'name' in i)
    added = foundnow - present
    removed = present - foundnow
    present = foundnow  # Maintain state continuously across sequence

    # 5. Execute DB write within a managed transaction block
    with conn:
        cursor = conn.execute(
            "INSERT OR IGNORE INTO commits (sha, timestamp) VALUES (?, ?)",
            (sha, local.timestamp())
        )

        # Only log events if this commit sha hadn't been recorded yet
        if cursor.rowcount > 0:
            if added:
                conn.executemany(
                    "INSERT INTO domain_events (domain, commit_sha, action) VALUES (?, ?, ?)",
                    [(name, sha, 'added') for name in added],
                )
            if removed:
                conn.executemany(
                    "INSERT INTO domain_events (domain, commit_sha, action) VALUES (?, ?, ?)",
                    [(name, sha, 'removed') for name in removed],
                )

    timer = time.perf_counter() - step
    print(
        f"{count}/{total} [{len(added):03d} added] [{len(removed):03d} removed]",
        f"{timer:.4f}s (sha-{sha_short}) {local.astimezone(datetime.timezone.utc)}"
    )
conn.close()
class GitBatchReader:
    def __init__(self, repo_dir="chromium-hsts"):
        self.proc = subprocess.Popen(
            ["git", "cat-file", "--batch"],
            cwd=repo_dir,
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            text=False
        )

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