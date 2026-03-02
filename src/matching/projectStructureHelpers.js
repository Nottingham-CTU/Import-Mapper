/**
 * Helper functions for accessing and querying REDCap project structure data.
 * These functions provide consistent null-safe access to project structure properties.
 */

/**
 * Gets all event names in the project.
 * @param {Object} projectStructure - The project structure object
 * @returns {Array<string>} Array of event names
 */
export function getAllEvents(projectStructure) {
  return Object.keys(projectStructure?.all_event_names || {});
}

/**
 * Gets all form names in the project.
 * @param {Object} projectStructure - The project structure object
 * @returns {Array<string>} Array of form names
 */
export function getAllForms(projectStructure) {
  return projectStructure?.all_form_names || [];
}

/**
 * Gets all forms available for a specific event.
 * @param {Object} projectStructure - The project structure object
 * @param {string} eventName - The event name
 * @returns {Array<string>} Array of form names for the event
 */
export function getFormsForEvent(projectStructure, eventName) {
  return projectStructure?.all_form_names_by_event?.[eventName] || [];
}

/**
 * Gets all fields for a specific form.
 * @param {Object} projectStructure - The project structure object
 * @param {string} formName - The form name
 * @returns {Array<string>} Array of field names for the form
 */
export function getFieldsForForm(projectStructure, formName) {
  return projectStructure?.fields_by_form?.[formName] || [];
}

/**
 * Checks if an event is configured as repeating.
 * @param {Object} projectStructure - The project structure object
 * @param {string} eventName - The event name to check
 * @returns {boolean} True if the event is repeating
 */
export function isRepeatingEvent(projectStructure, eventName) {
  return projectStructure?.repeating_event_names?.includes(eventName) || false;
}

/**
 * Checks if a form is configured as repeating within an event.
 * Forms can only be repeating within non-repeating events.
 * @param {Object} projectStructure - The project structure object
 * @param {string} eventName - The event name
 * @param {string} formName - The form name to check
 * @returns {boolean} True if the form is repeating in the given event
 */
export function isRepeatingEventForm(projectStructure, eventName, formName) {
  if (!eventName || !formName) return false;
  // Forms can only be repeating within non-repeating events
  if (isRepeatingEvent(projectStructure, eventName)) return false;
  return (
    projectStructure?.repeating_forms_by_event?.[eventName]?.includes(
      formName,
    ) || false
  );
}

export function isRepeatingForm(projectStructure, formName) {
  if (!formName) return false;
  return projectStructure?.repeating_forms?.includes(formName) || false;
}
