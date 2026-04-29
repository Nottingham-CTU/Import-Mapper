<?php

namespace Nottingham\ImportMapper\Services\Validation;

use Nottingham\ImportMapper\Models\ProjectStructure;

/**
 * Validates field mappings and matching config against project structure.
 */
final readonly class FieldMappingStructureValidator
{
    /**
     * Validate field mappings against project structure.
     * Ensures events, forms, and fields exist and detects duplicates.
     *
     * @param array $fieldMappings Array of field mapping objects
     * @param ProjectStructure $projectStructure Current project structure
     * @return array Array of error messages (empty if valid)
     */
    public function validateFieldMappings(array $fieldMappings, ProjectStructure $projectStructure): array
    {
        $errors = [];
        $projectData = $projectStructure->toArray();
        $isLongitudinal = $projectStructure->isLongitudinal;
        $seenMappings = [];

        foreach ($fieldMappings as $index => $mapping) {
            if (empty($mapping['csvFieldName']) && empty($mapping['redcapFieldName'])) {
                continue;
            }

            if (empty($mapping['csvFieldName'])) {
                $errors[] = "Field mapping #" . ($index + 1) . ": CSV field name is required";
                continue;
            }

            if (empty($mapping['redcapFormName'])) {
                $errors[] = "Field mapping #" . ($index + 1) . " ({$mapping['csvFieldName']}): REDCap form is required";
                continue;
            }

            if (empty($mapping['redcapFieldName'])) {
                $errors[] = "Field mapping #" . ($index + 1) . " ({$mapping['csvFieldName']}): REDCap field is required";
                continue;
            }

            if ($isLongitudinal) {
                if (empty($mapping['redcapEventName'])) {
                    $errors[] = "Field mapping #" . ($index + 1) . " ({$mapping['csvFieldName']}): REDCap event is required for longitudinal projects";
                    continue;
                }

                if (!isset($projectData['all_event_names'][$mapping['redcapEventName']])) {
                    $errors[] = "Field mapping #" . ($index + 1) . " ({$mapping['csvFieldName']}): Event '{$mapping['redcapEventName']}' not found in project";
                    continue;
                }

                $formsForEvent = $projectData['all_form_names_by_event'][$mapping['redcapEventName']] ?? [];
                if (!in_array($mapping['redcapFormName'], $formsForEvent)) {
                    $errors[] = "Field mapping #" . ($index + 1) . " ({$mapping['csvFieldName']}): Form '{$mapping['redcapFormName']}' is not assigned to event '{$mapping['redcapEventName']}'";
                    continue;
                }
            } else {
                if (!in_array($mapping['redcapFormName'], $projectData['all_form_names'])) {
                    $errors[] = "Field mapping #" . ($index + 1) . " ({$mapping['csvFieldName']}): Form '{$mapping['redcapFormName']}' not found in project";
                    continue;
                }
            }

            $fieldsInForm = $projectData['fields_by_form'][$mapping['redcapFormName']] ?? [];
            if (!in_array($mapping['redcapFieldName'], $fieldsInForm)) {
                $errors[] = "Field mapping #" . ($index + 1) . " ({$mapping['csvFieldName']}): Field '{$mapping['redcapFieldName']}' not found in form '{$mapping['redcapFormName']}'";
                continue;
            }

            $eventName = $isLongitudinal ? ($mapping['redcapEventName'] ?? '') : '';
            $duplicateKey = "{$mapping['csvFieldName']}::$eventName::{$mapping['redcapFormName']}::{$mapping['redcapFieldName']}";
            if (isset($seenMappings[$duplicateKey])) {
                $errors[] = "Duplicate field mapping: CSV field '{$mapping['csvFieldName']}' is already mapped to '{$mapping['redcapFieldName']}'";
            } else {
                $seenMappings[$duplicateKey] = true;
            }
        }

        return $errors;
    }

    /**
     * Validate matching configuration.
     *
     * @param array $matching Matching configuration object
     * @param array $fieldMappings Array of field mapping objects
     * @param ProjectStructure $projectStructure Current project structure
     * @return array Array of error messages (empty if valid)
     */
    public function validateMatchingConfig(array $matching, array $fieldMappings, ProjectStructure $projectStructure): array
    {
        $errors = [];
        $projectData = $projectStructure->toArray();
        $isLongitudinal = $projectStructure->isLongitudinal;

        $mappingById = [];
        foreach ($fieldMappings as $mapping) {
            if (!empty($mapping['id'])) {
                $mappingById[$mapping['id']] = $mapping;
            }
        }

        if (!empty($matching['record']['enabled'])) {
            $fieldMappingId = $matching['record']['fieldMappingId'] ?? '';
            if (empty($fieldMappingId)) {
                $errors[] = 'Record matching is enabled but no field mapping is selected';
            } elseif (!isset($mappingById[$fieldMappingId])) {
                $errors[] = 'Record matching references a field mapping that does not exist';
            }
        }

        if ($isLongitudinal && !empty($matching['event']['enabled'])) {
            $fieldMappingIds = $matching['event']['fieldMappingIds'] ?? [];

            if (empty($fieldMappingIds)) {
                $errors[] = 'Event matching is enabled but no field mappings are selected';
            } else {
                foreach ($fieldMappingIds as $fieldMappingId) {
                    if (!isset($mappingById[$fieldMappingId])) {
                        $errors[] = 'Event matching references a field mapping that does not exist';
                        continue;
                    }

                    $mapping = $mappingById[$fieldMappingId];
                    $eventName = $mapping['redcapEventName'] ?? '';

                    if (!in_array($eventName, $projectData['repeating_event_names'])) {
                        $errors[] = "Event matching for '$eventName' is invalid because the event is not repeating";
                    }
                }
            }
        }

        if (!empty($matching['form']['enabled'])) {
            $fieldMappingIds = $matching['form']['fieldMappingIds'] ?? [];

            if (empty($fieldMappingIds)) {
                $errors[] = 'Form matching is enabled but no field mappings are selected';
            } else {
                foreach ($fieldMappingIds as $fieldMappingId) {
                    if (!isset($mappingById[$fieldMappingId])) {
                        $errors[] = 'Form matching references a field mapping that does not exist';
                        continue;
                    }

                    $mapping = $mappingById[$fieldMappingId];
                    $formName = $mapping['redcapFormName'] ?? '';
                    $eventName = $mapping['redcapEventName'] ?? '';

                    if ($isLongitudinal) {
                        $repeatingFormsInEvent = $projectData['repeating_forms_by_event'][$eventName] ?? [];
                        if (!in_array($formName, $repeatingFormsInEvent)) {
                            $errors[] = "Form matching for '$formName' in event '$eventName' is invalid because the form is not repeating";
                        }
                    } else {
                        if (!in_array($formName, $projectData['repeating_forms'])) {
                            $errors[] = "Form matching for '$formName' is invalid because the form is not repeating";
                        }
                    }
                }
            }
        }

        if (!empty($matching['dag']['enabled'])) {
            $mode = $matching['dag']['mode'] ?? '';

            if (empty($mode)) {
                $errors[] = 'DAG matching is enabled but no mode is selected';
            } elseif ($mode === 'same_for_all') {
                $dagUniqueName = $matching['dag']['dagUniqueName'] ?? '';
                if (empty($dagUniqueName)) {
                    $errors[] = 'DAG matching mode is "same_for_all" but no DAG is selected';
                } else {
                    $dagExists = array_key_exists($dagUniqueName, $projectData['data_access_groups']);
                    if (!$dagExists) {
                        $errors[] = "Selected DAG '$dagUniqueName' does not exist in the project";
                    }
                }
            } elseif ($mode === 'csv_field') {
                $csvFieldName = $matching['dag']['csvFieldName'] ?? '';
                if (empty($csvFieldName)) {
                    $errors[] = 'DAG matching mode is "csv_field" but no CSV field is selected';
                }
            }
        }

        return $errors;
    }
}
