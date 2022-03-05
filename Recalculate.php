<?php

namespace AbtAssoc\Recalculate;

use ExternalModules\AbstractExternalModule;
use REDCap;
use Calculate;

class Recalculate extends AbstractExternalModule
{
    /*
    Performs core functionality. Invoked via router/ajax.
    Fire the native redcap calculated field routines.
    */
    public function recalculate($fields, $events, $records)
    {
        $errors = [];
        $eventNames = REDCap::getEventNames();

        // Load everything into an array for easy looping
        $config = [
            "field" => [
                "post" => array_map('trim', explode(',', $fields)),
                "valid" => array_keys($this->getAllCalcFields()),
            ],
            "record" => [
                "post" => array_map('trim', explode(',', $records)),
                "valid" => $this->getAllRecordIds($eventNames),
            ],
            "event" => [
                "post" => array_map('trim', explode(',', $events)),
                "valid" => array_keys($eventNames),
            ]
        ];

        // Validate submission
        foreach ($config as $name => $c) {
            if ($c['post'][0] == "*") {
                $config[$name]['post'] = $c['valid'];
                $config[$name]['all'] = true;
                break;
            }
            $intersection = array_intersect($c['post'], $c['valid']);
            if (length($intersection) != length($c['post'])) {
                $errors[] = [
                    "text" => str_replace("_", $name, $this->tt('label_record')),
                    "display" => true
                ];
            }
        }

        // Execute Calc
        $updates = 0;
        $batchSize = $this->getBatchSize(count($config['fields']['post']));
        $recordBatches = array_chunk($config['record']['post'], $batchSize);
        foreach ($recordBatches as $recordSubset) {
            foreach ($config['event']['post'] as $event_id) {
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

        // Return values
        $records = $config['record']['all'] ? ['*'] : $config['record']['post'];
        echo json_encode([
            'changes' => $updates,
            'errors' => $errors,
            'records' => $records
        ]);
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
            "router" => $this->getUrl('router.php'),
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
}
