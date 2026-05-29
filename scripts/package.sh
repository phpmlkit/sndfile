#!/usr/bin/env bash
set -euo pipefail

# Create a distribution archive for a given platform.
# Packages source code + that platform's lib/ binaries only.
#
# Usage:
#   ./scripts/package.sh <platform>
#
# Examples:
#   ./scripts/package.sh darwin-arm64
#   ./scripts/package.sh darwin-x86_64
#   ./scripts/package.sh linux-x86_64
#   ./scripts/package.sh windows-64

if [[ $# -lt 1 ]]; then
    echo "Usage: $0 <platform>" >&2
    echo "  platform   e.g. darwin-arm64, darwin-x86_64, linux-x86_64, windows-64" >&2
    exit 1
fi

PLATFORM="$1"

if [[ "$PLATFORM" == windows-* ]]; then
    FORMAT="zip"
else
    FORMAT="tar.gz"
fi

DIST_NAME="dist-${PLATFORM}"

echo "Creating distribution: ${DIST_NAME} (${FORMAT})"
echo "Platform: ${PLATFORM}"

rm -rf "${DIST_NAME}"
rm -f "${DIST_NAME}.tar.gz" "${DIST_NAME}.zip"

mkdir -p "${DIST_NAME}"

# Source directories to include
dirs_to_include=(
    "src"
    "include"
    "lib/${PLATFORM}"
)

files_to_include=(
    "composer.json"
    "LICENSE"
    "README.md"
    "CONTRIBUTING.md"
    "THIRD-PARTY-NOTICES.md"
)

for dir in "${dirs_to_include[@]}"; do
    if [[ -d "$dir" ]]; then
        mkdir -p "$(dirname "${DIST_NAME}/${dir}")"
        cp -r "$dir" "${DIST_NAME}/${dir}"
    else
        echo "error: expected directory '${dir}' not found" >&2
        exit 1
    fi
done

for file in "${files_to_include[@]}"; do
    if [[ -f "$file" ]]; then
        cp "$file" "${DIST_NAME}/"
    else
        echo "warning: expected file '${file}' not found, skipping" >&2
    fi
done

# Clean up unnecessary files
find "${DIST_NAME}" -name '.DS_Store' -delete

if [[ "$FORMAT" == "zip" ]]; then
    if command -v zip >/dev/null 2>&1; then
        (cd . && zip -rq "${DIST_NAME}.zip" "${DIST_NAME}")
        echo "Created ${DIST_NAME}.zip"
    elif command -v 7z >/dev/null 2>&1; then
        7z a -r "${DIST_NAME}.zip" "${DIST_NAME}" >/dev/null
        echo "Created ${DIST_NAME}.zip (using 7z)"
    else
        echo "error: 'zip' or '7z' is required for Windows-style archives" >&2
        exit 1
    fi
    rm -rf "${DIST_NAME}"
    ls -lh "${DIST_NAME}.zip"
else
    tar -czf "${DIST_NAME}.tar.gz" "${DIST_NAME}"
    rm -rf "${DIST_NAME}"
    ls -lh "${DIST_NAME}.tar.gz"
    echo "Created ${DIST_NAME}.tar.gz"
fi
