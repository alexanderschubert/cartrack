#!/bin/sh
set -eu

repo_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
pnpm_home="$repo_root/docker/pnpm"
mkdir -p "$pnpm_home"

docker run --rm \
    --volume "$pnpm_home:/pnpm" \
    node:22 \
    npm install --prefix /pnpm --no-audit --no-fund pnpm@11.25.0

printf 'Prepared pnpm %s for the Docker build.\n' 11.25.0
