<?php

namespace Nottingham\ImportMapper\Services;

use Nottingham\ImportMapper\ImportMapper;
use Nottingham\ImportMapper\Models\ProjectStructure;
use Project;

/**
 * Service for reading REDCap project structure
 */
final class ProjectService
{
    /**
     * Constructor
     *
     * @param ImportMapper $module The external module instance
     */
    public function __construct(private readonly ImportMapper $module)
    {
    }

    private ?int $cachedProjectId = null;
    private ?ProjectStructure $cachedStructure = null;
    private ?Project $cachedGlobalProject = null;

    /**
     * Get the project structure
     *
     * @param int|null $projectId
     * @return ProjectStructure
     */
    public function get(?int $projectId = null): ProjectStructure
    {
        if ($this->cachedStructure !== null
            && ($projectId === null || $projectId === $this->cachedProjectId)) {
            return $this->cachedStructure;
        }

        $frameworkProject = $this->module->getProject($projectId ?? null);
        $projectId ??= $frameworkProject->getProjectId();
        $globalProject = $frameworkProject->getREDCapProjectObject();

        $structure = [
            'is_longitudinal' => (bool)$globalProject->longitudinal,
            'repeating_event_names' => [],
            'repeating_forms' => [],
            'repeating_forms_by_event' => [],
            'all_event_names' => [],
            'all_form_names_by_event' => [],
            'fields_by_form' => [],
            'field_types_by_form' => [],
            'record_id_field_name' => $globalProject->table_pk,
        ];

        // Build fields_by_form and field_types_by_form from metadata
        foreach ($globalProject->metadata as $fieldName => $fieldMeta) {
            $formName = $fieldMeta['form_name'] ?? null;
            if ($formName === null) {
                continue;
            }
            $elementType = $fieldMeta['element_type'] ?? 'text';
            // Skip descriptive fields
            if ($elementType === 'descriptive') {
                continue;
            }
            $structure['fields_by_form'][$formName][] = $fieldName;
            $structure['field_types_by_form'][$formName][$fieldName] = $elementType;
        }

        // handle longitudinal projects and classic projects differently
        if ($structure['is_longitudinal']) {
            foreach ($globalProject->getUniqueEventNames() as $eventId => $eventName) {
                $formNames = $frameworkProject->getFormsForEventId($eventId);
                if (empty($formNames)) {
                    continue;
                }
                $structure['all_event_names'][$eventName] = $eventId;

                $isRepeatingEvent = $globalProject->hasRepeatingEvents() && $globalProject->isRepeatingEvent($eventId);
                if ($isRepeatingEvent) {
                    $structure['repeating_event_names'][$eventName] = true;
                }

                $formNamesForEvent = [];

                foreach ($formNames as $formName) {
                    $formNamesForEvent[] = $formName;

                    if ($globalProject->hasRepeatingForms() && !$isRepeatingEvent) {
                        $isRepeatingForm = $globalProject->isRepeatingForm($eventId, $formName);
                        if ($isRepeatingForm) {
                            $structure['repeating_forms_by_event'][$eventName][$formName] = true;
                        }
                    }
                }

                $structure['all_form_names_by_event'][$eventName] = $formNamesForEvent;
            }
        } else {
            // classic project
            $formNames = [];

            foreach (array_keys($globalProject->forms) as $formName) {
                $formNames[] = $formName;
                if ($globalProject->hasRepeatingForms()) {
                    $isRepeatingForm = $globalProject->isRepeatingFormAnyEvent($formName);
                    if ($isRepeatingForm) {
                        $structure['repeating_forms'][$formName] = true;
                    }
                }
            }

            $structure['all_form_names'] = $formNames;
        }

        // Add DAG information as uniqueName => groupId
        $structure['data_access_groups'] = [];
        $dagNames = $globalProject->getUniqueGroupNames();
        if (!empty($dagNames)) {
            $structure['data_access_groups'] = array_flip($dagNames);
        }

        $result = ProjectStructure::fromSerializable($structure);
        $this->cachedProjectId = $projectId;
        $this->cachedStructure = $result;
        $this->cachedGlobalProject = $globalProject;
        return $result;
    }

    /**
     * Inject a pre-built ProjectStructure into the cache, bypassing the full Project load.
     * Used to restore a cached structure from run state on chunks 2+.
     */
    public function setCache(int $projectId, ProjectStructure $structure): void
    {
        $this->cachedProjectId = $projectId;
        $this->cachedStructure = $structure;
        $this->cachedGlobalProject = null;
    }

    /**
     * Return the cached REDCap Project object
     *
     * @param int|null $projectId
     * @return Project|null
     */
    public function getGlobalProject(?int $projectId = null): ?Project
    {
        if ($this->cachedGlobalProject === null || ($projectId !== null && $projectId !== $this->cachedProjectId)) {
            $this->get($projectId);
        }
        return $this->cachedGlobalProject;
    }

    /**
     * Compute a hash of the current project structure.
     *
     * @param int|null $projectId
     * @return string Base-36 hash string
     */
    public function getProjectStructureHash(?int $projectId = null): string
    {
        $project = $this->get($projectId);
        $arr = $project->toArray();

        $hashInput = json_encode([
            'is_longitudinal' => $arr['is_longitudinal'],
            'all_event_names' => !empty($arr['all_event_names']) ? $arr['all_event_names'] : (object)[],
            'all_form_names' => $arr['all_form_names'] ?? [],
            'all_form_names_by_event' => !empty($arr['all_form_names_by_event']) ? $arr['all_form_names_by_event'] : (object)[],
            'fields_by_form' => !empty($arr['fields_by_form']) ? $arr['fields_by_form'] : (object)[],
            'repeating_event_names' => $arr['repeating_event_names'] ?? [],
            'repeating_forms' => $arr['repeating_forms'] ?? [],
            'repeating_forms_by_event' => !empty($arr['repeating_forms_by_event']) ? $arr['repeating_forms_by_event'] : (object)[],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $hash = 5381;
        for ($i = 0, $len = strlen($hashInput); $i < $len; $i++) {
            $char = ord($hashInput[$i]);
            $hash = (($hash << 5) + $hash + $char) & 0xFFFFFFFF;
        }

        return base_convert((string)abs($hash), 10, 36);
    }
}
