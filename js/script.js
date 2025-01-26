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
        // "Nein" -> falls Freitextfeld aktiviert: einblenden, sonst ebenso per „Feedback senden“-Button absenden
        else if (vote === 'no') {
            // Wir verhindern, dass beim ersten "no"-Klick sofort ein Insert passiert.
            // Stattdessen zeigen wir (falls aktiviert) das Textfeld an oder wir könnten auch hier direkt submitten,
            // wenn Option ausgeschaltet ist.
            
            if (enableFeedbackField === '1') {
                // Button "no" deaktivieren, damit kein Mehrfachklick
                $(this).prop('disabled', true);
                // Textcontainer einblenden
                container.find('.feedback-no-text-container').slideDown();
            } else {
                // Wenn das Feld laut Einstellung nicht gezeigt wird, direkt speichern
                // Button "no" deaktivieren, damit kein Mehrfachklick
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
