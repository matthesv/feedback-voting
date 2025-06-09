<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_Shortcode {

    public function __construct() {
        add_shortcode('feedback_voting', array($this, 'render_shortcode'));
        add_shortcode('feedback_score', array($this, 'render_score_shortcode'));
    }

    /**
     * Rendert den Shortcode [feedback_voting question="..."]
     * Ermittelt außerdem die aktuelle Post-ID, um sie mitzuspeichern.
     */
    public function render_shortcode($atts) {
        // Standardwerte festlegen
        $atts = shortcode_atts(array(
            // Neue Default-Frage:
            'question' => __('War diese Antwort hilfreich?', 'feedback-voting'),
        ), $atts, 'feedback_voting');

        $question = $atts['question'];

        // Post-ID ermitteln
        global $post;
        $post_id = (is_object($post) && isset($post->ID)) ? $post->ID : 0;

        // Generate unique ID for textarea and label to avoid conflicts
        $unique_id = 'feedback-no-text-' . uniqid();

        $yes_label    = get_option('feedback_voting_yes_label', __('Ja, war sie', 'feedback-voting'));
        $no_label     = get_option('feedback_voting_no_label', __('Nein, leider nicht', 'feedback-voting'));
        $submit_label = get_option('feedback_voting_submit_label', __('Feedback senden', 'feedback-voting'));

        ob_start();
        ?>
        <!-- Hauptcontainer mit Rahmen -->
        <div class="feedback-voting-container"
             data-question="<?php echo esc_attr($question); ?>"
             data-postid="<?php echo esc_attr($post_id); ?>">

            <div class="feedback-voting-top-row">
                <p class="feedback-question"><?php echo esc_html($question); ?></p>

                <!-- Daumen hoch -->
                <button class="feedback-button feedback-yes" data-vote="yes">
                    <span class="dashicons dashicons-thumbs-up"></span>
                    <span class="button-text"><?php echo esc_html($yes_label); ?></span>
                </button>

                <!-- Daumen runter -->
                <button class="feedback-button feedback-no" data-vote="no">
                    <span class="dashicons dashicons-thumbs-down"></span>
                    <span class="button-text"><?php echo esc_html($no_label); ?></span>
                </button>
            </div>
        </div>

        <!-- Separate Box ohne Rahmen für das "Nein"-Feedback -->
        <div class="feedback-no-text-box">
            <label for="<?php echo esc_attr( $unique_id ); ?>">
                <?php echo wp_kses_post( get_option( 'feedback_voting_before_text', __( 'Helfen Sie uns, was können wir besser machen?', 'feedback-voting' ) ) ); ?>
            </label>
            <textarea
            class="feedback-no-text"
            id="<?php echo esc_attr( $unique_id ); ?>"
            rows="3"
            placeholder="<?php esc_attr_e('Hier können Sie uns Ihre Anregungen mitteilen (optional)', 'feedback-voting'); ?>"
            ></textarea>

            <button class="feedback-button feedback-submit-no">
                <span class="button-text"><?php echo esc_html($submit_label); ?></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the [feedback_score] shortcode displaying the average score
     * for a question/post combination. Each "yes" counts as 5 and each
     * "no" counts as 1.
     */
    public function render_score_shortcode($atts) {
        global $wpdb, $post;

        $atts = shortcode_atts(array(
            'question'      => __('War diese Antwort hilfreich?', 'feedback-voting'),
            'post_id'       => isset($post->ID) ? $post->ID : 0,
            'schema_type'   => '',
            'schema_rating' => '',
        ), $atts, 'feedback_score');

        $atts['schema_type'] = $atts['schema_type'] !== '' ? $atts['schema_type'] : feedback_voting_get_schema_type($atts['post_id']);
        $atts['schema_rating'] = $atts['schema_rating'] !== '' ? $atts['schema_rating'] : ( feedback_voting_schema_disabled($atts['post_id']) ? 0 : get_option('feedback_voting_schema_rating', 0) );

        $question = $atts['question'];
        $post_id  = intval($atts['post_id']);
        $unique_id = 'feedback-score-' . uniqid();

        $table = $wpdb->prefix . 'feedback_votes';
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END) AS yes_count,
                        SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END) AS no_count
                   FROM {$table}
                  WHERE question = %s AND post_id = %d",
                $question,
                $post_id
            )
        );

        $yes = intval($row->yes_count);
        $no  = intval($row->no_count);
        $total = $yes + $no;

        $score = $total > 0 ? ($yes * 5 + $no) / $total : 0;

        if ( intval( $atts['schema_rating'] ) && $total > 0 ) {
            $title = get_the_title( $post_id );
            feedback_voting_track_schema( $score, $total, $title, $atts['schema_type'] );
        }

        $label     = get_option('feedback_voting_score_label', __('Euer Score', 'feedback-voting'));
        $alignment = get_option('feedback_voting_score_alignment', 'left');
        $wrap      = get_option('feedback_voting_score_wrap', 'none');
        $label_pos = get_option('feedback_voting_score_label_position', 'top');

        $classes = array('feedback-score-box', 'fv-align-' . $alignment);
        if ($wrap !== 'none') {
            $classes[] = 'fv-wrap-' . $wrap;
        }
        if ($label_pos === 'bottom') {
            $classes[] = 'fv-label-bottom';
        }
        $class_attr = implode(' ', array_map('sanitize_html_class', $classes));

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $unique_id ); ?>" class="<?php echo esc_attr( $class_attr ); ?>">
            <small class="feedback-score-label"><?php echo esc_html($label); ?></small>
            <span class="feedback-score-value"><?php echo number_format($score, 1) . '/5'; ?></span>
        </div>
        <?php
        return ob_get_clean();
    }
}
