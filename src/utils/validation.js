/**
 * Validation utilities
 * Centralized validation logic for forms, mappings, and data
 */

import { VALIDATION, VALIDATION_ERRORS } from "../constants";
import {
  getAllEvents,
  getAllForms,
  getFormsForEvent,
} from "../matching/projectStructureHelpers";

/**
 * Validates mapping name
 * @param {string} name - Mapping name to validate
 * @returns {string|null} Error message or null if valid
 */
export function validateMappingName(name) {
  if (!name || name.trim().length === 0) {
    return VALIDATION_ERRORS.NAME_REQUIRED;
  }

  if (name.length > VALIDATION.MAX_NAME_LENGTH) {
    return VALIDATION_ERRORS.NAME_TOO_LONG(VALIDATION.MAX_NAME_LENGTH);
  }

  return null;
}

/**
 * Validates a single field mapping against project structure
 * @param {Object} mapping - Field mapping to validate
 * @param {Object} projectStructure - ProjectStructure structure
 * @param {boolean} isLongitudinal - Whether project is longitudinal
 * @returns {Object} Validation issues { event, form, field }
 */
export function validateFieldMapping(mapping, projectStructure, isLongitudinal) {
  const issues = {
    event: null,
    form: null,
    field: null,
  };

  // Check if event exists (longitudinal only)
  if (isLongitudinal && mapping.redcapEventName) {
    const eventExists = getAllEvents(projectStructure).includes(
      mapping.redcapEventName,
    );
    if (!eventExists) {
      issues.event = VALIDATION_ERRORS.EVENT_NOT_FOUND(mapping.redcapEventName);
    }
  }

  // Check if form exists
  if (mapping.redcapFormName) {
    const availableForms = isLongitudinal
      ? getFormsForEvent(projectStructure, mapping.redcapEventName)
      : getAllForms(projectStructure);

    const formExists = availableForms.includes(mapping.redcapFormName);
    if (!formExists) {
      issues.form = VALIDATION_ERRORS.FORM_NOT_FOUND(
        mapping.redcapFormName,
        isLongitudinal && mapping.redcapEventName ? mapping.redcapEventName : null,
      );
    }
  }

  // Check if field exists
  if (mapping.redcapFieldName && mapping.redcapFormName) {
    const availableFields =
      projectStructure.fields_by_form?.[mapping.redcapFormName] || [];
    const fieldExists = availableFields.includes(mapping.redcapFieldName);
    if (!fieldExists) {
      issues.field = VALIDATION_ERRORS.FIELD_NOT_FOUND(
        mapping.redcapFieldName,
        mapping.redcapFormName,
      );
    }
  }

  return issues;
}

/**
 * Checks if any field mappings have validation issues
 * @param {Array} mappings - Array of field mappings
 * @param {Object} projectStructure - ProjectStructure structure
 * @param {boolean} isLongitudinal - Whether project is longitudinal
 * @returns {boolean} True if any issues found
 */
export function hasValidationIssues(mappings, projectStructure, isLongitudinal) {
  return mappings.some((mapping) => {
    const issues = validateFieldMapping(mapping, projectStructure, isLongitudinal);
    return issues.event || issues.form || issues.field;
  });
}

/**
 * Validates CSV file before processing
 * @param {File} file - File to validate
 * @returns {Object} { valid: boolean, error: string|null }
 */
export function validateCSVFile(file) {
  if (!file) {
    return { valid: false, error: "No file selected" };
  }

  // Check file type
  const validTypes = ["text/csv", "application/vnd.ms-excel"];
  const hasValidExtension = file.name.toLowerCase().endsWith(".csv");

  if (!validTypes.includes(file.type) && !hasValidExtension) {
    return { valid: false, error: "File must be a CSV file" };
  }

  // Check file size (max 10MB)
  const maxSize = 10 * 1024 * 1024; // 10MB
  if (file.size > maxSize) {
    return { valid: false, error: "File size must be less than 10MB" };
  }

  return { valid: true, error: null };
}

/**
 * Validates that required fields exist in CSV headers
 * @param {string[]} headers - CSV headers
 * @param {string[]} requiredFields - Required field names
 * @returns {Object} { valid: boolean, missingFields: string[] }
 */
export function validateRequiredFields(headers, requiredFields) {
  const missingFields = requiredFields.filter((field) => !headers.includes(field));

  return {
    valid: missingFields.length === 0,
    missingFields,
  };
}

/**
 * Checks if a string is empty or whitespace only
 * @param {string} str - String to check
 * @returns {boolean} True if empty or whitespace
 */
export function isEmpty(str) {
  return !str || str.trim().length === 0;
}

/**
 * Validates mapping status
 * @param {string} status - Status to validate
 * @returns {boolean} True if valid status
 */
export function isValidStatus(status) {
  return status === "draft" || status === "final";
}
