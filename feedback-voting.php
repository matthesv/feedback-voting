<?php
/*
Plugin Name: Feedback Voting
Plugin URI:  https://www.
Description: Bietet ein einfaches "War diese Antwort hilfreich?" (Ja/Nein) Feedback-Voting
Version:     1.0.23
Author:      Matthes Vogel
Text Domain: feedback-voting
*/

if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch, falls direkt aufgerufen
}

// Plugin-Konstanten definieren
define('FEEDBACK_VOTING_VERSION', '1.0.23');
define('FEEDBACK_VOTING_DB_VERSION', '1.0.1'); // unsere interne DB-Version
define('FEEDBACK_VOTING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FEEDBACK_VOTING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Benötigte Dateien einbinden
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'admin/class-my-feedback-plugin-admin.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-shortcode.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-ajax.php';

/**
 * Wird beim Aktivieren des Plugins ausgeführt.
 * Legt (bzw. aktualisiert) eine eigene Datenbank-Tabelle an und setzt Standard-Einstellungen.
 */
function feedback_voting_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback_votes';
    $charset_collate = $wpdb->get_charset_collate();

    // Angepasst: Keine "DEFAULT CURRENT_TIMESTAMP"
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        question TEXT NOT NULL,
        vote VARCHAR(10) NOT NULL,
        feedback_text TEXT NULL,
        post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Standard-Option für das Freitextfeld bei "Nein" (1 = aktiviert)
    add_option('feedback_voting_enable_feedback_field', '1');

    // DB-Versionsinfo abspeichern
    update_option('feedback_voting_db_version', FEEDBACK_VOTING_DB_VERSION);
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
 * Prüft bei jedem Laden, ob wir ein DB-Update durchführen müssen.
 */
function feedback_voting_maybe_update_db() {
    $installed_version = get_option('feedback_voting_db_version', '0.0.0');
    if (version_compare($installed_version, FEEDBACK_VOTING_DB_VERSION, '<')) {
        // Falls ein Update nötig ist, unsere Aktivierungsroutine aufrufen:
        feedback_voting_activate();
    }
}

/**
 * Initialisiert die Plugin-Klassen und führt ggf. ein Datenbank-Update durch.
 */
function feedback_voting_init() {
    // DB ggfs. aktualisieren
    feedback_voting_maybe_update_db();

    // Klassen initialisieren
    new My_Feedback_Plugin_Admin();
    new My_Feedback_Plugin_Shortcode();
    new My_Feedback_Plugin_Ajax();
}
add_action('plugins_loaded', 'feedback_voting_init');

/**
 * Lädt die Styles und Skripte im Frontend.
 */
function feedback_voting_enqueue_scripts() {

    // Dashicons und WP-Block-Stile
    wp_enqueue_style('dashicons');
    wp_enqueue_style('wp-block-library');

    // Cache-Buster anhand des Dateisystems (filemtime)
    $css_version = file_exists(FEEDBACK_VOTING_PLUGIN_DIR . 'css/style.css')
        ? filemtime(FEEDBACK_VOTING_PLUGIN_DIR . 'css/style.css')
        : FEEDBACK_VOTING_VERSION;

    $js_version = file_exists(FEEDBACK_VOTING_PLUGIN_DIR . 'js/script.js')
        ? filemtime(FEEDBACK_VOTING_PLUGIN_DIR . 'js/script.js')
        : FEEDBACK_VOTING_VERSION;

    wp_enqueue_style(
        'feedback-voting-style',
        FEEDBACK_VOTING_PLUGIN_URL . 'css/style.css',
        array(),
        $css_version,
        'all'
    );

    wp_enqueue_script(
        'feedback-voting-script',
        FEEDBACK_VOTING_PLUGIN_URL . 'js/script.js',
        array('jquery'),
        $js_version,
        true
    );

    // Nonce-Generierung und -Übergabe ans Script
    wp_localize_script('feedback-voting-script', 'feedbackVoting', array(
        'ajaxUrl'             => admin_url('admin-ajax.php'),
        'enableFeedbackField' => get_option('feedback_voting_enable_feedback_field', '1'),
        'nonce'               => wp_create_nonce('feedback_nonce_action'),
    ));
}
add_action('wp_enqueue_scripts', 'feedback_voting_enqueue_scripts');

// Plugin Update Checker laden (GitHub) - optional
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/matthesv/feedback-voting/',
    __FILE__,
    'feedback-voting'
);
$myUpdateChecker->setBranch('main');
