import { useState, useEffect } from "react";
import {
  getAllEvents,
  getAllForms,
  getFormsForEvent,
  getFieldsForForm,
  isRepeatingEvent,
  isRepeatingEventForm,
  isRepeatingForm,
} from "../matching/projectStructureHelpers";
import { mappingApi } from "../api/mappingApi";
import FieldMappingTransformsModal from "../matching/FieldMappingTransformsModal";

function MapStep({ value, onChange, onProjectStructureLoad }) {
  // Creates a new mapping with canonical field order
  function createEmptyMapping() {
    return {
      id: "",
      csvFieldName: "",
      redcapEventName: null,
      redcapFormName: "",
      redcapFieldName: "",
    };
  }

  const mappings =
    value.fieldMappings.length > 0
      ? value.fieldMappings
      : [createEmptyMapping()];
  const projectStructure = value.projectStructure;
  const isLongitudinal = projectStructure.is_longitudinal;
  const [loading, setLoading] = useState(true);
  const [optionsModalOpen, setOptionsModalOpen] = useState(false);
  const [currentFieldMappingId, setCurrentFieldMappingId] = useState(null);

  function getMappingId(csvFieldName, eventName, formName, fieldName) {
    return isLongitudinal
      ? `${csvFieldName}::${eventName}::${fieldName}`
      : `${csvFieldName}::${fieldName}`;
  }

  function validateMapping(mapping) {
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
        issues.event = `Event '${mapping.redcapEventName}' no longer exists`;
      }
    }

    // Check if form exists
    if (mapping.redcapFormName) {
      const availableForms = isLongitudinal
        ? getFormsForEvent(projectStructure, mapping.redcapEventName)
        : getAllForms(projectStructure);

      const formExists = availableForms.includes(mapping.redcapFormName);
      if (!formExists) {
        issues.form = `Form '${mapping.redcapFormName}' no longer exists${isLongitudinal && mapping.redcapEventName ? ` in event '${mapping.redcapEventName}'` : ""}`;
      }
    }

    // Check if field exists
    if (mapping.redcapFieldName && mapping.redcapFormName) {
      const availableFields =
        projectStructure.fields_by_form?.[mapping.redcapFormName] || [];
      const fieldExists = availableFields.includes(mapping.redcapFieldName);
      if (!fieldExists) {
        issues.field = `Field '${mapping.redcapFieldName}' no longer exists in form '${mapping.redcapFormName}'`;
      }
    }

    return issues;
  }

  // Helper to check if given mappings have validation issues
  function checkHasIssues(mappingsToCheck) {
    return mappingsToCheck.some((mapping) => {
      const issues = validateMapping(mapping);
      return issues.event || issues.form || issues.field;
    });
  }

  // Load project structure once on mount
  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => {
    async function fetchProjectStructure() {
      try {
        const projectStructure = await mappingApi.getProjectStructure();
        // Use separate callback for initial load - doesn't trigger unsavedChanges
        onProjectStructureLoad(projectStructure, checkHasIssues(mappings));
        setLoading(false);
      } catch (error) {
        // Error already logged in API layer
        alert(error.message);
        setLoading(false);
      }
    }
    fetchProjectStructure();
    // Intentionally empty: only fetch once on mount, not when mappings/callbacks change
  }, []);

  function addRow() {
    const newMappings = [...mappings, createEmptyMapping()];
    onChange(newMappings, projectStructure, checkHasIssues(newMappings));
  }

  function deleteRow(index) {
    if (mappings.length > 1) {
      const newMappings = mappings.filter((_, i) => i !== index);
      onChange(newMappings, projectStructure, checkHasIssues(newMappings));
    }
  }

  function updateRow(index, patch) {
    const updated = mappings.map((row, i) => {
      if (i !== index) return row;
      const newRow = { ...row, ...patch };
      // Recompute ID whenever any field changes
      newRow.id = getMappingId(
        newRow.csvFieldName,
        newRow.redcapEventName,
        newRow.redcapFormName,
        newRow.redcapFieldName,
      );
      return newRow;
    });
    onChange(updated, projectStructure, checkHasIssues(updated));
  }

  // Checks if a specific field combination would create a duplicate mapping
  function isAlreadyMapped(
    currentIndex,
    csvFieldName,
    eventName,
    formName,
    fieldName,
  ) {
    const potentialId = getMappingId(
      csvFieldName,
      eventName,
      formName,
      fieldName,
    );
    return mappings.some((m, i) => i !== currentIndex && m.id === potentialId);
  }

  function handleSaveOptions(fieldMappingId, options) {
    const updatedMappings = mappings.map((fm) =>
      fm.id === fieldMappingId
        ? {
            ...fm,
            dateFormat: options.dateFormat,
            valueMappings: options.valueMappings,
            fieldRegex: options.fieldRegex,
            combineFields: options.combineFields
          }
        : fm
    );

    onChange(updatedMappings, projectStructure, checkHasIssues(updatedMappings));
    setOptionsModalOpen(false);
  }

  // Calculate if any mappings have validation issues
  const hasIssues = checkHasIssues(mappings);

  if (loading) {
    return <p>Loading project structure...</p>;
  }

  return (
    <div>
      {hasIssues && (
        <small className="text-danger">
          This mapping contains fields that no longer exist in the project or
          have been renamed. Please update or remove these mappings before
          continuing.
        </small>
      )}
      <table className="table">
        <thead>
          <tr>
            <th>CSV</th>
            <th style={{ width: "50px" }}></th>
            <th>REDCap</th>
            <th className="text-center">Transforms</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {mappings.map((mapping, index) => {
            const validationIssues = validateMapping(mapping);
            // Use mapping ID if available, otherwise fall back to index for new rows
            const rowKey = mapping.id || `new-mapping-${index}`;

            return (
              <tr key={rowKey}>
                <td>
                  {mapping.combineFields?.enabled ? (
                    <div>
                      <select
                        className="form-select"
                        value={mapping.csvFieldName}
                        onChange={(e) =>
                          updateRow(index, { csvFieldName: e.target.value })
                        }
                        required
                      >
                        <option value="">Select field...</option>
                        {value.csvFields.map((csvField) => {
                          const isDuplicate = isAlreadyMapped(
                            index,
                            csvField,
                            mapping.redcapEventName,
                            mapping.redcapFormName,
                            mapping.redcapFieldName,
                          );

                          return (
                            <option
                              key={csvField}
                              value={csvField}
                              disabled={isDuplicate}
                            >
                              {csvField}
                            </option>
                          );
                        })}
                      </select>
                    </div>
                  ) : (
                    <select
                      className="form-select"
                      value={mapping.csvFieldName}
                      onChange={(e) =>
                        updateRow(index, { csvFieldName: e.target.value })
                      }
                      required
                    >
                      <option value="">Select field...</option>
                      {value.csvFields.map((csvField) => {
                        const isDuplicate = isAlreadyMapped(
                          index,
                          csvField,
                          mapping.redcapEventName,
                          mapping.redcapFormName,
                          mapping.redcapFieldName,
                        );

                        return (
                          <option
                            key={csvField}
                            value={csvField}
                            disabled={isDuplicate}
                          >
                            {csvField}
                          </option>
                        );
                      })}
                    </select>
                  )}
                </td>
                <td className="text-center align-middle">→</td>
                <td>
                  <div className="input-group">
                    {isLongitudinal && (
                      <>
                        <select
                          className={`form-select${validationIssues.event ? " is-invalid" : ""}`}
                          value={mapping.redcapEventName}
                          onChange={(e) => {
                            updateRow(index, {
                              redcapEventName: e.target.value || null,
                              redcapFormName: "",
                              redcapFieldName: "",
                            });
                          }}
                          title={validationIssues.event || ""}
                          required
                        >
                          <option value="">Select event...</option>
                          {getAllEvents(projectStructure).map((eventName) => {
                            const isRepeating = isRepeatingEvent(
                              projectStructure,
                              eventName,
                            );
                            return (
                              <option
                                key={eventName}
                                value={eventName}
                                data-repeating={isRepeating}
                              >
                                {eventName} {isRepeating && " 🔁"}
                              </option>
                            );
                          })}
                        </select>
                      </>
                    )}
                    <select
                      className={`form-select${validationIssues.form ? " is-invalid" : ""}`}
                      value={mapping.redcapFormName}
                      disabled={isLongitudinal && !mapping.redcapEventName}
                      onChange={(e) => {
                        updateRow(index, {
                          redcapFormName: e.target.value,
                          redcapFieldName: "",
                        });
                      }}
                      title={validationIssues.form || ""}
                    >
                      <option value="">Select form...</option>

                      {(isLongitudinal
                        ? getFormsForEvent(
                            projectStructure,
                            mapping.redcapEventName,
                          )
                        : getAllForms(projectStructure)
                      ).map((formName) => {
                        const isRepeating = isLongitudinal
                          ? isRepeatingEventForm(
                              projectStructure,
                              mapping.redcapEventName,
                              formName,
                            )
                          : isRepeatingForm(projectStructure, formName);
                        return (
                          <option
                            key={formName}
                            value={formName}
                            data-repeating={isRepeating}
                          >
                            {formName} {isRepeating && " 🔁"}
                          </option>
                        );
                      })}
                    </select>
                    <select
                      className={`form-select${validationIssues.field ? " is-invalid" : ""}`}
                      value={mapping.redcapFieldName}
                      disabled={
                        (isLongitudinal && !mapping.redcapEventName) ||
                        !mapping.redcapFormName
                      }
                      onChange={(e) =>
                        updateRow(index, { redcapFieldName: e.target.value })
                      }
                      title={validationIssues.field || ""}
                      required
                    >
                      <option value="">Select field...</option>
                      {getFieldsForForm(
                        projectStructure,
                        mapping.redcapFormName,
                      ).map((fieldName) => {
                        const isDuplicate = isAlreadyMapped(
                          index,
                          mapping.csvFieldName,
                          mapping.redcapEventName,
                          mapping.redcapFormName,
                          fieldName,
                        );

                        return (
                          <option
                            key={fieldName}
                            value={fieldName}
                            disabled={isDuplicate}
                          >
                            {fieldName}
                          </option>
                        );
                      })}
                    </select>
                  </div>
                </td>
                <td className="text-center">
                  {(() => {
                    const hasTransforms = !!(
                      mapping.id && (
                        mapping.combineFields?.enabled ||
                        mapping.fieldRegex ||
                        mapping.dateFormat ||
                        (mapping.valueMappings?.length > 0)
                      )
                    );
                    return (
                      <button
                        type="button"
                        className={`btn btn-sm ${hasTransforms ? 'btn-primary' : 'btn-secondary'}`}
                        onClick={() => {
                          setCurrentFieldMappingId(mapping.id);
                          setOptionsModalOpen(true);
                        }}
                        disabled={!mapping.id}
                        title={mapping.id ? "Configure transforms" : "Save mapping first"}
                      >
                        {hasTransforms ? 'Edit' : 'Add'}
                      </button>
                    );
                  })()}
                </td>
                <td>
                  <button
                    type="button"
                    className={`btn btn btn-danger${mappings.length === 1 ? " invisible" : ""}`}
                    onClick={() => deleteRow(index)}
                    title="Delete row"
                  >
                    x
                  </button>
                </td>
              </tr>
            );
          })}
          <tr>
            <td colSpan="5" className="text-center">
              <button
                type="button"
                className="btn btn-primary"
                onClick={addRow}
              >
                Add field mapping
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      {optionsModalOpen && (
        <FieldMappingTransformsModal
          fieldMapping={mappings.find((fm) => fm.id === currentFieldMappingId)}
          csvFields={value.csvFields}
          onSave={handleSaveOptions}
          onClose={() => setOptionsModalOpen(false)}
        />
      )}
    </div>
  );
}

export default MapStep;
