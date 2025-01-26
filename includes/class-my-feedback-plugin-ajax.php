<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_Ajax {

    public function __construct() {
        // AJAX Hooks
        add_action('wp_ajax_my_feedback_plugin_vote', array($this, 'handle_ajax_vote'));
        add_action('wp_ajax_nopriv_my_feedback_plugin_vote', array($this, 'handle_ajax_vote'));
    }

    /**
     * Nimmt per AJAX das Feedback entgegen und speichert es in der Datenbank.
     */
    public function handle_ajax_vote() {
        check_ajax_referer( 'feedback_nonce_action', 'security' ); // Optional: Zusätzlicher Nonce-Check
        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback_votes';

        $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
        $vote     = isset($_POST['vote']) ? sanitize_text_field($_POST['vote']) : '';
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';

        if (empty($question) || empty($vote)) {
            wp_send_json_error(array(
                'message' => __('Ungültige Daten übermittelt.', 'feedback-voting')
            ));
        }

        $data = array(
            'question'      => $question,
            'vote'          => $vote,
            'feedback_text' => $feedback,
            'created_at'    => current_time('mysql')
        );

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            wp_send_json_error(array(
                'message' => __('Fehler beim Speichern der Bewertung.', 'feedback-voting')
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('Bewertung erfolgreich gespeichert.', 'feedback-voting')
            ));
        }
    }
}
