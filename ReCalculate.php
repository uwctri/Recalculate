<?php

namespace AbtAssoc\ReCalculate;

use ExternalModules\AbstractExternalModule;
use REDCap;
use Records;
use Calculate;

class ReCalculate extends AbstractExternalModule
{

    private $module_global = 'Recalc';

    /*
    Redcap Hook, load config customizations or settings for core page
    */
    public function redcap_every_page_top($project_id)
    {

        // Custom Config page
        if ($this->isPage('ExternalModules/manager/project.php') && $project_id) {
            $this->loadPrefix();
            $this->includeJs();
        }

        // Index.php
        elseif ($_GET['prefix'] == $this->getPrefix() && $_GET['page'] == 'index') {
            $this->loadSettings();
        }
    }

    /*
    Redcap Hook, always show EM link
    */
    public function redcap_module_link_check_display($project_id, $link)
    {
        return True;
    }

    /*
    Performs core functionality. Invoked via router/ajax.
    Fire the native redcap calculated field routines.
    */
    public function recalculate($fields, $events, $records, $pid)
    {
        // Gather info
        $fields = explode(',', $fields);
        $records = explode(',', $_POST['record']);
        $events = explode(',', $_POST['event']);

        // TODO
        // Double check that the fields are calcs

        // Double check the records exist

        // Double check the events are valid

        // Return values
        echo json_encode([
            'changes' => 0,
            'errors' => []
        ]);
    }

    /*
    Inits the ReCalc global and loads all settings.
    Also packs the Redcap JS object
    */
    private function loadSettings()
    {
        // Project metadata
        global $Proj;

        // Setup Redcap JS object
        $this->initializeJavascriptModuleObject();
        $this->tt_transferToJavascriptModuleObject();

        // List of all valid fields
        $fields = [];
        foreach ($Proj->metadata as $attr) {
            if ($attr['element_type'] == 'calc') {
                $fields[$attr['field_name']] = $attr['element_label'];
            }
        }

        // All events maped as event_id:event_name 
        $events = REDCap::getEventNames();

        // All record ids as an array
        $records = $this->getAllRecordIds($events);

        // Organize the strucutre
        $data = json_encode([
            "records" => $records,
            "events" => $events,
            "fields" => $fields,
            "csrf" => $this->getCSRFToken(),
            "router" => $this->getUrl('router.php'),
        ]);

        // Pass down to JS
        echo "<script>var {$this->module_global} = {$data};</script>";
        echo "<script> {$this->module_global}.em = {$this->getJavascriptModuleObjectName()}</script>";
    }

    /*
    Return all records in the project, optionally pass events to
    speed up the data pull
    */
    private function getAllRecordIds($events = null)
    {
        if (is_null($events)) {
            $events = REDCap::getEventNames();
        }
        return array_keys(REDCap::getData('array', null, REDCap::getRecordIdField(), array_keys($events)[0]));
    }

    /*
    HTML to pass down module prefix for the config page.
    */
    private function loadPrefix()
    {
        echo "<script>var {$this->module_global} = {'modulePrefix': '{$this->getPrefix()}'};</script>";
    }

    /*
    HTML to include the local JS file
    */
    private function includeJs()
    {
        echo "<script src={$this->getUrl('config.js')}></script>";
    }
}
