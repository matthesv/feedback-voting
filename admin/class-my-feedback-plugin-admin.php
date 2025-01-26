<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Erstellt den Menüpunkt in der WordPress-Admin-Oberfläche.
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
     * Registriert die Plugin-Einstellungen im Bereich "Einstellungen".
     */
    public function register_settings() {
        register_setting('feedback_voting_settings_group', 'feedback_voting_enable_feedback_field');

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
    }

    /**
     * Rendert das Eingabefeld für die Einstellung, ob ein Freitextfeld bei "Nein" angezeigt wird.
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
     * Baut die eigentliche Admin-Seite (Dashboard) auf.
     * Hier werden außerdem CSV-Export und "Alle löschen"-Funktion angeboten.
     */
    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback_votes';

        // Falls "Alle löschen" geklickt wurde
        if (isset($_POST['feedback_voting_delete_all'])) {
            check_admin_referer('feedback_voting_delete_all_action'); // Sicherheit
            $wpdb->query("TRUNCATE TABLE $table_name");
            echo '<div class="updated"><p>' . __('Alle Feedback-Einträge wurden gelöscht.', 'feedback-voting') . '</p></div>';
        }

        // Falls "CSV Export" geklickt wurde
        if (isset($_POST['feedback_voting_export_csv'])) {
            check_admin_referer('feedback_voting_export_csv_action'); // Sicherheit

            // Kopfzeilen für die CSV-Ausgabe
            $filename = 'feedback_voting_' . date('Y-m-d_H-i-s') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            // Ausgabe-Stream öffnen
            $output = fopen('php://output', 'w');

            // Spaltenkopf
            fputcsv($output, array(
                __('Datum', 'feedback-voting'),
                __('Frage', 'feedback-voting'),
                __('Vote', 'feedback-voting'),
                __('Feedback-Text', 'feedback-voting'),
                __('Shortcode-Location (post_id)', 'feedback-voting')
            ));

            // Alle Datensätze
            $rows = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
            if (!empty($rows)) {
                foreach ($rows as $r) {
                    fputcsv($output, array(
                        $r->created_at,
                        $r->question,
                        $r->vote,
                        $r->feedback_text,
                        $r->post_id
                    ));
                }
            }
            fclose($output);
            exit; // WICHTIG: Damit kein zusätzliches HTML mehr ausgegeben wird
        }

        // Gesamtanzahl Ja/Nein
        $total_yes = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE vote = 'yes'");
        $total_no  = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE vote = 'no'");

        // Top Fragen
        $results = $wpdb->get_results("
            SELECT question,
                   SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END) AS total_yes,
                   SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END) AS total_no
            FROM $table_name
            GROUP BY question
            ORDER BY (SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END)
                    + SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END)) DESC
            LIMIT 10
        ");

        // Letzte 50 Feedbacks (inkl. Freitext)
        $all_feedbacks = $wpdb->get_results("
            SELECT *
            FROM $table_name
            ORDER BY created_at DESC
            LIMIT 50
        ");
        ?>
        <div class="wrap">
            <h1><?php _e('Feedback Voting Dashboard', 'feedback-voting'); ?></h1>
            <hr>

            <h2><?php _e('Gesamtübersicht', 'feedback-voting'); ?></h2>
            <p><strong><?php _e('Anzahl "Ja":', 'feedback-voting'); ?></strong> <?php echo intval($total_yes); ?></p>
            <p><strong><?php _e('Anzahl "Nein":', 'feedback-voting'); ?></strong> <?php echo intval($total_no); ?></p>

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
            <h2><?php _e('Alle Feedback-Einträge (letzte 50)', 'feedback-voting'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Datum', 'feedback-voting'); ?></th>
                        <th><?php _e('Frage', 'feedback-voting'); ?></th>
                        <th><?php _e('Vote', 'feedback-voting'); ?></th>
                        <th><?php _e('Feedback-Text', 'feedback-voting'); ?></th>
                        <th><?php _e('Shortcode-Location', 'feedback-voting'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_feedbacks)) : ?>
                        <?php foreach ($all_feedbacks as $feedback) :
                            $post_title = get_the_title($feedback->post_id);
                            $post_link  = get_permalink($feedback->post_id);
                            ?>
                            <tr>
                                <td><?php echo esc_html($feedback->created_at); ?></td>
                                <td><?php echo esc_html($feedback->question); ?></td>
                                <td><?php echo esc_html($feedback->vote); ?></td>
                                <td><?php echo esc_html($feedback->feedback_text); ?></td>
                                <td>
                                    <?php if (!empty($post_title)) : ?>
                                        <a href="<?php echo esc_url($post_link); ?>" target="_blank">
                                            <?php echo esc_html($post_title); ?>
                                        </a>
                                        <br>
                                        <small><?php echo 'ID: ' . intval($feedback->post_id); ?></small>
                                    <?php else : ?>
                                        <em><?php _e('Keine Zuordnung', 'feedback-voting'); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php _e('Keine Feedbacks vorhanden.', 'feedback-voting'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <hr>
            <h2><?php _e('CSV-Export', 'feedback-voting'); ?></h2>
            <!-- Formular NUR für CSV-Export -->
            <form method="post">
                <?php wp_nonce_field('feedback_voting_export_csv_action'); ?>
                <input
                    type="submit"
                    name="feedback_voting_export_csv"
                    class="button button-secondary"
                    value="<?php esc_attr_e('Alle Feedback-Einträge als CSV herunterladen', 'feedback-voting'); ?>"
                />
            </form>

            <hr>
            <h2><?php _e('Alle Feedback-Einträge löschen', 'feedback-voting'); ?></h2>
            <!-- Formular NUR für "Alle löschen" -->
            <form method="post">
                <?php wp_nonce_field('feedback_voting_delete_all_action'); ?>
                <input
                    type="submit"
                    name="feedback_voting_delete_all"
                    class="button button-secondary"
                    value="<?php esc_attr_e('Alle Feedback-Einträge löschen', 'feedback-voting'); ?>"
                    onclick="return confirm('<?php _e('Wirklich alle Feedback-Einträge löschen?', 'feedback-voting'); ?>');"
                />
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
}
