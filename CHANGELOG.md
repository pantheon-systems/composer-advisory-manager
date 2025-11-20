# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-01-XX

### Added
- Initial release of Composer Advisory Manager plugin
- Automatic management of Composer security advisories to maintain build continuity
- Auto-configuration of `audit.block-insecure` setting during plugin activation
- Automatic tracking of advisory IDs in `audit.ignore` list
- Prominent display of security advisories in build output
- Remediation guidance for affected packages
- Support for PHP 7.4, 8.0, 8.1, 8.2, 8.3
- Support for Composer 2.2+ (with allow-plugins requirement)
- Comprehensive integration test suite
- GitHub Actions CI/CD workflow

### Features
- **Advisory Management**: Automatically manages security advisories without blocking builds
- **Build Continuity**: Prevents Composer 2.9+ audit blocking from breaking deployments
- **Security Visibility**: Displays security warnings prominently in output
- **Remediation Guidance**: Provides actionable upgrade recommendations
- **Auto-Configuration**: Configures audit settings automatically on activation
- **Advisory Tracking**: Maintains list of ignored advisory IDs

### Security
- Does not suppress vulnerability reporting
- Displays all security advisories in build output
- Encourages remediation through prominent warnings
- Maintains full audit trail of ignored advisories

[Unreleased]: https://github.com/pantheon-systems/composer-advisory-manager/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/pantheon-systems/composer-advisory-manager/releases/tag/v1.0.0

