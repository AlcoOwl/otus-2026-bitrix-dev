BX.ready(function () {
    document.addEventListener('click', function (event) {
        const procedureButton = event.target.closest('[data-role="otus-booking-procedure"]');
        if (!procedureButton) {
            return;
        }

        const fioInput = BX.create('input', {
            attrs: {
                type: 'text',
                placeholder: BX.message('OTUS_BOOKING_PATIENT_FIO'),
            },
            props: {
                className: 'ui-ctl-element',
            },
        });
        const dateInput = BX.create('input', {
            attrs: {
                type: 'date',
            },
            props: {
                className: 'ui-ctl-element',
            },
        });
        BX.bind(dateInput, 'click', function () {
            dateInput.showPicker();
        });

        const hourSelect = BX.create('select', {
            props: {
                className: 'ui-ctl-element',
            },
        });
        for (let hour = 11; hour <= 17; hour++) {
            hourSelect.append(BX.create('option', {
                text: `${hour}:00`,
                attrs: {
                    value: hour,
                },
            }));
        }

        const result = BX.create('div', {
            props: {
                className: 'otus-booking-popup__result',
            },
        });
        const saveButton = BX.create('button', {
            text: BX.message('OTUS_BOOKING_SAVE'),
            attrs: {
                type: 'button',
            },
            props: {
                className: 'ui-btn ui-btn-primary',
            },
        });

        BX.bind(saveButton, 'click', function () {
            BX.ajax({
                url: '/local/ajax/homework7/create_booking.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    sessid: BX.bitrix_sessid(),
                    doctor_id: procedureButton.dataset.doctorId,
                    procedure_id: procedureButton.dataset.procedureId,
                    patient_fio: fioInput.value,
                    appointment_at: dateInput.value
                        ? `${dateInput.value}T${hourSelect.value}:00`
                        : '',
                },
                onsuccess: function (response) {
                    result.textContent = response.message;
                },
                onfailure: function () {
                    result.textContent = BX.message('OTUS_BOOKING_AJAX_ERROR');
                },
            });
        });

        const content = BX.create('div', {
            props: {
                className: 'otus-booking-popup',
            },
            children: [
                BX.create('p', {
                    text: `${BX.message('OTUS_BOOKING_PROCEDURE')}: ${procedureButton.dataset.procedureName}`,
                    props: {
                        className: 'otus-booking-popup__procedure',
                    },
                }),
                BX.create('p', {
                    text: `${BX.message('OTUS_BOOKING_DURATION')}: ${procedureButton.dataset.procedureDuration} ${BX.message('OTUS_BOOKING_HOUR_SHORT')}`,
                    props: {
                        className: 'otus-booking-popup__duration',
                    },
                }),
                BX.create('div', {
                    props: {
                        className: 'otus-booking-popup__field',
                    },
                    children: [
                        BX.create('label', {
                            text: BX.message('OTUS_BOOKING_PATIENT_FIO'),
                            props: {
                                className: 'ui-ctl-label-text',
                            },
                        }),
                        BX.create('div', {
                            props: {
                                className: 'ui-ctl ui-ctl-textbox ui-ctl-w100',
                            },
                            children: [fioInput],
                        }),
                    ],
                }),
                BX.create('div', {
                    props: {
                        className: 'otus-booking-popup__field',
                    },
                    children: [
                        BX.create('label', {
                            text: BX.message('OTUS_BOOKING_APPOINTMENT_AT'),
                            props: {
                                className: 'ui-ctl-label-text',
                            },
                        }),
                        BX.create('div', {
                            props: {
                                className: 'otus-booking-popup__datetime',
                            },
                            children: [
                                BX.create('div', {
                                    props: {
                                        className: 'ui-ctl ui-ctl-textbox ui-ctl-w100',
                                    },
                                    children: [dateInput],
                                }),
                                BX.create('div', {
                                    props: {
                                        className: 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown otus-booking-popup__hour',
                                    },
                                    children: [
                                        hourSelect,
                                        BX.create('div', {
                                            props: {
                                                className: 'ui-ctl-after ui-ctl-icon-angle',
                                            },
                                        }),
                                    ],
                                }),
                            ],
                        }),
                    ],
                }),
                saveButton,
                result,
            ],
        });

        BX.PopupWindowManager.create(
            `otus-booking-${procedureButton.dataset.doctorId}-${procedureButton.dataset.procedureId}`,
            procedureButton,
            {
                content: content,
                minWidth: 400,
                closeIcon: true,
                closeByEsc: true,
                autoHide: true,
            }
        ).show();
    });
});
