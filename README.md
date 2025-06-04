# Feedback Voting Plugin

This plugin adds a simple voting system with optional feedback field. PHPUnit tests require the WordPress testing framework.

Since version 1.2.11 you can configure the box width in the admin settings. All labels are now readable in dark mode.

## Installing the WordPress testing framework

Before running the tests, install the WordPress development repository and create the test database:

```bash
bin/install-wp-tests.sh
```

This clones `wordpress-develop` into `/tmp/wordpress-develop`, creates the `wptests` database (if MySQL is available) and copies the configuration file.

## Running tests

Once the framework is installed, run:

```bash
phpunit -c phpunit.xml
```

