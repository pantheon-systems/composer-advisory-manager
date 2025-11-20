# Contributing to Composer Advisory Manager

Thank you for your interest in contributing to the Pantheon Composer Advisory Manager!

## Development Setup

1. Clone the repository:
   ```bash
   git clone git@github.com:pantheon-systems/composer-advisory-manager.git
   cd composer-advisory-manager
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Run the test suite:
   ```bash
   ./tests/test-composer-plugin.sh
   ```

## Testing

### Running Tests Locally

The integration test requires Composer >= 2.9:

```bash
./tests/test-composer-plugin.sh
```

### Testing with Different Composer Versions

```bash
# Install specific Composer version
composer self-update 2.9.0

# Run tests
./tests/test-composer-plugin.sh
```

## Code Style

- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Add comments for complex logic
- Keep methods focused and single-purpose

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests to ensure everything works
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to your fork (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### PR Guidelines

- Provide a clear description of the changes
- Reference any related issues
- Ensure all tests pass
- Update documentation if needed
- Add entries to CHANGELOG.md

## Reporting Issues

When reporting issues, please include:

- Composer version (`composer --version`)
- PHP version (`php --version`)
- Operating system
- Steps to reproduce
- Expected vs actual behavior
- Any error messages or logs

## Questions?

Feel free to open an issue for questions or discussions about the plugin.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

