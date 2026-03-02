/**
 * Application-wide constants
 * Centralizes magic strings and numbers for better maintainability
 */

// Mapping statuses
export const MAPPING_STATUS = {
  DRAFT: "draft",
  FINAL: "final",
};

// Import statuses
export const IMPORT_STATUS = {
  QUEUED:      "queued",
  IN_PROGRESS: "in_progress",
  COMPLETED:   "completed",
  FAILED: "failed",
  CANCELLED:   "cancelled",
};

// Import outcomes (set when status is completed or failed)
export const IMPORT_OUTCOME = {
  CLEAN:    "clean",
  WARNINGS: "warnings",
  ERRORS:   "errors",
};

// Wizard steps
export const WIZARD_STEPS = {
  NAME: 1,
  UPLOAD: 2,
  MAP: 3,
  MATCH: 4,
  TOTAL: 4,
};

// Validation limits
export const VALIDATION = {
  MAX_NAME_LENGTH: 255,
  MIN_NAME_LENGTH: 1,
  CSV_PREVIEW_ROWS: 5,
};

// File types
export const FILE_TYPES = {
  CSV: {
    ACCEPT: ".csv,text/csv",
    MIME: "text/csv",
  },
};

// Error types for CSV parsing
export const ERROR_TYPES = {
  EXPANDABLE: "expandable",
  SIMPLE: "simple",
};

// CSV affects types
export const AFFECTS_TYPES = {
  ROWS: "rows",
  COLUMNS: "columns",
  HEADERS: "headers",
};

// Default matching configuration
export const DEFAULT_MATCHING = {
  record: {
    enabled: false,
    fieldMappingId: "",
  },
  form: {
    enabled: false,
    fieldMappingIds: [],
  },
  event: {
    enabled: false,
    fieldMappingIds: [],
  },
  dag: {
    enabled: false,
    mode: "",
    dagUniqueName: "",
    csvFieldName: "",
  },
};

// DAG matching modes
export const DAG_MODES = {
  SAME_FOR_ALL: "same_for_all",
  CSV_FIELD: "csv_field",
};

// Modal IDs
export const MODAL_IDS = {
  CONFIRM_NAV: "confirm-nav-modal",
  STRUCTURE_CHANGED: "structure-changed-modal",
  OVERWRITE_CSV: "overwrite-csv-modal",
  EMPTY_HEADER: "empty-header-modal",
};

// Bootstrap classes for alerts
export const ALERT_TYPES = {
  SUCCESS: "alert-success",
  ERROR: "alert-danger",
  WARNING: "alert-warning",
  INFO: "alert-info",
};

// Button classes
export const BUTTON_TYPES = {
  PRIMARY: "btn-primary",
  SECONDARY: "btn-secondary",
  SUCCESS: "btn-success",
  DANGER: "btn-danger",
  WARNING: "btn-warning",
  INFO: "btn-info",
  OUTLINE_PRIMARY: "btn-primary",
  OUTLINE_SECONDARY: "btn-secondary",
  OUTLINE_DANGER: "btn-danger",
  OUTLINE_INFO: "btn-info",
};

// ARIA labels and descriptions
export const ARIA_LABELS = {
  // Navigation
  BACK_BUTTON: "Go back to previous step",
  CONTINUE_BUTTON: "Continue to next step",
  CANCEL_BUTTON: "Cancel and return to mappings list",
  SAVE_BUTTON: "Save mapping and return to list",
  SAVE_DRAFT_BUTTON: "Save current progress as draft",

  // Wizard
  STEP_INDICATOR: "Mapping wizard progress",
  CURRENT_STEP: "Current step",

  // File upload
  CSV_FILE_INPUT: "Select CSV file to upload",
  SAMPLE_CSV_INPUT: "Select sample CSV file to extract field names",

  // Import
  START_IMPORT_BUTTON: "Begin importing data",
  IMPORT_IN_PROGRESS: "Import operation in progress",

  // Modals
  MODAL_CLOSE: "Close dialog",

  // Loading states
  LOADING_MAPPING: "Loading mapping data",
  LOADING_STRUCTURE: "Loading project structure",

  // Tables
  FIELD_MAPPINGS_TABLE: "Field mappings configuration",
  REPEATING_EVENTS_TABLE: "Repeating event matching fields",
  REPEATING_FORMS_TABLE: "Repeating form matching fields",

  // Forms
  MAPPING_NAME_INPUT: "Mapping name",
  RECORD_MATCHING_ENABLE: "Enable record matching",
  EVENT_MATCHING_ENABLE: "Enable matching for this event",
  FORM_MATCHING_ENABLE: "Enable matching for this form",
  DAG_MATCHING_ENABLE: "Enable Data Access Group assignment",
};

// ARIA descriptions
export const ARIA_DESCRIPTIONS = {
  MAPPING_NAME_HELP: "Enter a unique name to identify this mapping configuration",
  CSV_UPLOAD_HELP: "Upload a sample CSV file to extract column headers",
  FIELD_MAPPING_HELP: "Map CSV columns to REDCap fields",
  MATCHING_HELP: "Configure how records and instances should be matched during import",
  DAG_ASSIGNMENT_HELP: "Configure Data Access Group assignment for imported records",
};

// Validation error messages
export const VALIDATION_ERRORS = {
  NAME_REQUIRED: "Name is required",
  NAME_TOO_LONG: (maxLength) => `Name must be ${maxLength} characters or less`,
  CSV_EMPTY: "CSV file is empty",
  CSV_READ_FAILED: "Failed to read file. Please try again.",
  CSV_PARSE_FAILED: (message) => `Failed to parse CSV: ${message}`,
  CSV_MISSING_FIELDS: (fields) => `CSV is missing required fields: ${fields.join(", ")}`,
  CSV_EMPTY_HEADERS: "Some column headers are empty. Please ensure every column has a name.",
  EVENT_NOT_FOUND: (eventName) => `Event '${eventName}' no longer exists`,
  FORM_NOT_FOUND: (formName, eventName) =>
    eventName
      ? `Form '${formName}' no longer exists in event '${eventName}'`
      : `Form '${formName}' no longer exists`,
  FIELD_NOT_FOUND: (fieldName, formName) =>
    `Field '${fieldName}' no longer exists in form '${formName}'`,
};

// Success messages
export const SUCCESS_MESSAGES = {
  MAPPING_SAVED: "Mapping saved successfully",
  IMPORT_COMPLETE: "Import completed successfully",
  CSV_UPLOADED: "Sample CSV uploaded",
};

// Warning messages
export const WARNING_MESSAGES = {
  UNSAVED_CHANGES: "You have unsaved changes",
  STRUCTURE_CHANGED: "ProjectStructure structure has changed",
  NO_DAGS: "This project does not have any Data Access Groups defined",
  DRAFT_STATUS: "This mapping is in draft status and cannot be used for import",
};