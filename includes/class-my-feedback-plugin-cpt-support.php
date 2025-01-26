<?php
if (!defined('ABSPATH')) {
    exit; // Sicherheitsabbruch
}

class My_Feedback_Plugin_CPT_Support {

    public function __construct() {
        add_filter('the_content', array($this, 'ensure_shortcodes_in_cpt'));
    }

    /**
     * Stellt sicher, dass auch in unserem CPT Shortcodes korrekt ausgeführt werden,
     * wenn 'the_content()' aufgerufen wird.
     */
    public function ensure_shortcodes_in_cpt($content) {
        // Beispiel: Nur bei einem bestimmten CPT "mein_cpt_slug"
        // kannst du den Code anpassen oder auch die Abfrage ganz weglassen,
        // um Shortcodes überall zu erzwingen.
        if (is_singular('faq')) {
            $content = do_shortcode($content);
        }
        return $content;
    }
}
