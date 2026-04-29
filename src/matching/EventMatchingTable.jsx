/**
 * Event matching table component.
 * Displays repeating events with matching field selection.
 * Allows enabling/disabling matching per event and selecting form/field combinations.
 */

import {
  getFormsForEventWithMappings,
  getMappedFieldsForEventForm,
  disableFieldsUsedForRecordMatching,
  getEventFieldMappingId,
} from "./matchingHelpers";
import { ARIA_LABELS } from "../constants";

function EventMatchingTable({
  mappedRepeatingEvents,
  fieldMappings,
  projectStructure,
  enabledEvents,
  selectedEventForms,
  recordMatchingEnabled,
  recordMatchingFieldMappingId,
  eventFieldMappingIds,
  mappingById,
  toggleEventEnabled,
  handleEventFormChange,
  updateEventFieldMappingId,
}) {
  if (mappedRepeatingEvents.length === 0) {
    return null;
  }

  return (
    <div className="mt-4">
      <p className="fs-6">Repeating event matching fields</p>
      <div className="form-text">
        Select which field will be used to locate and update existing
        instances in repeating events. If no matching field is selected for
        an event, new instances will always be created.
      </div>
      <table className="table" aria-label={ARIA_LABELS.REPEATING_EVENTS_TABLE}>
        <thead>
          <tr>
            <th scope="col">Match</th>
            <th scope="col">Event</th>
            <th scope="col">Form</th>
            <th scope="col">Matching field</th>
          </tr>
        </thead>
        <tbody>
          {mappedRepeatingEvents.map((event) => {
            const eventName = event.repeatingEventName;
            const selectedForm = selectedEventForms[eventName];
            const enabled = enabledEvents[eventName] ?? false;
            const availableForms = getFormsForEventWithMappings(
              fieldMappings,
              projectStructure,
              event,
            );
            const selectedFieldId = getEventFieldMappingId(
              eventFieldMappingIds,
              mappingById,
              eventName,
            );

            const fields = selectedForm
              ? disableFieldsUsedForRecordMatching(
                  getMappedFieldsForEventForm(fieldMappings, event, selectedForm),
                  recordMatchingEnabled,
                  recordMatchingFieldMappingId,
                )
              : [];

            return (
              <tr key={eventName}>
                <td>
                  <input
                    type="checkbox"
                    className="form-check-input"
                    checked={enabled}
                    onChange={(e) =>
                      toggleEventEnabled(eventName, e.target.checked)
                    }
                    aria-label={`${ARIA_LABELS.EVENT_MATCHING_ENABLE} ${eventName}`}
                  />
                </td>
                <td>{eventName}</td>
                <td>
                  <select
                    className="form-select"
                    value={selectedForm || ""}
                    onChange={(e) =>
                      handleEventFormChange(eventName, e.target.value)
                    }
                    disabled={!enabled}
                    required={enabled}
                  >
                    <option value="">Select form...</option>
                    {availableForms.map((form) => (
                      <option key={form} value={form}>
                        {form}
                      </option>
                    ))}
                  </select>
                </td>
                <td>
                  <select
                    className="form-select"
                    value={selectedFieldId}
                    onChange={(e) =>
                      updateEventFieldMappingId(eventName, e.target.value)
                    }
                    disabled={!enabled || !selectedForm}
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

export default EventMatchingTable;
