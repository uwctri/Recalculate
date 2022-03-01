<link rel='stylesheet' href='<?= $module->getURL('loading.min.css'); ?>'>
<div class="projhdr"><i class="fas fa-calculator"></i> <?= $module->tt('module_name'); ?></div>

<div id="recalcEm" class="container float-left" style="max-width:800px">
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
            <div class="custom-control custom-switch float-right">
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
            <div class="custom-control custom-switch float-right">
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
            <div class="custom-control custom-switch float-right">
                <input type="checkbox" class="custom-control-input" id="allFields" data-state="0">
                <label class="custom-control-label" for="allFields"></label>
            </div>
        </div>
    </div>
    <div class="row p-2">
        <div class="offset-2 col-10">
            <button id="recalc" name="submit" type="submit" class="btn btn-primary float-right">
                <span class="btnText"> <?= $module->tt('button'); ?> </span>
                <i class="ld ld-spin ld-ring" style="display:none"></i>
            </button>
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
        let isLongitudinal = true;
        const label_length = 46;
        const $calcBtn = $("#recalc");
        const $eventsSelect = $("#events");
        const $fieldsSelect = $("#fields");
        const $recordsText = $("#records");
        const $allRecords = $("#allRecords");
        const $allEvents = $("#allEvents");
        const $allFields = $("#allFields");

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

        // Force the submit button to static width
        $calcBtn.css('width', $calcBtn.css('width'));

        // Force all checkboxes to default to off (browsers will remember last state)
        $("input").prop("checked", false);

        // Build out event options
        const eventBox = document.getElementById('events');
        $.each(Recalc.events, (id, name) => {
            let newOption = new Option(name, id);
            eventBox.add(newOption);
        });

        // Hide the events if we only have 1
        if (Object.keys(Recalc.events).length < 2) {
            isLongitudinal = false;
            $eventsSelect.closest('.row').hide();
            $eventsSelect.val($eventsSelect.find("option").val());
        }

        // Build out field options
        const fieldBox = document.getElementById('fields');
        $.each(Recalc.fields, (id, name) => {
            name = name.slice(0, label_length) + " : " + id;
            let newOption = new Option(name, id);
            fieldBox.add(newOption);
        });

        // Button trigger
        $calcBtn.on('click', () => {

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
                return;
            }

            // Show loading icon
            toggleBtn();

            $.ajax({
                method: 'POST',
                url: Recalc.router,
                data: {
                    route: 'recalculate',
                    records: records.join(),
                    events: events.join(),
                    fields: fields.join(),
                    redcap_csrf_token: Recalc.csrf
                },

                // Only occurs on network or technical issue
                error: (jqXHR, textStatus, errorThrown) => {
                    toggleBtn();
                    console.log(`${jqXHR}\n${textStatus}\n${errorThrown}`)
                    Toast.fire({
                        icon: 'error',
                        title: 'Unable to perform recalcuation'
                    });
                },

                // Response returned from server
                success: (data) => {

                    toggleBtn();
                    timeoutBtn();
                    data = JSON.parse(data);
                    console.log(data);

                    // Server returned a validation error
                    if (data.errors.length) {
                        data.errors.forEach((err) => {
                            Toast.fire({
                                icon: 'error',
                                title: err.display ? err.text : "Unknown error occured!"
                            });
                        });
                    }

                    // No errors, everything went well
                    else {
                        Toast.fire({
                            icon: 'success',
                            title: `Recalculatd ${data.changes} data points!`
                        });
                    }
                }
            });
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
    })();
</script>