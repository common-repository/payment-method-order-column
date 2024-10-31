jQuery(document).ready(function ($) {
    var file_frame;

    $('.upload_icon_button').on('click', function (e) {
        e.preventDefault();
        
        
        var button = $(this);
        var input = button.prev('input'); // Trova l'input di testo relativo per memorizzare l'URL dell'immagine
        var preview = button.closest('.payment-icons-row').find('.payment-method-icon img'); // Trova l'elemento <img> per l'anteprima

        // Se esiste già un'istanza del file frame, la riutilizziamo
        if (file_frame) {
            file_frame.open();
            return;
        }

        // Crea la finestra del file frame
        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Choose Icon',
            button: {
                text: 'Choose Icon'
            },
            multiple: false
        });

        // Quando un file viene selezionato, lo impostiamo come valore dell'input
        file_frame.on('select', function () {
            var attachment = file_frame.state().get('selection').first().toJSON();
            input.val(attachment.url); // Imposta il valore dell'input di testo con l'URL dell'icona
            preview.attr('src', attachment.url); // Aggiorna l'immagine di anteprima
        });

        // Apre la finestra di dialogo del file frame
        file_frame.open();
    });
});
