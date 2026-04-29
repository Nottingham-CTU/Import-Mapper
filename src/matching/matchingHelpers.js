/**
 * Pure utility functions for matching configuration.
 * These functions have no React dependencies and are easily testable.
 *
 * ARCHITECTURAL NOTE: These helpers remain on the frontend (not moved to backend) because:
 *
 * 1. **Dynamic Data**: They operate on work-in-progress field mappings that change as the user edits.
 *    Moving to backend would require API calls on every keystroke/change = poor UX and performance.
 *
 * 2. **Performance**: Client-side computation with useMemo is faster than round-trip API calls.
 *    These computations are lightweight (filtering/mapping arrays) and benefit from memoization.
 *
 * 3. **Separation of Concerns**: These are presentation/UI helpers (filtering options for dropdowns).
 *    Backend handles validation (what's allowed), frontend handles presentation (what to show).
 *
 * 4. **Real-time Feedback**: Users need instant updates as they edit. Backend validation happens
 *    on save, frontend helpers provide immediate visual feedback.
 *
 * Backend validation (validateMatchingConfig) ensures data integrity on save.
 * Frontend helpers optimize UX by showing relevant options in real-time.
 */

import {
  getAllEvents,
  getFormsForEvent,
  isRepeatingEvent,
  isRepeatingEventForm,
  isRepeatingForm,
} from "./projectStructureHelpers";

/**
 * Converts field mappings to select option format.
 * @param {Array} relevantMappings - Array of field mapping objects
 * @returns {Array} Array of option objects with id, value, and label
 */
export function buildFieldOptions(relevantMappings) {
  return relevantMappings.map((m) => ({
    id: m.id,
    value: m.id,
    label: `${m.csvFieldName} = ${m.redcapFieldName}`,
  }));
}

/**
 * Builds a map of enabled events from field mapping IDs.
 * @param {Array} fieldMappingIds - Array of field mapping ID strings
 * @param {Map} mappingById - Map of field mappings by ID
 * @returns {Object} Object with event names as keys, true as values
 */
export function buildEnabledEvents(fieldMappingIds, mappingById) {
  const enabled = {};
  fieldMappingIds.forEach((id) => {
    const mapping = mappingById.get(id);
    if (mapping?.redcapEventName) {
      enabled[mapping.redcapEventName] = true;
    }
  });
  return enabled;
}

/**
 * Builds a map of enabled forms from field mapping IDs.
 * @param {Array} fieldMappingIds - Array of field mapping ID strings
 * @param {Map} mappingById - Map of field mappings by ID
 * @returns {Object} Object with composite keys (event::form) as keys, true as values
 */
export function buildEnabledForms(fieldMappingIds, mappingById) {
  const enabled = {};
  fieldMappingIds.forEach((id) => {
    const mapping = mappingById.get(id);
    if (mapping?.redcapFormName) {
      const compositeKey = `${mapping.redcapEventName ?? ""}::${mapping.redcapFormName}`;
      enabled[compositeKey] = true;
    }
  });
  return enabled;
}

/**
 * Gets all unique events that have field mappings.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @param {Object} projectStructure - ProjectStructure structure object
 * @returns {Array} Array of event names
 */
export function getEventsWithMappings(fieldMappings, projectStructure) {
  const mappedEventNames = new Set(
    fieldMappings.map((m) => m.redcapEventName).filter(Boolean),
  );
  return getAllEvents(projectStructure).filter((eventName) =>
    mappedEventNames.has(eventName),
  );
}

/**
 * Gets forms for a specific event that have field mappings.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @param {Object} projectStructure - ProjectStructure structure object
 * @param {string} eventName - Event name
 * @returns {Array} Array of form names
 */
export function getMappedFormsForEvent(fieldMappings, projectStructure, eventName) {
  if (!eventName) return [];
  const allFormsForEvent = getFormsForEvent(projectStructure, eventName);
  const mappedFormNames = new Set(
    fieldMappings
      .filter((m) => m.redcapEventName === eventName)
      .map((m) => m.redcapFormName),
  );
  return allFormsForEvent.filter((formName) => mappedFormNames.has(formName));
}

/**
 * Gets all unique forms that have field mappings.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @returns {Array} Array of form names
 */
export function getMappedFormNames(fieldMappings) {
  return [...new Set(fieldMappings.map((m) => m.redcapFormName))].filter(
    Boolean,
  );
}

/**
 * Gets forms available for a repeating event that have mapped fields.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @param {Object} projectStructure - ProjectStructure structure object
 * @param {Object} event - Event object with repeatingEventName property
 * @returns {Array} Array of form names
 */
export function getFormsForEventWithMappings(fieldMappings, projectStructure, event) {
  if (!event.repeatingEventName) {
    return [];
  }

  // Get all forms for this event
  const allForms = getFormsForEvent(
    projectStructure,
    event.repeatingEventName,
  );

  // Only return forms that have at least one mapped field for this event
  return allForms.filter((formName) => {
    return fieldMappings.some(
      (m) =>
        m.redcapEventName === event.repeatingEventName &&
        m.redcapFormName === formName,
    );
  });
}

/**
 * Gets field options for a specific event/form combination.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @param {Object} event - Event object with repeatingEventName property
 * @param {string} formName - Form name
 * @returns {Array} Array of field option objects
 */
export function getMappedFieldsForEventForm(fieldMappings, event, formName) {
  if (!event.repeatingEventName || !formName) return [];
  const relevantMappings = fieldMappings.filter(
    (m) =>
      m.redcapEventName === event.repeatingEventName &&
      m.redcapFormName === formName,
  );
  return buildFieldOptions(relevantMappings);
}

/**
 * Gets field options for a repeating form.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @param {Object} form - Form object with repeatingFormName and optional eventName
 * @returns {Array} Array of field option objects
 */
export function getMappedFieldsForForm(fieldMappings, form) {
  if (!form.repeatingFormName) return [];
  const relevantMappings = fieldMappings.filter((m) => {
    const formMatches = m.redcapFormName === form.repeatingFormName;
    const eventMatches = form.eventName
      ? m.redcapEventName === form.eventName
      : true;
    return formMatches && eventMatches;
  });
  return buildFieldOptions(relevantMappings);
}

/**
 * Gets record matching field options for selected event/form.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @param {Object} projectStructure - ProjectStructure structure object
 * @param {boolean} isLongitudinal - Whether project is longitudinal
 * @param {string} recordMatchingEvent - Selected event name
 * @param {string} recordMatchingForm - Selected form name
 * @returns {Array} Array of field option objects
 */
export function getRecordMatchingFieldOptions(
  fieldMappings,
  projectStructure,
  isLongitudinal,
  recordMatchingEvent,
  recordMatchingForm,
) {
  const relevantMappings = fieldMappings.filter((m) =>
    isLongitudinal
      ? m.redcapEventName === recordMatchingEvent &&
        m.redcapFormName === recordMatchingForm
      : m.redcapFormName === recordMatchingForm,
  );
  return buildFieldOptions(relevantMappings);
}

/**
 * Marks fields as disabled if used for record matching.
 * @param {Array} fields - Array of field option objects
 * @param {boolean} recordMatchingEnabled - Whether record matching is enabled
 * @param {string} recordMatchingFieldMappingId - ID of field used for record matching
 * @returns {Array} Array of field option objects with disabled property
 */
export function disableFieldsUsedForRecordMatching(
  fields,
  recordMatchingEnabled,
  recordMatchingFieldMappingId,
) {
  return fields.map((field) => ({
    ...field,
    disabled:
      recordMatchingEnabled && field.value === recordMatchingFieldMappingId,
    disabledReason: "used for record matching",
  }));
}

/**
 * Gets the field mapping ID for a specific event.
 * @param {Array} eventFieldMappingIds - Array of field mapping IDs for events
 * @param {Map} mappingById - Map of field mappings by ID
 * @param {string} eventName - Event name
 * @returns {string} Field mapping ID or empty string
 */
export function getEventFieldMappingId(eventFieldMappingIds, mappingById, eventName) {
  return (
    eventFieldMappingIds.find((id) => {
      const mapping = mappingById.get(id);
      return mapping?.redcapEventName === eventName;
    }) || ""
  );
}

/**
 * Gets the field mapping ID for a specific form (using composite key).
 * @param {Array} formFieldMappingIds - Array of field mapping IDs for forms
 * @param {Map} mappingById - Map of field mappings by ID
 * @param {string} compositeKey - Composite key in format "eventName::formName"
 * @returns {string} Field mapping ID or empty string
 */
export function getFormFieldMappingId(formFieldMappingIds, mappingById, compositeKey) {
  return (
    formFieldMappingIds.find((id) => {
      const mapping = mappingById.get(id);
      if (!mapping) return false;
      const mappingKey = `${mapping.redcapEventName ?? ""}::${mapping.redcapFormName}`;
      return mappingKey === compositeKey;
    }) || ""
  );
}

/**
 * Gets all mapped repeating forms.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @param {Object} projectStructure - ProjectStructure structure object
 * @param {boolean} isLongitudinal - Whether project is longitudinal
 * @returns {Array} Array of unique repeating form objects
 */
export function getMappedRepeatingForms(fieldMappings, projectStructure, isLongitudinal) {
  return [
    ...new Map(
      fieldMappings
        .filter((m) =>
          isLongitudinal
            ? isRepeatingEventForm(projectStructure, m.redcapEventName, m.redcapFormName)
            : isRepeatingForm(projectStructure, m.redcapFormName),
        )
        .map((m) => [
          `${m.redcapEventName ?? ""}::${m.redcapFormName}`,
          {
            id: `${m.redcapEventName ?? ""}::${m.redcapFormName}`,
            eventName: m.redcapEventName,
            repeatingFormName: m.redcapFormName,
          },
        ]),
    ).values(),
  ];
}

/**
 * Gets all mapped repeating events.
 * @param {Array} fieldMappings - Array of field mapping objects
 * @param {Object} projectStructure - ProjectStructure structure object
 * @returns {Array} Array of unique repeating event objects
 */
export function getMappedRepeatingEvents(fieldMappings, projectStructure) {
  return [
    ...new Map(
      fieldMappings
        .filter((m) => isRepeatingEvent(projectStructure, m.redcapEventName))
        .map((m) => [
          m.redcapEventName,
          { repeatingEventName: m.redcapEventName },
        ]),
    ).values(),
  ];
}
