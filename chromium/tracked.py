import subprocess, re, json, time


def main():
    print('start')
    start = time.perf_counter()
    result = subprocess.run([
        "git", "--no-pager", "log", '--all',  # '-n 5',
        "--format=sha-%H (%aI) %x00%s%x00%b%x00", '--',
        "net/http/transport_security_state_static.json",
        "net/base/transport_security_state_static.json",
        "net/base/transport_security_state.json",
    ], encoding='utf-8', errors='replace', capture_output=True,
        cwd="../../../chromium-hsts", check=True, text=True)
    seconds = time.perf_counter() - start
    print('all hashes gotten in', f"{seconds:.6f} seconds ({(seconds / 60):.3} minutes)")
    fields = ["sha", "Y", "M", "D", "H", "I", "S", 'O', 't', 'm']
    with open('hashes.json', 'wt', encoding='utf8') as file, open('raw.txt', 'wt', encoding='utf8') as raw:
        raw.write(result.stdout)
        file.write(json.dumps([dict(zip(fields, i.groups())) for i in re.finditer(
            'sha-([a-f0-9]+) \\((\\d{4})-(\\d{2})-(\\d{2})T(\\d{2}):(\\d{2}):(\\d{2})(Z|[+\\-]\\d{2}:\\d{2})\\)'
            + r' \x00([^\x00]+)\x00([^\x00]+)\x00', result.stdout)]))
        pass


if __name__ == '__main__':
    main()

pass
