<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_Admin {

    public function __construct() {
        // Admin-Menüs und -Einstellungen
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // CSV-Export und Löschen
        add_action('admin_post_feedback_voting_export_csv', array($this, 'handle_export_csv'));
        add_action('admin_post_feedback_voting_delete_all', array($this, 'handle_delete_all'));
    }

    /**
     * Lädt Color-Picker und Admin-Script für Copy-Buttons
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_feedback-voting') {
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
    }

    /**
     * Registriert alle Plugin-Einstellungen
     */
    public function register_settings() {
        register_setting('feedback_voting_settings_group', 'feedback_voting_enable_feedback_field');
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
        register_setting('feedback_voting_settings_group', 'feedback_voting_border_radius', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '9999px',
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

        add_settings_section(
            'feedback_voting_settings_section',
            __('Feedback Voting Einstellungen', 'feedback-voting'),
            null,
            'feedback_voting_settings'
        );

        add_settings_field(
            'feedback_voting_enable_feedback_field',
            __('Freitext-Feld bei "Nein" aktivieren?', 'feedback-voting'),
            array($this, 'feedback_field_render'),
            'feedback_voting_settings',
            'feedback_voting_settings_section'
        );
        add_settings_field(
            'feedback_voting_primary_color',
            __('Primär-Farbe', 'feedback-voting'),
            array($this, 'color_field_render'),
            'feedback_voting_settings',
            'feedback_voting_settings_section',
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
            'feedback_voting_settings_section',
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
            'feedback_voting_settings_section',
            array(
                'option_name' => 'feedback_voting_button_hover_color',
                'label_for'   => 'feedback_voting_button_hover_color',
                'default'     => '#005b8d',
            )
        );
        add_settings_field(
            'feedback_voting_border_radius',
            __('Button-Rundungen (CSS)', 'feedback-voting'),
            array($this, 'border_radius_render'),
            'feedback_voting_settings',
            'feedback_voting_settings_section'
        );
        add_settings_field(
            'feedback_voting_before_text',
            __('Text oberhalb des Feedback-Felds', 'feedback-voting'),
            array($this, 'before_text_render'),
            'feedback_voting_settings',
            'feedback_voting_settings_section'
        );
        add_settings_field(
            'feedback_voting_box_width',
            __('Box-Breite (%)', 'feedback-voting'),
            array($this, 'box_width_render'),
            'feedback_voting_settings',
            'feedback_voting_settings_section'
        );
        add_settings_field(
            'feedback_voting_score_label',
            __('Text innerhalb der Score-Box', 'feedback-voting'),
            array($this, 'score_label_render'),
            'feedback_voting_settings',
            'feedback_voting_settings_section'
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
            "SELECT question,
                    SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END) AS total_yes,
                    SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END) AS total_no
             FROM {$table_name}
             GROUP BY question
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
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Frage', 'feedback-voting'); ?></th>
                        <th><?php _e('"Ja" Stimmen', 'feedback-voting'); ?></th>
                        <th><?php _e('"Nein" Stimmen', 'feedback-voting'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($results)) : ?>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->question); ?></td>
                            <td><?php echo intval($row->total_yes); ?></td>
                            <td><?php echo intval($row->total_no); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3"><?php _e('Keine Daten vorhanden.', 'feedback-voting'); ?></td>
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
                            <td><?php echo intval($feedback->post_id); ?></td>
                            <td><button type="button" class="button feedback-copy-button"><?php _e('Kopieren', 'feedback-voting'); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7"><?php _e('Keine Feedbacks vorhanden.', 'feedback-voting'); ?></td></tr>
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

            <hr>
            <h2><?php _e('Einstellungen', 'feedback-voting'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('feedback_voting_settings_group'); do_settings_sections('feedback_voting_settings'); submit_button(); ?>
            </form>
        </div>
        <?php
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
}
