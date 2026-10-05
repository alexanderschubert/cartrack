#!/bin/sh
set -eu

repo_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
pnpm_home="$repo_root/docker/pnpm"
mkdir -p "$pnpm_home"

curl -fsSL https://get.pnpm.io/install.sh | env \
    ENV="$pnpm_home/.profile" \
    PNPM_HOME="$pnpm_home" \
    PNPM_VERSION=11.25.0 \
    SHELL=/bin/sh \
    sh -

chmod +x "$pnpm_home/pnpm"
printf 'Prepared pnpm %s for the Docker build.\n' 11.25.0
