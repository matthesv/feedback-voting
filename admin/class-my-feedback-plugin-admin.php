<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_Admin {

    /** Singleton instance */
    private static $instance = null;

    /**
     * Retrieve the singleton instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Admin-Menüs und -Einstellungen
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box'));

        // CSV-Export und Löschen
        add_action('admin_post_feedback_voting_export_csv', array($this, 'handle_export_csv'));
        add_action('admin_post_feedback_voting_delete_all', array($this, 'handle_delete_all'));
    }

    /**
     * Lädt Color-Picker und Admin-Script für Copy-Buttons
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_feedback-voting' && $hook !== 'feedback-voting_page_feedback-voting-settings') {
            return;
        }
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script(
            'feedback-voting-admin',
            FEEDBACK_VOTING_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'wp-color-picker'),
            filemtime(FEEDBACK_VOTING_PLUGIN_DIR . 'admin/js/admin.js'),
            true
        );

        if ($hook === 'toplevel_page_feedback-voting') {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                null,
                true
            );
            wp_enqueue_script(
                'feedback-voting-chart',
                FEEDBACK_VOTING_PLUGIN_URL . 'admin/js/feedback-chart.js',
                array('jquery', 'chartjs'),
                filemtime(FEEDBACK_VOTING_PLUGIN_DIR . 'admin/js/feedback-chart.js'),
                true
            );
        }
    }

    /**
     * Menüpunkt im Admin-Backend
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Feedback Voting', 'feedback-voting'),
            __('Feedback Voting', 'feedback-voting'),
            'manage_options',
            'feedback-voting',
            array($this, 'render_admin_page'),
            'dashicons-thumbs-up',
            80
        );

        add_submenu_page(
            'feedback-voting',
            __('Analyse', 'feedback-voting'),
            __('Analyse', 'feedback-voting'),
            'manage_options',
            'feedback-voting',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'feedback-voting',
            __('Einstellungen', 'feedback-voting'),
            __('Einstellungen', 'feedback-voting'),
            'manage_options',
            'feedback-voting-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'feedback-voting',
            __('Info', 'feedback-voting'),
            __('Info', 'feedback-voting'),
            'manage_options',
            'feedback-voting-info',
            array($this, 'render_info_page')
        );
    }

    /**
     * Registriert alle Plugin-Einstellungen
     */
    public function register_settings() {
        register_setting('feedback_voting_settings_group', 'feedback_voting_enable_feedback_field');
        register_setting('feedback_voting_settings_group', 'feedback_voting_prevent_multiple', array(
            'type'              => 'boolean',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_primary_color', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#0073aa',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_button_color', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#0073aa',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_button_hover_color', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#005b8d',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_text_color', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#1b1c1c',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_border_radius', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '9999px',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_yes_label', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => __('Ja, war sie', 'feedback-voting'),
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_no_label', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => __('Nein, leider nicht', 'feedback-voting'),
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_container_radius', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '6rem',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_score_radius', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '6px',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_submit_label', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => __('Feedback senden', 'feedback-voting'),
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_thankyou_text', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => __('Vielen Dank für Ihr Feedback! Jede Antwort hilft uns, uns zu verbessern.', 'feedback-voting'),
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_before_text', array(
            'type'              => 'string',
            'sanitize_callback' => 'wp_kses_post',
            'default'           => __('Helfen Sie uns, was können wir besser machen?', 'feedback-voting'),
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_box_width', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 100,
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_score_label', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => __('Euer Score', 'feedback-voting'),
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_score_alignment', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'left',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_score_wrap', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'none',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_score_label_position', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'top',
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_auto_post', array(
            'type'              => 'boolean',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_auto_page', array(
            'type'              => 'boolean',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_auto_score', array(
            'type'              => 'boolean',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ));
        register_setting('feedback_voting_settings_group', 'feedback_voting_auto_question', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => __('War dieser Beitrag hilfreich?', 'feedback-voting'),
        ));

        add_settings_section(
            'feedback_voting_general_section',
            __('Allgemein', 'feedback-voting'),
            null,
            'feedback_voting_settings'
        );
        add_settings_section(
            'feedback_voting_appearance_section',
            __('Design & Styling', 'feedback-voting'),
            null,
            'feedback_voting_settings'
        );
        add_settings_section(
            'feedback_voting_score_section',
            __('Score Box Layout', 'feedback-voting'),
            null,
            'feedback_voting_settings'
        );

        add_settings_field(
            'feedback_voting_enable_feedback_field',
            __('Freitext-Feld bei "Nein" aktivieren?', 'feedback-voting'),
            array($this, 'feedback_field_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_prevent_multiple',
            __('Mehrfach-Votes innerhalb 24h verhindern?', 'feedback-voting'),
            array($this, 'prevent_multiple_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_auto_post',
            __('Feedback automatisch unter Beiträgen anzeigen?', 'feedback-voting'),
            array($this, 'auto_post_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_auto_page',
            __('Feedback automatisch unter Seiten anzeigen?', 'feedback-voting'),
            array($this, 'auto_page_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_auto_score',
            __('Score nach dem Feedback anzeigen?', 'feedback-voting'),
            array($this, 'auto_score_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_auto_question',
            __('Standardfrage für automatisches Feedback', 'feedback-voting'),
            array($this, 'auto_question_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_primary_color',
            __('Primär-Farbe', 'feedback-voting'),
            array($this, 'color_field_render'),
            'feedback_voting_settings',
            'feedback_voting_appearance_section',
            array(
                'option_name' => 'feedback_voting_primary_color',
                'label_for'   => 'feedback_voting_primary_color',
                'default'     => '#0073aa',
            )
        );
        add_settings_field(
            'feedback_voting_button_color',
            __('Button-Farbe', 'feedback-voting'),
            array($this, 'color_field_render'),
            'feedback_voting_settings',
            'feedback_voting_appearance_section',
            array(
                'option_name' => 'feedback_voting_button_color',
                'label_for'   => 'feedback_voting_button_color',
                'default'     => '#0073aa',
            )
        );
        add_settings_field(
            'feedback_voting_button_hover_color',
            __('Button Hover-Farbe', 'feedback-voting'),
            array($this, 'color_field_render'),
            'feedback_voting_settings',
            'feedback_voting_appearance_section',
            array(
                'option_name' => 'feedback_voting_button_hover_color',
                'label_for'   => 'feedback_voting_button_hover_color',
                'default'     => '#005b8d',
            )
        );
        add_settings_field(
            'feedback_voting_text_color',
            __('Text-Farbe', 'feedback-voting'),
            array($this, 'color_field_render'),
            'feedback_voting_settings',
            'feedback_voting_appearance_section',
            array(
                'option_name' => 'feedback_voting_text_color',
                'label_for'   => 'feedback_voting_text_color',
                'default'     => '#1b1c1c',
            )
        );
        add_settings_field(
            'feedback_voting_border_radius',
            __('Button-Rundungen (CSS)', 'feedback-voting'),
            array($this, 'border_radius_render'),
            'feedback_voting_settings',
            'feedback_voting_appearance_section'
        );
        add_settings_field(
            'feedback_voting_before_text',
            __('Text oberhalb des Feedback-Felds', 'feedback-voting'),
            array($this, 'before_text_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_box_width',
            __('Box-Breite (%)', 'feedback-voting'),
            array($this, 'box_width_render'),
            'feedback_voting_settings',
            'feedback_voting_appearance_section'
        );
        add_settings_field(
            'feedback_voting_score_label',
            __('Text innerhalb der Score-Box', 'feedback-voting'),
            array($this, 'score_label_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_yes_label',
            __('Text Button "Ja"', 'feedback-voting'),
            array($this, 'yes_label_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_no_label',
            __('Text Button "Nein"', 'feedback-voting'),
            array($this, 'no_label_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_container_radius',
            __('Box-Rundungen (CSS)', 'feedback-voting'),
            array($this, 'container_radius_render'),
            'feedback_voting_settings',
            'feedback_voting_appearance_section'
        );
        add_settings_field(
            'feedback_voting_score_radius',
            __('Score-Box-Rundungen (CSS)', 'feedback-voting'),
            array($this, 'score_radius_render'),
            'feedback_voting_settings',
            'feedback_voting_appearance_section'
        );
        add_settings_field(
            'feedback_voting_submit_label',
            __('Beschriftung Feedback-Button', 'feedback-voting'),
            array($this, 'submit_label_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_thankyou_text',
            __('Text nach dem Absenden', 'feedback-voting'),
            array($this, 'thankyou_text_render'),
            'feedback_voting_settings',
            'feedback_voting_general_section'
        );
        add_settings_field(
            'feedback_voting_score_alignment',
            __('Ausrichtung der Score-Box', 'feedback-voting'),
            array($this, 'score_alignment_render'),
            'feedback_voting_settings',
            'feedback_voting_score_section'
        );
        add_settings_field(
            'feedback_voting_score_wrap',
            __('Textumfluss um die Score-Box', 'feedback-voting'),
            array($this, 'score_wrap_render'),
            'feedback_voting_settings',
            'feedback_voting_score_section'
        );
        add_settings_field(
            'feedback_voting_score_label_position',
            __('Position des Labels', 'feedback-voting'),
            array($this, 'score_label_position_render'),
            'feedback_voting_settings',
            'feedback_voting_score_section'
        );
    }

    /** Render Checkbox für Freitext-Feld */
    public function feedback_field_render() {
        $value = get_option('feedback_voting_enable_feedback_field', '1');
        ?>
        <label for="feedback_voting_enable_feedback_field">
            <input type="checkbox" id="feedback_voting_enable_feedback_field" name="feedback_voting_enable_feedback_field" value="1" <?php checked($value, '1'); ?> />
            <?php _e('Freitext-Feld für "Nein" aktivieren','feedback-voting'); ?>
        </label>
        <?php
    }

    /** Render Checkbox for preventing multiple votes */
    public function prevent_multiple_render() {
        $value = get_option('feedback_voting_prevent_multiple', 0);
        ?>
        <label for="feedback_voting_prevent_multiple">
            <input type="checkbox" id="feedback_voting_prevent_multiple" name="feedback_voting_prevent_multiple" value="1" <?php checked($value, 1); ?> />
            <?php _e('Per Cookie verhindern, dass innerhalb von 24h mehrfach abgestimmt wird.', 'feedback-voting'); ?>
        </label>
        <?php
    }


    /** Render Color-Picker-Felder */
    public function color_field_render($args) {
        $option  = $args['option_name'];
        $id      = $args['label_for'];
        $default = isset($args['default']) ? $args['default'] : '';

        $color = get_option($option, $default);

        printf(
            '<input class="feedback-color-field" type="text" id="%1$s" name="%1$s" value="%2$s" data-default-color="%3$s" />',
            esc_attr($id),
            esc_attr($color),
            esc_attr($default)
        );
    }

    /** Render Input für Border-Radius */
    public function border_radius_render() {
        $value = get_option('feedback_voting_border_radius', '9999px');
        printf(
            '<input type="text" id="feedback_voting_border_radius" name="feedback_voting_border_radius" value="%s" />',
            esc_attr($value)
        );
        echo '<p class="description">' . __('z.B. "4px", "1rem", "50%"','feedback-voting') . '</p>';
    }

    /** Render Textarea for label above feedback field */
    public function before_text_render() {
        $value = get_option('feedback_voting_before_text', __('Helfen Sie uns, was können wir besser machen?', 'feedback-voting'));
        printf(
            '<textarea id="feedback_voting_before_text" name="feedback_voting_before_text" rows="3" class="large-text code">%s</textarea>',
            esc_textarea($value)
        );
    }

    /** Render Input for box width percentage */
    public function box_width_render() {
        $value = get_option('feedback_voting_box_width', 100);
        printf(
            '<input type="number" id="feedback_voting_box_width" name="feedback_voting_box_width" value="%d" min="10" max="100" />%%',
            intval($value)
        );
    }

    /** Render input for the score label */
    public function score_label_render() {
        $value = get_option('feedback_voting_score_label', __('Euer Score', 'feedback-voting'));
        printf(
            '<input type="text" id="feedback_voting_score_label" name="feedback_voting_score_label" value="%s" class="regular-text" />',
            esc_attr($value)
        );
    }

    /** Render input for the yes button label */
    public function yes_label_render() {
        $value = get_option('feedback_voting_yes_label', __('Ja, war sie', 'feedback-voting'));
        printf(
            '<input type="text" id="feedback_voting_yes_label" name="feedback_voting_yes_label" value="%s" class="regular-text" />',
            esc_attr($value)
        );
    }

    /** Render input for the no button label */
    public function no_label_render() {
        $value = get_option('feedback_voting_no_label', __('Nein, leider nicht', 'feedback-voting'));
        printf(
            '<input type="text" id="feedback_voting_no_label" name="feedback_voting_no_label" value="%s" class="regular-text" />',
            esc_attr($value)
        );
    }

    /** Render input for feedback box radius */
    public function container_radius_render() {
        $value = get_option('feedback_voting_container_radius', '6rem');
        printf(
            '<input type="text" id="feedback_voting_container_radius" name="feedback_voting_container_radius" value="%s" />',
            esc_attr($value)
        );
    }

    /** Render input for score box radius */
    public function score_radius_render() {
        $value = get_option('feedback_voting_score_radius', '6px');
        printf(
            '<input type="text" id="feedback_voting_score_radius" name="feedback_voting_score_radius" value="%s" />',
            esc_attr($value)
        );
    }

    /** Render input for submit button label */
    public function submit_label_render() {
        $value = get_option('feedback_voting_submit_label', __('Feedback senden', 'feedback-voting'));
        printf(
            '<input type="text" id="feedback_voting_submit_label" name="feedback_voting_submit_label" value="%s" class="regular-text" />',
            esc_attr($value)
        );
    }

    /** Render input for thank you message */
    public function thankyou_text_render() {
        $value = get_option('feedback_voting_thankyou_text', __('Vielen Dank für Ihr Feedback! Jede Antwort hilft uns, uns zu verbessern.', 'feedback-voting'));
        printf(
            '<input type="text" id="feedback_voting_thankyou_text" name="feedback_voting_thankyou_text" value="%s" class="regular-text" />',
            esc_attr($value)
        );
    }

    /** Render select for score alignment */
    public function score_alignment_render() {
        $value = get_option('feedback_voting_score_alignment', 'left');
        ?>
        <select id="feedback_voting_score_alignment" name="feedback_voting_score_alignment">
            <option value="left" <?php selected($value, 'left'); ?>><?php _e('Links', 'feedback-voting'); ?></option>
            <option value="center" <?php selected($value, 'center'); ?>><?php _e('Zentriert', 'feedback-voting'); ?></option>
            <option value="right" <?php selected($value, 'right'); ?>><?php _e('Rechts', 'feedback-voting'); ?></option>
        </select>
        <?php
    }

    /** Render select for score wrap */
    public function score_wrap_render() {
        $value = get_option('feedback_voting_score_wrap', 'none');
        ?>
        <select id="feedback_voting_score_wrap" name="feedback_voting_score_wrap">
            <option value="none" <?php selected($value, 'none'); ?>><?php _e('Kein Textumfluss', 'feedback-voting'); ?></option>
            <option value="left" <?php selected($value, 'left'); ?>><?php _e('Textumfluss links', 'feedback-voting'); ?></option>
            <option value="right" <?php selected($value, 'right'); ?>><?php _e('Textumfluss rechts', 'feedback-voting'); ?></option>
        </select>
        <?php
    }

    /** Render select for label position */
    public function score_label_position_render() {
        $value = get_option('feedback_voting_score_label_position', 'top');
        ?>
        <select id="feedback_voting_score_label_position" name="feedback_voting_score_label_position">
            <option value="top" <?php selected($value, 'top'); ?>><?php _e('Label oben', 'feedback-voting'); ?></option>
            <option value="bottom" <?php selected($value, 'bottom'); ?>><?php _e('Label unten', 'feedback-voting'); ?></option>
        </select>
        <?php
    }

    /** Render checkbox for auto post insertion */
    public function auto_post_render() {
        $value = get_option('feedback_voting_auto_post', 0);
        ?>
        <label for="feedback_voting_auto_post">
            <input type="checkbox" id="feedback_voting_auto_post" name="feedback_voting_auto_post" value="1" <?php checked($value, 1); ?> />
            <?php _e('Automatisch unter Beiträgen anzeigen', 'feedback-voting'); ?>
        </label>
        <?php
    }

    /** Render checkbox for auto page insertion */
    public function auto_page_render() {
        $value = get_option('feedback_voting_auto_page', 0);
        ?>
        <label for="feedback_voting_auto_page">
            <input type="checkbox" id="feedback_voting_auto_page" name="feedback_voting_auto_page" value="1" <?php checked($value, 1); ?> />
            <?php _e('Automatisch unter Seiten anzeigen', 'feedback-voting'); ?>
        </label>
        <?php
    }

    /** Render checkbox for auto score display */
    public function auto_score_render() {
        $value = get_option('feedback_voting_auto_score', 0);
        ?>
        <label for="feedback_voting_auto_score">
            <input type="checkbox" id="feedback_voting_auto_score" name="feedback_voting_auto_score" value="1" <?php checked($value, 1); ?> />
            <?php _e('Score mit anzeigen', 'feedback-voting'); ?>
        </label>
        <?php
    }

    /** Render input for auto question */
    public function auto_question_render() {
        $value = get_option('feedback_voting_auto_question', __('War dieser Beitrag hilfreich?', 'feedback-voting'));
        printf(
            '<input type="text" id="feedback_voting_auto_question" name="feedback_voting_auto_question" value="%s" class="regular-text" />',
            esc_attr($value)
        );
    }

    /** Admin-Dashboard rendern */
    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback_votes';

        if (isset($_GET['feedback_voting_deleted']) && $_GET['feedback_voting_deleted'] === '1') {
            echo '<div class="updated"><p>' . __('Alle Feedback-Einträge wurden gelöscht.', 'feedback-voting') . '</p></div>';
        }

        $total_yes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE vote = 'yes'");
        $total_no  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE vote = 'no'");

        $results = $wpdb->get_results(
            "SELECT post_id,
                    question,
                    SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END) AS total_yes,
                    SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END) AS total_no
               FROM {$table_name}
              GROUP BY post_id, question
              ORDER BY (SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END)
                      + SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END)) DESC
              LIMIT 10"
        );

        $per_page = 20;
        $paged    = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset   = ($paged - 1) * $per_page;
        $selected_post_id = isset($_GET['post_id_filter']) ? intval($_GET['post_id_filter']) : 0;

        if ($selected_post_id > 0) {
            $total_items = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d", $selected_post_id)
            );
            $all_feedbacks = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE post_id = %d
                     ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $selected_post_id, $per_page, $offset
                )
            );
        } else {
            $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            $all_feedbacks = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $per_page, $offset
                )
            );
        }

        $base_url = admin_url('admin.php?page=feedback-voting');
        if ($selected_post_id) {
            $base_url = add_query_arg('post_id_filter', $selected_post_id, $base_url);
        }

        $chart_labels = array();
        $chart_yes    = array();
        $chart_no     = array();
        foreach ($results as $r) {
            $chart_labels[] = (string) $r->post_id;
            $chart_yes[]    = (int) $r->total_yes;
            $chart_no[]     = (int) $r->total_no;
        }
        wp_localize_script('feedback-voting-chart', 'feedbackChartData', array(
            'labels' => $chart_labels,
            'yes'    => $chart_yes,
            'no'     => $chart_no,
        ));

        $pagination = paginate_links(array(
            'base'      => $base_url . '&paged=%#%',
            'format'    => '',
            'current'   => $paged,
            'total'     => ceil($total_items / $per_page),
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;'
        ));
        ?>
        <div class="wrap">
            <h1><?php _e('Feedback Voting Dashboard', 'feedback-voting'); ?></h1>
            <hr>

            <h2><?php _e('Gesamtübersicht', 'feedback-voting'); ?></h2>
            <p><strong><?php _e('Anzahl "Ja":', 'feedback-voting'); ?></strong> <?php echo $total_yes; ?></p>
            <p><strong><?php _e('Anzahl "Nein":', 'feedback-voting'); ?></strong> <?php echo $total_no; ?></p>

            <h2><?php _e('Top Fragen', 'feedback-voting'); ?></h2>
            <canvas id="feedback-chart-canvas" style="max-width:100%;height:400px;"></canvas>
            <p><button id="download-chart" class="button">PNG herunterladen</button></p>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Post ID', 'feedback-voting'); ?></th>
                        <th><?php _e('Titel', 'feedback-voting'); ?></th>
                        <th><?php _e('Frage', 'feedback-voting'); ?></th>
                        <th><?php _e('"Ja" Stimmen', 'feedback-voting'); ?></th>
                        <th><?php _e('"Nein" Stimmen', 'feedback-voting'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($results)) : ?>
                    <?php foreach ($results as $row) :
                        $post_title = get_the_title($row->post_id);
                        if (empty($post_title)) {
                            $post_title = __('Keine Zuordnung', 'feedback-voting');
                        }
                        $post_title_short = mb_substr($post_title, 0, 30);
                    ?>
                        <tr>
                            <td><?php echo intval($row->post_id); ?></td>
                            <td><?php echo esc_html($post_title_short); ?></td>
                            <td><?php echo esc_html($row->question); ?></td>
                            <td><?php echo intval($row->total_yes); ?></td>
                            <td><?php echo intval($row->total_no); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php _e('Keine Daten vorhanden.', 'feedback-voting'); ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

            <hr>
            <h2><?php _e('Alle Feedback-Einträge', 'feedback-voting'); ?></h2>

            <form method="get" style="margin-bottom:1em;">
                <input type="hidden" name="page" value="feedback-voting"/>
                <label for="post_id_filter"><?php _e('Post ID filtern:', 'feedback-voting'); ?></label>
                <input type="number" name="post_id_filter" id="post_id_filter" value="<?php echo $selected_post_id ? $selected_post_id : ''; ?>"/>
                <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Filter', 'feedback-voting'); ?>"/>
                <?php if ($selected_post_id) : ?>
                    <a class="button button-link" href="<?php echo admin_url('admin.php?page=feedback-voting'); ?>"><?php _e('Filter zurücksetzen', 'feedback-voting'); ?></a>
                <?php endif; ?>
            </form>

            <div class="tablenav">
                <div class="tablenav-pages"><?php echo $pagination; ?></div>
            </div>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Datum', 'feedback-voting'); ?></th>
                        <th><?php _e('Frage', 'feedback-voting'); ?></th>
                        <th><?php _e('Vote', 'feedback-voting'); ?></th>
                        <th><?php _e('Feedback-Text', 'feedback-voting'); ?></th>
                        <th><?php _e('Shortcode-Location', 'feedback-voting'); ?></th>
                        <th><?php _e('URL', 'feedback-voting'); ?></th>
                        <th><?php _e('Post ID', 'feedback-voting'); ?></th>
                        <th><?php _e('Aktion', 'feedback-voting'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($all_feedbacks)) : ?>
                    <?php foreach ($all_feedbacks as $feedback) :
                        $post_title = get_the_title($feedback->post_id);
                        if (empty($post_title)) {
                            $post_title = __('Keine Zuordnung', 'feedback-voting');
                        }
                        $post_link = get_permalink($feedback->post_id);
                    ?>
                        <tr>
                            <td><?php echo esc_html($feedback->created_at); ?></td>
                            <td><?php echo esc_html($feedback->question); ?></td>
                            <td><?php echo esc_html($feedback->vote); ?></td>
                            <td><?php echo esc_html($feedback->feedback_text); ?></td>
                            <td>
                                <?php if (!empty($post_link) && $post_title !== __('Keine Zuordnung', 'feedback-voting')) : ?>
                                    <a href="<?php echo esc_url($post_link); ?>" target="_blank"><?php echo esc_html($post_title); ?></a>
                                <?php else : ?>
                                    <em><?php echo esc_html($post_title); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($feedback->page_url)) : ?>
                                    <a href="<?php echo esc_url($feedback->page_url); ?>" target="_blank"><?php echo esc_html($feedback->page_url); ?></a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo intval($feedback->post_id); ?></td>
                            <td><button type="button" class="button feedback-copy-button"><?php _e('Kopieren', 'feedback-voting'); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="8"><?php _e('Keine Feedbacks vorhanden.', 'feedback-voting'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <div class="tablenav">
                <div class="tablenav-pages"><?php echo $pagination; ?></div>
            </div>

            <hr>
            <h2><?php _e('CSV-Export', 'feedback-voting'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('feedback_voting_export_csv_action'); ?>
                <input type="hidden" name="action" value="feedback_voting_export_csv">
                <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Alle Feedback-Einträge als CSV herunterladen', 'feedback-voting'); ?>"/>
            </form>

            <hr>
            <h2><?php _e('Alle Feedback-Einträge löschen', 'feedback-voting'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('feedback_voting_delete_all_action'); ?>
                <input type="hidden" name="action" value="feedback_voting_delete_all">
                <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Alle Feedback-Einträge löschen', 'feedback-voting'); ?>" onclick="return confirm('<?php _e('Wirklich alle Feedback-Einträge löschen?', 'feedback-voting'); ?>');"/>
            </form>

        </div>
        <?php
    }

    /** Render settings page */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Feedback Voting Einstellungen', 'feedback-voting'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('feedback_voting_settings_group'); do_settings_sections('feedback_voting_settings'); submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /** Add meta box for per-post settings */
    public function add_meta_boxes() {
        foreach (array('post', 'page') as $screen) {
            add_meta_box(
                'feedback_voting_meta',
                __('Feedback Voting', 'feedback-voting'),
                array($this, 'render_meta_box'),
                $screen,
                'side'
            );
        }
    }

    /** Render the meta box */
    public function render_meta_box($post) {
        $disable_auto = get_post_meta($post->ID, '_feedback_voting_disable_auto', true);
        $schema_type  = get_post_meta($post->ID, '_feedback_voting_schema_type', true);
        $schema_type  = $schema_type ? $schema_type : 'Product';
        $address      = get_post_meta($post->ID, '_feedback_voting_address', true);
        $lb_data      = get_post_meta($post->ID, '_feedback_voting_localbusiness', true);
        if (!is_array($lb_data)) {
            $lb_data = array();
        }
        wp_nonce_field('feedback_voting_meta_box', 'feedback_voting_meta_box_nonce');
        ?>
        <div class="fv-meta-tabs">
            <h2 class="nav-tab-wrapper">
                <a href="#" class="nav-tab nav-tab-active" data-fv-tab="general"><?php _e('Allgemein', 'feedback-voting'); ?></a>
                <a href="#" class="nav-tab" data-fv-tab="localbusiness" id="fv-tab-header-localbusiness" style="display:none"><?php _e('LocalBusiness', 'feedback-voting'); ?></a>
            </h2>
            <div id="fv-tab-content-general" class="fv-tab-content">
                <p>
                    <label>
                        <input type="checkbox" name="feedback_voting_disable_auto" value="1" <?php checked($disable_auto, '1'); ?> />
                        <?php _e('Automatisches Feedback auf dieser Seite deaktivieren', 'feedback-voting'); ?>
                    </label>
                </p>
                <p>
                    <label for="feedback_voting_schema_type"><?php _e('Schema Typ', 'feedback-voting'); ?></label>
                    <select id="feedback_voting_schema_type" name="feedback_voting_schema_type">
                        <?php foreach (array('Book','Course','Event','LocalBusiness','Movie','Product','Recipe','SoftwareApplication') as $t) { ?>
                            <option value="<?php echo esc_attr($t); ?>" <?php selected($schema_type, $t); ?>><?php echo esc_html($t); ?></option>
                        <?php } ?>
                    </select>
                </p>
            </div>
            <div id="fv-tab-content-localbusiness" class="fv-tab-content" style="display:none">
                <p id="feedback_voting_address_wrap">
                    <label for="feedback_voting_address"><?php _e('Adresse', 'feedback-voting'); ?></label>
                    <input type="text" id="feedback_voting_address" name="feedback_voting_address" value="<?php echo esc_attr($address); ?>" class="widefat" />
                </p>
                <div id="feedback_voting_lb_fields">
                <label for="fv_lb_name"><?php _e('Name', 'feedback-voting'); ?></label>
                <input type="text" id="fv_lb_name" name="feedback_voting_localbusiness[name]" value="<?php echo esc_attr($lb_data['name'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_id">@id</label>
                <input type="text" id="fv_lb_id" name="feedback_voting_localbusiness[id]" value="<?php echo esc_attr($lb_data['id'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_street"><?php _e('Stra\xC3\x9Fe', 'feedback-voting'); ?></label>
                <input type="text" id="fv_lb_street" name="feedback_voting_localbusiness[streetAddress]" value="<?php echo esc_attr($lb_data['streetAddress'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_locality"><?php _e('Ort', 'feedback-voting'); ?></label>
                <input type="text" id="fv_lb_locality" name="feedback_voting_localbusiness[addressLocality]" value="<?php echo esc_attr($lb_data['addressLocality'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_region"><?php _e('Region', 'feedback-voting'); ?></label>
                <input type="text" id="fv_lb_region" name="feedback_voting_localbusiness[addressRegion]" value="<?php echo esc_attr($lb_data['addressRegion'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_postal"><?php _e('Postleitzahl', 'feedback-voting'); ?></label>
                <input type="text" id="fv_lb_postal" name="feedback_voting_localbusiness[postalCode]" value="<?php echo esc_attr($lb_data['postalCode'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_country"><?php _e('Land', 'feedback-voting'); ?></label>
                <input type="text" id="fv_lb_country" name="feedback_voting_localbusiness[addressCountry]" value="<?php echo esc_attr($lb_data['addressCountry'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_tel"><?php _e('Telefon', 'feedback-voting'); ?></label>
                <input type="text" id="fv_lb_tel" name="feedback_voting_localbusiness[telephone]" value="<?php echo esc_attr($lb_data['telephone'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_url">URL</label>
                <input type="text" id="fv_lb_url" name="feedback_voting_localbusiness[url]" value="<?php echo esc_attr($lb_data['url'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_logo">Logo</label>
                <input type="text" id="fv_lb_logo" name="feedback_voting_localbusiness[logo]" value="<?php echo esc_attr($lb_data['logo'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_desc"><?php _e('Beschreibung', 'feedback-voting'); ?></label>
                <textarea id="fv_lb_desc" name="feedback_voting_localbusiness[description]" class="widefat" rows="2"><?php echo esc_textarea($lb_data['description'] ?? ''); ?></textarea>
            </p>
            <p>
                <label for="fv_lb_hours"><?php _e('\xC3\x96ffnungszeiten', 'feedback-voting'); ?></label>
                <input type="text" id="fv_lb_hours" name="feedback_voting_localbusiness[openingHours]" value="<?php echo esc_attr($lb_data['openingHours'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_lat">Latitude</label>
                <input type="text" id="fv_lb_lat" name="feedback_voting_localbusiness[latitude]" value="<?php echo esc_attr($lb_data['latitude'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_lon">Longitude</label>
                <input type="text" id="fv_lb_lon" name="feedback_voting_localbusiness[longitude]" value="<?php echo esc_attr($lb_data['longitude'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_sameAs">sameAs</label>
                <textarea id="fv_lb_sameAs" name="feedback_voting_localbusiness[sameAs]" class="widefat" rows="2"><?php echo esc_textarea($lb_data['sameAs'] ?? ''); ?></textarea>
            </p>
            <p>
                <label for="fv_lb_image">image</label>
                <input type="text" id="fv_lb_image" name="feedback_voting_localbusiness[image]" value="<?php echo esc_attr($lb_data['image'] ?? ''); ?>" class="widefat" />
            </p>
            <p>
                <label for="fv_lb_price">priceRange</label>
                <input type="text" id="fv_lb_price" name="feedback_voting_localbusiness[priceRange]" value="<?php echo esc_attr($lb_data['priceRange'] ?? ''); ?>" class="widefat" />
            </p>
                </div>
            </div>
        </div>
        <script>
        jQuery(function($){
            function switchTab(tab){
                $('.fv-meta-tabs .nav-tab').removeClass('nav-tab-active');
                $('.fv-meta-tabs .nav-tab[data-fv-tab="'+tab+'"]').addClass('nav-tab-active');
                $('.fv-tab-content').hide();
                $('#fv-tab-content-' + tab).show();
            }
            function toggleLbFields(){
                var $sel = $('#feedback_voting_schema_type');
                if(!$sel.length) return;
                var isLb = $sel.val().toLowerCase() === 'localbusiness';
                $('#fv-tab-header-localbusiness').toggle(isLb);
                if(!isLb && $('.fv-meta-tabs .nav-tab-active').data('fv-tab') === 'localbusiness'){
                    switchTab('general');
                }
            }
            $(document).on('click', '.fv-meta-tabs .nav-tab', function(e){
                e.preventDefault();
                switchTab($(this).data('fv-tab'));
            });
            $(document).on('change', '#feedback_voting_schema_type', toggleLbFields);
            toggleLbFields();
            switchTab($('.fv-meta-tabs .nav-tab-active').data('fv-tab'));
});
</script>
<?php
    }

    /** Save meta box values */
    public function save_meta_box($post_id) {
        if (!isset($_POST['feedback_voting_meta_box_nonce']) || !wp_verify_nonce($_POST['feedback_voting_meta_box_nonce'], 'feedback_voting_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $disable_auto = isset($_POST['feedback_voting_disable_auto']) ? '1' : '0';
        update_post_meta($post_id, '_feedback_voting_disable_auto', $disable_auto);

        $schema_type = isset($_POST['feedback_voting_schema_type']) ? sanitize_text_field($_POST['feedback_voting_schema_type']) : '';
        update_post_meta($post_id, '_feedback_voting_schema_type', $schema_type);

        $address = isset($_POST['feedback_voting_address']) ? sanitize_text_field($_POST['feedback_voting_address']) : '';
        update_post_meta($post_id, '_feedback_voting_address', $address);

        $lb = isset($_POST['feedback_voting_localbusiness']) && is_array($_POST['feedback_voting_localbusiness']) ? array_map('sanitize_text_field', $_POST['feedback_voting_localbusiness']) : array();
        update_post_meta($post_id, '_feedback_voting_localbusiness', $lb);
    }

    /**
     * Exportiert alle Einträge als CSV (Windows-1252)
     */
    public function handle_export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die(
                __('Du hast keine Berechtigung, dies zu tun.'),
                '',
                array('response' => 403)
            );
        }
        check_admin_referer('feedback_voting_export_csv_action');

        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback_votes';
        $filename   = 'feedback_voting_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=Windows-1252');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        $columns = array(
            __('Datum', 'feedback-voting'),
            __('Frage', 'feedback-voting'),
            __('Vote', 'feedback-voting'),
            __('Feedback-Text', 'feedback-voting'),
            __('Shortcode-Location', 'feedback-voting'),
            __('URL', 'feedback-voting'),
            __('Post ID', 'feedback-voting'),
        );
        $columns_1252 = array_map(function($col) {
            return iconv('UTF-8', 'Windows-1252//TRANSLIT', $col);
        }, $columns);
        fputcsv($output, $columns_1252);

        $rows = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC");
        foreach ($rows as $r) {
            $post_title = get_the_title($r->post_id);
            if (empty($post_title)) {
                $post_title = __('Keine Zuordnung', 'feedback-voting');
            }
            $line = array(
                $r->created_at,
                $r->question,
                $r->vote,
                $r->feedback_text,
                $post_title,
                $r->page_url,
                $r->post_id
            );
            $line_1252 = array_map(function($val) {
                return iconv('UTF-8', 'Windows-1252//TRANSLIT', $val);
            }, $line);
            fputcsv($output, $line_1252);
        }

        fclose($output);
        exit;
    }

    /**
     * Löscht alle Feedback-Einträge
     */
    public function handle_delete_all() {
        if (!current_user_can('manage_options')) {
            wp_die(
                __('Du hast keine Berechtigung, dies zu tun.'),
                '',
                array('response' => 403)
            );
        }
        check_admin_referer('feedback_voting_delete_all_action');

        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback_votes';
        $wpdb->query("TRUNCATE TABLE {$table_name}");

        wp_redirect(add_query_arg(
            array('feedback_voting_deleted' => '1'),
            admin_url('admin.php?page=feedback-voting')
        ));
        exit;
    }

    /**
     * Render simple info page with description and demo shortcode.
     */
    public function render_info_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Feedback Voting Info', 'feedback-voting'); ?></h1>
            <p><?php _e('Dieses Plugin fügt ein Feedback-Formular und optional eine Score-Anzeige hinzu. Alle Einstellungen können im Block oder per Shortcode angepasst werden.', 'feedback-voting'); ?></p>
            <p><?php _e('Verwende den folgenden Shortcode als Beispiel:', 'feedback-voting'); ?></p>
            <input type="text" class="large-text" readonly value="[feedback_voting question='War diese Antwort hilfreich?'][feedback_score question='War diese Antwort hilfreich?' post_id='123']" onclick="this.select();" />
        </div>
        <?php
    }
}
