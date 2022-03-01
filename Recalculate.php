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

        // Double check validation for everything
        foreach ($config as $name => $c) {
            if ($c['post'][0] == "*") {
                $config[$name]['post'] = $c['valid'];
            } else {
                $intersection = array_intersect($c['post'], $c['valid']);
                if (length($intersection) != length($c['post'])) {
                    $errors[] = [
                        "text" => str_replace("_", $name, $this->tt('label_record')),
                        "display" => true
                    ];
                }
            }
        }

        // Find a batch size (literally just guessing)
        $batchSize = 100;
        $size = $config['fields']['size'];
        if ($size > 400) {
            $batchSize = 5;
        } else if ($size > 60) {
            $batchSize = 20;
        }

        // Execute Calc
        $updates = 0;
        $recordBatches = array_chunk($config['record']['post'], $batchSize);
        foreach ($recordBatches as $recordSet) {
            foreach ($config['event']['post'] as $event_id) {
                $calcUpdates = Calculate::saveCalcFields($recordSet, $config['fields']['post'], $event_id);
                if (is_numeric($calcUpdates)) {
                    $updates += $calcUpdates;
                } else {
                    $errors[] = [
                        "text" => $calcUpdates,
                        "display" => false
                    ];
                }
            }
        }

        // Return values
        echo json_encode([
            'changes' => $updates,
            'errors' => $errors
        ]);
    }

    /*
    Inits the Recalc global and loads all settings.
    */
    public function loadSettings()
    {
        // List of all valid fields
        $fields = $this->getAllCalcFields();

        // All events maped as event_id:event_name 
        $events = REDCap::getEventNames();

        // Organize the strucutre
        return [
            "events" => $events,
            "fields" => $fields,
            "csrf" => $this->getCSRFToken(),
            "router" => $this->getUrl('router.php'),
        ];
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
