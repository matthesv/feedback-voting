jQuery(function($) {

    // Globale Einstellungen
    var enableFeedbackField = feedbackVoting.enableFeedbackField;
    var preventMultiple    = feedbackVoting.preventMultiple === '1';

    function setCookie(name, value, hours) {
        var expires = '';
        if (hours) {
            var date = new Date();
            date.setTime(date.getTime() + (hours * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/';
    }

    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // Klick auf "Ja" oder "Nein"
    $(document).on('click', '.feedback-voting-container .feedback-button', function(e) {
        e.preventDefault();

        var container = $(this).closest('.feedback-voting-container');
        var noBox = container.next('.feedback-no-text-box');

        var question = container.data('question');
        var postId = container.data('postid') || 0;
        var vote = $(this).data('vote');
        var cookieName = 'fv_' + encodeURIComponent(question) + '_' + postId;

        if (preventMultiple && getCookie(cookieName)) {
            alert('Sie haben bereits abgestimmt.');
            return;
        }

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
                    if (preventMultiple) {
                        setCookie(cookieName, '1', 24);
                    }
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
                        if (preventMultiple) {
                            setCookie(cookieName, '1', 24);
                        }
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
        var cookieName = 'fv_' + encodeURIComponent(question) + '_' + postId;

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
                if (preventMultiple) {
                    setCookie(cookieName, '1', 24);
                }
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
