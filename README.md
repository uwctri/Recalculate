# Recalculate - Redcap External Module

## What does it do?

This module allows you to calculate and save values for calc fields in a project by selecting some subset/combination of events, records, and feilds. This is usefull if you need to update a very large number of calcs, or only want to update values on a subset of records/events. Logging occurs as expected with the user and a typical "Update record (Auto calculation)" event logged in addition to an EM specifc log event when new recalcs are started via the EM. Access to the module's page is restricted to those with permissions to execute data quality rules.

## Installing

You can install the module from the REDCap EM repo or drop it directly in your modules folder (i.e. `redcap/modules/recalculate_v1.0.0`).

## API

This module exposes a simple API to trigger recalcuations ...

`POST /api/?type=module&prefix=recalculate&page=api&NOAUTH`

|**Body Parameter**|              **Description**             |   **Type**   |
|:-----------------:|:---------------------------------------:|:------------:|
|   token           |   API Token from module configuration   |  string      |
|   fields          |   Unique field names or '*' for all     |  json array  |
|   events          |   Event IDs or '*' for all              |  json array  |
|   records         |   Record IDs or '*' for all             |  json array  |
|   pid             |   Project ID                            |  int         |

No defaults are assumed by the api, thus **all fields are requried**. The API token is generated per project and can be found in the module configuration. The token can be changed, but a blank token will always be rejected by the API.

## TODO

* Preview of changes to be made without saving
* Persist previewed changes after leaving page (cookie on user)