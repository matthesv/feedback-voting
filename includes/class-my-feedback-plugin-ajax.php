<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_Ajax {

    public function __construct() {
        // AJAX Hooks: für eingeloggte und ausgeloggte Benutzer
        add_action('wp_ajax_my_feedback_plugin_vote', array($this, 'handle_ajax_vote'));
        add_action('wp_ajax_nopriv_my_feedback_plugin_vote', array($this, 'handle_ajax_vote'));
    }

    /**
     * Nimmt per AJAX das Feedback entgegen und speichert es in der Datenbank.
     */
    public function handle_ajax_vote() {
        // Nonce-Check (schlägt fehl, wenn Cache oder abgelaufene Nonce)
        check_ajax_referer('feedback_nonce_action', 'security');

        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback_votes';

        $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
        $vote     = isset($_POST['vote']) ? sanitize_text_field($_POST['vote']) : '';
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';
        // Neu: post_id übernehmen
        $post_id  = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($question) || empty($vote)) {
            wp_send_json_error(array(
                'message' => __('Ungültige Daten übermittelt.', 'feedback-voting')
            ));
        }

        $data = array(
            'question'      => $question,
            'vote'          => $vote,
            'feedback_text' => $feedback,
            'post_id'       => $post_id,
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
