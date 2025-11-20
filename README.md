# Pantheon Composer Advisory Manager

[![CI](https://github.com/pantheon-systems/composer-advisory-manager/actions/workflows/ci.yml/badge.svg)](https://github.com/pantheon-systems/composer-advisory-manager/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)
[![Composer](https://img.shields.io/badge/composer-%3E%3D2.2-885630.svg)](https://getcomposer.org/)

A Composer plugin that manages security advisories to maintain build continuity while promoting remediation on Pantheon.

---

## 🎯 Problem

Pantheon customers may encounter Composer installation failures such as:

```
Your requirements could not be resolved to an installable set of packages.

- symfony/process v5.4.40 was not loaded because it is affected by security advisories
- To ignore the advisories, add ("PKSA-wws7-mr54-jsny") to the audit "ignore" config
- To turn the feature off entirely, set "block-insecure": false in your "audit" config
```

This behavior was introduced in Composer **2.9**, which blocks dependency resolution if any packages are affected by security advisories. This can cause **install or update failures even when a site is otherwise stable and secure**.

The Pantheon `composer-advisory-manager` plugin keeps builds working by:
- **Managing security advisories to maintain build continuity**
- **Automatically tracking advisory IDs in the `audit.ignore` list**
- **Displaying security warnings prominently in build output**
- **Providing remediation guidance for site owners and developers**

---

## 🚀 Installation

### Simple Installation (Recommended)

For most users, just run these two commands:

```bash
composer config --no-plugins allow-plugins.pantheon-systems/composer-advisory-manager true
composer require pantheon-systems/composer-advisory-manager
```

The plugin will automatically configure advisory management during installation.

### Manual Installation (If Simple Method Fails)

If your project is **already blocked** and the simple method fails, use these commands:

```bash
composer config --no-plugins allow-plugins.pantheon-systems/composer-advisory-manager true
composer config audit.block-insecure false
composer require pantheon-systems/composer-advisory-manager
```

Once installed, the plugin activates automatically and will handle all future `composer install` and `composer update` commands by managing security advisories and maintaining build continuity.

**What these commands do**:
- `allow-plugins` - Grants permission for the plugin to run (required by Composer 2.2+)
- `audit.block-insecure false` - (Manual method only) Temporarily disables audit blocking to allow installation
- The plugin automatically manages audit settings after installation

---

## 🔍 What the plugin does

| Behavior                         | Details                                                                 |
|----------------------------------|-------------------------------------------------------------------------|
| Prevents install/update failures | Forces `"audit.block-insecure": false` unless the project already sets this |
| Automatically ignores advisories | Adds advisory IDs to `"config.audit.ignore"` on `composer update`        |
| Surfaces remediation steps       | Prints actionable upgrade instructions so you can resolve issues later   |
| Does not hide risk               | Warnings are printed on every run so security status is visible         |

---

## 🔒 Security Model

This plugin **does not suppress vulnerability reporting**. It only prevents dependency blockers that stop development.

Security advisories are still:
- Displayed in Composer output
- Logged to CI build logs
- Suggested for remediation during upgrades

This avoids failed builds while helping teams upgrade safely over time.

---

## 🧹 Removing or disabling the plugin

To fully restore strict Composer auditing:

```bash
composer config --unset audit.block-insecure
composer remove pantheon-systems/composer-advisory-manager
```

To temporarily turn strict mode back on:

```bash
composer config audit.block-insecure true
```

---

## 🧩 When should I still upgrade affected packages?

If the remediation message suggests updating to a newer safe version (for example `twig/twig ^3.22` or `symfony/process ^5.4.47`), we strongly recommend doing so at the earliest safe opportunity.

The plugin keeps your builds unblocked now — **but does not replace patching.**

---

## 🆘 When to contact Pantheon Support

Open a support ticket if:
- You attempt the recommended version upgrades and still hit audit blockers
- You suspect a legitimate security issue in a dependency
- CI builds fail even after the plugin is installed



---

## 🧪 Development & Testing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup and testing instructions.

To run the integration test suite:

```bash
./tests/test-composer-plugin.sh
```

---

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history and changes.

---

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

Copyright (c) 2025 Pantheon Systems, Inc.
