<?php
if (!defined('ABSPATH')) {
    exit;
}

class My_Feedback_Plugin_Block {
    public function __construct() {
        add_action('init', array($this, 'register_block'));
    }

    public function register_block() {
        wp_register_script(
            'feedback-voting-block',
            FEEDBACK_VOTING_PLUGIN_URL . 'admin/js/block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-block-editor'),
            FEEDBACK_VOTING_VERSION,
            true
        );

        register_block_type('feedback-voting/module', array(
            'editor_script'   => 'feedback-voting-block',
            'render_callback' => array($this, 'render_block'),
            'attributes'      => array(
                'question' => array(
                    'type'    => 'string',
                    'default' => __('War diese Antwort hilfreich?', 'feedback-voting'),
                ),
                'showScore' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'scoreLabel' => array(
                    'type'    => 'string',
                    'default' => __('Euer Score', 'feedback-voting'),
                ),
                'scoreAlignment' => array(
                    'type'    => 'string',
                    'default' => 'left',
                ),
                'scoreWrap' => array(
                    'type'    => 'string',
                    'default' => 'none',
                ),
                'scoreLabelPosition' => array(
                    'type'    => 'string',
                    'default' => 'top',
                ),
                'schemaRating' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'schemaType' => array(
                    'type'    => 'string',
                    'default' => 'Product',
                ),
            ),
        ));
    }

    public function render_block($attributes) {
        $attrs = wp_parse_args($attributes, array(
            'question' => __('War diese Antwort hilfreich?', 'feedback-voting'),
            'showScore' => true,
            'scoreLabel' => __('Euer Score', 'feedback-voting'),
            'scoreAlignment' => 'left',
            'scoreWrap' => 'none',
            'scoreLabelPosition' => 'top',
            'schemaRating' => true,
            'schemaType' => feedback_voting_get_schema_type(),
        ));

        global $post;
        $post_id = isset($post->ID) ? $post->ID : 0;

        $shortcode = '[feedback_voting question="' . esc_attr($attrs['question']) . '"]';
        if ($attrs['showScore']) {
            $shortcode .= ' [feedback_score'
                . ' question="' . esc_attr($attrs['question']) . '"'
                . ' post_id="' . $post_id . '"'
                . ' schema_type="' . esc_attr($attrs['schemaType']) . '"'
                . ' schema_rating="' . ($attrs['schemaRating'] ? '1' : '0') . '"'
                . ' label="' . esc_attr($attrs['scoreLabel']) . '"'
                . ' alignment="' . esc_attr($attrs['scoreAlignment']) . '"'
                . ' wrap="' . esc_attr($attrs['scoreWrap']) . '"'
                . ' label_position="' . esc_attr($attrs['scoreLabelPosition']) . '"'
                . ']';
        }
        return do_shortcode($shortcode);
    }
}
