import { useState, useEffect } from "react";
import { mappingApi } from "../api/mappingApi";

const REGEX_TOOLTIP_TEXT =
  "Apply a regex find/replace. Use PHP preg_replace patterns, e.g. /(\\w)\\w+/ with replacement $1 extracts the first character.";

/**
 * Creates initial per-regex state from a saved regex object (or null).
 */
function makeRegexSlot(saved = null) {
  return {
    pattern: saved?.pattern || "",
    replacement: saved?.replacement || "",
    sampleValue: "",
  };
}

/**
 * Purely presentational regex inputs (pattern + replacement only).
 * Sample values and preview have moved to the consolidated Preview panel.
 */
function RegexInputs({
  pattern,
  replacement,
  onPatternChange,
  onReplacementChange,
}) {
  return (
    <div className="ms-3 mt-1">
      <div className="d-flex gap-2 mb-1">
        <div style={{ flex: 1 }}>
          <input
            type="text"
            className="form-control form-control-sm"
            placeholder="Pattern e.g. /(\w)\w+/"
            value={pattern}
            onChange={(e) => onPatternChange(e.target.value)}
          />
        </div>
        <div style={{ flex: 1 }}>
          <input
            type="text"
            className="form-control form-control-sm"
            placeholder="Replacement e.g. $1"
            value={replacement}
            onChange={(e) => onReplacementChange(e.target.value)}
          />
        </div>
      </div>
    </div>
  );
}

function FieldMappingTransformsModal({
  fieldMapping,
  csvFields,
  onSave,
  onClose,
}) {
  const [dateFormat, setDateFormat] = useState(fieldMapping.dateFormat || "");
  const [dateConversionEnabled, setDateConversionEnabled] = useState(
    !!fieldMapping.dateFormat,
  );
  const [valueMappings, setValueMappings] = useState(
    fieldMapping.valueMappings || [],
  );

  // Field regex slot
  const [fieldRegexEnabled, setFieldRegexEnabled] = useState(
    !!fieldMapping.fieldRegex,
  );
  const [fieldRegexSlot, setFieldRegexSlot] = useState(
    makeRegexSlot(fieldMapping.fieldRegex),
  );

  // Field combination state
  const [combineEnabled, setCombineEnabled] = useState(
    fieldMapping.combineFields?.enabled || false,
  );

  // primaryFieldRegex slot within combination
  const [primaryFieldRegexEnabled, setPrimaryFieldRegexEnabled] = useState(
    !!fieldMapping.combineFields?.primaryFieldRegex,
  );
  const [primaryFieldRegexSlot, setPrimaryFieldRegexSlot] = useState(
    makeRegexSlot(fieldMapping.combineFields?.primaryFieldRegex),
  );

  // Plain sample for {1} when primary regex is off
  const [primarySampleValue, setPrimarySampleValue] = useState("");

  // additionalFields: array of { fieldName, regexEnabled, slot }
  // slot.sampleValue is used as the sample input for each additional field
  const [additionalFields, setAdditionalFields] = useState(() => {
    const saved = fieldMapping.combineFields?.additionalFields || [];
    return saved.map((f) => {
      if (typeof f === "string") {
        return { fieldName: f, regexEnabled: false, slot: makeRegexSlot() };
      }
      return {
        fieldName: f.fieldName || "",
        regexEnabled: !!f.regex,
        slot: makeRegexSlot(f.regex),
      };
    });
  });

  const [template, setTemplate] = useState(
    fieldMapping.combineFields?.template || "{1} {2}",
  );

  // Consolidated preview panel state
  const [previewOutput, setPreviewOutput] = useState(null); // { fieldRegex?: string, combined?: string } | null
  const [previewLoading, setPreviewLoading] = useState(false);
  const [previewError, setPreviewError] = useState(null);

  // Initialise / re-initialise Bootstrap tooltips whenever relevant state changes
  useEffect(() => {
    if (typeof window === "undefined" || !window.bootstrap?.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
      if (!window.bootstrap.Tooltip.getInstance(el)) {
        new window.bootstrap.Tooltip(el);
      }
    });
  }, [
    fieldRegexEnabled,
    combineEnabled,
    primaryFieldRegexEnabled,
    additionalFields,
  ]);

  // Clear stale preview whenever any config that affects it changes
  const clearPreviewOutput = () => {
    setPreviewOutput(null);
    setPreviewError(null);
  };

  // ── Value mapping helpers ────────────────────────────────────────────────

  const addValueMapping = () => {
    setValueMappings([...valueMappings, { input: "", output: "" }]);
  };

  const updateValueMapping = (index, field, value) => {
    setValueMappings(
      valueMappings.map((mapping, i) =>
        i === index ? { ...mapping, [field]: value } : mapping,
      ),
    );
  };

  const deleteValueMapping = (index) => {
    setValueMappings(valueMappings.filter((_, i) => i !== index));
  };

  // ── Additional field helpers ─────────────────────────────────────────────

  const addAdditionalField = () => {
    setAdditionalFields([
      ...additionalFields,
      { fieldName: "", regexEnabled: false, slot: makeRegexSlot() },
    ]);
  };

  const updateAdditionalField = (index, patch) => {
    setAdditionalFields(
      additionalFields.map((f, i) => (i === index ? { ...f, ...patch } : f)),
    );
    clearPreviewOutput();
  };

  const deleteAdditionalField = (index) => {
    setAdditionalFields(additionalFields.filter((_, i) => i !== index));
    clearPreviewOutput();
  };

  // Available CSV fields for additional field selects
  const availableAdditionalFields = (csvFields || []).filter(
    (field) => field !== fieldMapping.csvFieldName,
  );

  // ── Consolidated Generate Preview ────────────────────────────────────────

  const handleGeneratePreview = async () => {
    setPreviewLoading(true);
    setPreviewOutput(null);
    setPreviewError(null);

    try {
      // Build list of async calls to fire in parallel
      const tasks = {};

      if (
        fieldRegexEnabled &&
        !combineEnabled &&
        fieldRegexSlot.pattern &&
        fieldRegexSlot.sampleValue
      ) {
        tasks.fieldRegex = mappingApi.previewRegex(
          fieldRegexSlot.pattern,
          fieldRegexSlot.replacement,
          fieldRegexSlot.sampleValue,
        );
      }

      if (
        combineEnabled &&
        primaryFieldRegexEnabled &&
        primaryFieldRegexSlot.pattern &&
        primaryFieldRegexSlot.sampleValue
      ) {
        tasks.primaryRegex = mappingApi.previewRegex(
          primaryFieldRegexSlot.pattern,
          primaryFieldRegexSlot.replacement,
          primaryFieldRegexSlot.sampleValue,
        );
      }

      const additionalRegexTasks = additionalFields.map((field) => {
        if (
          field.regexEnabled &&
          field.slot.pattern &&
          field.slot.sampleValue
        ) {
          return mappingApi.previewRegex(
            field.slot.pattern,
            field.slot.replacement,
            field.slot.sampleValue,
          );
        }
        return Promise.resolve(null);
      });

      // Fire all in parallel
      const [resolvedTasks, additionalResults] = await Promise.all([
        Promise.all(
          Object.entries(tasks).map(async ([key, p]) => [key, await p]),
        ).then(Object.fromEntries),
        Promise.all(additionalRegexTasks),
      ]);

      const output = {};

      // Field regex result (standalone, not combination)
      if (fieldRegexEnabled && !combineEnabled) {
        output.fieldRegex =
          (resolvedTasks.fieldRegex ?? fieldRegexSlot.sampleValue) ||
          "(no sample)";
      }

      // Combination preview
      if (combineEnabled) {
        // Resolve {1} value
        let primaryValue;
        if (primaryFieldRegexEnabled) {
          primaryValue =
            (resolvedTasks.primaryRegex ?? primaryFieldRegexSlot.sampleValue) ||
            fieldMapping.csvFieldName ||
            "(primary)";
        } else {
          primaryValue =
            primarySampleValue || fieldMapping.csvFieldName || "(primary)";
        }

        let combined = template;
        combined = combined.replace("{1}", primaryValue);

        additionalFields.forEach((field, index) => {
          const regexResult = additionalResults[index];
          let value;
          if (regexResult !== null && regexResult !== undefined) {
            value = regexResult;
          } else if (field.slot.sampleValue) {
            value = field.slot.sampleValue;
          } else {
            value = field.fieldName || "(select field)";
          }
          combined = combined.replace(`{${index + 2}}`, value);
        });

        output.combined = combined;

        // If field regex is also enabled, apply it to the combined result
        if (fieldRegexEnabled && fieldRegexSlot.pattern) {
          const regexResult = await mappingApi.previewRegex(
            fieldRegexSlot.pattern,
            fieldRegexSlot.replacement,
            combined,
          );
          output.combined = regexResult ?? combined;
        }
      }

      setPreviewOutput(output);
    } catch (err) {
      setPreviewError(err.message || "Preview failed");
    } finally {
      setPreviewLoading(false);
    }
  };

  // ── Can Generate Preview? ────────────────────────────────────────────────

  const canPreview = fieldRegexEnabled || combineEnabled;

  // ── Save ─────────────────────────────────────────────────────────────────

  const handleSave = () => {
    onSave(fieldMapping.id, {
      dateFormat: dateFormat || null,
      valueMappings: valueMappings.length > 0 ? valueMappings : null,
      fieldRegex:
        fieldRegexEnabled && fieldRegexSlot.pattern
          ? {
              pattern: fieldRegexSlot.pattern,
              replacement: fieldRegexSlot.replacement,
            }
          : null,
      combineFields:
        combineEnabled && additionalFields.length > 0
          ? {
              enabled: true,
              primaryFieldRegex:
                primaryFieldRegexEnabled && primaryFieldRegexSlot.pattern
                  ? {
                      pattern: primaryFieldRegexSlot.pattern,
                      replacement: primaryFieldRegexSlot.replacement,
                    }
                  : null,
              additionalFields: additionalFields
                .filter((f) => f.fieldName !== "")
                .map((f) => ({
                  fieldName: f.fieldName,
                  regex:
                    f.regexEnabled && f.slot.pattern
                      ? {
                          pattern: f.slot.pattern,
                          replacement: f.slot.replacement,
                        }
                      : null,
                })),
              template: template,
            }
          : null,
    });
  };

  // ── Render ────────────────────────────────────────────────────────────────

  return (
    <div
      className="modal fade show d-block"
      tabIndex="-1"
      style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
    >
      <div className="modal-dialog">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title">Transforms</h5>
            <button
              type="button"
              className="btn-close"
              onClick={onClose}
            ></button>
          </div>

          <div className="modal-body">
            <div className="mb-3">
              <div>
                <strong>CSV Field:</strong> {fieldMapping.csvFieldName}
              </div>
              <div>
                <strong>REDCap Field:</strong> {fieldMapping.redcapFieldName}
              </div>
            </div>

            <hr />
            <p className="text-muted small mb-0">
              Any enabled transforms are applied in the order listed below.
            </p>

            <hr />

            <h6>Field Combination</h6>
            <div className="form-check mb-3">
              <input
                type="checkbox"
                className="form-check-input"
                id="combineEnabled"
                checked={combineEnabled}
                onChange={(e) => {
                  setCombineEnabled(e.target.checked);
                  clearPreviewOutput();
                }}
              />
              <label className="form-check-label" htmlFor="combineEnabled">
                Combine multiple CSV fields
              </label>
            </div>

            {combineEnabled && (
              <>
                <p className="text-muted small">
                  Combine this field with additional CSV fields. Use
                  placeholders in the template: <code>{"{1}"}</code> for{" "}
                  {fieldMapping.csvFieldName}, <code>{"{2}"}</code> for the
                  first additional field, etc.
                </p>

                <div className="mb-3">
                  <label className="form-label">
                    Primary Field (from main table)
                  </label>
                  <div className="d-flex align-items-center gap-2">
                    <input
                      type="text"
                      className="form-control"
                      value={fieldMapping.csvFieldName}
                      disabled
                      style={{ flex: 1 }}
                    />
                    <div className="form-check mb-0">
                      <input
                        type="checkbox"
                        className="form-check-input"
                        id="primaryFieldRegexEnabled"
                        checked={primaryFieldRegexEnabled}
                        onChange={(e) => {
                          setPrimaryFieldRegexEnabled(e.target.checked);
                          clearPreviewOutput();
                        }}
                      />
                      <label
                        className="form-check-label"
                        htmlFor="primaryFieldRegexEnabled"
                      >
                        Regex
                      </label>{" "}
                      <span
                        data-bs-toggle="tooltip"
                        data-bs-title={REGEX_TOOLTIP_TEXT}
                        style={{ cursor: "help" }}
                      >
                        &#9432;
                      </span>
                    </div>
                  </div>
                  {primaryFieldRegexEnabled && (
                    <RegexInputs
                      pattern={primaryFieldRegexSlot.pattern}
                      replacement={primaryFieldRegexSlot.replacement}
                      onPatternChange={(v) => {
                        setPrimaryFieldRegexSlot((s) => ({ ...s, pattern: v }));
                        clearPreviewOutput();
                      }}
                      onReplacementChange={(v) => {
                        setPrimaryFieldRegexSlot((s) => ({
                          ...s,
                          replacement: v,
                        }));
                        clearPreviewOutput();
                      }}
                    />
                  )}
                </div>

                <div className="mb-3">
                  <label className="form-label">Additional Fields</label>
                  {additionalFields.length > 0 ? (
                    <div className="list-group mb-2">
                      {additionalFields.map((field, index) => (
                        <div key={index} className="list-group-item">
                          <div className="d-flex align-items-center gap-2">
                            <span
                              className="text-muted"
                              style={{ minWidth: "30px" }}
                            >
                              {"{"}
                              {index + 2}
                              {"}"}
                            </span>
                            <select
                              className="form-select form-select-sm"
                              value={field.fieldName}
                              onChange={(e) =>
                                updateAdditionalField(index, {
                                  fieldName: e.target.value,
                                })
                              }
                              style={{ flex: 1 }}
                            >
                              <option value="">Select field...</option>
                              {availableAdditionalFields.map((csvField) => (
                                <option
                                  key={csvField}
                                  value={csvField}
                                  disabled={additionalFields.some(
                                    (f, i) =>
                                      i !== index && f.fieldName === csvField,
                                  )}
                                >
                                  {csvField}
                                </option>
                              ))}
                            </select>
                            <div className="form-check mb-0">
                              <input
                                type="checkbox"
                                className="form-check-input"
                                id={`fieldRegex-${index}`}
                                checked={field.regexEnabled}
                                onChange={(e) =>
                                  updateAdditionalField(index, {
                                    regexEnabled: e.target.checked,
                                  })
                                }
                              />
                              <label
                                className="form-check-label"
                                htmlFor={`fieldRegex-${index}`}
                              >
                                Regex
                              </label>{" "}
                              <span
                                data-bs-toggle="tooltip"
                                data-bs-title={REGEX_TOOLTIP_TEXT}
                                style={{ cursor: "help" }}
                              >
                                &#9432;
                              </span>
                            </div>
                            <button
                              type="button"
                              className="btn btn-sm btn-danger"
                              onClick={() => deleteAdditionalField(index)}
                              title="Remove field"
                            >
                              ×
                            </button>
                          </div>
                          {field.regexEnabled && (
                            <RegexInputs
                              pattern={field.slot.pattern}
                              replacement={field.slot.replacement}
                              onPatternChange={(v) =>
                                updateAdditionalField(index, {
                                  slot: { ...field.slot, pattern: v },
                                })
                              }
                              onReplacementChange={(v) =>
                                updateAdditionalField(index, {
                                  slot: { ...field.slot, replacement: v },
                                })
                              }
                            />
                          )}
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-muted small">
                      No additional fields added yet.
                    </p>
                  )}
                  <button
                    type="button"
                    className="btn btn-sm btn-primary"
                    onClick={addAdditionalField}
                  >
                    + Add Field
                  </button>
                </div>

                <div className="mb-3">
                  <label htmlFor="template" className="form-label">
                    Template
                  </label>
                  <input
                    type="text"
                    id="template"
                    className="form-control"
                    placeholder="e.g., {1} {2}"
                    value={template}
                    onChange={(e) => {
                      setTemplate(e.target.value);
                      clearPreviewOutput();
                    }}
                  />
                  <small className="form-text text-muted">
                    Use placeholders {"{1}"}, {"{2}"}, etc. to position field
                    values.
                  </small>
                </div>
              </>
            )}

            <hr />

            <h6>Field Regex</h6>
            <div className="form-check mb-2">
              <input
                type="checkbox"
                className="form-check-input"
                id="fieldRegexEnabled"
                checked={fieldRegexEnabled}
                onChange={(e) => {
                  setFieldRegexEnabled(e.target.checked);
                  clearPreviewOutput();
                }}
              />
              <label className="form-check-label" htmlFor="fieldRegexEnabled">
                Apply regex transformation
              </label>{" "}
              <span
                data-bs-toggle="tooltip"
                data-bs-title={REGEX_TOOLTIP_TEXT}
                style={{ cursor: "help" }}
              >
                &#9432;
              </span>
            </div>
            {fieldRegexEnabled && (
              <RegexInputs
                pattern={fieldRegexSlot.pattern}
                replacement={fieldRegexSlot.replacement}
                onPatternChange={(v) => {
                  setFieldRegexSlot((s) => ({ ...s, pattern: v }));
                  clearPreviewOutput();
                }}
                onReplacementChange={(v) => {
                  setFieldRegexSlot((s) => ({ ...s, replacement: v }));
                  clearPreviewOutput();
                }}
              />
            )}

            <hr />

            <h6>Date Conversion</h6>
            <div className="form-check mb-2">
              <input
                type="checkbox"
                className="form-check-input"
                id="dateConversionEnabled"
                checked={dateConversionEnabled}
                onChange={(e) => {
                  setDateConversionEnabled(e.target.checked);
                  if (e.target.checked && !dateFormat) {
                    setDateFormat("MDY");
                  } else if (!e.target.checked) {
                    setDateFormat("");
                  }
                }}
              />
              <label
                className="form-check-label"
                htmlFor="dateConversionEnabled"
              >
                Convert date format
              </label>
            </div>
            {dateConversionEnabled && (
              <div className="form-group">
                <label htmlFor="dateFormat">CSV Date Format</label>
                <select
                  id="dateFormat"
                  className="form-select"
                  value={dateFormat}
                  onChange={(e) => setDateFormat(e.target.value)}
                >
                  <option value="DMY">DD/MM/YYYY (e.g., 15/01/2025)</option>
                  <option value="MDY">MM/DD/YYYY (e.g., 01/15/2025)</option>
                  <option value="YMD">YYYY/MM/DD (e.g., 2025/01/15)</option>
                </select>
                <small className="form-text text-muted">
                  Dates will be automatically converted during import to
                  REDCap's required YYYY-MM-DD format.
                </small>
            </div>
            )}

            <hr />

            <h6>Value Mapping</h6>
            <p className="text-muted small">
              Define custom value transformations. Values not in this table will
              pass through unchanged.
            </p>

            {valueMappings.length > 0 && (
              <table className="table table-sm">
                <thead>
                  <tr>
                    <th>CSV Value (Input)</th>
                    <th>REDCap Value (Output)</th>
                    <th style={{ width: "50px" }}></th>
                  </tr>
                </thead>
                <tbody>
                  {valueMappings.map((mapping, index) => (
                    <tr key={index}>
                      <td>
                        <input
                          type="text"
                          className="form-control form-control-sm"
                          placeholder="e.g., NOPE"
                          value={mapping.input}
                          onChange={(e) =>
                            updateValueMapping(index, "input", e.target.value)
                          }
                        />
                      </td>
                      <td>
                        <input
                          type="text"
                          className="form-control form-control-sm"
                          placeholder="e.g., 0"
                          value={mapping.output}
                          onChange={(e) =>
                            updateValueMapping(index, "output", e.target.value)
                          }
                        />
                      </td>
                      <td>
                        <button
                          type="button"
                          className="btn btn-sm btn-danger"
                          onClick={() => deleteValueMapping(index)}
                          title="Delete mapping"
                        >
                          ×
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}

            <button
              type="button"
              className="btn btn-sm btn-primary"
              onClick={addValueMapping}
            >
              + Add Value Mapping
            </button>

            {/* ── Consolidated Preview Panel ── */}
            {canPreview && (
              <>
                <hr />
                <h6>Preview</h6>
                <p className="text-muted small mb-2">
                  Only field combination and regex transforms affect this
                  preview. Date conversion and value mapping are applied during
                  import.
                </p>

                <div className="mb-3">
                  {/* Sample inputs */}
                  {fieldRegexEnabled && !combineEnabled && (
                    <div className="mb-2">
                      <label className="form-label form-label-sm mb-1">
                        Field Regex sample ({fieldMapping.csvFieldName})
                      </label>
                      <input
                        type="text"
                        className="form-control form-control-sm"
                        placeholder="Enter a sample value…"
                        value={fieldRegexSlot.sampleValue}
                        onChange={(e) => {
                          setFieldRegexSlot((s) => ({
                            ...s,
                            sampleValue: e.target.value,
                          }));
                          clearPreviewOutput();
                        }}
                      />
                    </div>
                  )}

                  {combineEnabled && (
                    <>
                      <div className="mb-2">
                        <label className="form-label form-label-sm mb-1">
                          {"{1}"} {fieldMapping.csvFieldName} sample
                        </label>
                        <input
                          type="text"
                          className="form-control form-control-sm"
                          placeholder="Enter a sample value…"
                          value={
                            primaryFieldRegexEnabled
                              ? primaryFieldRegexSlot.sampleValue
                              : primarySampleValue
                          }
                          onChange={(e) => {
                            if (primaryFieldRegexEnabled) {
                              setPrimaryFieldRegexSlot((s) => ({
                                ...s,
                                sampleValue: e.target.value,
                              }));
                            } else {
                              setPrimarySampleValue(e.target.value);
                            }
                            clearPreviewOutput();
                          }}
                        />
                      </div>

                      {additionalFields.map((field, index) => (
                        <div className="mb-2" key={index}>
                          <label className="form-label form-label-sm mb-1">
                            {"{"}
                            {index + 2}
                            {"}"} {field.fieldName || "(select field)"} sample
                          </label>
                          <input
                            type="text"
                            className="form-control form-control-sm"
                            placeholder="Enter a sample value…"
                            value={field.slot.sampleValue}
                            onChange={(e) => {
                              updateAdditionalField(index, {
                                slot: {
                                  ...field.slot,
                                  sampleValue: e.target.value,
                                },
                              });
                            }}
                          />
                        </div>
                      ))}
                    </>
                  )}
                </div>

                <button
                  type="button"
                  className="btn btn-sm btn-secondary mb-3"
                  onClick={handleGeneratePreview}
                  disabled={previewLoading}
                >
                  {previewLoading ? (
                    <>
                      <span
                        className="spinner-border spinner-border-sm me-1"
                        role="status"
                        aria-hidden="true"
                      />
                      Generating…
                    </>
                  ) : (
                    "Generate Preview"
                  )}
                </button>

                {previewError && (
                  <div className="alert alert-danger py-2">
                    <small>{previewError}</small>
                  </div>
                )}

                {previewOutput && (
                  <div className="alert alert-info py-2">
                    {previewOutput.fieldRegex !== undefined &&
                      !combineEnabled && (
                        <div>
                          <strong>Field Regex:</strong>{" "}
                          <code>{previewOutput.fieldRegex}</code>
                        </div>
                      )}
                    {previewOutput.combined !== undefined && (
                      <div>
                        <strong>Combined:</strong>{" "}
                        <code>{previewOutput.combined}</code>
                      </div>
                    )}
                  </div>
                )}
              </>
            )}
          </div>

          <div className="modal-footer">
            <button
              type="button"
              className="btn btn-secondary"
              onClick={onClose}
            >
              Cancel
            </button>
            <button
              type="button"
              className="btn btn-primary"
              onClick={handleSave}
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default FieldMappingTransformsModal;
