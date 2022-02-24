$(document).ready(function () {

    console.log("Loaded ReCalc config")
    var $modal = $('#external-modules-configure-modal');

    $modal.on('show.bs.modal', function () {
        // Making sure we are overriding this modules's modal only.
        if ($(this).data('module') !== ReCalc.modulePrefix)
            return;

        if (typeof ExternalModules.Settings.prototype.resetConfigInstancesOld === 'undefined')
            ExternalModules.Settings.prototype.resetConfigInstancesOld = ExternalModules.Settings.prototype.resetConfigInstances;

        ExternalModules.Settings.prototype.resetConfigInstances = function () {
            ExternalModules.Settings.prototype.resetConfigInstancesOld();

            if ($modal.data('module') !== ReCalc.modulePrefix)
                return;

            // Only customization
            $modal.addClass('reCalcConfig');
        };
    });

    $modal.on('hide.bs.modal', function () {

        // Making sure we are overriding this modules's modal only.
        if ($(this).data('module') !== ReCalc.modulePrefix)
            return;

        $modal.removeClass('reCalcConfig');

        if (typeof ExternalModules.Settings.prototype.resetConfigInstancesOld !== 'undefined')
            ExternalModules.Settings.prototype.resetConfigInstances = ExternalModules.Settings.prototype.resetConfigInstancesOld;
    });
});