<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Hooks für Export und Löschen
        add_action('admin_post_feedback_voting_export_csv', array($this, 'handle_export_csv'));
        add_action('admin_post_feedback_voting_delete_all', array($this, 'handle_delete_all'));
    }

    /**
     * Enqueue für WP-Color-Picker und Admin-JS (Copy-Buttons)
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf unserem Plugin-Admin-Screen
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
     * Menüpunkt im WP-Admin
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
     * Registriert Plugin-Einstellungen
     */
    public function register_settings() {
        register_setting('feedback_voting_settings_group', 'feedback_voting_enable_feedback_field');
        register_setting('feedback_voting_settings_group', 'feedback_voting_primary_color', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#0073aa',
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
            __('Design-Farbe (Primär)', 'feedback-voting'),
            array($this, 'primary_color_render'),
            'feedback_voting_settings',
            'feedback_voting_settings_section'
        );
    }

    /**
     * Rendert den Checkbox-Field für Freitext bei "Nein"
     */
    public function feedback_field_render() {
        $value = get_option('feedback_voting_enable_feedback_field', '1');
        ?>
        <label for="feedback_voting_enable_feedback_field">
            <input
                type="checkbox"
                id="feedback_voting_enable_feedback_field"
                name="feedback_voting_enable_feedback_field"
                value="1"
                <?php checked($value, '1'); ?>
            />
            <?php _e('Aktivieren, damit ein Freitext-Feld erscheint, wenn der Benutzer "Nein" wählt.', 'feedback-voting'); ?>
        </label>
        <?php
    }

    /**
     * Rendert den Color-Picker für die Primär-Farbe
     */
    public function primary_color_render() {
        $color = get_option('feedback_voting_primary_color', '#0073aa');
        printf(
            '<input type="text" id="feedback_voting_primary_color" name="feedback_voting_primary_color" value="%s" data-default-color="#0073aa" />',
            esc_attr($color)
        );
    }

    /**
     * Baut das Dashboard im Admin-Bereich auf
     */
    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback_votes';

        // Erfolgs-Hinweis beim Löschen
        if (isset($_GET['feedback_voting_deleted']) && $_GET['feedback_voting_deleted'] === '1') {
            echo '<div class="updated"><p>' . __('Alle Feedback-Einträge wurden gelöscht.', 'feedback-voting') . '</p></div>';
        }

        // Gesamtübersicht Ja/Nein
        $total_yes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE vote = 'yes'");
        $total_no  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE vote = 'no'");

        // Top-Fragen
        $results = $wpdb->get_results("
            SELECT question,
                   SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END) AS total_yes,
                   SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END) AS total_no
            FROM {$table_name}
            GROUP BY question
            ORDER BY (SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END)
                    + SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END)) DESC
            LIMIT 10
        ");

        // Pagination-Einstellungen
        $per_page = 20;
        $paged    = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset   = ($paged - 1) * $per_page;

        // Filter nach Post ID
        $selected_post_id = isset($_GET['post_id_filter']) ? intval($_GET['post_id_filter']) : 0;
        if ($selected_post_id > 0) {
            $total_items = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d", $selected_post_id)
            );
            $all_feedbacks = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE post_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
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

        // Pagination-Links
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
            'next_text' => '&raquo;',
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
                <?php if ($results) : foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->question); ?></td>
                        <td><?php echo intval($row->total_yes); ?></td>
                        <td><?php echo intval($row->total_no); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="3"><?php _e('Keine Daten vorhanden.', 'feedback-voting'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <hr>
            <h2><?php _e('Alle Feedback-Einträge', 'feedback-voting'); ?></h2>

            <form method="get" style="margin-bottom:1em;">
                <input type="hidden" name="page" value="feedback-voting"/>
                <label for="post_id_filter"><?php _e('Post ID filtern:', 'feedback-voting'); ?></label>
                <input type="number" name="post_id_filter" id="post_id_filter" value="<?php echo $selected_post_id ?: ''; ?>"/>
                <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Filter', 'feedback-voting'); ?>"/>
                <?php if ($selected_post_id) : ?>
                    <a class="button button-link" href="<?php echo admin_url('admin.php?page=feedback-voting'); ?>">
                        <?php _e('Filter zurücksetzen', 'feedback-voting'); ?>
                    </a>
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
                <?php if ($all_feedbacks) : foreach ($all_feedbacks as $feedback) :
                    $post_title = get_the_title($feedback->post_id) ?: __('Keine Zuordnung', 'feedback-voting');
                    $post_link  = get_permalink($feedback->post_id);
                ?>
                    <tr>
                        <td><?php echo esc_html($feedback->created_at); ?></td>
                        <td><?php echo esc_html($feedback->question); ?></td>
                        <td><?php echo esc_html($feedback->vote); ?></td>
                        <td><?php echo esc_html($feedback->feedback_text); ?></td>
                        <td>
                            <?php if ($post_link && $post_title !== __('Keine Zuordnung', 'feedback-voting')) : ?>
                                <a href="<?php echo esc_url($post_link); ?>" target="_blank">
                                    <?php echo esc_html($post_title); ?>
                                </a>
                            <?php else : ?>
                                <em><?php echo esc_html($post_title); ?></em>
                            <?php endif; ?>
                        </td>
                        <td><?php echo intval($feedback->post_id); ?></td>
                        <td>
                            <button type="button" class="button feedback-copy-button">
                                <?php _e('Kopieren', 'feedback-voting'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
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
                <input type="submit" class="button button-secondary"
                       value="<?php esc_attr_e('Alle Feedback-Einträge als CSV herunterladen', 'feedback-voting'); ?>"/>
            </form>

            <hr>
            <h2><?php _e('Alle Feedback-Einträge löschen', 'feedback-voting'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('feedback_voting_delete_all_action'); ?>
                <input type="hidden" name="action" value="feedback_voting_delete_all">
                <input type="submit" class="button button-secondary"
                       value="<?php esc_attr_e('Alle Feedback-Einträge löschen', 'feedback-voting'); ?>"
                       onclick="return confirm('<?php _e('Wirklich alle Feedback-Einträge löschen?', 'feedback-voting'); ?>');"/>
            </form>

            <hr>
            <h2><?php _e('Einstellungen', 'feedback-voting'); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('feedback_voting_settings_group');
                do_settings_sections('feedback_voting_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * CSV-Export-Handler
     */
    public function handle_export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Du hast keine Berechtigung, dies zu tun.'), 403);
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
            $post_title = get_the_title($r->post_id) ?: __('Keine Zuordnung', 'feedback-voting');
            $line = array(
                $r->created_at, $r->question, $r->vote,
                $r->feedback_text, $post_title, $r->post_id
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
     * Handler für "Alle löschen"
     */
    public function handle_delete_all() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Du hast keine Berechtigung, dies zu tun.'), 403);
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
