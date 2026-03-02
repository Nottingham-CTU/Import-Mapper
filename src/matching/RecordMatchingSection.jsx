/**
 * Record matching configuration section.
 * Allows selecting which field will be used to locate and update existing records.
 * Includes event/form/field navigation for selecting the matching field.
 */

import {
  getEventsWithMappings,
  getMappedFormsForEvent,
  getMappedFormNames,
  getRecordMatchingFieldOptions,
} from "./matchingHelpers";
import { ARIA_LABELS } from "../constants";

function RecordMatchingSection({
  recordMatching,
  fieldMappings,
  projectStructure,
  isLongitudinal,
  recordMatchingSelection,
  setRecordMatchingSelection,
  updateRecordMatching,
  eventFieldMappingIds,
  formFieldMappingIds,
  enabledEvents,
  enabledForms,
  mappingById,
}) {
  const recordMatchingEnabled = recordMatching.enabled ?? false;
  const recordMatchingFieldMappingId = recordMatching.fieldMappingId || "";
  const recordMatchingEvent = recordMatchingSelection.event;
  const recordMatchingForm = recordMatchingSelection.form;

  // Build record matching field options with disabled state for instance matching
  const recordMatchingFieldOptions = (() => {
    const baseOptions = getRecordMatchingFieldOptions(
      fieldMappings,
      projectStructure,
      isLongitudinal,
      recordMatchingEvent,
      recordMatchingForm,
    );

    return baseOptions.map((field) => {
      // Check if field is used in any enabled instance matching
      const isUsedInEnabledEventMatching = eventFieldMappingIds.some((id) => {
        if (id !== field.value) return false;
        const mapping = mappingById.get(id);
        return Boolean(mapping && enabledEvents[mapping.redcapEventName]);
      });
      const isUsedInEnabledFormMatching = formFieldMappingIds.some((id) => {
        if (id !== field.value) return false;
        const mapping = mappingById.get(id);
        if (!mapping) return false;
        const compositeKey = `${mapping.redcapEventName ?? ""}::${mapping.redcapFormName}`;
        return Boolean(enabledForms[compositeKey]);
      });

      return {
        ...field,
        disabled: isUsedInEnabledEventMatching || isUsedInEnabledFormMatching,
        disabledReason: "used for instance matching",
      };
    });
  })();

  return (
    <div>
      <label className="form-label">Record matching field</label>
      <div className="form-text">
        Select which field will be used to locate and update existing records.
        If no matching field is selected, new records will always be created.
      </div>
      <table className="table">
        <tbody>
          <tr>
            <td>
              <input
                type="checkbox"
                className="form-check-input"
                checked={recordMatchingEnabled}
                onChange={(e) => {
                  const enabled = e.target.checked;
                  updateRecordMatching({
                    enabled,
                    fieldMappingId: enabled
                      ? recordMatchingFieldMappingId
                      : "",
                  });
                  if (!enabled) {
                    setRecordMatchingSelection({ event: "", form: "" });
                  }
                }}
                aria-label={ARIA_LABELS.RECORD_MATCHING_ENABLE}
              />
            </td>
            {isLongitudinal && (
              <td>
                <select
                  className="form-select"
                  value={recordMatchingEvent}
                  onChange={(e) => {
                    // Update navigation event, clear form and field
                    setRecordMatchingSelection({
                      event: e.target.value,
                      form: "",
                    });
                    updateRecordMatching({ fieldMappingId: "" });
                  }}
                  disabled={!recordMatchingEnabled}
                  required={recordMatchingEnabled}
                >
                  <option value="">Select event...</option>
                  {getEventsWithMappings(fieldMappings, projectStructure).map((eventName) => (
                    <option key={eventName} value={eventName}>
                      {eventName}
                    </option>
                  ))}
                </select>
              </td>
            )}
            <td>
              <select
                className="form-select"
                value={recordMatchingForm}
                onChange={(e) => {
                  // Update navigation form, clear field
                  setRecordMatchingSelection({
                    event: recordMatchingEvent,
                    form: e.target.value,
                  });
                  updateRecordMatching({ fieldMappingId: "" });
                }}
                disabled={
                  !recordMatchingEnabled ||
                  (isLongitudinal && !recordMatchingEvent)
                }
                required={recordMatchingEnabled}
              >
                <option value="">Select form...</option>
                {(isLongitudinal
                  ? getMappedFormsForEvent(fieldMappings, projectStructure, recordMatchingEvent)
                  : getMappedFormNames(fieldMappings)
                ).map((formName) => (
                  <option key={formName} value={formName}>
                    {formName}
                  </option>
                ))}
              </select>
            </td>
            <td>
              <select
                className="form-select"
                value={recordMatchingFieldMappingId}
                onChange={(e) => {
                  const fieldId = e.target.value;
                  updateRecordMatching({ fieldMappingId: fieldId });

                  // Sync navigation to match selected field's mapping
                  if (fieldId) {
                    const selectedMapping = fieldMappings.find(
                      (m) => m.id === fieldId,
                    );
                    if (selectedMapping) {
                      setRecordMatchingSelection({
                        event: selectedMapping.redcapEventName || "",
                        form: selectedMapping.redcapFormName || "",
                      });
                    }
                  }
                }}
                disabled={!recordMatchingEnabled || !recordMatchingForm}
                required={recordMatchingEnabled}
              >
                <option value="">Select field...</option>
                {recordMatchingFieldOptions.map((field) => (
                  <option
                    key={field.value}
                    value={field.value}
                    disabled={field.disabled}
                  >
                    {field.label}
                    {field.disabled && ` (${field.disabledReason})`}
                  </option>
                ))}
              </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  );
}

export default RecordMatchingSection;
