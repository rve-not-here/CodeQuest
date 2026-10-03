#!/usr/bin/env bash
set -eu
V3_LAB_DIR="$(cd -- "$(dirname -- "$0")" && pwd)"
V3_PROJECT_DIR="$(cd -- "$V3_LAB_DIR/../.." && pwd)"
exec node "$V3_PROJECT_DIR/node_modules/vite/bin/vite.js" --config "$V3_LAB_DIR/vite.config.js"
