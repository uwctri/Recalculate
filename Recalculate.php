<?php

namespace AbtAssoc\Recalculate;

use ExternalModules\AbstractExternalModule;
use REDCap;
use Calculate;

class Recalculate extends AbstractExternalModule
{
    /*
    Redcap Hook. Allow nav to the index page only if user rights are met
    */
    public function redcap_module_link_check_display($project_id, $link)
    {
        return $this->userHasRights();
    }

    /*
    Redcap Hook. Prevent opening module config on the project if no user rights
    Always allow in the control center
    */
    public function redcap_module_configure_button_display()
    {
        return $this->isPage('ExternalModules/manager/control_center.php') || $this->userHasRights();
    }

    /*
    Redcap Hook. Create a reasonable project API key when enabled.
    */
    public function redcap_module_project_enable()
    {
        if (empty($this->getProjectSetting('api_token'))) {
            $this->setProjectSetting('api_token', $this->generateToken());
        }
    }

    /*
    Performs core functionality. Invoked via router/ajax/api.
    Fire the native redcap calculated field routines.
    */
    public function recalculate($fields, $events, $records, $previewOnly = false)
    {
        $errors = [];
        $eventNames = REDCap::getEventNames();
        $page = $_GET['page'];

        // Load everything into an array for easy looping
        $config = [
            "field" => [
                "post" => array_map('trim', json_decode($fields, true) ?? []),
                "valid" => array_keys($this->getAllCalcFields()),
            ],
            "event" => [
                "post" => array_map('trim', json_decode($events, true) ?? []),
                "valid" => array_keys($eventNames),
            ],
            "record" => [
                "post" => array_map('trim', json_decode($records, true) ?? []),
                "valid" => $this->getAllRecordIds($eventNames),
            ]
        ];

        // Log the event
        $action = $page == "api" ? "api" : "direct";
        $this->projectLog($action, $config['field']['post'], $config['event']['post'], $config['record']['post']);

        // Validate submission
        foreach ($config as $name => $c) {
            if (count($c['post']) == 0) {
                $errors[] = [
                    "text" => str_replace("_", $name, $this->tt('error_missing')),
                    "display" => true
                ];
                continue;
            }
            if ($c['post'][0] == "*") {
                $config[$name]['post'] = $c['valid'];
                $config[$name]['all'] = true;
                continue;
            }
            $intersection = array_intersect($c['post'], $c['valid']);
            if (length($intersection) != length($c['post'])) {
                $errors[] = [
                    "text" => str_replace("_", $name, $this->tt('error_invalid')),
                    "display" => true
                ];
            }
        }

        // Execute Calc
        $updates = 0;
        $preview = [];
        if (count($errors) == 0) {
            $batchSize = $this->getBatchSize(count($config['fields']['post']));
            $recordBatches = array_chunk($config['record']['post'], $batchSize);
            foreach ($recordBatches as $recordSubset) {
                foreach ($config['event']['post'] as $event_id) {
                    if ($previewOnly) {
                        $preview[] = Calculate::calculateMultipleFields($recordSubset, $config['fields']['post'], true, $event_id);
                        continue;
                    }
                    $calcUpdates = Calculate::saveCalcFields($recordSubset, $config['fields']['post'], $event_id);
                    if (is_numeric($calcUpdates)) {
                        $updates += $calcUpdates;
                        break;
                    }
                    $errors[] = [
                        "text" => $calcUpdates,
                        "display" => false
                    ];
                }
            }
        }

        // Return values
        $records = $config['record']['all'] ? ['*'] : $config['record']['post'];
        echo json_encode([
            'changes' => $updates,
            'errors' => $errors,
            'records' => $records,
            'source' => $page,
            'preview' => $preview
        ]);
    }

    /*
    Log an action for the EM
    */
    private function projectLog($action, $fieldList, $eventList, $recordList)
    {
        $sql = null;
        $record = count($recordList) == 1 && $recordList[0] != "*" ? $recordList[0] : NULL;
        $event = count($eventList) == 1 && $eventList[0] != "*" ? $eventList[0] : NULL;
        $config = [
            "direct" => [
                "blurb" => "Recalculate",
                "changes" => "Perfromed recalc for ..."
            ],
            "api" => [
                "blurb" => "Recalculate API",
                "changes" => "Perfromed recalc for ..."
            ],
            "preview" => [
                "blurb" => "Recalculate Preview",
                "changes" => "Generated preview for ..."
            ]
        ];
        if (!empty($config[$action])) {
            $fields = implode(', ', $fieldList);
            $events = implode(', ', $eventList);
            $records = implode(', ', $recordList);
            $changes = $config[$action]["changes"] . "\nFields = $fields\nEvents = $events\nRecords = $records";
            REDCap::logEvent($config[$action]["blurb"], $changes, $sql, $record, $event);
        }
    }

    /*
    Fetch and return all sever-sourced info
    */
    public function loadSettings()
    {
        $events = REDCap::getEventNames();
        return [
            "events" => $events,
            "fields" => $this->getAllCalcFields(),
            "records" => $this->getAllRecordIds($events),
            "csrf"   => $this->getCSRFToken(),
            "router" => $this->getUrl('router.php')
        ];
    }

    /*
    Return a record batch size for calcs. Mostly just guessing.
    */
    private function getBatchSize($size)
    {
        if ($size > 400) return 5;
        if ($size > 60) return 20;
        return 100;
    }

    /*
    Return all field names that are configured as "calc" or via
    a calc action tag
    */
    private function getAllCalcFields()
    {
        global $Proj;
        $fields = [];
        foreach ($Proj->metadata as $attr) {
            $actionTag = strtoupper($attr['misc'] ?? "");
            if (
                $attr['element_type'] == 'calc' ||
                strpos($actionTag, "@CALCDATE") !== false ||
                strpos($actionTag, "@CALCTEXT") !== false
            ) {
                $fields[$attr['field_name']] = strip_tags($attr['element_label']);
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
    Check if current user is allowed to use the module
    */
    private function userHasRights()
    {
        return reset(Redcap::getUserRights())['data_quality_execute'] == "1";
    }

    /*
    Generate a reasonable token
    */
    private function generateToken()
    {
        return strtoupper(md5(USERID . APP_PATH_WEBROOT_FULL  . generateRandomHash(mt_rand(64, 128))));
    }
}
