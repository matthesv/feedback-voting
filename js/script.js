jQuery(function($) {
    // Globale Einstellung, ob das Freitextfeld bei "Nein" aktiviert ist
    var enableFeedbackField = feedbackVoting.enableFeedbackField;

    // Delegierter Klick-Handler für alle "Ja/Nein"-Buttons
    $(document).on('click', '.feedback-voting-container .feedback-button', function(e) {
        e.preventDefault();

        var container = $(this).closest('.feedback-voting-container');
        var question = container.data('question');
        var vote = $(this).data('vote');

        // Bei "Ja" wird direkt abgesendet
        if (vote === 'yes') {
            submitVote(container, question, 'yes', '');
        }
        // Bei "Nein" und aktivierter Option -> Freitextfeld einblenden, noch kein Submit
        else if (vote === 'no' && enableFeedbackField === '1') {
            container.find('.feedback-no-text-container').slideDown();
        }
        else {
            // Wenn das Freitextfeld deaktiviert ist, dann trotzdem sofort "no" absenden
            submitVote(container, question, 'no', '');
        }
    });

    // Klick-Handler auf den "Feedback senden" Button bei "Nein"
    $(document).on('click', '.feedback-voting-container .feedback-submit-no', function(e) {
        e.preventDefault();

        var container = $(this).closest('.feedback-voting-container');
        var question = container.data('question');
        // Der Benutzer könnte noch nichts eingetippt haben, also darf es leer sein
        var feedbackText = container.find('#feedback-no-text').val().trim();

        // Jetzt wird "no" plus optionaler Freitext abgesendet
        submitVote(container, question, 'no', feedbackText);
    });

    // AJAX-Vote-Funktion
    function submitVote(container, question, vote, feedback) {
        // Buttons deaktivieren, damit nicht mehrmals geklickt wird
        container.find('.feedback-button').prop('disabled', true);

        $.ajax({
            url: feedbackVoting.ajaxUrl,
            method: 'POST',
            data: {
                action: 'my_feedback_plugin_vote',
                question: question,
                vote: vote,
                feedback: feedback,
                // NEU: Nonce-Parameter
                security: feedbackVoting.nonce
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
