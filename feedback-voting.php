<?php
/*
Plugin Name: Feedback Voting
Plugin URI:  https://www.abg.de
Description: Bietet ein einfaches "Hat Ihnen diese Antwort geholfen?" (Ja/Nein) Feedback-Voting
Version:     1.0.6
Author:      Matthes Vogel
Text Domain: feedback-voting
*/

if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch, falls direkt aufgerufen
}

// Plugin-Konstanten definieren
define('FEEDBACK_VOTING_VERSION', '1.0.6');
define('FEEDBACK_VOTING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FEEDBACK_VOTING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Benötigte Dateien einbinden
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'admin/class-my-feedback-plugin-admin.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-shortcode.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-ajax.php';

/**
 * Wird beim Aktivieren des Plugins ausgeführt.
 * Legt z.B. eine eigene Datenbank-Tabelle an und setzt Standard-Einstellungen.
 */
function feedback_voting_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback_votes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        question TEXT NOT NULL,
        vote VARCHAR(10) NOT NULL,
        feedback_text TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Standard-Option für das Freitextfeld bei "Nein"
    add_option('feedback_voting_enable_feedback_field', '1');
}
register_activation_hook(__FILE__, 'feedback_voting_activate');

/**
 * Wird beim Deaktivieren des Plugins ausgeführt.
 */
function feedback_voting_deactivate() {
    // Ggf. weitere Aufräumarbeiten
}
register_deactivation_hook(__FILE__, 'feedback_voting_deactivate');

/**
 * Initialisiert die Plugin-Klassen.
 */
function feedback_voting_init() {
    new My_Feedback_Plugin_Admin();
    new My_Feedback_Plugin_Shortcode();
    new My_Feedback_Plugin_Ajax();
}
add_action('plugins_loaded', 'feedback_voting_init');

/**
 * Lädt die Styles und Skripte im Frontend.
 */
function feedback_voting_enqueue_scripts() {
    wp_enqueue_style(
        'feedback-voting-style',
        FEEDBACK_VOTING_PLUGIN_URL . 'css/style.css',
        array(),
        FEEDBACK_VOTING_VERSION,
        'all'
    );

    wp_enqueue_script(
        'feedback-voting-script',
        FEEDBACK_VOTING_PLUGIN_URL . 'js/script.js',
        array('jquery'),
        FEEDBACK_VOTING_VERSION,
        true
    );

    // Übergibt PHP-Daten an das JavaScript (AJAX-URL, Plugin-Einstellungen etc.)
    wp_localize_script('feedback-voting-script', 'feedbackVoting', array(
        'ajaxUrl'             => admin_url('admin-ajax.php'),
        'enableFeedbackField' => get_option('feedback_voting_enable_feedback_field', '1'),
    ));
}
add_action('wp_enqueue_scripts', 'feedback_voting_enqueue_scripts');

// Plugin Update Checker laden (GitHub) - falls erwünscht, kann man das beibehalten
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/matthesv/feedback-voting/',
    __FILE__,
    'feedback-voting'
);
$myUpdateChecker->setBranch('main');
