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
    let glo = <?= json_encode($module->loadSettings()); ?>;
    glo.em = <?= $module->getJavascriptModuleObjectName(); ?>;
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
                <div class="custom-control custom-switch float-right" data-trigger="hover" data-toggle="popover" data-content="<?= $module->tt('select_record'); ?>">
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
                <div class="custom-control custom-switch float-right" data-trigger="hover" data-toggle="popover" data-content="<?= $module->tt('select_event'); ?>">
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
                <div class="custom-control custom-switch float-right" data-trigger="hover" data-toggle="popover" data-content="<?= $module->tt('select_field'); ?>">
                    <input type="checkbox" class="custom-control-input" id="allFields" data-state="0">
                    <label class="custom-control-label" for="allFields"></label>
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
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="onErrorStop" name="onError">
                        <label class="custom-control-label" for="onErrorStop"><?= $module->tt('label_on_error_a'); ?></label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="onErrorCont" name="onError">
                        <label class="custom-control-label" for="onErrorCont"><?= $module->tt('label_on_error_b'); ?></label>
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
        <div class="custom-control custom-switch float-right" data-trigger="hover" data-toggle="popover" data-content="">
            <input type="checkbox" class="custom-control-input" id="equalCalcs" data-state="1">
            <label class="custom-control-label" for="equalCalcs"></label>
        </div>
        <div class="col-12">
            <table style="width:100%" class="table"></table>
        </div>
    </div>

    <!-- Scheduled To Run Button -->
    <div class="row p-2 collapse mb-4" id="schBtnRow">
        <label for="records" class="col-3 col-form-label font-weight-bold"><?= $module->tt('label_time'); ?></label>
        <div class="col-5">
            <input id="cronTime" placeholder="<?= $module->tt('placeholder_date'); ?>" type="text" class="form-control">
        </div>
        <div class="col-4">
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