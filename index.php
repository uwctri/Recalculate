<link rel='stylesheet' href='<?= $module->getURL('loading.min.css'); ?>'>
<link rel='stylesheet' href='<?= $module->getURL('cookie.min.css'); ?>'>
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
                    <a class="dropdown-item" href="#"><?= $module->tt('button_preview'); ?></a>
                    <a class="dropdown-item" href="#">Another action</a>
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
        const $calcBtn = $("#recalc");
        const $calcGrp = $("#recalcBtnGroup")
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

        // Timeout button to prevent double click
        function timeoutBtn() {
            $calcBtn.prop('disabled', true);
            setTimeout(() => $calcBtn.prop('disabled', false), 2500);
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
        $calcBtn.on('click', () => {

            glo.batchSize = $bSize.change().val();
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
            glo.totalChanges = 0;
            glo.eventCache = JSON.stringify(events);
            glo.fieldCache = JSON.stringify(fields);
            glo.batchNumber = 1;
            glo.time = new Date();
            glo.recordBatches = batchArray(records, glo.batchSize).reverse();
            glo.totalBatches = glo.recordBatches.length;
            sendCalcRequest(glo.recordBatches.pop(), glo.eventCache, glo.fieldCache);
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
        function sendCalcRequest(records, events, fields) {
            $.ajax({
                method: 'POST',
                url: glo.router,
                data: {
                    route: 'recalculate',
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

                    data = JSON.parse(data);
                    console.log(data);

                    // Server returned a validation error
                    if (data.errors.length) {
                        data.errors.forEach((err) => {
                            Toast.fire({
                                icon: 'error',
                                title: err.display ? err.text : "<?= $module->tt('error_unknown'); ?>"
                            });
                        });
                        return;
                    }

                    // For any valid response, log and update
                    glo.totalChanges += data.changes;
                    $log.val(`${$log.val()}Batch ${glo.batchNumber} of ${glo.totalBatches}\nRecords ${data.records.join(', ')}\n`);
                    glo.batchNumber += 1;

                    // Multi batch with more to send
                    if (glo.batchSize > 0 && glo.recordBatches.length) {
                        $logRow.collapse("show");
                        sendCalcRequest(glo.recordBatches.pop(), glo.eventCache, glo.fieldCache);
                        return;
                    }

                    // Single post or done with posts
                    toggleBtn();
                    timeoutBtn();
                    Toast.fire({
                        icon: 'success',
                        title: "<?= $module->tt('msg_success'); ?>".replace('_', glo.totalChanges)
                    });
                    const secondsSpent = ((new Date()).getTime() - glo.time.getTime()) / 1000;
                    $log.val(`${$log.val()}${glo.totalChanges} total changes in ${rounddown(secondsSpent/60)} minutes ${round(secondsSpent%60)} seconds`);
                }
            });
        }
    })();
</script>