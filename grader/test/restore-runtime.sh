#!/bin/sh
set -eu
umask 077

# Optional Arch Linux fallback. Everything stays in a private temporary directory.
if command -v podman >/dev/null 2>&1; then
    if [ "$(podman info --format '{{.Host.Security.Rootless}}')" != true ]; then
        echo 'ERROR: acceptance requires rootless Podman.' >&2
        exit 1
    fi
    echo 'Native rootless Podman ready.'
    exit 0
fi

if [ "$(uname -s)" != Linux ] || [ "$(uname -m)" != x86_64 ]; then
    echo 'ERROR: install a rootless container runtime for this platform.' >&2
    exit 1
fi
if ! grep -Eq '^(ID=arch|ID=cachyos|ID_LIKE=.*arch)' /etc/os-release; then
    echo 'ERROR: the package fallback requires an Arch-compatible host.' >&2
    exit 1
fi
for executable in curl bsdtar sha256sum newuidmap newgidmap python3; do
    command -v "$executable" >/dev/null 2>&1 || {
        echo "ERROR: missing prerequisite $executable." >&2
        exit 1
    }
done

CODEQUEST_RUNTIME_DIR="${CODEQUEST_RUNTIME_DIR:-/tmp/codequest-rootless-$(id -u)}"
export CODEQUEST_RUNTIME_DIR
python3 <<'PYTHON'
import os
from pathlib import Path

root = Path(os.environ['CODEQUEST_RUNTIME_DIR'])
if root.parent != Path('/tmp') or not root.name.startswith('codequest-'):
    raise SystemExit('ERROR: use a private /tmp/codequest-* runtime directory.')
if root.is_symlink() or (root.exists() and (not root.is_dir() or root.stat().st_uid != os.getuid())):
    raise SystemExit('ERROR: runtime directory must belong to this user and not be a symlink.')
root.mkdir(mode=0o700, exist_ok=True)
root.chmod(0o700)
for name in ['packages', 'tools', 'bin', 'runtime', 'config', 'containers.conf']:
    path = root / name
    if path.is_symlink():
        raise SystemExit('ERROR: runtime paths must not be symlinks.')
    if name != 'runtime' and path.is_dir():
        for child in path.rglob('*'):
            if child.is_symlink() and (name != 'tools' or not child.resolve().is_relative_to(path)):
                raise SystemExit('ERROR: runtime symlink leaves its private tool directory.')
PYTHON
mkdir -p "$CODEQUEST_RUNTIME_DIR/packages" "$CODEQUEST_RUNTIME_DIR/tools" "$CODEQUEST_RUNTIME_DIR/bin" \
    "$CODEQUEST_RUNTIME_DIR/runtime" "$CODEQUEST_RUNTIME_DIR/config/containers"
chmod 700 "$CODEQUEST_RUNTIME_DIR" "$CODEQUEST_RUNTIME_DIR/runtime"

while read -r checksum package; do
    archive="$CODEQUEST_RUNTIME_DIR/packages/$package"
    if [ ! -f "$archive" ]; then
        curl --fail --location --retry 2 --max-time 180 \
            "https://archive.archlinux.org/packages/$(printf '%s' "$package" | cut -c 1)/${package%%-[0-9]*}/$package" \
            --output "$archive.download"
        mv "$archive.download" "$archive"
    fi
    printf '%s  %s\n' "$checksum" "$archive" | sha256sum --check --status
    bsdtar -xf "$archive" -C "$CODEQUEST_RUNTIME_DIR/tools"
done <<'PACKAGES'
698497677a8d94602ed89eef456267aa532b95a2305a1cb2ade8114a422d7131 podman-6.1.3-1-x86_64.pkg.tar.zst
feb57ea5ff1546b2bed4c5ffff9f523f6a394588cc696104f8c45c35ec09cbbd crun-1.30.1-1-x86_64.pkg.tar.zst
3cc9679725446c229735055f36d417cc0a00d38fd5c619cb7367faa54276caae conmon-1:2.2.1-1-x86_64.pkg.tar.zst
74bdbfe0fcde6e42a52d678b4db121ed6ed923662d3d4c9c8a9182cf034fdee0 containers-common-1:0.69.2-1-any.pkg.tar.zst
5da92f0f1a3aa627febe36ccdbb851f45acf4dd6ea2530f3de7587d61c329860 netavark-2.1.0-3-x86_64.pkg.tar.zst
PACKAGES

python3 <<'PYTHON'
import json
import os
import shlex
from pathlib import Path

root = Path(os.environ['CODEQUEST_RUNTIME_DIR']).resolve()
tools = root / 'tools'
config = root / 'config/containers'
for name in ['policy.json', 'registries.conf']:
    (config / name).write_bytes((tools / 'usr/share/containers' / name).read_bytes())

quote = lambda path: json.dumps(str(path))
(root / 'containers.conf').write_text(
    '[containers]\nseccomp_profile=' + quote(tools / 'usr/share/containers/seccomp.json') + '\n'
    '[engine]\nruntime="crun"\nconmon_path=[' + quote(tools / 'usr/bin/conmon') + ']\n'
    'helper_binaries_dir=[' + quote(tools / 'usr/lib/podman') + ']\n'
    '[engine.runtimes]\ncrun=[' + quote(root / 'bin/crun') + ']\n'
)
(config / 'storage.conf').write_text(
    '[storage]\ndriver="vfs"\nrunroot=' + quote(root / 'run') + '\n'
    'graphroot=' + quote(root / 'storage') + '\n'
)
for name in ['crun', 'podman']:
    lines = ['#!/bin/sh', 'export LD_LIBRARY_PATH=' + shlex.quote(str(tools / 'usr/lib'))]
    if name == 'podman':
        for variable, path in [
            ('XDG_RUNTIME_DIR', root / 'runtime'),
            ('XDG_CONFIG_HOME', root / 'config'),
            ('CONTAINERS_CONF', root / 'containers.conf'),
        ]:
            lines.append('export ' + variable + '=' + shlex.quote(str(path)))
    lines.append('exec ' + shlex.quote(str(tools / 'usr/bin' / name)) + ' "$@"')
    wrapper = root / 'bin' / name
    wrapper.write_text('\n'.join(lines) + '\n')
    wrapper.chmod(0o755)
PYTHON

echo "Runtime restored at $CODEQUEST_RUNTIME_DIR/bin."
echo 'Verify and run inside a user scope with Delegate=cpu memory pids.'
