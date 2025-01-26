jQuery(document).ready(function($) {

    $('.feedback-voting-container').each(function() {
        var container = $(this);
        var question = container.data('question');

        // Aus den per wp_localize_script übergebenen Daten
        var enableFeedbackField = feedbackVoting.enableFeedbackField;

        // Klick auf Ja/Nein-Button
        container.find('.feedback-button').on('click', function() {
            var vote = $(this).data('vote');

            // Bei "Nein" und aktivierter Option -> Freitextfeld anzeigen
            if (vote === 'no' && enableFeedbackField === '1') {
                container.find('.feedback-no-text-container').slideDown();
            } else {
                // Sonst direkt Vote abschicken
                container.find('.feedback-no-text-container').slideUp();
                submitVote(question, vote, '');
            }
        });

        // Wenn der User das Textfeld verlässt (blur), wird automatisch gesendet
        container.find('#feedback-no-text').on('blur', function() {
            var feedbackText = $(this).val();
            if (feedbackText.trim() !== '') {
                submitVote(question, 'no', feedbackText);
            }
        });

        // AJAX-Vote-Funktion
        function submitVote(question, vote, feedback) {
            // Buttons deaktivieren, damit nicht doppelt geklickt wird
            container.find('.feedback-button').prop('disabled', true);

            $.ajax({
                url: feedbackVoting.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'my_feedback_plugin_vote',
                    question: question,
                    vote: vote,
                    feedback: feedback
                },
                success: function(response) {
                    if (response.success) {
                        // Danke-Nachricht einblenden
                        container.find('.feedback-thankyou-message').slideDown();
                    } else {
                        // Bei Fehler Buttons wieder aktivieren
                        container.find('.feedback-button').prop('disabled', false);
                        alert(response.data.message);
                    }
                },
                error: function() {
                    // Bei Netzwerkfehler Buttons wieder aktivieren
                    container.find('.feedback-button').prop('disabled', false);
                    alert('Es ist ein Fehler beim Senden der Bewertung aufgetreten.');
                }
            });
        }
    });

});
