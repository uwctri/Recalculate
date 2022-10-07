<?php

namespace UWMadison\Recalculate;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;
use Calculate;
use Project;
use RestUtility;

class Recalculate extends AbstractExternalModule
{
    private $oneMonth = (60 * 60 * 24 * 30);
    private $maxCrons = 120;
    private $timeFormat = 'Y-m-d\TH:i:s.000\Z';

    /*
    Redcap Hook. Allow nav to the index page only if user rights are met
    */
    public function redcap_module_link_check_display($project_id, $link)
    {
        return $this->userHasRights() ? true : null;
    }

    /*
    Redcap Hook. Prevent opening module config on the project if no user rights.
    Only impacts the modal in projects, not in the admin panel.
    */
    public function redcap_module_configure_button_display()
    {
        return $this->userHasRights() ? true : null;
    }

    /*
    Process a post request from API or router
    */
    public function process($tokenRequired)
    {
        global $Proj;

        $request = RestUtility::processRequest($tokenRequired);
        $params = $request->getRequestVars();
        $project_id = $params['projectid'];

        // API calls need to have a new project instance created
        if (!isset($Proj)) {
            $Proj = new Project($project_id);
        }

        // Only really needed for API, but just check for everyone
        if (!$this->isModuleEnabledForProject($project_id)) {
            RestUtility::sendResponse(400, "The requested module is currently disabled on this project.");
        }

        // Run core code
        $result = [];
        $action = $params["action"] ?? "api";
        $config = $this->parse_field_event_record($params["fields"], $params["events"], $params["records"]);
        if ($action == "cron") {
            $result = $this->setup_cron($config, $params["time"], $params["batchSize"]);
        } else { // API, Calc
            $this->projectLog($action, $config['field']['post'], $config['event']['post'], $config['record']['post']);
            $result =  $this->recalculate($config, $action);
        }
        return json_encode($result);
    }

    /*
    Cron job method to check if any user defined recalcs should run
    */
    public function run_cron($cronInfo)
    {
        // Stash original PID, probably not needed, but docs recommend
        $originalPid = $_GET['pid'];
        $maxTime = $cronInfo["cron_max_run_time"];
        global $Proj;

        // Loop over every pid using this EM
        foreach ($this->getProjectsWithModuleEnabled() as $pid) {

            // Act like we are in that project
            $_GET['pid'] = $pid;
            $Proj = new Project($pid);
            define('PROJECT_ID', $pid);

            // Gather a bunch of info
            $time = gmdate($this->timeFormat); // ISO time in same format as JS
            $expire = gmdate($this->timeFormat, time() - $maxTime);
            $veryOld = gmdate($this->timeFormat, time() - $this->oneMonth);
            $crons = $this->getProjectSetting('cron');
            $crons = empty($crons) ? [] : json_decode($crons, true);
            $large = count($crons) > $this->maxCrons;

            // Loop over all configed crons
            foreach ($crons as $id => $cron) {
                if (in_array($cron["status"], [0, 1])) { // Running or Scheduled

                    // Mark the cron as error
                    if ($expire > $cron["time"]) {
                        $cron["status"] = -1;
                        $this->update_cron($id, $cron);
                    }
                    // Run the cron
                    elseif ($time > $cron["time"] && $cron["status"] == 0) {

                        // Set to running
                        $cron["status"] = 1;
                        $this->update_cron($id, $cron);
                        $this->projectLog("cron", $cron["fields"], $cron["events"], $cron["records"]);

                        // Perform recalcs
                        $size = $cron["size"] > 0 ? $cron["size"] : 1000;
                        $config = $this->parse_field_event_record($cron["fields"], $cron["events"], $cron["records"]);
                        $records = ((count($cron["records"]) == 0) || (count($cron["records"]) == 1 && $cron["records"][0] == "*")) ? $config["record"]["valid"] : $cron["records"];
                        $batchedRecords = array_chunk($records, $size);
                        foreach ($batchedRecords as $set) {
                            $config['record']['post'] = $set;
                            $this->recalculate($config, "cron");
                        }

                        // Done
                        $cron["status"] = 2;
                        $cron["completed"] = gmdate($this->timeFormat);
                        $this->update_cron($id, $cron);
                        return; // Only run one at a time
                    }
                } elseif ($large && ($veryOld > $cron["time"])) {
                    // Remove old entry
                    unset($crons[$id]);
                }
            }
        }

        // Put the pid back the way it was before this cron job
        // likely doesn't matter, but is good housekeeping practice
        $_GET['pid'] = $originalPid;
        return "The \"{$cronInfo['cron_name']}\" cron job completed successfully.";
    }

    /*
    Setup a new user defined cron
    */
    private function setup_cron($config, $time, $size)
    {
        $json = $this->getProjectSetting('cron');
        $json = empty($json) ? [] : json_decode($json, true);
        $json[] = [
            "fields" => $config['field']['post'],
            "events" => $config['event']['post'],
            "records" => $config['record']['post'],
            "time" => $time,
            "size" => intval($size),
            "status" => 0
        ];
        $this->setProjectSetting('cron', json_encode($json));
        return [
            "errors" =>  []
        ];
    }


    /*
    Re-pull the cron json, update a cron, and save. 
    Avoids any overlapping cron issues.
    */
    private function update_cron($id, $cron)
    {
        $json = $this->getProjectSetting('cron');
        $json = empty($json) ? [] : json_decode($json, true);
        $json[$id] = $cron;
        $this->setProjectSetting('cron', json_encode($json));
    }

    /*
    Formats Fields Events and Records from Ajax into
    an array of ready-to-use values
    */
    private function parse_field_event_record($fields, $events, $records)
    {
        $isClassic = !REDCap::isLongitudinal();
        $eventNames = REDCap::getEventNames();
        function decode($input)
        {
            return array_map('trim', is_string($input) ? (json_decode($input, true) ?? []) : $input);
        }
        return [
            "field" => [
                "post" => decode($fields),
                "valid" => array_keys($this->getAllCalcFields()),
            ],
            "event" => [
                "post" => decode($events),
                "valid" => $isClassic ? ['all'] : array_keys($eventNames),
            ],
            "record" => [
                "post" => decode($records),
                "valid" => $this->getAllRecordIds(),
            ]
        ];
    }

    /*
    Performs core functionality. Invoked via ajax/api.
    Fire the native redcap calculated field routines.
    */
    private function recalculate($config, $action)
    {
        $errors = [];
        $this->checkMemory();

        // Validate submission
        foreach ($config as $name => $c) {

            // Missing param
            if (count($c['post']) == 0) {
                $errors[] = [
                    "text" => $this->tt('error_missing', $name),
                    "display" => true
                ];
                continue;
            }

            // Substitue in wild cards
            if (in_array($c['post'][0], ["all", "*"])) {
                $config[$name]['post'] = $c['valid'];
                $config[$name]['all'] = true;
                continue;
            }

            // We can't validate events on an API call due to context
            if ($action == "api" && $name == "event") {
                continue;
            }

            $intersection = array_intersect($c['post'], $c['valid']);
            if (count($intersection) != count($c['post'])) {
                $errors[] = [
                    "text" => $this->tt('error_invalid', $name),
                    "display" => true
                ];
            }
        }

        // Execute Calc
        $updates = 0;
        $preview = [];
        if (count($errors) == 0) {
            $batchSize = $this->getBatchSize(count($config['field']['post']));
            $recordBatches = array_chunk($config['record']['post'], $batchSize);
            $fields = $config['field']['all'] ? Null : $config['field']['post'];
            foreach ($recordBatches as $recordSubset) {

                // Preview generation only
                if ($action == "preview") {
                    $tmp = Calculate::calculateMultipleFields($recordSubset, $fields, false, 'all');
                    if (count($tmp) > 0) {

                        // Combine the array with rest of batches
                        $tmp = array_combine(array_map(function ($a) {
                            return '_' . $a;
                        }, array_keys($tmp)), array_values($tmp));
                        $preview = array_merge_recursive($preview, $tmp);
                    }
                    continue;
                }

                // For specific event writes, flip through events or post 'all'
                if ($config['event']['all']) $config['event']['post'] = ['all'];
                foreach ($config['event']['post'] as $event_id) {
                    $calcUpdates = Calculate::saveCalcFields($recordSubset, $fields, $event_id);
                    if (is_numeric($calcUpdates)) {
                        $updates += $calcUpdates;
                        continue;
                    }
                    $errors[] = [
                        "text" => $calcUpdates,
                        "display" => false
                    ];
                }
            }
        }

        // For previews, search for data user can't access
        if ($action == "preview") {
            $rights = $this->getFieldAccessMap();
            foreach ($rights as $field => $frights) {
                if (!$frights['access']) {
                    $this->censorData($preview, $field);
                }
            }
        }

        // Return values
        $records = $config['record']['all'] ? ['*'] : $config['record']['post'];
        return [
            'changes' => $updates,
            'errors'  => $errors,
            'records' => $records,
            'preview' => $preview
        ];
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
            "calculate" => [
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
            ],
            "cron" => [
                "blurb" => "Scheduled Recalculate",
                "changes" => "Perfromed recalc for ..."
            ]
        ];
        $fields = implode(', ', $fieldList);
        $events = implode(', ', $eventList);
        $records = implode(', ', $recordList);
        $changes = $config[$action]["changes"] . "\nFields = $fields\nEvents = $events\nRecords = $records";
        REDCap::logEvent($config[$action]["blurb"], $changes, $sql, $record, $event);
    }

    /*
    Fetch and return all sever-sourced info
    */
    public function loadSettings()
    {
        $json = $this->getProjectSetting('cron');
        $json = empty($json) ? [] : json_decode($json, true);
        return [
            "crons" => $json,
            "events" => REDCap::getEventNames(),
            "isClassic" => !REDCap::isLongitudinal(),
            "fields" => $this->getAllCalcFields(),
            "records" => $this->getAllRecordIds(),
            "csrf"   => $this->getCSRFToken(),
            "router" => $this->getUrl('router.php'),
            "em" => $this->getJavascriptModuleObjectName()
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
    Return a map from fields to forms/access rights to the field
    */
    private function getFieldAccessMap()
    {
        global $Proj;
        $rights = reset(REDCap::getUserRights(USERID))['forms'];
        $map = [];
        foreach ($Proj->metadata as $attr) {
            $map[$attr['field_name']] = [
                "form" => $attr['form_name'],
                "access" => $rights[$attr['form_name']] != "0"
            ];
        }
        return $map;
    }

    /*
    Recursively search for a field and redact
    */
    private function censorData(array &$arr, $field)
    {
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                $this->censorData($value, $field);
            }
            if ($key == $field) {
                $value = array_merge(
                    $value,
                    [
                        "saved" => "",
                        "censor" => true
                    ]
                );
            }
        }
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
    Check system settings, we might want to increase allowed RAM
    */
    private function checkMemory()
    {
        $mb = intval($this->getSystemSetting('memory'));
        $current = ini_get('memory_limit');
        $current = intval($current) * (stripos($current, 'g') ? 1024 : 1);
        if ($mb < 100 || $mb <= $current) return;
        ini_set('memory_limit', $mb . 'M');
    }

    /*
    Return all records in the project, optionally pass events to
    speed up the data pull
    */
    private function getAllRecordIds()
    {
        return array_keys(REDCap::getData('array', null, REDCap::getRecordIdField()));
    }

    /*
    Check if current user is allowed to use the module
    */
    private function userHasRights()
    {
        $user = $this->getUser();
        if ($user->isSuperUser()) return true;
        $whitelist = array_filter($this->getProjectSetting("user-whitelist"));
        $dataQuality = $user->getRights()["data_quality_execute"] == "1";
        return count($whitelist) > 0 ? in_array($user->getUsername(), $whitelist) : $dataQuality;
    }

    /*
    Check if module is enabled on project
    */
    private function isModuleEnabledForProject($project_id)
    {
        return ExternalModules::getProjectSetting($this->PREFIX, $project_id, ExternalModules::KEY_ENABLED);
    }
}
