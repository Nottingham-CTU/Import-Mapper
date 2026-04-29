/**
 * Main matching configuration step component.
 * Orchestrates the display of all matching sections using specialized sub-components.
 */

import { useMemo } from "react";
import RecordMatchingSection from "../matching/RecordMatchingSection";
import DagMatchingSection from "../matching/DagMatchingSection";
import EventMatchingTable from "../matching/EventMatchingTable";
import FormMatchingTable from "../matching/FormMatchingTable";
import { useMatchingState } from "../hooks/useMatchingState";
import { getMappedRepeatingForms, getMappedRepeatingEvents } from "../matching/matchingHelpers";
import { ARIA_DESCRIPTIONS } from "../constants";

function MatchStep({ value, onChange }) {
  const fieldMappings = value.fieldMappings;
  const projectStructure = value.projectStructure;
  const isLongitudinal = projectStructure.is_longitudinal;
  const matching = value.matching || {};

  // Use custom hook for state management
  const matchingState = useMatchingState({
    fieldMappings,
    matching,
    onChange,
  });

  // Compute derived data using helpers (memoized)
  const mappedRepeatingForms = useMemo(
    () => getMappedRepeatingForms(fieldMappings, projectStructure, isLongitudinal),
    [fieldMappings, projectStructure, isLongitudinal],
  );

  const mappedRepeatingEvents = useMemo(
    () => getMappedRepeatingEvents(fieldMappings, projectStructure),
    [fieldMappings, projectStructure],
  );

  return (
    <div role="region" aria-label={ARIA_DESCRIPTIONS.MATCHING_HELP}>
      <RecordMatchingSection
        recordMatching={matchingState.recordMatching}
        fieldMappings={fieldMappings}
        projectStructure={projectStructure}
        isLongitudinal={isLongitudinal}
        recordMatchingSelection={matchingState.recordMatchingSelection}
        setRecordMatchingSelection={matchingState.setRecordMatchingSelection}
        updateRecordMatching={matchingState.updateRecordMatching}
        eventFieldMappingIds={matchingState.eventFieldMappingIds}
        formFieldMappingIds={matchingState.formFieldMappingIds}
        enabledEvents={matchingState.enabledEvents}
        enabledForms={matchingState.enabledForms}
        mappingById={matchingState.mappingById}
      />

      <DagMatchingSection
        dagMatching={matchingState.dagMatching}
        projectStructure={projectStructure}
        csvFields={value.csvFields}
        updateDagMatching={matchingState.updateDagMatching}
      />

      <EventMatchingTable
        mappedRepeatingEvents={mappedRepeatingEvents}
        fieldMappings={fieldMappings}
        projectStructure={projectStructure}
        enabledEvents={matchingState.enabledEvents}
        selectedEventForms={matchingState.selectedEventForms}
        recordMatchingEnabled={matchingState.recordMatching.enabled ?? false}
        recordMatchingFieldMappingId={matchingState.recordMatchingFieldMappingId}
        eventFieldMappingIds={matchingState.eventFieldMappingIds}
        mappingById={matchingState.mappingById}
        toggleEventEnabled={matchingState.toggleEventEnabled}
        handleEventFormChange={matchingState.handleEventFormChange}
        updateEventFieldMappingId={matchingState.updateEventFieldMappingId}
      />

      <FormMatchingTable
        mappedRepeatingForms={mappedRepeatingForms}
        fieldMappings={fieldMappings}
        enabledForms={matchingState.enabledForms}
        recordMatchingEnabled={matchingState.recordMatching.enabled ?? false}
        recordMatchingFieldMappingId={matchingState.recordMatchingFieldMappingId}
        formFieldMappingIds={matchingState.formFieldMappingIds}
        mappingById={matchingState.mappingById}
        toggleFormEnabled={matchingState.toggleFormEnabled}
        updateFormFieldMappingId={matchingState.updateFormFieldMappingId}
      />
    </div>
  );
}

export default MatchStep;
