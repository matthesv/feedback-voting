jQuery(function($){
    // Color Picker initialisieren
    $('#feedback_voting_primary_color').wpColorPicker();

    // Copy-Button: kopiert alle TDs der Zeile als Tab-getrennten Text
    $('.feedback-copy-button').on('click', function(e){
        e.preventDefault();
        var row = $(this).closest('tr');
        var texts = row.find('td').map(function(){
            return $(this).text().trim();
        }).get().join('\t');

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texts).then(function(){
                alert('Zeile kopiert!');
            });
        } else {
            // Fallback
            var temp = $('<textarea>');
            $('body').append(temp);
            temp.val(texts).select();
            document.execCommand('copy');
            temp.remove();
            alert('Zeile kopiert!');
        }
    });
});
