<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_Shortcode {

    public function __construct() {
        add_shortcode('feedback_voting', array($this, 'render_shortcode'));
    }

    /**
     * Rendert den Shortcode [feedback_voting question="..."]
     */
    public function render_shortcode($atts) {
        // Standardwerte festlegen
        $atts = shortcode_atts(array(
            'question' => __('Hat Ihnen diese Antwort geholfen?', 'feedback-voting'),
        ), $atts, 'feedback_voting');

        $question = $atts['question'];

        ob_start();
        ?>
        <div class="feedback-voting-container wp-block-group" data-question="<?php echo esc_attr($question); ?>">
            <p class="feedback-question wp-block-paragraph"><?php echo esc_html($question); ?></p>

            <!-- Buttons im WP-Block-Design -->
            <div class="wp-block-buttons">
                <div class="wp-block-button">
                    <button class="wp-block-button__link feedback-button feedback-yes" data-vote="yes">
                        <span class="dashicons dashicons-thumbs-up"></span>
                        <?php _e('Ja', 'feedback-voting'); ?>
                    </button>
                </div>
                <div class="wp-block-button">
                    <button class="wp-block-button__link feedback-button feedback-no" data-vote="no">
                        <span class="dashicons dashicons-thumbs-down"></span>
                        <?php _e('Nein', 'feedback-voting'); ?>
                    </button>
                </div>
            </div>

            <!-- Freitext-Bereich bei "Nein" (anfangs ausgeblendet) -->
            <div class="feedback-no-text-container" style="display: none;">
                <label for="feedback-no-text">
                    <?php _e('Helfen Sie uns, was können wir besser machen?', 'feedback-voting'); ?>
                </label>
                <textarea id="feedback-no-text" rows="3"></textarea>
            </div>

            <!-- Danke-Nachricht (anfangs ausgeblendet) -->
            <div class="feedback-thankyou-message" style="display: none;">
                <?php _e('Vielen Dank für Ihr Feedback!', 'feedback-voting'); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
