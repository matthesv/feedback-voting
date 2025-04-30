jQuery(function($){
    // Color Picker initialisieren
    $('#feedback_voting_primary_color').wpColorPicker();

    // Copy-Button per Delegation: kopiert alle TDs außer der Aktionsspalte als Tab-getrennten Text
    $(document).on('click', '.feedback-copy-button', function(e){
        e.preventDefault();
        var row = $(this).closest('tr');
        // Alle Zellen außer der letzten (Action)
        var texts = row.find('td').not(':last-child').map(function(){
            return $(this).text().trim();
        }).get().join('\t');

        // Funktion, um Text in die Zwischenablage zu kopieren
        var copyToClipboard = function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text);
            } else {
                var temp = $('<textarea>');
                temp.css({position: 'absolute', left: '-9999px', top: '0'});
                $('body').append(temp);
                temp.val(text).select();
                var successful = document.execCommand('copy');
                temp.remove();
                return successful ? Promise.resolve() : Promise.reject();
            }
        };

        copyToClipboard(texts).then(function(){
            alert('Zeile kopiert!');
        }).catch(function(){
            alert('Fehler beim Kopieren!');
        });
    });
});
