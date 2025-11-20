#!/usr/bin/env bash
#
# Test script for pantheon-systems/composer-advisory-manager plugin
#
# This script:
# 1. Creates a test project with known vulnerable dependencies
# 2. Verifies that Composer >= 2.9 blocks the install due to security advisories
# 3. Installs the advisory manager plugin from local path
# 4. Verifies that the plugin allows the install to succeed
# 5. Validates that advisory IDs are auto-added to the ignore list
#
# Usage: ./tests/test-composer-plugin.sh
# Override plugin path: PLUGIN_PATH=/path/to/plugin ./tests/test-composer-plugin.sh
#
set -e

# Path where the plugin repo exists locally (auto-detect from script location)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Plugin is one directory up from tests/
PLUGIN_PATH="${PLUGIN_PATH:-$(dirname "$SCRIPT_DIR")}"

# Verify plugin path exists and has composer.json
if [ ! -f "$PLUGIN_PATH/composer.json" ]; then
  echo "❌ ERROR: Plugin composer.json not found at: $PLUGIN_PATH"
  echo "   Set PLUGIN_PATH environment variable to the correct location"
  exit 1
fi

# Working directory for the repro test
TESTDIR="/tmp/composer-audit-repro"

# Verify Composer version >= 2.9
COMPOSER_VERSION=$(composer --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
COMPOSER_MAJOR=$(echo "$COMPOSER_VERSION" | cut -d. -f1)
COMPOSER_MINOR=$(echo "$COMPOSER_VERSION" | cut -d. -f2)

if [ "$COMPOSER_MAJOR" -lt 2 ] || ([ "$COMPOSER_MAJOR" -eq 2 ] && [ "$COMPOSER_MINOR" -lt 9 ]); then
  echo "❌ ERROR: This test requires Composer >= 2.9 (found $COMPOSER_VERSION)"
  echo "   Audit blocking was introduced in Composer 2.9"
  exit 1
fi

echo "✓ Using Composer $COMPOSER_VERSION"

# Cleanup function (optional - uncomment to auto-cleanup on success)
# cleanup() {
#   if [ $? -eq 0 ]; then
#     echo "🧹 Cleaning up test directory..."
#     rm -rf "$TESTDIR"
#   fi
# }
# trap cleanup EXIT

echo "🔄 Cleaning previous test directory..."
rm -rf "$TESTDIR"
mkdir -p "$TESTDIR"
cd "$TESTDIR"

echo "📌 Creating intentionally vulnerable composer.json..."
cat > composer.json << 'EOF'
{
  "name": "pantheon/test-repro",
  "version": "1.0.0",
  "description": "Test project for composer-advisory-manager plugin",
  "type": "project",
  "require": {
    "symfony/process": "5.4.40",
    "twig/twig": "3.11.1"
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
EOF

echo "⏳ Reproducing original failure — expecting Composer to BLOCK on advisories..."
set +e
composer update --no-interaction >fail.log 2>&1
EXIT_CODE=$?
set -e

if [ $EXIT_CODE -eq 0 ]; then
  echo "❌ ERROR: Composer DID NOT fail as expected — this means the environment isn't reproducing the original bug."
  echo "   The vulnerable packages may have been updated. Check fail.log for details."
  cat fail.log
  exit 1
fi

echo "✔️ Confirmed: Composer install/update fails due to audit blocking."
echo "   (proof stored in $TESTDIR/fail.log)"

echo "➕ Installing advisory manager plugin from LOCAL PATH: $PLUGIN_PATH"
composer config repositories.pantheon-advisory-manager '{ "type": "path", "url": "'"$PLUGIN_PATH"'" }'
composer config --no-plugins allow-plugins.pantheon-systems/composer-advisory-manager true

echo "   Attempting simple installation (plugin will auto-configure)..."
set +e
composer require pantheon-systems/composer-advisory-manager --no-interaction >install.log 2>&1
INSTALL_EXIT=$?
set -e

if [ $INSTALL_EXIT -ne 0 ]; then
  echo "   ⚠️  Simple installation failed (expected with vulnerable packages)"
  echo "   Using manual method: temporarily disabling audit blocking..."
  composer config audit.block-insecure false
  composer require pantheon-systems/composer-advisory-manager --no-interaction >install.log 2>&1

  echo "🧹 Unsetting manual audit config to let plugin take over..."
  composer config --unset audit.block-insecure
else
  echo "   ✅ Simple installation succeeded!"
fi

echo "🚦 Running composer update WITH plugin enabled — should now SUCCEED"
composer update --no-interaction >pass.log 2>&1

echo "🧪 Validating plugin behavior..."
echo
echo "🔍 Checking that plugin suppressed audit blocking..."
if grep -qi "audit.*block" pass.log || grep -qi "Pantheon.*Plugin" pass.log; then
  echo "  ✔ Plugin emitted expected advisory warning"
else
  echo "  ❌ Validation failed: expected plugin warnings not found"
  echo "  📄 Contents of pass.log:"
  cat pass.log
  exit 1
fi

echo "🔍 Checking that advisory IDs were auto-added to audit.ignore..."
# Check install.log since that's where advisories are first detected and added
ADDED=$(grep "Added advisory ID to ignore list" install.log || true)
if [ -n "$ADDED" ]; then
  echo "  ✔ Advisory IDs auto-appended:"
  echo "$ADDED"
else
  echo "  ❌ No advisory IDs were appended — may indicate upstream packages changed"
  echo "  📄 Pantheon plugin output from install.log:"
  grep -i "pantheon" install.log || echo "  (no Pantheon output found)"
  exit 1
fi

echo
echo "🎉 SUCCESS — plugin worked as expected!"
echo "➡ Composer update originally failed"
echo "➡ Plugin installed from local path"
echo "➡ Composer update then succeeded and advisories were ignored"
echo
echo "📁 Logs for reference:"
echo "  - Failure before plugin: $TESTDIR/fail.log"
echo "  - Success after plugin:  $TESTDIR/pass.log"