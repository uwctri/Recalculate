<link rel="stylesheet" href="<?= $module->getURL('lib/loading.min.css'); ?>">
<script src="<?= $module->getURL('lib/cookie.min.js'); ?>"></script>
<div class="projhdr"><i class="fas fa-calculator"></i> <?= $module->tt('module_name'); ?></div>
<style>
    .dropdown-item:hover,
    .dropdown-item:active {
        outline: none;
        background-color: #e9ecef;
    }

    #recalcBtnGroup .dropdown-item {
        color: #212529;
        font-size: 0.95rem;
    }

    .detailsGrid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(2, 1fr);
        grid-column-gap: 20px;
        grid-row-gap: 0px;
        color: #6d6d6d;
        font-weight: bold;
    }

    #records:disabled {
        color: #6c757d;
    }

    .custom-select {
        resize: vertical;
        scrollbar-width: thin;
    }

    .dataTables_filter {
        float: left !important;
        font-weight: bold;
    }

    .dataTables_filter input {
        margin-left: 10px;
    }

    #previewTable tr.odd {
        background-color: #eeeeee !important;
    }
</style>
<div class="container float-left" style="max-width:800px">

    <!-- Top Text -->
    <div class="row p-2">
        <div class="col-12">
            <p>
                <?= $module->tt('label_form'); ?>
            </p>
        </div>
    </div>

    <!-- Records -->
    <div class="row p-2">
        <label for="records" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_record'); ?></label>
        <div class="col-10">
            <input id="records" name="records" placeholder="record_1, record_2 etc" type="text" class="form-control">
            <div class="custom-control custom-switch float-right" data-trigger="hover" data-toggle="popover" data-content="Select all records for recalculation">
                <input type="checkbox" class="custom-control-input" id="allRecords" data-state="0">
                <label class="custom-control-label" for="allRecords"></label>
            </div>
        </div>
    </div>

    <!-- Events -->
    <div class="row p-2">
        <label for="events" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_event'); ?></label>
        <div class="col-10">
            <select multiple="multiple" id="events" name="events" class="custom-select">
            </select>
            <div class="custom-control custom-switch float-right" data-trigger="hover" data-toggle="popover" data-content="Select all events for recalculation">
                <input type="checkbox" class="custom-control-input" id="allEvents" data-state="0">
                <label class="custom-control-label" for="allEvents"></label>
            </div>
        </div>
    </div>

    <!-- Fields -->
    <div class="row p-2">
        <label for="fields" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_field'); ?></label>
        <div class="col-10">
            <select id="fields" name="fields" class="custom-select" multiple="multiple">
            </select>
            <div class="custom-control custom-switch float-right" data-trigger="hover" data-toggle="popover" data-content="Select all fields for recalculation">
                <input type="checkbox" class="custom-control-input" id="allFields" data-state="0">
                <label class="custom-control-label" for="allFields"></label>
            </div>
        </div>
    </div>

    <!-- Advanced Button -->
    <div class="row p-2">
        <div class="offset-2  col-10">
            <a class="color-primary font-weight-bold" role="button" data-toggle="collapse" data-target="#batchSizeRow"><?= $module->tt('button_advanced'); ?> <i class="fa fa-arrow-down"></i></a>
        </div>
    </div>

    <!-- Batch Size -->
    <div id="batchSizeRow" class="row p-2 collapse">
        <label for="batchSize" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_batch'); ?></label>
        <div class="col-10">
            <input id="batchSize" name="batchSize" placeholder="all" type="text" aria-describedby="batchSizeHelpBlock" class="form-control">
            <span id="batchSizeHelpBlock" class="form-text text-muted"><?= $module->tt('label_batch_help'); ?></span>
        </div>
    </div>

    <!-- Go Button & Details -->
    <div class="row p-2">
        <div class="offset-2 col-6 detailsGrid">
            <div>Batch N of M</div>
            <div>Time Elapsed: 00:00</div>
            <div style="grid-area:2/1/3/3;white-space:nowrap;">Processing: record_1, record_2, record_3, rec...</div>
        </div>
        <div class="col-4">
            <div id="recalcBtnGroup" class="btn-group float-right">
                <button id="recalc" type="button" class="btn btn-primary pr-0">
                    <span class="btnText"> <?= $module->tt('button_submit'); ?> </span>
                    <span class="hidden"><i class="ld ld-spin ld-ring"></i></span>
                </button>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" data-action="preview" href="#"><?= $module->tt('button_preview'); ?></a>
                    <a class="dropdown-item" data-action="cancel" href="#"><?= $module->tt('button_cancel'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Preview -->
    <div id="previewTable" class="row p-2 collapse">
        <div class="col-12">
            <table style="width:100%" class="table"></table>
        </div>
    </div>
</div>

<script>
    (() => {
        // Pop-up config
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Static refs and config
        let glo = <?= json_encode($module->loadSettings()); ?>;
        glo.isLongitudinal = true;
        glo.run = false;
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

        // Setup Table
        $table.find('table').DataTable({
            data: [],
            pageLength: 50,
            dom: "<'row'<'col'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columns: [{
                    title: 'Record',
                    data: 'record'
                },
                {
                    title: 'Event',
                    data: 'event',
                    render: (data, type, row, meta) => glo.events[data]
                },
                {
                    title: 'Field',
                    data: 'field'
                },
                {
                    title: 'Current Value',
                    data: 'current'
                },
                {
                    title: 'Calculated Value',
                    data: 'calc'
                },
                {
                    title: '',
                    data: 'action',
                    sortable: false
                }
            ]
        });

        // Toggle loading ring
        const toggleBtn = () => {
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

        // Interpolate Util functions for templates
        String.prototype.interpolate = function(params) {
            let newString = this;
            Object.entries(params).forEach((replacePair) => {
                newString = newString.replaceAll(`{${replacePair[0]}}`, replacePair[1]);
            });
            return newString;
        };

        // Log util functions
        const clearLog = () => $details.find("div").text("");
        const updateLog = (batch, records) => {
            const $divs = $details.find('div');
            if (glo.totalBatches > 1) {
                $divs.first().text("<?= $module->tt('log_batch'); ?>".interpolate({
                    batchNumber: batch,
                    totalBatches: glo.totalBatches
                }));
            }
            records = (records == glo.records) || (records[0] == "*") ? ["<?= $module->tt('log_all'); ?>"] : records;
            $divs.last().text("<?= $module->tt('log_records'); ?>" + records.join(', ').slice(0, 70));
        };
        const startLogClock = () => {
            glo.time = new Date();
            const clock = () => {
                const secondsSpent = ((new Date()).getTime() - glo.time.getTime()) / 1000;
                const min = String(rounddown(secondsSpent / 60)).padStart(2, '0');
                const sec = String(round(secondsSpent % 60)).padStart(2, '0');
                $details.find('div').eq(glo.totalBatches > 1 ? 1 : 0).text("<?= $module->tt('log_time'); ?>" + `${min}:${sec}`)
            };
            clock();
            glo.interval = setInterval(clock, 1000);
        };
        const stopLogClock = () => clearInterval(glo.interval);

        // Enable popovers, static button width, clear all prev values
        $('[data-toggle="popover"]').popover();
        $calcBtn.css('width', $calcBtn.css('width'));
        $("#center input").prop("checked", false).val("");
        clearLog();

        // Build out event options
        const eventBox = document.getElementById('events');
        $.each(glo.events, (id, name) => {
            let newOption = new Option(name, id);
            eventBox.add(newOption);
        });

        // Hide the events if we only have 1
        if (Object.keys(glo.events).length < 2) {
            glo.isLongitudinal = false;
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
        $("#recalc, #recalcBtnGroup .dropdown-item").on('click', (event) => {

            // Check if we are already running or need cancel
            const action = $(event.currentTarget).data("action");
            if (action == "cancel") {
                glo.run = false;
                return;
            }
            if (glo.run) return;

            // Grab all used values
            const batchSize = $bSize.change().val() || 0;
            const allFields = $allFields.is(':checked');
            const allEvents = $allEvents.is(':checked');

            const fields = allFields ? ['*'] : $fieldsSelect.val();
            const events = (allEvents || !glo.isLongitudinal) ? ['*'] : $eventsSelect.val();
            let records = $recordsText.val().replaceAll(' ', '').split(',').filter(e => e);
            records = records[0] == '*' && batchSize > 0 ? glo.records : records;

            // Color missing fields (validation)
            $fieldsSelect.addClass(fields.length ? '' : 'is-invalid');
            $eventsSelect.addClass(events.length ? '' : 'is-invalid');
            $recordsText.addClass(records.length ? '' : 'is-invalid');

            // Exit if missing anything (validation)
            if (!fields.length || !events.length || !records.length) {
                return;
            }

            // Send request
            toggleBtn();
            clearLog();
            glo.run = true;
            glo.recordBatches = batchArray(records, batchSize).reverse();
            glo.totalBatches = glo.recordBatches.length;
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

        // Ajax for the post
        const sendRequest = (records, events, fields, preview, batchSize, batchNumber, totalChanges) => {

            // Bail if cancel was called
            if (!glo.run) {
                toggleBtn();
                stopLogClock();
                Toast.fire({
                    icon: 'info',
                    title: "<?= $module->tt('msg_cancel'); ?>"
                });
                return;
            }

            // Update the Detials area
            updateLog(batchNumber, records);

            $.ajax({
                method: 'POST',
                url: glo.router,
                data: {
                    route: preview ? 'preview' : 'recalculate',
                    records: JSON.stringify(records),
                    events: events,
                    fields: fields,
                    redcap_csrf_token: glo.csrf
                },

                // Only occurs on network or technical issue
                error: (jqXHR, textStatus, errorThrown) => {
                    toggleBtn();
                    console.log(`${jqXHR}\n${textStatus}\n${errorThrown}`)
                    Toast.fire({
                        icon: 'error',
                        title: "<?= $module->tt('error_network'); ?>"
                    });
                },

                // Response returned from server
                success: (data) => {

                    // Check if a 500 error occured, possible memory issue
                    let fatal = false;
                    try {
                        data = JSON.parse(data);
                    } catch {
                        toggleBtn();
                        glo.run = false;
                        fatal = true;
                        Swal.fire({
                            icon: "error",
                            title: "<?= $module->tt('error_500'); ?>",
                            text: "<?= $module->tt('error_500_text'); ?>"
                        })
                    }
                    console.log(data);
                    if (fatal) return;

                    // Server returned a validation error
                    if (data.errors.length) {
                        toggleBtn();
                        glo.run = false;
                        data.errors.forEach((err) => {
                            Toast.fire({
                                icon: 'error',
                                title: err.display ? err.text : "<?= $module->tt('error_unknown'); ?>"
                            });
                        });
                        return;
                    }

                    // For any valid response, log and update
                    totalChanges += data.changes;
                    batchNumber += 1;

                    // Update preview table
                    if (preview && Object.entries(data.preview).length) {
                        $dt = $table.find('table').DataTable();
                        // $dt.row.add([
                        // TODO Show data
                        // ]);
                        $table.collapse('show');
                    }

                    // Multi batch with more to send
                    if (batchSize > 0 && glo.recordBatches.length) {
                        sendRequest(glo.recordBatches.pop(), events, fields, preview, batchSize, batchNumber, totalChanges);
                        return;
                    }

                    // Single post or done with posts, she success toast
                    toggleBtn();
                    stopLogClock();
                    glo.run = false;
                    if (preview && $table.is(":visible")) return;
                    let msg = preview ? "<?= $module->tt('msg_nopreview'); ?>" : "<?= $module->tt('msg_success'); ?>".interpolate({
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
</script>