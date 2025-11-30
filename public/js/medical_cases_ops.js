/**
 * Gestisce la sottomissione del form per una nuova casistica.
 * Utilizza jQuery per la sottomissione AJAX e SweetAlert per le notifiche.
 */
$(function () {
    // Assicurati che il DOM sia pronto

    // Intercetta l'evento di submit del form
    $("#form-nuova-casistica").on("submit", function (event) {
        event.preventDefault();

        var unindexed_array = $(this).serializeArray();
        var formData = {};

        $.map(unindexed_array, function(n, i){
            formData[n['name']] = n['value'];
        });

        Swal.fire({
            title: 'Salvataggio in corso...',
            text: 'Attendere prego.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '/api/patient',
            type: 'POST',
            data: JSON.stringify({
                "name" : $("#paziente").val(),
                "birthday" : $("#data_nascita").val(),
                "city" : $("#city").val(),
            }),
            contentType: "application/json; charset=utf-8",
            dataType: 'json'
        }).done(function(response){
            formData["patient"] = response.response.id
            $.ajax({
                url: '/api/report',
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: "application/json; charset=utf-8",
                dataType: 'json'
            })
            .done(function (response) {
                console.log(response)
                Swal.fire({
                    icon: 'success',
                    title: 'Successo!',
                    text: 'La nuova casistica è stata salvata correttamente.',
                });
                $('#modal-nuova-casistica').addClass('invisible opacity-0');
                window.location.reload()
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                let errorMessage = 'Si è verificato un errore di rete o del server.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message.errors;
                } else if (errorThrown) {
                    errorMessage = `Errore: ${errorThrown}`;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Operazione fallita!',
                    text: errorMessage
                });
            });
        })

    });

    $(".trashbin").click(function(){
        Swal.fire({
            title: "Sei sicuro?",
            text: "Una volta eliminato, il medical case sparirà!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                // console.log("id", )
                const medical_case = $(this).data("medical-case-id")

                $.ajax({
                    url: '/api/report/'+medical_case,
                    type: 'DELETE',
                    dataType: 'json'
                })
                .done(function (response) {
                    console.log(response)
                    Swal.fire({
                        icon: 'success',
                        title: 'Successo!',
                        text: 'La casistica è stata eliminata.',
                    });
                    window.location.reload()
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'Si è verificato un errore di rete o del server.';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message.errors;
                    } else if (errorThrown) {
                        errorMessage = `Errore: ${errorThrown}`;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Operazione fallita!',
                        text: errorMessage
                    });
                });
            }
        });
    })

    // Seleziona gli elementi del modale
    const $modal = $('#modal-modifica-casistica');
    const $modalPanel = $('#modal-panel-modifica');
    const $form = $('#form-modifica-casistica');

    // --- Funzione per CHIUDERE e RESETTARE il modale ---
    function closeModalAndReset() {
        $modal.addClass('invisible opacity-0');
        $modalPanel.addClass('scale-95');

        $form[0].reset();
        document.body.style.overflow = 'auto'; // Riabilita lo scroll
        $('#dimission_text_wrapper').addClass('opacity-50 pointer-events-none select-none');
    }

    // --- Event Listeners ---

    $('#modal-modifica-close-btn').on('click', closeModalAndReset);
    $('#modal-modifica-cancel-btn').on('click', closeModalAndReset);

    $('#data_dimissione').on('change', function() {
        const $wrapper = $('#dimission_text_wrapper');

        if ($(this).val()) {
            $wrapper.removeClass('opacity-50 pointer-events-none select-none');
        } else {
            $wrapper.addClass('opacity-50 pointer-events-none select-none');
        }
    });

    $form.on('submit', function(event) {
        event.preventDefault(); // Impedisce l'invio reale del form

        var unindexed_array = $(this).serializeArray();
        var formData = {};

        $.map(unindexed_array, function(n, i){
            if(n['value'] !== undefined && n['value'].length > 0) formData[n['name']] = n['value'];
        });

        Swal.fire({
            title: 'Salvataggio in corso...',
            text: 'Attendere prego.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '/api/report/'+$form.data("medical-case-id"),
            type: 'PUT',
            data: JSON.stringify(formData),
            contentType: "application/json; charset=utf-8",
            dataType: 'json'
        })
        .done(function (response) {
            console.log(response)
            Swal.fire({
                icon: 'success',
                title: 'Successo!',
                text: 'La casistica modificata è stata salvata correttamente.',
            });
            window.location.reload()
            $('#modal-modifica-casistica').addClass('invisible opacity-0');
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            let errorMessage = 'Si è verificato un errore di rete o del server.';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message.errors;
            } else if (errorThrown) {
                errorMessage = `Errore: ${errorThrown}`;
            }

            Swal.fire({
                icon: 'error',
                title: 'Operazione fallita!',
                text: errorMessage
            });
        });

    });

    $(".editbtn").click(function(){
        const medical_case = $(this).data("medical-case-id")
        console.log(medical_case)

        $form.data("medical-case-id", medical_case)

        $.ajax({
            url: '/api/report/'+medical_case,
            type: 'GET',
            dataType: 'json'
        }).done(function (response) {
            const data = response.response

            let [hospitalization_date_day, hospitalization_date_month, hospitalization_date_year] = data.hospitalization_date.split("-");
            let [discharge_date_day, discharge_date_month, discharge_date_year] = data.discharge_date.split("-");

            $('#form-modifica-casistica #paziente').val(data.patient.name);
            $('#form-modifica-casistica #data_nascita').val(data.patient.birthday ? data.patient.birthday.split(' ')[0] : '');
            $('#form-modifica-casistica #city').val(data.patient.city);

            $('#form-modifica-casistica #stato').val(data.status);
            $('#form-modifica-casistica #data_registrazione').val(`${hospitalization_date_year}-${hospitalization_date_month}-${hospitalization_date_day}`);
            $('#form-modifica-casistica #diagnosi_passata').val(data.past_illness_history);
            $('#form-modifica-casistica #diagnosi_attuale').val(data.present_illness_history);
            $('#form-modifica-casistica #clinical_evolution').val(data.clinical_evolution);

            $('#form-modifica-casistica #data_dimissione').val(`${discharge_date_year}-${discharge_date_month}-${discharge_date_day}`);
            $('#form-modifica-casistica #dimission_text').val(data?.discharge_description);

            // $('#form-modifica-casistica #data_dimissione').trigger('change');
            $('#form-modifica-casistica #dimission_text_wrapper').removeClass('opacity-50 pointer-events-none select-none');

            document.body.style.overflow = 'hidden'; // Blocca lo scroll
            $modal.removeClass('invisible opacity-0');
            $modalPanel.removeClass('scale-95');
        }).fail(function (jqXHR, textStatus, errorThrown) {
            let errorMessage = 'Si è verificato un errore di rete o del server.';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message.errors;
            } else if (errorThrown) {
                errorMessage = `Errore: ${errorThrown}`;
            }

            closeModalAndReset()

            Swal.fire({
                icon: 'error',
                title: 'Operazione fallita!',
                text: errorMessage
            });
        });
    })

});
