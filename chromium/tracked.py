import subprocess, re, json, time

print('start')
start = time.perf_counter()
result = subprocess.run(
    [
        "git", "--no-pager", "log", '--all',
        "--format=sha-%H (%aI) %x00%s%x00", '--',
        "net/http/transport_security_state_static.json",
        "net/base/transport_security_state_static.json",
        "net/base/transport_security_state.json",
    ], cwd="../../../chromium-hsts", check=True, text=True,
    encoding='utf-8', errors='replace', capture_output=True,
)
print('all hashes gotten in', f"{time.perf_counter() - start:.6f}s")
fields = ["sha", "Y", "M", "D", "H", "I", "S", 'O', 'message']
with open('hashes.json', 'wt', encoding='utf8') as file, open('raw.txt', 'wt', encoding='utf8') as raw:
    raw.write(result.stdout)
    file.write(json.dumps([dict(zip(fields, i.groups())) for i in re.finditer(
        'sha-([a-f0-9]+) \\((\\d{4})-(\\d{2})-(\\d{2})T(\\d{2}):(\\d{2}):(\\d{2})(Z|[+\\-]\\d{2}:\\d{2})\\)' +
        r' \x00([^\x00]+)\x00',
        result.stdout)]))
    pass
pass
