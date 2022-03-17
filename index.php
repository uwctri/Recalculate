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
</style>
<div class="container float-left" style="max-width:800px">
    <div class="row p-2">
        <div class="col-12">
            <p>
                <?= $module->tt('label_form'); ?>
            </p>
        </div>
    </div>
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
    <div class="row p-2">
        <div class="offset-2  col-10">
            <a class="color-primary font-weight-bold" style="cursor:pointer" data-toggle="collapse" data-target="#batchSizeRow"><?= $module->tt('button_advanced'); ?> <i class="fa fa-arrow-down"></i></a>
        </div>
    </div>
    <div id="batchSizeRow" class="row p-2 collapse">
        <label for="batchSize" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_batch'); ?></label>
        <div class="col-10">
            <input id="batchSize" name="batchSize" placeholder="all" type="text" aria-describedby="batchSizeHelpBlock" class="form-control">
            <span id="batchSizeHelpBlock" class="form-text text-muted"><?= $module->tt('label_batch_help'); ?></span>
        </div>
    </div>
    <div class="row p-2">
        <div class="offset-2 col-10">
            <div id="recalcBtnGroup" class="btn-group float-right">
                <button id="recalc" type="button" class="btn btn-primary pr-0">
                    <span class="btnText"> <?= $module->tt('button_submit'); ?> </span>
                    <i class="ld ld-spin ld-ring" style="display:none"></i>
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
    <div id="logRow" class="row p-2 collapse">
        <div class="offset-2 col-10">
            <span>
                <textarea id="recalcLog" name="recalcLog" cols="40" rows="20" class="form-control" disabled="" style="font-size:12px;font-family:monospace;scrollbar-width:thin;"></textarea>
                <i data-toggle="collapse" data-target="#logRow" class="fa fa-times" aria-hidden="true" style="cursor:pointer;position:absolute;top:0.65rem;right:1.75rem"></i>
            </span>
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
        const $logRow = $("#logRow");
        const $log = $("#recalcLog");

        // Toggle loading ring
        function toggleBtn() {
            $calcBtn.find('.btnText').toggle();
            $calcBtn.find('.ld').toggle();
        }

        // Util func to batch an array
        function batchArray(arr, size) {
            if (size >= arr.length || size < 1) return [arr];
            const chunks = [];
            while (arr.length) {
                const chunk = arr.slice(0, size);
                chunks.push(chunk);
                arr = arr.slice(size);
            };
            return chunks;
        }

        // Interpolate Util functions for templates
        String.prototype.interpolate = function(params) {
            let newString = this;
            Object.entries(params).forEach(function(replacePair) {
                newString = newString.replaceAll(`{${replacePair[0]}}`, replacePair[1]);
            });
            return newString;
        }

        // Enable popovers, static button width, clear all prev values
        $('[data-toggle="popover"]').popover();
        $calcBtn.css('width', $calcBtn.css('width'));
        $("#center input").prop("checked", false).val("");

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
            const batchSize = $bSize.change().val();
            const allFields = $allFields.is(':checked');
            const allEvents = $allEvents.is(':checked');

            const fields = allFields ? ['*'] : $fieldsSelect.val();
            const events = (allEvents || !glo.isLongitudinal) ? ['*'] : $eventsSelect.val();
            let records = $recordsText.val().replaceAll(' ', '').split(',').filter(e => e);
            records = records[0] == '*' ? glo.records : records;

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
            $log.val("");
            glo.run = true;
            glo.time = new Date();
            glo.recordBatches = batchArray(records, batchSize).reverse();
            glo.totalBatches = glo.recordBatches.length;
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
        function sendRequest(records, events, fields, preview, batchSize, batchNumber, totalChanges) {

            // Bail if cancel was called
            if (!glo.run) {
                toggleBtn();
                Toast.fire({
                    icon: 'info',
                    title: "<?= $module->tt('msg_cancel'); ?>"
                });
                const secondsSpent = ((new Date()).getTime() - glo.time.getTime()) / 1000;
                $log.val("<?= $module->tt('log_end'); ?>".interpolate({
                    current: $log.val(),
                    total: totalChanges,
                    minute: rounddown(secondsSpent / 60),
                    seconds: round(secondsSpent % 60)
                }));
                return;
            }

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
                    $log.val("<?= $module->tt('log_line'); ?>".interpolate({
                        current: $log.val(),
                        batchNumber: batchNumber,
                        totalBatches: glo.totalBatches,
                        records: data.records.join(', ')
                    }));
                    batchNumber += 1;

                    // Multi batch with more to send
                    if (batchSize > 0 && glo.recordBatches.length) {
                        $logRow.collapse("show");
                        sendRequest(glo.recordBatches.pop(), events, fields, preview, batchSize, batchNumber, totalChanges);
                        return;
                    }

                    // Single post or done with posts
                    toggleBtn();
                    Toast.fire({
                        icon: 'success',
                        title: "<?= $module->tt('msg_success'); ?>".interpolate({
                            count: totalChanges
                        })
                    });
                    const secondsSpent = ((new Date()).getTime() - glo.time.getTime()) / 1000;
                    $log.val("<?= $module->tt('log_end'); ?>".interpolate({
                        current: $log.val(),
                        total: totalChanges,
                        minute: rounddown(secondsSpent / 60),
                        seconds: round(secondsSpent % 60)
                    }));
                    glo.run = false;
                }
            });
        }
    })();
</script>