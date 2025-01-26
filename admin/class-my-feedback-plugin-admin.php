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
            'dashicons-thumbs-up', // Icon
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
        <input
            type="checkbox"
            name="feedback_voting_enable_feedback_field"
            value="1"
            <?php checked($value, '1'); ?>
        />
        <span><?php _e('Aktivieren, damit ein Freitext-Feld erscheint, wenn der Benutzer "Nein" wählt.', 'feedback-voting'); ?></span>
        <?php
    }

    /**
     * Baut die eigentliche Admin-Seite (Dashboard) auf.
     */
    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback_votes';

        // Gesamtanzahl Ja/Nein
        $total_yes = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE vote = 'yes'");
        $total_no  = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE vote = 'no'");

        // Hol dir die am häufigsten bewerteten Fragen
        $results = $wpdb->get_results("
            SELECT question,
                   SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END) AS total_yes,
                   SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END) AS total_no
            FROM $table_name
            GROUP BY question
            ORDER BY (SUM(CASE WHEN vote='yes' THEN 1 ELSE 0 END) + SUM(CASE WHEN vote='no' THEN 1 ELSE 0 END)) DESC
            LIMIT 10
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
