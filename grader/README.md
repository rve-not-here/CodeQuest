# CodeQuest JS grader

Internal service. It runs hidden behavioral tests for coding missions in
fresh ephemeral rootless containers. Laravel never executes student code
and never touches the container runtime.

## Layout

- `src/server.js`: loopback HTTP service. `POST /grade` with a bearer
  token, spawns one container per request with fixed flags, returns the
  strict result contract.
- `src/runner.js`: runs inside the sandbox. Reads the payload from stdin,
  evaluates the student source in a bare VM context (ergonomics only),
  captures `console.log` and `console.info`, compares strictly, prints one
  JSON result line.
- `test/`: `node:test` suites driving `runner.js` as a subprocess.
- `Dockerfile`: builds the runner image. Hard limits live in the `run`
  flags in `server.js`, not in the image.

## Run the service

```sh
GRADER_TOKEN="$(openssl rand -hex 32)" \
GRADER_RUNTIME=podman \
GRADER_RUNNER_IMAGE=codequest-js-runner:1.0 \
npm start
```

Build the runner image first:

```sh
podman build -t codequest-js-runner:1.0 .
```

## Deployment roles

Production grading runs under a dedicated unprivileged account with a
rootless runtime. Rootless Podman is the recommended setup. Rootful
Docker is acceptable for local development only, never as the
production deployment, because a root daemon widens every container
escape into a host compromise. Never use privileged containers, host
mounts, host networking, or the Docker socket from Laravel.

## Environment

- `GRADER_HOST`, default `127.0.0.1`. Keep loopback or private infra.
- `GRADER_PORT`, default `4317`.
- `GRADER_TOKEN`, required. Empty token refuses every request.
- `GRADER_RUNTIME`, `podman` or `docker`.
- `GRADER_RUNNER_IMAGE`, default `codequest-js-runner:1.0`.
- `GRADER_EXEC_TIMEOUT_MS`, default `10000`.
- `RUNNER_HARD_LIMIT_MS`, runner-side watchdog, default `20000`.

## Tests

```sh
npm test
```

Unit suites run the runner as a subprocess with hard timeouts. The
container integration script runs only when a runtime exists:

```sh
sh test/integration-container.sh
```
