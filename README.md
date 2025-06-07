# Feedback Voting Plugin

This plugin adds a simple voting system with optional feedback field. PHPUnit tests require the WordPress testing framework.

Since version 1.3.2 you can customize button labels and the border radius for the feedback and score boxes. Version 1.3.1 allowed multiple `[feedback_score]` shortcodes on one page. The shortcode was introduced in 1.3.0. Version 1.2.11 added configurable box width and improved dark mode labels. Version 1.3.3 introduced layout options for the score box including alignment and text wrapping. Version 1.3.4 added padding inside the score box and centered the text. Version 1.4.0 splits the admin area into analysis and settings pages and optionally blocks repeat votes for 24 hours using a cookie.

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

