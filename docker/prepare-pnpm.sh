#!/bin/sh
set -eu

repo_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
pnpm_home="$repo_root/docker/pnpm"
pnpm_package="$pnpm_home/package"
pnpm_archive="$pnpm_home/pnpm-11.25.0.tgz"
mkdir -p "$pnpm_package"

curl -fsSL https://registry.npmjs.org/pnpm/-/pnpm-11.25.0.tgz -o "$pnpm_archive"
tar -xzf "$pnpm_archive" --strip-components=1 -C "$pnpm_package"
rm "$pnpm_archive"

printf 'Prepared pnpm %s for the Docker build.\n' 11.25.0
