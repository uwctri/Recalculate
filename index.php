<?php
$module->initializeJavascriptModuleObject();
$module->tt_transferToJavascriptModuleObject();
?>
<link rel="stylesheet" href="<?= $module->getURL('css/loading.min.css'); ?>">
<link rel="stylesheet" href="<?= $module->getURL('css/main.css'); ?>">
<script src="<?= $module->getURL('js/jquery.contextMenu.js'); ?>"></script>
<script src="<?= $module->getURL('js/jquery.ui.position.min.js'); ?>"></script>
<link rel="stylesheet" href="<?= $module->getURL('css/jquery.contextMenu.css'); ?>">
<script>
    <?= $module->getJavascriptModuleObjectName(); ?>.config = <?= json_encode($module->loadSettings()); ?>;
</script>

<div class="projhdr"><i class="fas fa-calculator"></i> <?= $module->tt('module_name'); ?></div>
<div class="container float-left" style="max-width:800px">

    <div id="primaryForm" class="collapse show">

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
                <input id="records" name="records" placeholder="<?= $module->tt('placeholder_record'); ?>" type="text" class="form-control">
                <div class="form-check form-switch float-right" data-bs-trigger="hover" data-bs-toggle="popover" data-bs-content="<?= $module->tt('select_record'); ?>">
                    <input type="checkbox" class="form-check-input" id="allRecords" data-state="0">
                    <label class="form-check-label" for="allRecords"></label>
                </div>
            </div>
        </div>

        <!-- Events -->
        <div class="row p-2">
            <label for="events" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_event'); ?></label>
            <div class="col-10">
                <select multiple="multiple" id="events" name="events" class="form-select">
                </select>
                <div class="form-check form-switch float-right" data-bs-trigger="hover" data-bs-toggle="popover" data-bs-content="<?= $module->tt('select_event'); ?>">
                    <input type="checkbox" class="form-check-input" id="allEvents" data-state="0">
                    <label class="form-check-label" for="allEvents"></label>
                </div>
            </div>
        </div>

        <!-- Fields -->
        <div class="row p-2">
            <label for="fields" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_field'); ?></label>
            <div class="col-10">
                <select id="fields" name="fields" class="form-select" multiple="multiple">
                </select>
                <div class="form-check form-switch float-right" data-bs-trigger="hover" data-bs-toggle="popover" data-bs-content="<?= $module->tt('select_field'); ?>">
                    <input type="checkbox" class="form-check-input" id="allFields" data-state="0">
                    <label class="form-check-label" for="allFields"></label>
                </div>
            </div>
        </div>

        <!-- Advanced Button -->
        <div id="advRow" class="row p-2">
            <div class="offset-2  col-10">
                <a class="color-primary font-weight-bold" role="button" data-toggle="collapse" data-target="#advSettings">
                    <?= $module->tt('button_advanced'); ?>
                    <i class="fa fa-arrow-down"></i>
                </a>
            </div>
        </div>

        <!-- Advanced Settings -->
        <div id="advSettings" class="collapse">

            <!-- Batch Size -->
            <div id="batchSizeRow" class="row p-2">
                <label for="batchSize" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_batch'); ?></label>
                <div class="col-10">
                    <input id="batchSize" name="batchSize" placeholder="all" type="text" aria-describedby="batchSizeHelpBlock" class="form-control">
                    <span id="batchSizeHelpBlock" class="form-text text-muted"><?= $module->tt('label_batch_help'); ?></span>
                </div>
            </div>

            <!-- On Error -->
            <div id="onErrorRow" class="row p-2">
                <label for="onError" class="col-2 col-form-label font-weight-bold"><?= $module->tt('label_on_error'); ?></label>
                <div class="col-10">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="onErrorStop" name="onError">
                        <label class="form-check-label" for="onErrorStop"><?= $module->tt('label_on_error_a'); ?></label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="onErrorCont" name="onError">
                        <label class="form-check-label" for="onErrorCont"><?= $module->tt('label_on_error_b'); ?></label>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Reopen Button for Table -->
    <div id="reopenBtn" class="row p-2 collapse">
        <a class="color-primary font-weight-bold text-center w-100 wideBtn" role="button" data-toggle="collapse" data-target="#primaryForm">
            <span>
                <?= $module->tt('label_reopen'); ?>
                <i class="fa fa-arrow-down"></i>
            </span>
        </a>
    </div>

    <!-- Go Button & Details -->
    <div class="row p-2 collapse show" id="recalcBtnRow">
        <div class="offset-2 col-6 detailsGrid">
            <div></div>
            <div></div>
            <div style="grid-area:2/1/3/3;white-space:nowrap;"></div>
        </div>
        <div class="col-4">
            <div id="recalcBtnGroup" class="btn-group float-right text-nowrap">
                <button id="recalc" data-action="run" type="button" class="btn btn-primary pr-0">
                    <span class="btnText"> <?= $module->tt('button_submit'); ?> </span>
                    <span class="hidden"><i class="ld ld-spin ld-ring"></i></span>
                </button>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" data-action="preview" href="#"><?= $module->tt('button_preview'); ?></a>
                    <a class="dropdown-item hidden" data-action="cancel" href="#"><?= $module->tt('button_cancel'); ?></a>
                    <a class="dropdown-item hidden" data-action="old" href="#"><?= $module->tt('button_old'); ?></a>
                    <a class="dropdown-item" data-action="cron" href="#"><?= $module->tt('button_cron'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Preview -->
    <div id="previewTable" class="row p-2 collapse">
        <div class="float-right downloadBtn" id="previewCsv"><a><i class="fa fa-download"></i></a></div>
        <div class="form-check form-switch float-right" data-bs-trigger="hover" data-bs-toggle="popover" data-bs-content="<?= $module->tt('select_table'); ?>">
            <input type="checkbox" class="form-check-input" id="equalCalcs" data-state="1">
            <label class="form-check-label" for="equalCalcs"></label>
        </div>
        <div class="col-12">
            <table style="width:100%" class="table"></table>
        </div>
    </div>

    <!-- Scheduled To Run Button -->
    <div class="row p-2 collapse mb-4" id="schBtnRow">
        <label for="records" class="col-3 col-form-label font-weight-bold"><?= $module->tt('label_time'); ?></label>
        <div class="col-6 d-flex">
            <input id="cronTime" placeholder="<?= $module->tt('placeholder_date'); ?>" type="text" class="form-control mr-1">
            <select id="cronRepeat" class="form-select ml-1">
                <option value=""><?= $module->tt('cron_once'); ?></option>
                <option value="1"><?= $module->tt('cron_daily'); ?></option>
                <option value="7"><?= $module->tt('cron_weekly'); ?></option>
            </select>
        </div>
        <div class="col-3">
            <button type="button" data-action="makeCron" class="btn btn-primary float-right">
                <span class="btnText"> <?= $module->tt('button_cron'); ?> </span>
            </button>
        </div>
    </div>

    <!-- Can't Schedule Cron -->
    <div class="row p-2 collapse" id="noschRow">
        <div class="col-12">
            <div class="alert alert-primary" role="alert">
                <?= $module->tt('label_no_sch'); ?>
            </div>
        </div>
    </div>

    <!-- Scheduled Reclacs -->
    <div id="cronTable" class="row p-2 collapse">
        <div class="col-12">
            <table style="width:100%" class="table"></table>
        </div>
    </div>

</div>
<script src="<?= $module->getURL('js/main.js'); ?>"></script>