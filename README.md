# Feedback Voting Plugin

This plugin adds a simple voting system with optional feedback field. PHPUnit tests require the WordPress testing framework.

Since version 1.3.2 you can customize button labels and the border radius for the feedback and score boxes. Version 1.3.1 allowed multiple `[feedback_score]` shortcodes on one page. The shortcode was introduced in 1.3.0. Version 1.2.11 added configurable box width and improved dark mode labels. Version 1.3.3 introduced layout options for the score box including alignment and text wrapping. Version 1.3.4 added padding inside the score box and centered the text. Version 1.4.0 splits the admin area into analysis and settings pages and optionally blocks repeat votes for 24 hours using a cookie. Version 1.5.0 adds optional star rating schema for Google search results. Version 1.6.0 introduces a PNG-exportable bar chart of the ten most common feedbacks by post. Version 1.7.0 can automatically append the feedback shortcode (with optional score) to posts and pages and lets you define the question globally. Version 1.7.1 aligns the question text vertically with the voting buttons.

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

