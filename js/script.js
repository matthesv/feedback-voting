jQuery(function($) {

    // Globale Einstellung, ob das Freitextfeld bei "Nein" aktiviert ist:
    var enableFeedbackField = feedbackVoting.enableFeedbackField;

    // Klick auf "Ja" oder "Nein"
    $(document).on('click', '.feedback-voting-container .feedback-button', function(e) {
        e.preventDefault();

        var container = $(this).closest('.feedback-voting-container');
        var question = container.data('question');
        var postId = container.data('postid') || 0;
        var vote = $(this).data('vote');

        // "Ja" -> direkt speichern
        if (vote === 'yes') {
            // Button sofort deaktivieren, damit nicht mehrfach geklickt wird
            $(this).prop('disabled', true);
            submitVote(container, question, 'yes', '', postId);
        }
        // "Nein"
        else if (vote === 'no') {
            // Wenn das Freitextfeld laut Einstellung aktiv ist, erst Feld einblenden,
            // sonst direkt absenden.
            if (enableFeedbackField === '1') {
                // Button "no" deaktivieren, damit kein Mehrfachklick
                $(this).prop('disabled', true);
                // Textcontainer einblenden
                container.find('.feedback-no-text-container').slideDown();
            } else {
                // Falls Freitextfeld deaktiviert, sofort abschicken
                $(this).prop('disabled', true);
                submitVote(container, question, 'no', '', postId);
            }
        }
    });

    // Klick auf "Feedback senden" bei "Nein"
    $(document).on('click', '.feedback-voting-container .feedback-submit-no', function(e) {
        e.preventDefault();

        var container = $(this).closest('.feedback-voting-container');
        var question = container.data('question');
        var postId = container.data('postid') || 0;
        var feedbackText = container.find('#feedback-no-text').val().trim();

        // Button deaktivieren, um Doppelklick zu verhindern
        $(this).prop('disabled', true);

        // "no" + Freitext speichern
        submitVote(container, question, 'no', feedbackText, postId);
    });

    // AJAX-Vote-Funktion
    function submitVote(container, question, vote, feedback, postId) {
        // Alle Buttons deaktivieren, damit wirklich nichts doppelt gesendet wird
        container.find('.feedback-button').prop('disabled', true);

        $.ajax({
            url: feedbackVoting.ajaxUrl,
            method: 'POST',
            data: {
                action: 'my_feedback_plugin_vote',
                question: question,
                vote: vote,
                feedback: feedback,
                post_id: postId,
                // Nonce-Parameter:
                security: feedbackVoting.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Buttons & Frage entfernen, stattdessen Danke-Text einblenden
                    container.find('.feedback-question, .feedback-button, .feedback-no-text-container').remove();
                    container.append(
                        '<p class="feedback-thankyou">'+
                        'Vielen Dank für Ihr Feedback! Jede Antwort hilft uns, uns zu verbessern.'+
                        '</p>'
                    );
                } else {
                    // Bei Fehler wieder aktivieren
                    container.find('.feedback-button').prop('disabled', false);
                    alert(response.data.message);
                }
            },
            error: function() {
                // Bei Netzwerkfehler -> Buttons wieder aktivieren
                container.find('.feedback-button').prop('disabled', false);
                alert('Es ist ein Fehler beim Senden der Bewertung aufgetreten.');
            }
        });
    }
});
