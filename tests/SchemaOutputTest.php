<?php
/**
 * Tests for rating schema output in the footer.
 */
class Schema_Output_Test extends WP_UnitTestCase {
    public function test_footer_contains_rating_schema() {
        global $wpdb;

        update_option( 'feedback_voting_schema_rating', 1 );

        $post_id = self::factory()->post->create( [ 'post_title' => 'My Test Post' ] );
        $table   = $wpdb->prefix . 'feedback_votes';
        $now     = current_time( 'mysql' );

        $wpdb->insert( $table, [
            'question'      => 'Q',
            'vote'          => 'yes',
            'feedback_text' => '',
            'post_id'       => $post_id,
            'created_at'    => $now,
        ] );
        $wpdb->insert( $table, [
            'question'      => 'Q',
            'vote'          => 'no',
            'feedback_text' => '',
            'post_id'       => $post_id,
            'created_at'    => $now,
        ] );

        do_shortcode( "[feedback_score question=\"Q\" post_id=\"$post_id\"]" );

        wp_enqueue_block_template_skip_link();
        ob_start();
        do_action( 'wp_footer' );
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
        preg_match( '/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $m );
        $this->assertNotEmpty( $m );

        $schema = json_decode( $m[1], true );
        $this->assertSame( 'My Test Post', $schema['name'] );
        $this->assertSame( '3.0', $schema['aggregateRating']['ratingValue'] );
        $this->assertSame( 2, $schema['aggregateRating']['ratingCount'] );
    }
}
