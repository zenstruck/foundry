#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_SRC="$(cd "${SCRIPT_DIR}/../../.." && pwd)/src"
VENDOR_SRC="${SCRIPT_DIR}/vendor/zenstruck/foundry/src"

if [ ! -d "${VENDOR_SRC}" ]; then
    echo "Directory vendor/zenstruck/foundry/src does not exist yet."
    exit 1
fi

for item in "${ROOT_SRC}"/*; do
    name=$(basename "$item")

    if [ "$name" = "Test" ]; then
        continue
    fi

    target="${VENDOR_SRC}/${name}"

    rm -rf "$target"

    ln -s "$item" "$target"
done

echo -e "\nSymlinks created in ${VENDOR_SRC}\n"
