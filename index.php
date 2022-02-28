<link rel='stylesheet' href='<?= $module->getURL('loading.min.css'); ?>'>
<div class="projhdr"><i class="fas fa-calculator"></i> <?= $module->tt('module_name'); ?></div>

<div id="recalcEm" class="container float-left" style="max-width:800px">
    <div class="row p-2">
        <div class="col-12">
            <p>
                The below form can be used to recalculate some subset of REDCap "calc" fields.
                Select one or many records, events, and feilds to perform a reclculation on.
                You may also use the toggle to select all of applicable fields, events, or records.
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
                <option value="1">Placeholder A</option>
                <option value="2">Placeholder B</option>
                <option value="3">Placeholder C</option>
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
                <option value="1">Placeholder A</option>
                <option value="2">Placeholder B</option>
                <option value="3">Placeholder C</option>
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
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        })

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

        // Trash the placeholders
        $("#recalcEm option").remove();

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

            const fields = $fieldsSelect.val() || (allFields ? ['*'] : []);
            const events = $eventsSelect.val() || (allEvents || !isLongitudinal ? ['*'] : []);
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
                error: (jqXHR, textStatus, errorThrown) => {
                    console.log(`${jqXHR}\n${textStatus}\n${errorThrown}`)
                    toggleBtn();
                },
                success: (data) => {
                    toggleBtn();
                    console.log(data);
                    // TODO show toast of updates (or something else nice)
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