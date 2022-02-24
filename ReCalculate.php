<?php

namespace UWMadison\ReCalculate;

use ExternalModules\AbstractExternalModule;
use REDCap;
use Records;
use Calculate;

class ReCalculate extends AbstractExternalModule
{

    private $module_global = 'ReCalc';

    /*
    Redcap Hook, loads config
    */
    public function redcap_every_page_top($project_id)
    {

        // Custom Config page
        if ($this->isPage('ExternalModules/manager/project.php') && $project_id) {
            $this->loadPrefix();
            $this->includeCSS();
            $this->includeJs('config.js');
        }

        // Index.php
        elseif ($_GET['prefix'] == $this->getPrefix() && $_GET['page'] == 'index') {
            $this->loadSettings();
            $this->includeCSS();
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
        $pid = $_GET['pid'];
        $field = $_POST['field'];
        $record = $_POST['record'];
        $event = $_POST['event'];

        // TODO

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

        // TODO need a list of events, record IDs, and calc fields

        // Organize the strucutre
        $data = json_encode([
            "isLong" => REDCap::isLongitudinal(),
            "csrf" => $this->getCSRFToken(),
            "router" => $this->getUrl('router.php'),
        ]);

        // Pass down to JS
        echo "<script>var {$this->module_global} = {$data};</script>";
        echo "<script> {$this->module_global}.em = {$this->getJavascriptModuleObjectName()}</script>";
    }

    /*
    HTML to pass down module prefix for the config page.
    */
    private function loadPrefix()
    {
        echo "<script>var {$this->module_global} = {'modulePrefix': '{$this->getPrefix()}'};</script>";
    }

    /*
    HTML to include some local JS file
    */
    private function includeJs($path)
    {
        echo "<script src={$this->getUrl('js/' .$path)}></script>";
    }

    /*
    HTML to include the local css file
    */
    private function includeCSS()
    {
        echo "<link rel='stylesheet' href={$this->getURL('style.css')}>";
    }
}
