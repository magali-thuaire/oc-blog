import $ from "jquery";

import emptyModal from "./empty_modal";

// Appel AJAX et intégration d'une modale
export default function (target, modal) {
    // Récupère l'url depuis la propriété "data-href" de la balise html a
    let url = $(target).data('href');
    // Appel ajax vers l'action symfony qui nous renvoie la vue
    return $.ajax({
        method: 'GET',
        url: url
    }).done(function (data) {
        // Injecte le html dans la modale
        $('#' + modal).html(data);
        // Récupère l'id de la modale
        let modalId = $('.modal').attr('id');
        // Ouvre la modale
        let targetModal = new bootstrap.Modal($('#' + modalId));
        targetModal.show();
        emptyModal(modal);
    });
}