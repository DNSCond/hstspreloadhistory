from tracked import main as step1
from hashed import main as step2
import datetime, re, subprocess
import time

print('start manual update')
start = time.perf_counter()
subprocess.run(['git', 'pull'], cwd="../../../chromium-hsts")
seconds = time.perf_counter() - start
print('git pulled in', f"{seconds:.6f} seconds ({(seconds / 60):.3} minutes)")
step1()
last_sha = step2()
# if pathlib.Path('../manual-update-at.txt').exists():
#     with open('../manual-update-at.txt', 'rt', encoding='utf8') as file:
#         data = file.read().split(',')
with open('../manual-update-at.txt', 'wt', encoding='utf8') as file:
    dt = datetime.datetime.now().astimezone(datetime.timezone.utc).isoformat()
    file.write(re.sub('\\.\\d+\\+00:00', 'Z', dt))
    file.write(',' + last_sha)
pass
