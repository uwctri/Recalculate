<link rel='stylesheet' href='<?= $module->getURL('loading.min.css'); ?>'>
<div class="projhdr"><i class="fas fa-calculator"></i> <?= $module->tt('module_name'); ?></div>

<div class="container float-left" style="max-width:800px">
    <div class="row p-2">
        <label for="records" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_record'); ?></label>
        <div class="col-10">
            <input id="records" name="records" placeholder="record_1, record_2 etc" type="text" class="form-control">
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
    // Pop-up config
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })

    // Static refs and config
    const $calcBtn = $("#recalc");
    const label_length = 46;

    // Toggle loading ring
    function toggleBtn() {
        $calcBtn.find('.btnText').toggle();
        $calcBtn.find('.ld').toggle();
    }

    // Trash the placeholders, hide the loader
    $("option").remove();
    $calcBtn.css('width', $calcBtn.css('width'))

    // Build out event options
    const eventBox = document.getElementById('events');
    $.each(Recalc.events, (id, name) => {
        let newOption = new Option(name, id);
        eventBox.add(newOption);
    });

    // Hide the events if we only have 1
    if (Object.keys(Recalc.events).length < 2) {
        $("#events").closest('.row').hide();
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

        let fields = $("#fields").val() || [];
        let events = $("#events").val() || [];
        let records = $("#records").val() || [];

        // TODO check that selections are valid

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
                // TODO show toast of updates
            }
        });
    });
</script>