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

        // Bei "Ja" -> direkt speichern
        if (vote === 'yes') {
            submitVote(container, question, 'yes', '', postId);
        }
        // Bei "Nein" und Option aktiv -> Freitextfeld zuerst einblenden
        else if (vote === 'no' && enableFeedbackField === '1') {
            // Zeige Freitextfeld
            container.find('.feedback-no-text-container').slideDown();
        }
        else {
            // Wenn Freitextfeld deaktiviert ist, sofort "no" absenden
            submitVote(container, question, 'no', '', postId);
        }
    });

    // Klick auf "Feedback senden" bei "Nein"
    $(document).on('click', '.feedback-voting-container .feedback-submit-no', function(e) {
        e.preventDefault();

        var container = $(this).closest('.feedback-voting-container');
        var question = container.data('question');
        var postId = container.data('postid') || 0;
        var feedbackText = container.find('#feedback-no-text').val().trim();

        // Jetzt "no" + Freitext speichern
        submitVote(container, question, 'no', feedbackText, postId);
    });

    // AJAX-Vote-Funktion
    function submitVote(container, question, vote, feedback, postId) {
        // Buttons deaktivieren, damit nichts doppelt geklickt wird
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
                    // Box ausblenden
                    container.addClass('feedback-submitted');
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
