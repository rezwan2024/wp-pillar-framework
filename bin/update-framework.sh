#!/usr/bin/env bash
#
# update-framework.sh — pull the latest framework/ from wp-pillar-framework
# and reapply this plugin's namespace rename automatically.
#
# Usage (run from your plugin root):
#   bash bin/update-framework.sh [ref]
#
#   ref   Branch or tag to pull from wp-pillar-framework. Defaults to "main".
#
# What it does:
#   1. Detects this plugin's framework namespace (whatever you renamed
#      WPPillar\Framework to in Step 2 of the README) by reading it back
#      out of framework/src/Application.php.
#   2. Backs up your current framework/ folder to framework.backup.<timestamp>/.
#   3. Downloads the requested ref of wp-pillar-framework and replaces
#      framework/ with its framework/ folder.
#   4. Reapplies the namespace rename to the freshly-pulled files.
#   5. Regenerates the Composer autoloader.
#
# What it never touches: app/, boot/, config/, database/, plugin-entry.php —
# only framework/ is ever overwritten. Nothing here is destructive: your
# previous framework/ is preserved in the timestamped backup folder, and
# nothing is committed to git for you.
#
# After running this, review the diff, run your plugin's test suite (or at
# minimum activate/deactivate it on a staging site), then commit.

set -euo pipefail

UPSTREAM_REPO="https://github.com/rezwan2024/wp-pillar-framework"
UPSTREAM_NAMESPACE='WPPillar\\Framework'
REF="${1:-main}"

if [ ! -d "framework/src" ] || [ ! -f "composer.json" ]; then
    echo "Error: run this from your plugin's root directory (framework/src and composer.json must exist here)." >&2
    exit 1
fi

if [ ! -f "framework/src/Application.php" ]; then
    echo "Error: framework/src/Application.php not found — can't detect your plugin's namespace." >&2
    exit 1
fi

# Detect this plugin's current framework namespace, e.g. "YourPlugin\Framework".
CURRENT_NAMESPACE_LINE=$(grep -m1 -E '^namespace .+\\Framework;' framework/src/Application.php || true)

if [ -z "$CURRENT_NAMESPACE_LINE" ]; then
    echo "Error: couldn't find a 'namespace X\\Framework;' declaration in framework/src/Application.php." >&2
    echo "Has this plugin's framework already been renamed per Step 2 of the README?" >&2
    exit 1
fi

CURRENT_NAMESPACE=$(echo "$CURRENT_NAMESPACE_LINE" | sed -E 's/^namespace (.+);$/\1/')

echo "Detected framework namespace: ${CURRENT_NAMESPACE}"
echo "Pulling framework/ from ${UPSTREAM_REPO} @ ${REF}..."

WORK_DIR=$(mktemp -d)
trap 'rm -rf "$WORK_DIR"' EXIT

curl -sL "${UPSTREAM_REPO}/archive/refs/heads/${REF}.tar.gz" -o "${WORK_DIR}/upstream.tar.gz" \
    || curl -sL "${UPSTREAM_REPO}/archive/refs/tags/${REF}.tar.gz" -o "${WORK_DIR}/upstream.tar.gz"

if [ ! -s "${WORK_DIR}/upstream.tar.gz" ]; then
    echo "Error: failed to download ref '${REF}' from ${UPSTREAM_REPO}." >&2
    exit 1
fi

mkdir -p "${WORK_DIR}/upstream"
tar -xzf "${WORK_DIR}/upstream.tar.gz" -C "${WORK_DIR}/upstream" --strip-components=1

if [ ! -d "${WORK_DIR}/upstream/framework" ]; then
    echo "Error: downloaded archive has no framework/ folder — unexpected repo layout." >&2
    exit 1
fi

BACKUP_DIR="framework.backup.$(date +%Y%m%d%H%M%S)"
echo "Backing up your current framework/ to ${BACKUP_DIR}/"
cp -r framework "${BACKUP_DIR}"

echo "Replacing framework/ with the fresh copy..."
rm -rf framework
cp -r "${WORK_DIR}/upstream/framework" framework

echo "Reapplying your namespace rename (${UPSTREAM_NAMESPACE} -> ${CURRENT_NAMESPACE//\\/\\\\})..."
find framework -name "*.php" \
    -exec sed -i '' "s/${UPSTREAM_NAMESPACE}/${CURRENT_NAMESPACE//\\/\\\\}/g" {} \;

echo "Regenerating the Composer autoloader..."
composer dump-autoload -o

cat <<EOF

Done. framework/ has been updated to ${UPSTREAM_REPO}@${REF} and re-namespaced to ${CURRENT_NAMESPACE}.

Your previous framework/ is preserved at ${BACKUP_DIR}/ — nothing has been committed.

Next steps:
  1. Review what changed:   diff -rq ${BACKUP_DIR} framework
  2. Test the plugin (activate/deactivate on staging, run your test suite)
  3. Commit framework/ and delete ${BACKUP_DIR}/ once you're satisfied
EOF
