/**
 * Form matching table component.
 * Displays repeating forms with matching field selection.
 * Allows enabling/disabling matching per form and selecting matching fields.
 */

import {
  getMappedFieldsForForm,
  disableFieldsUsedForRecordMatching,
  getFormFieldMappingId,
} from "./matchingHelpers";
import { ARIA_LABELS } from "../constants";

function FormMatchingTable({
  mappedRepeatingForms,
  fieldMappings,
  enabledForms,
  recordMatchingEnabled,
  recordMatchingFieldMappingId,
  formFieldMappingIds,
  mappingById,
  toggleFormEnabled,
  updateFormFieldMappingId,
}) {
  if (mappedRepeatingForms.length === 0) {
    return null;
  }

  return (
    <div className="mt-4">
      <p className="fs-6">Repeating form matching fields</p>
      <div className="form-text">
        Select which field will be used to locate and update existing
        instances in repeating forms. If no matching field is selected for a
        form, new instances will always be created.
      </div>
      <table className="table" aria-label={ARIA_LABELS.REPEATING_FORMS_TABLE}>
        <thead>
          <tr>
            <th scope="col">Match</th>
            <th scope="col">Form</th>
            <th scope="col">Matching field</th>
          </tr>
        </thead>
        <tbody>
          {mappedRepeatingForms.map((form) => {
            const formName = form.repeatingFormName;
            const compositeKey = form.id; // eventName::formName
            const enabled = enabledForms[compositeKey] ?? false;
            const selectedFieldId = getFormFieldMappingId(
              formFieldMappingIds,
              mappingById,
              compositeKey,
            );

            const fields = disableFieldsUsedForRecordMatching(
              getMappedFieldsForForm(fieldMappings, form),
              recordMatchingEnabled,
              recordMatchingFieldMappingId,
            );

            return (
              <tr key={compositeKey}>
                <td>
                  <input
                    type="checkbox"
                    className="form-check-input"
                    checked={enabled}
                    onChange={(e) =>
                      toggleFormEnabled(compositeKey, e.target.checked)
                    }
                    aria-label={`${ARIA_LABELS.FORM_MATCHING_ENABLE} ${formName}`}
                  />
                </td>
                <td>
                  <div>{formName}</div>
                  {form.eventName && <div>({form.eventName})</div>}
                </td>
                <td>
                  <select
                    className="form-select"
                    value={selectedFieldId}
                    onChange={(e) =>
                      updateFormFieldMappingId(compositeKey, e.target.value)
                    }
                    disabled={!enabled}
                    required={enabled}
                  >
                    <option value="">Select field...</option>
                    {fields.map((field) => (
                      <option
                        key={field.id}
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
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

export default FormMatchingTable;
