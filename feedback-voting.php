<?php
/*
Plugin Name: Feedback Voting
Plugin URI:  https://vogel-webmarketing.de/feedback-voting/
Description: Bietet ein einfaches "War diese Antwort hilfreich?" (Ja/Nein) Feedback-Voting
Version:     1.16.5
Author:      Matthes Vogel
Text Domain: feedback-voting
*/

if (!defined('ABSPATH')) {
    exit;
}

define('FEEDBACK_VOTING_VERSION', '1.16.5');
define('FEEDBACK_VOTING_DB_VERSION', '1.0.2');
define('FEEDBACK_VOTING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FEEDBACK_VOTING_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-db-manager.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'admin/class-my-feedback-plugin-admin.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-shortcode.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-ajax.php';
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/class-my-feedback-plugin-block.php';

register_activation_hook(__FILE__, array('My_Feedback_Plugin_DB_Manager', 'activate'));
register_deactivation_hook(__FILE__, array('My_Feedback_Plugin_DB_Manager', 'deactivate'));

function feedback_voting_init() {
    load_plugin_textdomain('feedback-voting', false, dirname(plugin_basename(__FILE__)) . '/languages');
    My_Feedback_Plugin_DB_Manager::maybe_update_db();
    My_Feedback_Plugin_Admin::get_instance();
    new My_Feedback_Plugin_Shortcode();
    new My_Feedback_Plugin_Ajax();
    new My_Feedback_Plugin_Block();
}
add_action('plugins_loaded', 'feedback_voting_init');

function feedback_voting_enqueue_scripts() {
    wp_enqueue_style('dashicons');
    wp_enqueue_style('wp-block-library');

    $css_version = file_exists(FEEDBACK_VOTING_PLUGIN_DIR . 'css/style.css')
        ? filemtime(FEEDBACK_VOTING_PLUGIN_DIR . 'css/style.css')
        : FEEDBACK_VOTING_VERSION;
    $js_version  = file_exists(FEEDBACK_VOTING_PLUGIN_DIR . 'js/script.js')
        ? filemtime(FEEDBACK_VOTING_PLUGIN_DIR . 'js/script.js')
        : FEEDBACK_VOTING_VERSION;

    wp_enqueue_style(
        'feedback-voting-style',
        FEEDBACK_VOTING_PLUGIN_URL . 'css/style.css',
        array(),
        $css_version
    );

    // Inline CSS-Variablen für Farben und Rundungen
    $primary_color     = get_option('feedback_voting_primary_color', '#0073aa');
    $button_color      = get_option('feedback_voting_button_color', $primary_color);
    $hover_color       = get_option('feedback_voting_button_hover_color', '#005b8d');
    $border_radius     = get_option('feedback_voting_border_radius', '9999px');
    $container_radius  = get_option('feedback_voting_container_radius', '6rem');
    $score_radius      = get_option('feedback_voting_score_radius', '6px');
    $text_color        = get_option('feedback_voting_text_color', '#1b1c1c');
    $box_width         = absint(get_option('feedback_voting_box_width', 100));
    $custom_css = "
        :root {
            --fv-primary: {$primary_color};
            --fv-button-color: {$button_color};
            --fv-button-hover-color: {$hover_color};
            --fv-border-radius: {$border_radius};
            --fv-container-radius: {$container_radius};
            --fv-score-radius: {$score_radius};
            --fv-text-color: {$text_color};
            --fv-box-width: {$box_width}%;
        }
    ";
    wp_add_inline_style('feedback-voting-style', $custom_css);

    wp_enqueue_script(
        'feedback-voting-script',
        FEEDBACK_VOTING_PLUGIN_URL . 'js/script.js',
        array('jquery'),
        $js_version,
        true
    );

    wp_localize_script('feedback-voting-script', 'feedbackVoting', array(
        'ajaxUrl'             => admin_url('admin-ajax.php'),
        'enableFeedbackField' => get_option('feedback_voting_enable_feedback_field', '1'),
        'preventMultiple'    => get_option('feedback_voting_prevent_multiple', '0'),
        'nonce'               => wp_create_nonce('feedback_nonce_action'),
        'thankYouMsg'         => get_option('feedback_voting_thankyou_text', __('Vielen Dank f\xc3\xbcr Ihr Feedback! Jede Antwort hilft uns, uns zu verbessern.', 'feedback-voting')),
    ));
}
add_action('wp_enqueue_scripts', 'feedback_voting_enqueue_scripts');

// Update Checker
require_once FEEDBACK_VOTING_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/matthesv/feedback-voting/',
    __FILE__,
    'feedback-voting'
);
$myUpdateChecker->setBranch('main');

// Helper for optional rating schema
global $feedback_voting_schema;
$feedback_voting_schema = array('score' => 0, 'count' => 0, 'name' => '', 'type' => '');

function feedback_voting_get_schema_type($post_id = 0) {
    if (!$post_id) {
        $post_id = is_singular() ? get_the_ID() : 0;
    }
    if ($post_id) {
        $type = get_post_meta($post_id, '_feedback_voting_schema_type', true);
        if ($type) {
            return $type;
        }
    }
    return 'Product';
}

function feedback_voting_get_address($post_id = 0) {
    if (!$post_id) {
        $post_id = is_singular() ? get_the_ID() : 0;
    }
    if ($post_id) {
        return get_post_meta($post_id, '_feedback_voting_address', true);
    }
    return '';
}

function feedback_voting_get_localbusiness_data($post_id = 0) {
    if (!$post_id) {
        $post_id = is_singular() ? get_the_ID() : 0;
    }
    if ($post_id) {
        $data = get_post_meta($post_id, '_feedback_voting_localbusiness', true);
        if (is_array($data)) {
            return $data;
        }
    }
    return array();
}

function feedback_voting_schema_disabled($post_id = 0) {
    return false;
}

function feedback_voting_track_schema($score, $count, $name = '', $type = null) {
    global $feedback_voting_schema;
    if ($score > $feedback_voting_schema['score']) {
        if ($type === null) {
            $type = feedback_voting_get_schema_type();
        }
        $feedback_voting_schema = array(
            'score' => $score,
            'count' => $count,
            'name'  => $name,
            'type'  => $type,
        );
    }
}

function feedback_voting_output_schema() {
    global $feedback_voting_schema;
    if (empty($feedback_voting_schema) || $feedback_voting_schema['score'] <= 0) {
        return;
    }
    $post_id = is_singular() ? get_the_ID() : 0;

    $type = !empty($feedback_voting_schema['type']) ? $feedback_voting_schema['type'] : feedback_voting_get_schema_type($post_id);
    $itemReviewed = array(
        '@type' => $type,
        'name'  => $feedback_voting_schema['name'],
    );
    if ($type === 'LocalBusiness') {
        $lb = feedback_voting_get_localbusiness_data($post_id);
        if (!empty($lb)) {
            $itemReviewed = array_merge($itemReviewed, $lb);
        } else {
            $address = feedback_voting_get_address($post_id);
            if ($address) {
                $itemReviewed['address'] = $address;
            }
        }
    }
    $data = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'AggregateRating',
        'itemReviewed'=> $itemReviewed,
        'ratingValue' => number_format($feedback_voting_schema['score'], 1),
        'ratingCount' => (int) $feedback_voting_schema['count'],
        'bestRating'  => '5',
    );
    echo '<script type="application/ld+json">' . wp_json_encode($data) . '</script>' . "\n";
}
add_action('wp_footer', 'feedback_voting_output_schema');

/**
 * Append feedback shortcode automatically to posts or pages.
 */
function feedback_voting_auto_append($content) {
    if (is_admin()) {
        return $content;
    }

    $post_id = is_singular() ? get_the_ID() : 0;
    if ($post_id && get_post_meta($post_id, '_feedback_voting_disable_auto', true)) {
        return $content;
    }

    $append = false;
    if (is_singular('post') && get_option('feedback_voting_auto_post', 0)) {
        $append = true;
    }
    if (is_page() && get_option('feedback_voting_auto_page', 0)) {
        $append = true;
    }

    if (!$append) {
        return $content;
    }

    $question = get_option(
        'feedback_voting_auto_question',
        __('War dieser Beitrag hilfreich?', 'feedback-voting')
    );

    $shortcode  = '[feedback_voting question="' . esc_attr($question) . '"]';
    $post_id       = get_the_ID();
    $schema_type   = feedback_voting_get_schema_type($post_id);
    $schema_rating = 0;

    if (get_option('feedback_voting_auto_score', 0)) {
        $shortcode .= ' [feedback_score question="' . esc_attr($question) . '" post_id="' . $post_id . '" schema_type="' . esc_attr($schema_type) . '" schema_rating="' . esc_attr($schema_rating) . '"]';
    }

    return $content . do_shortcode($shortcode);
}
add_filter('the_content', 'feedback_voting_auto_append');
