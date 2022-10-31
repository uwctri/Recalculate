(() => {
    // Pop-up config
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 30000,
        timerProgressBar: false,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // Globals and common queries
    let isLongitudinal = true;
    let run = false;
    let totalBatches = -1;
    let clockInterval = -1;
    const statusMap = { "-1": glo.em.tt("status_error"), "0": glo.em.tt("status_scheduled"), "1": glo.em.tt("status_running"), "2": glo.em.tt("status_complete") };
    const $calcBtn = $("#recalc");
    const $eventsSelect = $("#events");
    const $fieldsSelect = $("#fields");
    const $recordsText = $("#records");
    const $allRecords = $("#allRecords");
    const $allEvents = $("#allEvents");
    const $allFields = $("#allFields");
    const $bSize = $("#batchSize");
    const $details = $(".detailsGrid");
    const $table = $("#previewTable");
    const $showEqCalcs = $("#equalCalcs");
    const $bRow = $("#batchSizeRow");
    const $form = $("#primaryForm");
    const $reBtn = $("#reopenBtn");
    const $errorStop = $("#onErrorStop");
    const $recalcBtn = $("#recalcBtnRow");
    const $cronTable = $("#cronTable");
    const $cronBtn = $("#schBtnRow");
    const $noCronBtn = $("#noschRow");
    const $cronTime = $("#cronTime");

    // Enable popovers, static button width, clear all prev values
    $('[data-toggle="popover"]').popover();
    $calcBtn.css('width', $calcBtn.css('width'));
    $("#center input").prop("checked", false).val("");
    $errorStop.click();
    $cronTime.datetimepicker({
        ampm: true,
        timeFormat: 'hh:mm tt'
    });

    // Setup "Generate Preview" Table
    $table.find('table').DataTable({
        data: [],
        pageLength: 40,
        dom: `<'row'<'col-sm-10'f><'col-sm-2 customToggle'>>
              <'row'<'col-sm-12'tr>>"
              <'row'<'col-sm-12 col-md-6 small'i><'col-sm-12 col-md-6'p>>`,
        language: {
            emptyTable: glo.em.tt('table_empty'),
            zeroRecords: glo.em.tt('table_empty')
        },
        columns: [
            {
                title: glo.em.tt('table_record'),
                data: 'record',
                render: (data, type, row, meta) => data.slice(1)
            },
            {
                title: glo.em.tt('table_event'),
                data: 'event',
                render: (data, type, row, meta) => glo.events[data] || `[${glo.em.tt('table_unk_event', { event: data })}]`,
                visible: !glo.isClassic
            },
            {
                title: glo.em.tt('table_field'),
                data: 'field'
            },
            {
                title: glo.em.tt('table_current'),
                data: 'current',
                render: (data, type, row, meta) => row['censor'] ? `[${glo.em.tt('table_no_rights')}]` : data
            },
            {
                title: glo.em.tt('table_calc'),
                data: 'calc'
            }
        ]
    });

    // More Table setup
    $showEqCalcs.parent().appendTo('.customToggle').on('change', () => {
        const $t = $showEqCalcs.parent();
        $t.popover('dispose');
        let msg = "";
        if ($showEqCalcs.is(":checked")) {
            msg = glo.em.tt('select_table_show');
            $.fn.dataTable.ext.search.push(
                (settings, data, dataIndex, row) => {
                    return !row['c'];
                }
            )
        } else {
            msg = glo.em.tt('select_table_hide');
            $.fn.dataTable.ext.search.pop();
        }
        $t.popover({
            trigger: 'hover',
            content: msg
        });
        $table.find('table').DataTable().draw();
    });
    $showEqCalcs.prop("checked", true).change();

    // Context menu Setup
    $.contextMenu({
        selector: '#previewTable tr',
        callback: function (key, options) {
            if (key != "run" || run) return;
            toggleLoading();
            clearLog();
            run = true;
            glo.recordBatches = [];
            totalBatches = 1;
            startLogClock();
            const data = $table.find('table').DataTable().row(options.$trigger).data();
            const event = JSON.stringify([data.event]);
            const field = JSON.stringify([data.field]);
            sendRequest([data.record.slice(1)], event, field, false, 0, 1, 0);
        },
        items: {
            "run": { name: glo.em.tt("right_run"), icon: "fas fa-play text-primary" },
        }
    });

    // Remove Cron Function
    const cleanup = (idList, finishFunc) => {
        $.ajax({
            ...makePostSettings('rmCron', { ids: idList }),

            // Only occurs on network or technical issue
            error: (jqXHR, textStatus, errorThrown) => {
                console.log(`${JSON.stringify(jqXHR)}\n${textStatus}\n${errorThrown}`)
                show500(true);
            },

            // Response returned from server
            success: (data) => {
                console.log(data);

                // 500 error
                if ((typeof data == "string" && data.length === 0) || data.errors.length) {
                    show500(true);
                    return;
                }

                finishFunc();
            }
        });
    }

    // Setup "Scheduled Cron" table
    $cronTable.find('table').DataTable({
        data: glo.crons,
        pageLength: 40,
        dom: `<'row'<'col-sm-8'f><'col-sm-4 cleanupCron'>>
              <'row'<'col-sm-12'tr>>"
              <'row'<'col-sm-12 col-md-6 small'i><'col-sm-12 col-md-6'p>>`,
        language: {
            emptyTable: glo.em.tt('cron_table_empty'),
            zeroRecords: glo.em.tt('cron_table_empty')
        },
        columns: [
            {
                title: glo.em.tt('cron_table_time'),
                data: 'time',
                render: (data, type, row, meta) => {
                    if (type != 'display') return data;
                    data = new Date(data);
                    return data.toLocaleString().replace(',', '').replace(':00 ', '').toLowerCase();
                },
            },
            {
                title: glo.em.tt('cron_table_records'),
                data: 'records',
                render: (data, type, row, meta) => data.join(', ').slice(0, 220)
            },
            {
                title: glo.em.tt('cron_table_events'),
                data: 'events',
                render: (data, type, row, meta) => data.join(', ').slice(0, 220)
            },
            {
                title: glo.em.tt('cron_table_fields'),
                data: 'fields',
                render: (data, type, row, meta) => data.join(', ').slice(0, 220)
            },
            {
                title: glo.em.tt('cron_table_status'),
                data: 'status',
                render: (data, type, row, meta) => statusMap[data]
            },
            {
                data: null,
                className: 'dt-center',
                orderable: false,
                render: (data, type, row, meta) => row['status'] == 0 ? '<i class="row-remove fa fa-trash text-secondary"/>' : ""
            },
            {
                data: 'id',
                orderable: false,
                visible: false
            }
        ],
        initComplete: () => {
            // Refresh data every min
            setInterval(() => {
                $.ajax({
                    ...makePostSettings('settings'),
                    success: (crons) => {
                        console.log("Refreshed scheduled cron data");
                        $cronTable.find('table').DataTable().clear();
                        $cronTable.find('table').DataTable().rows.add(crons).draw();
                    }
                })
            }, 60 * 1000)
        }
    });

    // Cleanup button on scheduled cron table
    $(".cleanupCron").html(`<a><i class='fas fa-broom'></i>${glo.em.tt('button_clean')}</a>`);
    $(".cleanupCron a").on('click', () => {
        let idList = [];
        let nodes = [];
        const table = $cronTable.find('table').DataTable();
        table.rows().every(function (rowIdx, tableLoop, rowLoop) {
            let data = this.data();
            if ([-1, 2].includes(data['status'])) {
                idList.push(data['id']);
                nodes.push(this.node());
            }
        });
        cleanup(idList, () => {
            nodes.forEach((node) => {
                table.row(node).remove().draw();
            });
            Toast.fire({
                icon: 'success',
                title: glo.em.tt('msg_cleanup')
            });
        })
    });

    // Setup Con Removal on the above table
    $("body").on('click', '.row-remove', (icon) => {
        $row = $cronTable.find('table').DataTable().row($(icon.currentTarget).parents('tr'));
        const id = $row.data()['id'];
        cleanup([id], () => {
            $row.remove().draw();
            Toast.fire({
                icon: 'success',
                title: glo.em.tt('msg_cron_rm')
            });
        })
    });

    // Ajax settings Util function
    const makePostSettings = (action, data = {}) => {
        return {
            method: 'POST',
            url: glo.router,
            data: {
                action: action,
                redcap_csrf_token: glo.csrf,
                projectid: pid,
                ...data
            }
        }

    }

    // Toggle loading ring
    const toggleLoading = () => {
        $calcBtn.parent().find(".dropdown-item[data-action=cancel]").toggleClass('hidden');
        $calcBtn.find('.btnText').toggle();
        $calcBtn.find('.ld').parent().toggle();
    };

    // Util func to batch an array
    const batchArray = (arr, size) => {
        if (size >= arr.length || size < 1) return [arr];
        const chunks = [];
        while (arr.length) {
            const chunk = arr.slice(0, size);
            chunks.push(chunk);
            arr = arr.slice(size);
        };
        return chunks;
    };

    // Log util functions
    const clearLog = () => $details.find("div").text("");
    const updateLog = (batch, records) => {
        const $divs = $details.find('div');
        if (totalBatches > 1) {
            $divs.first().text(glo.em.tt('log_batch', {
                batchNumber: batch,
                totalBatches: totalBatches
            }));
        }
        records = (records == glo.records) || (records[0] == "*") ? [glo.em.tt('log_all')] : records;
        $divs.last().text(glo.em.tt('log_records') + records.join(', ').slice(0, 70));
    };
    const startLogClock = () => {
        glo.time = new Date();
        const clock = () => {
            const secondsSpent = ((new Date()).getTime() - glo.time.getTime()) / 1000;
            const min = String(rounddown(secondsSpent / 60)).padStart(2, '0');
            const sec = String(round(secondsSpent % 60)).padStart(2, '0');
            $details.find('div').eq(totalBatches > 1 ? 1 : 0).text(glo.em.tt('log_time') + `${min}:${sec}`)
        };
        clock();
        clockInterval = setInterval(clock, 1000);
    };
    const stopLogClock = () => clearInterval(clockInterval);

    // Show a 500 error
    const show500 = (cronError = false) => {
        let errorText = cronError ? glo.em.tt('error_cron') : glo.em.tt('error_500_text')
        run = false;
        toggleLoading();
        stopLogClock();
        Swal.fire({
            icon: 'error',
            title: glo.em.tt('error_500'),
            text: errorText
        });
    }

    // Generate the datatable given preview data
    const updatePreviewTable = (data) => {
        const gen = traverse(data);
        $dt = $table.find('table').DataTable();
        let next;
        while (!(next = gen.next()).done) {
            let t = next.value;
            $dt.row.add({
                ...next.value,
                current: next.value.saved
            });
        }
        $dt.draw(false);
    };

    // Toggle all collapses to show the table
    const showTable = () => {
        $table.collapse('show');
        $form.collapse('hide');
        $reBtn.collapse('show');
    }

    // Traverse the standard redcap strucutre
    const traverse = function* (data) {
        for (let record in data) {
            for (let event in data[record]) {
                for (let form in data[record][event]) {
                    for (let instance in data[record][event][form]) {
                        for (let field in data[record][event][form][instance]) {
                            yield {
                                ...data[record][event][form][instance][field],
                                record: record,
                                event: event,
                                form: form,
                                instance: instance,
                                field: field
                            }
                        }
                    }
                }
            }
        }
    };

    // Validate the forms data and highlight issues
    const validate = () => {
        // Grab all used values
        const batchSize = $bSize.change().val() || 0;
        const allFields = $allFields.is(':checked');
        const allEvents = $allEvents.is(':checked');

        const fields = allFields ? ['*'] : $fieldsSelect.val();
        const events = (allEvents || !isLongitudinal) ? ['*'] : $eventsSelect.val();
        const records = $recordsText.val().replaceAll(' ', '').split(',').filter(e => e);

        // Color missing fields (validation)
        $fieldsSelect.addClass(fields.length ? '' : 'is-invalid');
        $eventsSelect.addClass(events.length ? '' : 'is-invalid');
        $recordsText.addClass(records.length ? '' : 'is-invalid');

        // Exit if missing anything (validation)
        if (!fields.length || !events.length || !records.length) {
            return false;
        }

        return {
            records, fields, events, batchSize
        }
    }

    // Load any previous table
    let storage = JSON.parse(localStorage.getItem("RedcapEMcalcPreview") || '{}');
    if (storage.data) {
        $calcBtn.parent().find(".dropdown-item[data-action=old]").removeClass('hidden');
        updatePreviewTable(storage.data);
    }

    // Build out event options
    const eventBox = document.getElementById('events');
    $.each(glo.events, (id, name) => {
        let newOption = new Option(name, id);
        eventBox.add(newOption);
    });

    // Hide the events if we only have 1
    if (Object.keys(glo.events).length < 2) {
        isLongitudinal = false;
        $eventsSelect.closest('.row').hide();
        $eventsSelect.val($eventsSelect.find("option").val());
    }

    // Build out field options
    const fieldBox = document.getElementById('fields');
    $.each(glo.fields, (id, name) => {
        let newOption = new Option(`${id} : ${name}`, id);
        fieldBox.add(newOption);
    });

    // Button trigger
    $(".container [data-action]").on('click', (event) => {

        // Check if we are already running, need cancel, or just want to view old table / open modal
        const $target = $(event.currentTarget);
        const action = $target.data("action");

        // Cancel Current Action
        if (action == "cancel") {
            run = false;
            return;
        }

        // Show the old generated preview table
        if (action == "old") {
            if (storage.data) showTable();
            return;
        }

        // Show the Schedule Cron area
        if (action == "cron") {
            $table.collapse('hide');
            $form.collapse('hide');
            $reBtn.collapse('show');
            $recalcBtn.collapse('hide');
            $cronTable.collapse('show');
            const settings = validate();
            if (!settings) {
                $noCronBtn.collapse('show');
            } else {
                $cronBtn.collapse('show');
            }
            return;
        }

        // Write the cron back to settings
        if (action == "makeCron") {
            const settings = validate();
            $cronTime.removeClass("is-invalid");
            if (!settings) return;
            let time = new Date($cronTime.val());
            time = time.toJSON();
            if (time == null) {
                $cronTime.addClass("is-invalid");
                return;
            }
            $target.prop("disabled", true);
            setTimeout(() => $target.prop("disabled", false), 2000);

            $.ajax({
                ...makePostSettings('cron', { ...settings, time }),

                // Only occurs on network or technical issue
                error: (jqXHR, textStatus, errorThrown) => {
                    console.log(`${JSON.stringify(jqXHR)}\n${textStatus}\n${errorThrown}`)
                    show500(true);
                },

                // Response returned from server
                success: (data) => {
                    console.log(data);

                    // 500 error
                    if ((typeof data == "string" && data.length === 0) || data.errors.length) {
                        show500(true);
                        return;
                    }

                    $cronTable.find('table').DataTable().row.add({
                        ...settings,
                        time,
                        status: 0,
                        id: data.id
                    }).draw();
                    Toast.fire({
                        icon: 'success',
                        title: glo.em.tt('msg_cron')
                    });
                }
            });

            return
        }
        if (run) return;
        // Preview and Real Run below

        // Validation
        const settings = validate();
        if (!settings) return;
        let { records, fields, events, batchSize } = settings;

        // Send request
        toggleLoading();
        clearLog();
        run = true;
        records = records[0] == '*' && batchSize > 0 ? glo.records : records;
        glo.recordBatches = batchArray(records, batchSize).reverse();
        totalBatches = glo.recordBatches.length;
        $table.collapse('hide');
        $table.find('table').DataTable().clear();
        startLogClock();
        sendRequest(glo.recordBatches.pop(), JSON.stringify(events), JSON.stringify(fields), action == "preview", batchSize, 1, 0);
    });

    // All Records toggle
    $allRecords.on('click', () => {
        const all = $allRecords.is(':checked');
        $recordsText.val(all ? '*' : '').attr('disabled', all).removeClass('is-invalid');
    });

    // All Events & Fields toggle
    $allEvents.on('click', () => $eventsSelect.val([]).attr('disabled', $allEvents.is(':checked')).removeClass('is-invalid'));
    $allFields.on('click', () => $fieldsSelect.val([]).attr('disabled', $allFields.is(':checked')).removeClass('is-invalid'));

    // Remove validation decoration on change
    $recordsText.on('change', () => $recordsText.removeClass('is-invalid'));
    $eventsSelect.on('change', () => $eventsSelect.removeClass('is-invalid'));
    $fieldsSelect.on('change', () => $fieldsSelect.removeClass('is-invalid'));
    $bSize.on('change', () => isInteger($bSize.val(), true) ? $bSize.removeClass('is-invalid') : $bSize.val(""));
    $cronTime.on('change', () => $cronTime.removeClass('is-invalid'));

    // Advanced Button styling
    $bRow.on('show.bs.collapse', () => { $('#advRow i').addClass('rotate') });
    $bRow.on('hide.bs.collapse', () => { $('#advRow i').removeClass('rotate') });

    // Reopen Styling
    $form.on('show.bs.collapse', () => {
        $reBtn.collapse('hide');
        $recalcBtn.collapse('show');
        $cronTable.collapse('hide');
        $noCronBtn.collapse('hide');
        $cronBtn.collapse('hide')
    });

    // Ajax for the post
    const sendRequest = (records, events, fields, preview, batchSize, batchNumber, totalChanges) => {

        // Bail if cancel was called
        if (!run) {
            toggleLoading();
            stopLogClock();
            Toast.fire({
                icon: 'info',
                title: glo.em.tt('msg_cancel')
            });
            return;
        }

        // Update the Detials area
        updateLog(batchNumber, records);
        $.ajax({
            ...makePostSettings(preview ? 'preview' : 'calculate', {
                records: JSON.stringify(records),
                events: events,
                fields: fields,
            }),

            // Only occurs on network or technical issue
            error: (jqXHR, textStatus, errorThrown) => {
                console.log(`${JSON.stringify(jqXHR)}\n${textStatus}\n${errorThrown}`)
                show500();
            },

            // Response returned from server
            success: (data) => {
                console.log(data);

                // Empty string, 500 error
                if (typeof data == "string" && data.length === 0) {
                    show500();
                    return;
                }

                // Server returned a validation error
                if (data.errors.length && $errorStop.is(":checked")) {
                    toggleLoading();
                    stopLogClock();
                    run = false;
                    data.errors.forEach((err) => {
                        Toast.fire({
                            icon: 'error',
                            title: err.display ? err.text : glo.em.tt('error_unknown')
                        });
                    });
                    return;
                }

                // For any valid response, log and update
                totalChanges += data.changes;
                batchNumber += 1;

                // Update preview table
                if (preview && Object.entries(data.preview).length) {
                    updatePreviewTable(data.preview);
                    showTable();
                }

                // Multi batch with more to send
                if (batchSize > 0 && glo.recordBatches.length) {
                    sendRequest(glo.recordBatches.pop(), events, fields, preview, batchSize, batchNumber, totalChanges);
                    return;
                }

                // Single post or done with posts, show success toast
                toggleLoading();
                stopLogClock();
                run = false;
                if (preview && $table.is(":visible")) {
                    localStorage.setItem("RedcapEMcalcPreview", JSON.stringify({
                        date: (new Date()).getTime(),
                        data: data.preview
                    }));
                    return;
                }
                localStorage.removeItem('RedcapEMcalcPreview');
                let msg = preview ? glo.em.tt('msg_nopreview') : glo.em.tt('msg_success', {
                    count: totalChanges
                });
                Toast.fire({
                    icon: 'success',
                    title: msg
                });
            }
        });
    }
})();