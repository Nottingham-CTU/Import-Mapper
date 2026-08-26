/**
 * DAG (Data Access Group) matching configuration section.
 * Allows assigning records to DAGs either using the same DAG for all records,
 * or using a CSV column that contains DAG unique names.
 */

import { ARIA_LABELS, ARIA_DESCRIPTIONS, WARNING_MESSAGES } from "../constants";

function DagMatchingSection({
  dagMatching,
  projectStructure,
  csvFields,
  updateDagMatching,
}) {
  return (
    <div className="mt-4">
      <label className="form-label">Data Access Group assignment</label>
      <div className="form-text">
        {ARIA_DESCRIPTIONS.DAG_ASSIGNMENT_HELP}
      </div>
      <table className="table">
        <tbody>
          <tr>
            <td style={{ width: "50px" }}>
              <input
                type="checkbox"
                className="form-check-input"
                checked={dagMatching.enabled ?? false}
                onChange={(e) =>
                  updateDagMatching({
                    enabled: e.target.checked,
                  })
                }
                aria-label={ARIA_LABELS.DAG_MATCHING_ENABLE}
              />
            </td>
            <td>
              <select
                className="form-select"
                value={dagMatching.mode ?? ""}
                onChange={(e) => {
                  const newMode = e.target.value;
                  updateDagMatching({
                    mode: newMode,
                    dagUniqueName: "",
                    csvFieldName: "",
                  });
                }}
                disabled={!dagMatching.enabled}
                required={dagMatching.enabled}
              >
                <option value="">Select mode...</option>
                <option value="same_for_all">Same DAG for all records</option>
                <option value="csv_field">CSV column contains DAG</option>
                <option value="select_dag">Select DAG during import</option>
              </select>
            </td>
            <td>
              {dagMatching.mode === "same_for_all" && (
                <select
                  className="form-select"
                  value={dagMatching.dagUniqueName ?? ""}
                  onChange={(e) =>
                    updateDagMatching({
                      dagUniqueName: e.target.value,
                    })
                  }
                  disabled={!dagMatching.enabled}
                  required={dagMatching.enabled}
                >
                  <option value="">Select DAG...</option>
                  {Object.keys(projectStructure.data_access_groups || {}).map(
                    (dagName) => (
                      <option key={dagName} value={dagName}>
                        {dagName}
                      </option>
                    ),
                  )}
                </select>
              )}
              {dagMatching.mode === "csv_field" && (
                <select
                  className="form-select"
                  value={dagMatching.csvFieldName ?? ""}
                  onChange={(e) =>
                    updateDagMatching({
                      csvFieldName: e.target.value,
                    })
                  }
                  disabled={!dagMatching.enabled}
                  required={dagMatching.enabled}
                >
                  <option value="">Select CSV column...</option>
                  {csvFields.map((fieldName) => (
                    <option key={fieldName} value={fieldName}>
                      {fieldName}
                    </option>
                  ))}
                </select>
              )}
              {!dagMatching.mode && dagMatching.enabled && (
                <div className="text-muted fst-italic">
                  Select a mode first
                </div>
              )}
            </td>
          </tr>
        </tbody>
      </table>

      {dagMatching.enabled &&
        (!projectStructure.data_access_groups ||
          Object.keys(projectStructure.data_access_groups).length === 0) && (
          <div className="alert alert-warning mt-2" role="alert" aria-live="polite">
            <strong>Warning:</strong> {WARNING_MESSAGES.NO_DAGS} Please create DAGs in the project settings
            before using this feature.
          </div>
        )}
    </div>
  );
}

export default DagMatchingSection;
