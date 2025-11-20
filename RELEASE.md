# Release Preparation Guide

This document outlines the steps to push the package to GitHub and create a release.

## Pre-Release Checklist

- [x] Package renamed to `composer-advisory-manager`
- [x] All files updated with new package name
- [x] Plugin class renamed to `AdvisoryManagerPlugin`
- [x] Test script moved to `tests/` directory
- [x] GitHub Actions workflow configured
- [x] README.md updated with badges and documentation
- [x] CHANGELOG.md created
- [x] CONTRIBUTING.md created
- [x] LICENSE file created
- [x] .gitignore configured
- [x] composer.json metadata added (keywords, homepage, support, authors)
- [x] composer.json minimum-stability set to "stable"

## Step 1: Commit Initial Release

```bash
git commit -m "Initial release v1.0.0

- Composer plugin for managing security advisories
- Maintains build continuity while promoting remediation
- Auto-configures audit settings
- Displays security warnings prominently
- Supports PHP 7.4-8.3 and Composer 2.2+
"
```

## Step 2: Add Remote Repository

```bash
git remote add origin git@github.com:pantheon-systems/composer-advisory-manager.git
```

## Step 3: Push to GitHub

```bash
git push -u origin main
```

## Step 4: Create GitHub Release

### Option A: Via GitHub Web Interface

1. Go to https://github.com/pantheon-systems/composer-advisory-manager/releases/new
2. Click "Choose a tag" and type `v1.0.0`
3. Click "Create new tag: v1.0.0 on publish"
4. Set release title: `v1.0.0 - Initial Release`
5. Copy release notes from CHANGELOG.md
6. Click "Publish release"

### Option B: Via GitHub CLI

```bash
gh release create v1.0.0 \
  --title "v1.0.0 - Initial Release" \
  --notes-file CHANGELOG.md
```

### Option C: Via Git Tag

```bash
git tag -a v1.0.0 -m "Release v1.0.0 - Initial Release"
git push origin v1.0.0
```

Then create the release on GitHub using the tag.

## Step 5: Submit to Packagist

1. Go to https://packagist.org/packages/submit
2. Enter repository URL: `https://github.com/pantheon-systems/composer-advisory-manager`
3. Click "Check"
4. Click "Submit"

### Enable Auto-Update Hook (Recommended)

1. Go to package settings on Packagist
2. Copy the webhook URL
3. Add webhook to GitHub repository:
   - Go to Settings → Webhooks → Add webhook
   - Paste Packagist webhook URL
   - Content type: `application/json`
   - Select "Just the push event"
   - Click "Add webhook"

## Step 6: Verify Installation

Test that the package can be installed from Packagist:

```bash
# Create test project
mkdir /tmp/test-advisory-manager
cd /tmp/test-advisory-manager
composer init --no-interaction

# Install the plugin
composer config --no-plugins allow-plugins.pantheon-systems/composer-advisory-manager true
composer require pantheon-systems/composer-advisory-manager
```

## Post-Release Tasks

- [ ] Update CHANGELOG.md with release date
- [ ] Announce release (if applicable)
- [ ] Monitor GitHub Issues for feedback
- [ ] Update documentation if needed

## Future Releases

For subsequent releases:

1. Update version in CHANGELOG.md
2. Commit changes
3. Create new tag: `git tag -a vX.Y.Z -m "Release vX.Y.Z"`
4. Push tag: `git push origin vX.Y.Z`
5. Create GitHub release
6. Packagist will auto-update (if webhook configured)

## Versioning

This project follows [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.x.x): Breaking changes
- **MINOR** (x.1.x): New features, backward compatible
- **PATCH** (x.x.1): Bug fixes, backward compatible

