<div class="projhdr"><i class="fas fa-calculator"></i> <?= $module->tt('module_name'); ?></div>

<div class="container float-left" style="max-width:800px">
    <div class="row p-2">
        <label for="text" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_record'); ?></label>
        <div class="col-10">
            <input id="text" name="text" placeholder="record_1, record_2 etc" type="text" class="form-control">
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
            <button name="submit" type="submit" class="btn btn-primary float-right"><?= $module->tt('button'); ?></button>
        </div>
    </div>
</div>

<script>
    // TODO we need to have "all records/fields/events" options

    // Trash the placeholders
    $("option").remove();

    // Max label to display in select
    const label_length = 46;

    // Build out event options
    const eventBox = document.getElementById('events');
    $.each(ReCalc.events, (id, name) => {
        let newOption = new Option(name, id);
        eventBox.add(newOption);
    });

    // Hide the events if we only have 1
    if (Object.keys(ReCalc.events).length < 2) {
        $("#events").closest('.row').hide();
    }

    // Build out field options
    const fieldBox = document.getElementById('fields');
    $.each(ReCalc.fields, (id, name) => {
        name = name.slice(0, label_length) + " : " + id;
        let newOption = new Option(name, id);
        fieldBox.add(newOption);
    });
</script>