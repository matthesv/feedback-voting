jQuery(function($) {

    // Globale Einstellung, ob das Freitextfeld bei "Nein" aktiviert ist:
    var enableFeedbackField = feedbackVoting.enableFeedbackField;

    // Klick auf "Ja" oder "Nein"
    $(document).on('click', '.feedback-voting-container .feedback-button', function(e) {
        e.preventDefault();

        var container = $(this).closest('.feedback-voting-container');
        var noBox = container.next('.feedback-no-text-box');

        var question = container.data('question');
        var postId = container.data('postid') || 0;
        var vote = $(this).data('vote');

        // "Ja" -> direkt speichern (Insert)
        if (vote === 'yes') {
            // Button sofort deaktivieren, damit kein Mehrfachklick
            $(this).prop('disabled', true);

            ajaxVote(
                null,          // kein vote_id => Insert
                question,
                'yes',
                '',
                postId,
                function onSuccess() {
                    // Alles ausblenden + Danke-Text
                    showThankYou(container, noBox);
                },
                function onError(errorMsg) {
                    // Fehlermeldung und Buttons reaktivieren
                    alert(errorMsg);
                    $(this).prop('disabled', false);
                }.bind(this)
            );

        // "Nein" -> sofort speichern (Insert) + ggf. Feedback-Box einblenden
        } else if (vote === 'no') {
            // Button "No" deaktivieren, um Mehrfachklick zu verhindern
            $(this).prop('disabled', true);

            ajaxVote(
                null,          // kein vote_id => Insert
                question,
                'no',
                '',
                postId,
                function onSuccess(response) {
                    // Speichern des Vote-ID in container (für späteres Update)
                    if (response.vote_id) {
                        container.data('voteId', response.vote_id);
                    }

                    // Falls das Freitextfeld aktiviert ist -> Textbox anzeigen
                    // Buttons in der Box wieder aktivieren
                    if (enableFeedbackField === '1') {
                        noBox.slideDown();
                        noBox.find('.feedback-submit-no').prop('disabled', false);
                    } else {
                        // Feedback-Feld ist deaktiviert -> direkt "Danke" anzeigen
                        showThankYou(container, noBox);
                    }
                },
                function onError(errorMsg) {
                    alert(errorMsg);
                    $(this).prop('disabled', false);
                }.bind(this)
            );
        }
    });

    // Klick auf "Feedback senden" (bei "Nein")
    $(document).on('click', '.feedback-no-text-box .feedback-submit-no', function(e) {
        e.preventDefault();

        var noBox = $(this).closest('.feedback-no-text-box');
        var container = noBox.prev('.feedback-voting-container');

        var question = container.data('question');
        var postId   = container.data('postid') || 0;
        var feedbackText = noBox.find('.feedback-no-text').val().trim();

        // Die vote_id aus dem container holen (wurde beim ersten Klick auf "Nein" gesetzt).
        var existingVoteId = container.data('voteId');

        // Button deaktivieren, um Doppelklick zu verhindern
        $(this).prop('disabled', true);

        // Update des bereits vorhandenen Datensatzes (das Feedback-Feld)
        ajaxVote(
            existingVoteId,  // => Update
            question,
            'no',            // vote bleibt 'no'
            feedbackText,
            postId,
            function onSuccess() {
                // Danke einblenden
                showThankYou(container, noBox);
            },
            function onError(errorMsg) {
                alert(errorMsg);
                $(this).prop('disabled', false);
            }.bind(this)
        );
    });

    /**
     * AJAX-Helferfunktion: führt Insert oder Update aus,
     * je nachdem ob voteId null ist oder nicht.
     *
     * @param {number|null} voteId - null => Insert, sonst => Update
     * @param {string} question
     * @param {string} vote
     * @param {string} feedback
     * @param {number} postId
     * @param {function} successCallback
     * @param {function} errorCallback
     */
    function ajaxVote(voteId, question, vote, feedback, postId, successCallback, errorCallback) {
        $.ajax({
            url: feedbackVoting.ajaxUrl,
            method: 'POST',
            data: {
                action: 'my_feedback_plugin_vote',
                vote_id: voteId,     // wichtig für Update
                question: question,
                vote: vote,
                feedback: feedback,
                post_id: postId,
                // Nonce:
                security: feedbackVoting.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (typeof successCallback === 'function') {
                        successCallback(response.data);
                    }
                } else {
                    if (typeof errorCallback === 'function') {
                        errorCallback(response.data.message);
                    }
                }
            },
            error: function() {
                if (typeof errorCallback === 'function') {
                    errorCallback('Es ist ein Netzwerkfehler aufgetreten.');
                }
            }
        });
    }

    /**
     * Blendet das Feedback-Formular aus und zeigt stattdessen einen Danke-Text.
     */
    function showThankYou(container, noBox) {
        container.fadeOut(200, function() {
            noBox.fadeOut(200, function() {
                // Nach dem Ausblenden beider Boxen, einen Danke-Text einfügen
                container.after(
                    '<p class="feedback-thankyou">' +
                        'Vielen Dank für Ihr Feedback! Jede Antwort hilft uns, uns zu verbessern.' +
                    '</p>'
                );
            });
        });
    }
});
