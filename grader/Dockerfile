FROM docker.io/library/node:22-alpine

# Runner image for ephemeral grading sandboxes. The container is the
# security boundary; all hard limits (network, user, read-only root,
# capabilities, pids, cpu, memory, timeout) are applied by the grader
# service at `run` time, not baked in here.

WORKDIR /app
COPY package.json ./
COPY src/ ./src/

# Alpine already ships nobody:nogroup as 65534. The service also passes
# --user 65534:65534 explicitly at run time, so this is a second lock.
USER 65534:65534

# Default command is the student-code runner, not the service. The image
# is the sandbox: the grader service spawns it per submission with an
# explicit `node /app/src/runner.js` entrypoint override. The service
# itself runs from a source checkout on private infrastructure, never
# from this image, because the two are different trust roles.
CMD ["node", "src/runner.js"]
