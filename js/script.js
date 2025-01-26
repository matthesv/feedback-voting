jQuery(function($) {
    // Globale Einstellung, ob das Freitextfeld bei "Nein" aktiviert ist
    var enableFeedbackField = feedbackVoting.enableFeedbackField;

    // Delegierter Klick-Handler für alle "Ja/Nein"-Buttons
    $(document).on('click', '.feedback-voting-container .feedback-button', function(e) {
        e.preventDefault();

        var container = $(this).closest('.feedback-voting-container');
        var question = container.data('question');
        var vote = $(this).data('vote');

        // Bei "Nein" und aktivierter Option -> Freitextfeld anzeigen
        if (vote === 'no' && enableFeedbackField === '1') {
            container.find('.feedback-no-text-container').slideDown();
        } else {
            // Sonst direkt Vote abschicken
            container.find('.feedback-no-text-container').slideUp();
            submitVote(container, question, vote, '');
        }
    });

    // Delegierter Blur-Handler für das Freitextfeld
    $(document).on('blur', '.feedback-voting-container #feedback-no-text', function() {
        var container = $(this).closest('.feedback-voting-container');
        var question = container.data('question');
        var feedbackText = $(this).val().trim();

        if (feedbackText !== '') {
            submitVote(container, question, 'no', feedbackText);
        }
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
                feedback: feedback
                // 'security': feedbackVoting.nonce, // Falls du mit Nonce arbeiten willst
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
