# Recalculate - Redcap External Module

## What does it do?

This module allows you to calculate and save values for calc fields in a project by selecting some subset/combination of events, records, and feilds. This is usefull if you need to update a very large number of calcs, or only want to update values on a subset of records/events. Logging occurs as expected with the user and a typical "Update record (Auto calculation)" event logged. Access to the module's page is restricted to those with permissions to execute data quality rules.

## Installing

You can install the module from the REDCap EM repo or drop it directly in your modules folder (i.e. `redcap/modules/recalculate_v1.0.0`).

## Configuration

The module has no configuration, instead you should navigate to the dedicated module page, select an appropriate record, event, and field combination to trigger a recalcuation on them.
