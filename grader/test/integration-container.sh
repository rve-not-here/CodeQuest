#!/bin/sh
# Container security checks for the CodeQuest JS grader. Builds the runner
# image and exercises real ephemeral sandboxes: valid and hostile
# submissions, filesystem writes, network egress, host mounts, and
# cleanup after timeouts.
#
# Developer mode (default): an unavailable runtime prints SKIP and exits 0.
# Acceptance mode: CODEQUEST_REQUIRE_SANDBOX_TESTS=1 turns that SKIP into
# a non-zero failure, so a missing sandbox can never read as acceptance.
set -eu

STRICT="${CODEQUEST_REQUIRE_SANDBOX_TESTS:-0}"

RUNTIME=""
if command -v podman >/dev/null 2>&1; then
    RUNTIME="podman"
elif command -v docker >/dev/null 2>&1; then
    RUNTIME="docker"
else
    echo "SKIP: no podman or docker binary found."
    [ "$STRICT" = "1" ] && exit 1 || exit 0
fi

if ! "$RUNTIME" info >/dev/null 2>&1; then
    echo "SKIP: $RUNTIME daemon/socket is not reachable from this shell."
    [ "$STRICT" = "1" ] && exit 1 || exit 0
fi

# Rootless detection. Podman reports its own mode; for Docker only a
# rootless daemon counts, never the standard root socket.
ROOTLESS=0
if [ "$RUNTIME" = "podman" ]; then
    if "$RUNTIME" info --format '{{.Host.Security.Rootless}}' 2>/dev/null | grep -qi true; then
        MODE="rootless podman"
        ROOTLESS=1
    else
        MODE="rootful podman (NOT recommended for production)"
    fi
else
    if "$RUNTIME" info --format '{{.SecurityOptions}}' 2>/dev/null | grep -qi rootless; then
        MODE="rootless docker"
        ROOTLESS=1
    else
        MODE="rootful docker daemon (local development only, NOT production)"
    fi
fi
echo "Runtime: $RUNTIME ($MODE)"

# Strict mode is security acceptance: it fails closed on anything but a
# rootless runtime. Developer mode keeps rootful usable for debugging.
if [ "$STRICT" = "1" ] && [ "$ROOTLESS" != "1" ]; then
    echo "NOT OK: strict acceptance requires a rootless runtime (got: $MODE)"
    exit 1
fi

# Resolve host paths before changing directory below. No usernames are
# hard-coded: both derive from the invoking shell and the repo layout.
HOST_HOME="$HOME"
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

cd "$(dirname "$0")/.."
"$RUNTIME" build -t codequest-js-runner:1.0 . >/dev/null

RUN_FLAGS="--rm -i --network none --user 65534:65534 --read-only \
    --tmpfs /tmp:rw,size=16m,noexec --cap-drop ALL \
    --security-opt no-new-privileges --pids-limit 32 --cpus 0.5 \
    --memory 128m --memory-swap 128m"

# Word-split RUN_FLAGS on purpose: fixed flags, no user data.
# shellcheck disable=SC2086
grade() {
    printf '%s' "$1" | timeout 60 "$RUNTIME" run $RUN_FLAGS \
        codequest-js-runner:1.0 node /app/src/runner.js
}

PASS=0
FAIL=0
check() {
    description="$1"
    expected="$2"
    actual="$3"
    if [ "$actual" = "$expected" ]; then
        PASS=$((PASS + 1))
        echo "ok: $description"
    else
        FAIL=$((FAIL + 1))
        echo "NOT OK: $description (want $expected, got $actual)"
    fi
}

ADD='{"source":"function add(a, b) { return a + b; }","exec_timeout_ms":8000,"tests":[{"type":"function","payload":{"function":"add","cases":[{"args":[2,3],"expected":5},{"args":[10,20],"expected":30},{"args":[-5,5],"expected":0}]}}]}'
RESULT=$(grade "$ADD")
check "valid solution passes" "passed" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).status))")"

ARROW='{"source":"const add = (a, b) => a + b;","exec_timeout_ms":8000,"tests":[{"type":"function","payload":{"function":"add","cases":[{"args":[2,3],"expected":5}]}}]}'
RESULT=$(grade "$ARROW")
check "alternative valid solution passes" "passed" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).status))")"

HARDCODED='{"source":"function add() { return 8; }","exec_timeout_ms":8000,"tests":[{"type":"function","payload":{"function":"add","cases":[{"args":[2,3],"expected":5},{"args":[10,20],"expected":30}]}}]}'
RESULT=$(grade "$HARDCODED")
check "hard-coded answer fails hidden cases" "failed" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).status))")"

WRONG='{"source":"function add(a, b) { return a - b; }","exec_timeout_ms":8000,"tests":[{"type":"function","payload":{"function":"add","cases":[{"args":[2,3],"expected":5}]}}]}'
RESULT=$(grade "$WRONG")
check "wrong logic fails" "failed" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).status))")"

COMMENT='{"source":"function add(a, b) { return a + b; }\n// console.log(add(5, 3));","exec_timeout_ms":8000,"tests":[{"type":"console","payload":{"expected":[[8]]}}]}'
RESULT=$(grade "$COMMENT")
check "comment-only console output fails" "failed" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).status))")"

LOOP='{"source":"while (true) {}","exec_timeout_ms":3000,"tests":[{"type":"console","payload":{"expected":[[1]]}}]}'
RESULT=$(grade "$LOOP")
check "infinite loop terminates as timeout" "timeout" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).error_type))")"

BOMB='{"source":"for (let i = 0; i < 5000; i++) { console.log(\"line \" + i); }","exec_timeout_ms":8000,"tests":[{"type":"console","payload":{"expected":[[8]]}}]}'
RESULT=$(grade "$BOMB")
check "output bomb is bounded" "resource_limit" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).error_type))")"

FSWRITE='{"source":"try { require(\"fs\").writeFileSync(\"/app/pwned\", \"x\"); console.log(1); } catch (e) { console.log(0); }","exec_timeout_ms":8000,"tests":[{"type":"console","payload":{"expected":[[0]]}}]}'
RESULT=$(grade "$FSWRITE")
check "node modules stay unavailable to student code" "passed" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).status))")"

NETWORK='{"source":"try { require(\"https\").get(\"https://example.com\"); console.log(1); } catch (e) { console.log(0); }","exec_timeout_ms":8000,"tests":[{"type":"console","payload":{"expected":[[0]]}}]}'
RESULT=$(grade "$NETWORK")
check "node network modules stay unavailable to student code" "passed" "$(printf '%s' "$RESULT" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>process.stdout.write(JSON.parse(d).status))")"
# Live egress: independent of module isolation. A real fetch attempt from
# inside the sandbox must fail under NetworkMode=none. Bounded so an
# offline hang cannot stall the suite.
# shellcheck disable=SC2086
EGRESS=$(timeout 30 "$RUNTIME" run --rm -i $RUN_FLAGS \
    --entrypoint node \
    codequest-js-runner:1.0 -e 'fetch("http://example.com/", { signal: AbortSignal.timeout(8000) }).then(() => console.log("EGRESS")).catch(() => console.log("BLOCKED"))' 2>&1 || true)
check "live egress attempt is blocked" "BLOCKED" "$(printf '%s' "$EGRESS" | grep -E '^(BLOCKED|EGRESS)$' || echo MISSING)"

# Host mounts: two independent checks. First, the container configuration
# must carry no bind mounts, only the expected tmpfs on /tmp. Second, a
# live probe confirms the host paths are not visible inside the sandbox.
# A raw /proc/mounts text search is deliberately NOT used here: rootless
# runtimes record OverlayFS backing-store paths such as
# $HOME/.local/share/docker/... on the "/" line, which is storage
# metadata, not a host bind mount, and naive grepping false-positives.
# shellcheck disable=SC2086
PROBE_ID=$($RUNTIME create $RUN_FLAGS \
    --entrypoint sh \
    codequest-js-runner:1.0 -c "sleep 30" 2>/dev/null || true)
BINDS="null"
MOUNTS_JSON="null"
TMPFS_JSON="null"
NETMODE_JSON="null"
if [ -z "$PROBE_ID" ]; then
    FAIL=$((FAIL + 1))
    echo "NOT OK: could not create the inspection container"
else
    BINDS=$($RUNTIME inspect --format '{{json .HostConfig.Binds}}' "$PROBE_ID" 2>/dev/null)
    MOUNTS_JSON=$($RUNTIME inspect --format '{{json .Mounts}}' "$PROBE_ID" 2>/dev/null)
    TMPFS_JSON=$($RUNTIME inspect --format '{{json .HostConfig.Tmpfs}}' "$PROBE_ID" 2>/dev/null)
    NETMODE_JSON=$($RUNTIME inspect --format '{{json .HostConfig.NetworkMode}}' "$PROBE_ID" 2>/dev/null)
    $RUNTIME rm -f "$PROBE_ID" >/dev/null 2>&1 || true
fi
MOUNT_CHECK=$(BINDS_JSON="$BINDS" MOUNTS_JSON="$MOUNTS_JSON" TMPFS_JSON="$TMPFS_JSON" NETMODE_JSON="$NETMODE_JSON" node -e "
const binds = JSON.parse(process.env.BINDS_JSON || 'null');
const mounts = JSON.parse(process.env.MOUNTS_JSON || 'null');
const tmpfs = JSON.parse(process.env.TMPFS_JSON || 'null');
const netmode = JSON.parse(process.env.NETMODE_JSON || 'null');
const out = [];
const bindsEmpty = binds === null || (Array.isArray(binds) && binds.length === 0);
out.push(bindsEmpty ? 'binds-ok' : 'binds-dirty');
const mountsOk = Array.isArray(mounts) && mounts.every((m) => m.Type !== 'bind' && m.Type !== 'volume' && (m.Type !== 'tmpfs' || m.Destination === '/tmp'));
out.push(mountsOk ? 'mounts-ok' : 'mounts-dirty');
const tmpfsKeys = tmpfs && typeof tmpfs === 'object' ? Object.keys(tmpfs) : [];
const tmpfsEntry = typeof (tmpfs || {})['/tmp'] === 'string' ? tmpfs['/tmp'] : '';
const tmpfsOk = tmpfsKeys.length === 1 && tmpfsKeys[0] === '/tmp'
    && tmpfsEntry.includes('rw') && tmpfsEntry.includes('size=16m') && tmpfsEntry.includes('noexec');
out.push(tmpfsOk ? 'tmpfs-ok' : 'tmpfs-dirty');
out.push(netmode === 'none' ? 'netmode-ok' : 'netmode-dirty');
process.stdout.write(out.join(' '));
")
check "no bind or volume mounts in container config" "binds-ok mounts-ok" "$(printf '%s' "$MOUNT_CHECK" | cut -d' ' -f1,2)"
check "tmpfs is exactly /tmp with size and noexec" "tmpfs-ok" "$(printf '%s' "$MOUNT_CHECK" | cut -d' ' -f3)"
check "container network mode is none" "netmode-ok" "$(printf '%s' "$MOUNT_CHECK" | cut -d' ' -f4)"
# shellcheck disable=SC2086
VISIBILITY=$(timeout 60 "$RUNTIME" run --rm -i $RUN_FLAGS \
    --entrypoint sh \
    codequest-js-runner:1.0 -c 'for p in "$1" "$2" /host /mnt/host; do if [ -e "$p" ]; then echo "PRESENT:$p"; fi; done; echo "marker" > /tmp/probe && cat /tmp/probe; echo "intruder" > /app/pwned 2>/dev/null && echo WRITABLE_ROOT || echo READONLY_ROOT; printf "echo HI\n" > /tmp/noexec.sh; chmod +x /tmp/noexec.sh; /tmp/noexec.sh >/dev/null 2>&1 && echo EXEC_OK || echo NOEXEC_OK' \
    sh "$HOST_HOME" "$PROJECT_ROOT" 2>&1 || true)
if printf '%s' "$VISIBILITY" | grep -q PRESENT; then
    FAIL=$((FAIL + 1))
    echo "NOT OK: host path is visible inside the sandbox"
    printf '%s\n' "$VISIBILITY" | grep PRESENT | head -n 5
else
    PASS=$((PASS + 1))
    echo "ok: host home and project are absent inside the sandbox"
fi
check "read-only root rejects writes" "READONLY_ROOT" "$(printf '%s' "$VISIBILITY" | grep -E '^(READONLY_ROOT|WRITABLE_ROOT)$' || echo MISSING)"
check "tmpfs /tmp is writable" "marker" "$(printf '%s' "$VISIBILITY" | grep -E '^marker$' || echo MISSING)"
check "tmpfs /tmp denies execution" "NOEXEC_OK" "$(printf '%s' "$VISIBILITY" | grep -E '^(NOEXEC_OK|EXEC_OK)$' || echo MISSING)"

# Cleanup: after a timeout kill, no grading container may remain.
TIMEOUT_SRC='{"source":"while (true) {}","exec_timeout_ms":2000,"tests":[{"type":"console","payload":{"expected":[[1]]}}]}'
printf '%s' "$TIMEOUT_SRC" | timeout 60 "$RUNTIME" run --rm -i $RUN_FLAGS \
    --name "codequest-cleanup-probe" \
    codequest-js-runner:1.0 node /app/src/runner.js >/dev/null 2>&1 || true
sleep 2
LEFTOVER=$("$RUNTIME" ps --format '{{.Names}}' 2>/dev/null | grep -c codequest-cleanup-probe || true)
check "no grading container remains after timeout" "0" "$LEFTOVER"

echo "---"
echo "pass=$PASS fail=$FAIL runtime=$RUNTIME mode=$MODE"
[ "$FAIL" = "0" ]
