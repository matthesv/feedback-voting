<?php
/**
 * Tests for feedback_score shortcode.
 */
class Score_Shortcode_Test extends WP_UnitTestCase {
    public function test_feedback_score_shortcode_outputs_average() {
        global $wpdb;
        $table = $wpdb->prefix . 'feedback_votes';
        $now   = current_time( 'mysql' );

        $wpdb->insert( $table, [
            'question'      => 'Q1',
            'vote'          => 'yes',
            'feedback_text' => '',
            'post_id'       => 1,
            'created_at'    => $now,
        ] );
        $wpdb->insert( $table, [
            'question'      => 'Q1',
            'vote'          => 'no',
            'feedback_text' => '',
            'post_id'       => 1,
            'created_at'    => $now,
        ] );

        $output = do_shortcode( '[feedback_score question="Q1" post_id="1"]' );
        $this->assertStringContainsString( '3.0/5', $output );
    }

    public function test_multiple_score_shortcodes_on_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'feedback_votes';
        $now   = current_time( 'mysql' );

        $wpdb->insert( $table, [
            'question'      => 'Q1',
            'vote'          => 'yes',
            'feedback_text' => '',
            'post_id'       => 2,
            'created_at'    => $now,
        ] );

        $wpdb->insert( $table, [
            'question'      => 'Q2',
            'vote'          => 'no',
            'feedback_text' => '',
            'post_id'       => 2,
            'created_at'    => $now,
        ] );

        $output = do_shortcode( '[feedback_score question="Q1" post_id="2"][feedback_score question="Q2" post_id="2"]' );

        $this->assertStringContainsString( '5.0/5', $output );
        $this->assertStringContainsString( '1.0/5', $output );
    }
}

