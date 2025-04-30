<?php
/*
Plugin Name: Feedback Voting
Plugin URI:  https://vogel-webmarketing.de/feedback-voting/
Description: Bietet ein einfaches "War diese Antwort hilfreich?" (Ja/Nein) Feedback-Voting
Version:     1.2.5
Author:      Matthes Vogel
Text Domain: feedback-voting
*/

if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch, falls direkt aufgerufen
}

// Plugin-Konstanten definieren
define('FEEDBACK_VOTING_VERSION', '1.2.3');
define('FEEDBACK_VOTING_DB_VERSION', '1.0.1'); // unsere interne DB-Version
define('FEEDBACK_VOTING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FEEDBACK_VOTING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Benötigte Dateien einbinden
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-db-manager.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'admin/class-my-feedback-plugin-admin.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-shortcode.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-ajax.php';

/**
 * Registriert die Aktivierung/Deaktivierung mittels unserer DB-Manager-Klasse.
 */
register_activation_hook(__FILE__, array('My_Feedback_Plugin_DB_Manager', 'activate'));
register_deactivation_hook(__FILE__, array('My_Feedback_Plugin_DB_Manager', 'deactivate'));

/**
 * Initialisiert das Plugin: führt ggf. ein Datenbank-Update durch
 * und lädt unsere Haupt-Klassen.
 */
function feedback_voting_init() {
    // DB ggfs. aktualisieren
    My_Feedback_Plugin_DB_Manager::maybe_update_db();

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

    // Custom Design-Farbe aus Option
    $primary_color = get_option('feedback_voting_primary_color', '#0073aa');
    $custom_css = ':root{--fv-primary:' . esc_attr($primary_color) . ';}';
    wp_add_inline_style('feedback-voting-style', $custom_css);

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
