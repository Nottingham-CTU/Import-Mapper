/**
 * Custom hook for managing matching configuration state.
 * Encapsulates complex state management, auto-cleanup logic, and update functions.
 */

import { useEffect, useState } from "react";
import { buildEnabledEvents, buildEnabledForms } from "../matching/matchingHelpers";

/**
 * Manages all state and logic for matching configuration.
 * @param {Object} params - Hook parameters
 * @param {Array} params.fieldMappings - Array of field mapping objects
 * @param {Object} params.matching - Current matching configuration
 * @param {Function} params.onChange - Callback to update matching configuration
 * @returns {Object} State and update functions
 */
export function useMatchingState({
  fieldMappings,
  matching,
  onChange,
}) {
  const recordMatching = matching.record || {};
  const eventMatching = matching.event || {};
  const formMatching = matching.form || {};
  const dagMatching = matching.dag || {};
  const eventFieldMappingIds = eventMatching.fieldMappingIds || [];
  const formFieldMappingIds = formMatching.fieldMappingIds || [];
  const recordMatchingFieldMappingId = recordMatching.fieldMappingId || "";

  const mappingById = new Map(
    fieldMappings.map((mapping) => [mapping.id, mapping]),
  );

  // Separate UI state for event/form navigation (not persisted in matching)
  const [recordMatchingSelection, setRecordMatchingSelection] = useState(() => {
    // Initialize from selected field's mapping if it exists
    const selectedMapping = fieldMappings.find(
      (m) => m.id === recordMatchingFieldMappingId,
    );
    return {
      event: selectedMapping?.redcapEventName || "",
      form: selectedMapping?.redcapFormName || "",
    };
  });

  const [enabledEvents, setEnabledEvents] = useState(() =>
    buildEnabledEvents(eventFieldMappingIds, mappingById),
  );

  const [enabledForms, setEnabledForms] = useState(() =>
    buildEnabledForms(formFieldMappingIds, mappingById),
  );

  // Derive selected forms for events from event field mappings
  const [selectedEventForms, setSelectedEventForms] = useState(() => {
    const selections = {};
    eventFieldMappingIds.forEach((fieldId) => {
      const mapping = mappingById.get(fieldId);
      if (mapping) {
        selections[mapping.redcapEventName] = mapping.redcapFormName;
      }
    });
    return selections;
  });

  // Simplified: No auto-cleanup - backend validation will catch invalid references
  // This makes the UI more transparent: users see what they selected and get
  // clear error messages from backend if something is invalid.
  // The complex auto-cleanup logic has been removed in favor of server-side validation.

  // Reset navigation state when mapping changes (e.g., switching between mappings)
  useEffect(() => {
    setEnabledEvents(buildEnabledEvents(eventFieldMappingIds, mappingById));
    setEnabledForms(buildEnabledForms(formFieldMappingIds, mappingById));
    const selections = {};
    eventFieldMappingIds.forEach((fieldId) => {
      const mapping = mappingById.get(fieldId);
      if (mapping) {
        selections[mapping.redcapEventName] = mapping.redcapFormName;
      }
    });
    setSelectedEventForms(selections);
  }, [matching.id]);

  // Sync record matching selection when field mapping changes
  useEffect(() => {
    if (!recordMatchingFieldMappingId) {
      setRecordMatchingSelection({ event: "", form: "" });
      return;
    }
    const selectedMapping = mappingById.get(recordMatchingFieldMappingId);
    if (selectedMapping) {
      setRecordMatchingSelection({
        event: selectedMapping.redcapEventName || "",
        form: selectedMapping.redcapFormName || "",
      });
    }
  }, [recordMatchingFieldMappingId, fieldMappings]);

  // Update functions
  function updateRecordMatching(patch) {
    const nextRecord = { ...recordMatching, ...patch };
    onChange({ ...matching, record: nextRecord });
  }

  function updateEventMatching(patch) {
    const nextEvent = { ...eventMatching, ...patch };
    const fieldMappingIds = nextEvent.fieldMappingIds || [];
    const enabled =
      typeof patch.enabled === "boolean"
        ? patch.enabled
        : fieldMappingIds.length > 0
          ? true
          : (nextEvent.enabled ?? false);
    onChange({
      ...matching,
      event: { ...nextEvent, enabled, fieldMappingIds },
    });
  }

  function updateFormMatching(patch) {
    const nextForm = { ...formMatching, ...patch };
    const fieldMappingIds = nextForm.fieldMappingIds || [];
    const enabled =
      typeof patch.enabled === "boolean"
        ? patch.enabled
        : fieldMappingIds.length > 0
          ? true
          : (nextForm.enabled ?? false);
    onChange({
      ...matching,
      form: { ...nextForm, enabled, fieldMappingIds },
    });
  }

  function updateDagMatching(patch) {
    const nextDag = { ...dagMatching, ...patch };
    onChange({ ...matching, dag: nextDag });
  }

  function toggleEventEnabled(eventName, enabled) {
    const nextEnabledEvents = { ...enabledEvents, [eventName]: enabled };
    setEnabledEvents(nextEnabledEvents);
    const nextIds = enabled
      ? eventFieldMappingIds
      : eventFieldMappingIds.filter((id) => {
          const mapping = mappingById.get(id);
          return mapping?.redcapEventName !== eventName;
        });
    updateEventMatching({
      fieldMappingIds: nextIds,
      enabled: Object.values(nextEnabledEvents).some(Boolean),
    });
  }

  function toggleFormEnabled(compositeKey, enabled) {
    const nextEnabledForms = { ...enabledForms, [compositeKey]: enabled };
    setEnabledForms(nextEnabledForms);
    const nextIds = enabled
      ? formFieldMappingIds
      : formFieldMappingIds.filter((id) => {
          const mapping = mappingById.get(id);
          if (!mapping) return false;
          const mappingKey = `${mapping.redcapEventName ?? ""}::${mapping.redcapFormName}`;
          return mappingKey !== compositeKey;
        });
    updateFormMatching({
      fieldMappingIds: nextIds,
      enabled: Object.values(nextEnabledForms).some(Boolean),
    });
  }

  function updateEventFieldMappingId(eventName, fieldId) {
    const nextIds = eventFieldMappingIds.filter((id) => {
      const mapping = mappingById.get(id);
      return mapping?.redcapEventName !== eventName;
    });
    if (fieldId) {
      nextIds.push(fieldId);
    }
    const nextEnabledEvents = {
      ...enabledEvents,
      [eventName]: enabledEvents[eventName] || Boolean(fieldId),
    };
    setEnabledEvents(nextEnabledEvents);
    updateEventMatching({
      fieldMappingIds: nextIds,
      enabled: Object.values(nextEnabledEvents).some(Boolean),
    });
  }

  function updateFormFieldMappingId(compositeKey, fieldId) {
    const nextIds = formFieldMappingIds.filter((id) => {
      const mapping = mappingById.get(id);
      if (!mapping) return false;
      const mappingKey = `${mapping.redcapEventName ?? ""}::${mapping.redcapFormName}`;
      return mappingKey !== compositeKey;
    });
    if (fieldId) {
      nextIds.push(fieldId);
    }
    const nextEnabledForms = {
      ...enabledForms,
      [compositeKey]: enabledForms[compositeKey] || Boolean(fieldId),
    };
    setEnabledForms(nextEnabledForms);
    updateFormMatching({
      fieldMappingIds: nextIds,
      enabled: Object.values(nextEnabledForms).some(Boolean),
    });
  }

  // Updates selected form for an event and clears field if form is cleared
  function handleEventFormChange(eventName, formName) {
    // Update local UI state
    setSelectedEventForms({
      ...selectedEventForms,
      [eventName]: formName,
    });

    updateEventFieldMappingId(eventName, "");
  }

  return {
    recordMatchingSelection,
    setRecordMatchingSelection,
    enabledEvents,
    setEnabledEvents,
    enabledForms,
    setEnabledForms,
    selectedEventForms,
    setSelectedEventForms,
    mappingById,
    recordMatching,
    eventMatching,
    formMatching,
    dagMatching,
    eventFieldMappingIds,
    formFieldMappingIds,
    recordMatchingFieldMappingId,
    updateRecordMatching,
    updateEventMatching,
    updateFormMatching,
    updateDagMatching,
    toggleEventEnabled,
    toggleFormEnabled,
    updateEventFieldMappingId,
    updateFormFieldMappingId,
    handleEventFormChange,
  };
}
