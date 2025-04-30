jQuery(function($){
    // Alle Color-Picker-Felder initialisieren
    $('.feedback-color-field').wpColorPicker();

    // Copy-Button per Delegation
    $(document).on('click', '.feedback-copy-button', function(e){
        e.preventDefault();
        var row = $(this).closest('tr');
        var texts = row.find('td').not(':last-child').map(function(){
            return $(this).text().trim();
        }).get().join('\t');

        var copyToClipboard = function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text);
            } else {
                var temp = $('<textarea>');
                temp.css({position: 'absolute', left: '-9999px', top: '0'});
                $('body').append(temp);
                temp.val(text).select();
                var ok = document.execCommand('copy');
                temp.remove();
                return ok ? Promise.resolve() : Promise.reject();
            }
        };

        copyToClipboard(texts).then(function(){
            alert('Zeile kopiert!');
        }).catch(function(){
            alert('Fehler beim Kopieren!');
        });
    });
});
