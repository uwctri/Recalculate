<?php

namespace AbtAssoc\Recalculate;

use ExternalModules\AbstractExternalModule;
use REDCap;
use Records;
use Calculate;

class Recalculate extends AbstractExternalModule
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
        $errors = [];
        $eventNames = REDCap::getEventNames();
        $config = [
            "field" => [
                "post" => explode(',', $fields),
                "valid" => $this->getAllCalcFields()
            ],
            "record" => [
                "post" => explode(',', $records),
                "valid" => $this->getAllRecordIds($eventNames)
            ],
            "event" => [
                "post" => explode(',', $events),
                "valid" => array_keys($eventNames)
            ]
        ];

        // Double check validation for everything
        foreach ($config as $name => $c) {
            if ($c['post'][0] == "*") {
                $config[$name]['post'] = $c[$name]['valid'];
            } else {
                $intersection = array_intersect($c['post'], $c['valid']);
                if (length($intersection) != length($c['post'])) {
                    $errors[] = "Invalid {$name}(s) found";
                }
            }
        }

        // TODO Execute calc

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
        // Setup Redcap JS object
        $this->initializeJavascriptModuleObject();
        $this->tt_transferToJavascriptModuleObject();

        // List of all valid fields
        $fields = $this->getAllCalcFields();

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
    Return all all field names that are configured as "calc"
    */
    private function getAllCalcFields()
    {
        global $Proj;
        $fields = [];
        foreach ($Proj->metadata as $attr) {
            if ($attr['element_type'] == 'calc') {
                $fields[$attr['field_name']] = $attr['element_label'];
            }
        }
        return $fields;
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
