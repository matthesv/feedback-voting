<?php
/**
 * Tests for AJAX voting.
 */
class Vote_Ajax_Test extends WP_Ajax_UnitTestCase {
    public function set_up() : void {
        parent::set_up();
        // Ensure a user is logged in.
        $user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $user_id );
    }

    public function test_insert_vote_when_no_vote_id() {
        $_POST = [
            'question' => 'Did you like it?',
            'vote'     => 'yes',
            'feedback' => '',
            'post_id'  => 1,
            'page_url' => 'http://example.org/page',
            'security' => wp_create_nonce( 'feedback_nonce_action' ),
        ];

        try {
            $this->_handleAjax( 'my_feedback_plugin_vote' );
        } catch ( WPAjaxDieContinueException $e ) {}

        $response = json_decode( $this->_last_response, true );
        $this->assertTrue( $response['success'] );
        $this->assertNotEmpty( $response['data']['vote_id'] );

        global $wpdb;
        $table = $wpdb->prefix . 'feedback_votes';
        $stored = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $response['data']['vote_id'] ) );
        $this->assertSame( 'Did you like it?', $stored->question );
        $this->assertSame( 'yes', $stored->vote );
    }

    public function test_update_vote_when_vote_id_provided() {
        global $wpdb;
        $table = $wpdb->prefix . 'feedback_votes';

        $wpdb->insert( $table, [
            'question'      => 'Q',
            'vote'          => 'no',
            'feedback_text' => '',
            'post_id'       => 2,
            'page_url'      => 'http://example.org/post',
            'created_at'    => current_time( 'mysql' ),
        ] );
        $vote_id = $wpdb->insert_id;

        $_POST = [
            'vote_id'  => $vote_id,
            'feedback' => 'Updated text',
            'page_url' => 'http://example.org/post',
            'security' => wp_create_nonce( 'feedback_nonce_action' ),
        ];

        try {
            $this->_handleAjax( 'my_feedback_plugin_vote' );
        } catch ( WPAjaxDieContinueException $e ) {}

        $response = json_decode( $this->_last_response, true );
        $this->assertTrue( $response['success'] );
        $this->assertSame( $vote_id, $response['data']['vote_id'] );

        $updated = $wpdb->get_var( $wpdb->prepare( "SELECT feedback_text FROM $table WHERE id=%d", $vote_id ) );
        $this->assertSame( 'Updated text', $updated );
    }
}
