import { useState } from "react";
import { extractCSVHeaders, validateHeaders, mergeHeaders } from "../utils/csvUtils";
import { ARIA_LABELS, ARIA_DESCRIPTIONS, SUCCESS_MESSAGES, VALIDATION_ERRORS } from "../constants";

export default function UploadStep({ value, onChange }) {
  const existingHeaders = value || [];
  const hasHeaders = existingHeaders.length > 0;
  const [showOverwriteModal, setShowOverwriteModal] = useState(false);
  const [showEmptyHeaderModal, setShowEmptyHeaderModal] = useState(false);
  const [pendingHeaders, setPendingHeaders] = useState([]);

  async function handleFileChange(e) {
    const file = e.target.files[0];
    if (file) {
      try {
        const newHeaders = await extractCSVHeaders(file);
        processNewHeaders(newHeaders);
      } catch (error) {
        console.error("Error reading CSV:", error);
        setShowEmptyHeaderModal(true);
      }
    }
    // Clear file input
    e.target.value = "";
  }

  function processNewHeaders(newHeaders) {
    const headerValidation = validateHeaders(newHeaders);

    if (!headerValidation.valid) {
      setShowEmptyHeaderModal(true);
      return;
    }

    if (hasHeaders) {
      setPendingHeaders(newHeaders);
      setShowOverwriteModal(true);
    } else {
      onChange(newHeaders);
    }
  }

  function handleOverwrite() {
    onChange(pendingHeaders);
    setShowOverwriteModal(false);
    setPendingHeaders([]);
  }

  function handleAppend() {
    const merged = mergeHeaders(existingHeaders, pendingHeaders);
    onChange(merged);
    setShowOverwriteModal(false);
    setPendingHeaders([]);
  }

  function handleCancel() {
    setShowOverwriteModal(false);
    setPendingHeaders([]);
  }

  function handleOk() {
    setShowEmptyHeaderModal(false);
  }

  return (
    <div>
      {hasHeaders && (
        <div className="mb-3">
          <div className="alert alert-success" role="status" aria-live="polite">
            <strong>✓ {SUCCESS_MESSAGES.CSV_UPLOADED}</strong>
          </div>
          <div>
            <strong>CSV fields:</strong>
            <ul className="mb-0" aria-label="CSV field list">
              {existingHeaders.map((header) => (
                <li key={header}>{header}</li>
              ))}
            </ul>
          </div>
        </div>
      )}

      <div>
        <label htmlFor="csv" className="form-label">
          {hasHeaders ? "Upload another sample CSV file" : "Sample CSV file"}
        </label>
        <input
          id="csv"
          name="csv"
          type="file"
          accept=".csv,text/csv"
          required={!hasHeaders}
          className="form-control"
          onChange={handleFileChange}
          aria-label={ARIA_LABELS.SAMPLE_CSV_INPUT}
          aria-describedby="csv-help"
        />
        <div id="csv-help" className="form-text">
          {ARIA_DESCRIPTIONS.CSV_UPLOAD_HELP}
        </div>
      </div>

      {/* Modal for overwrite/append/cancel */}
      {showOverwriteModal && (
        <div
          className="modal fade show"
          style={{ display: "block" }}
          tabIndex="-1"
          role="dialog"
          aria-labelledby="overwrite-modal-title"
          aria-describedby="overwrite-modal-message"
        >
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title" id="overwrite-modal-title">Upload new file?</h5>
              </div>
              <div className="modal-body" id="overwrite-modal-message">
                <p>
                  You already have {existingHeaders.length} columns from a
                  previous upload. The new file contains {pendingHeaders.length}{" "}
                  columns.
                </p>
                <p>What would you like to do?</p>
              </div>
              <div className="modal-footer">
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={handleCancel}
                  aria-label="Cancel upload"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  className="btn btn-warning"
                  onClick={handleAppend}
                  aria-label="Merge new columns with existing columns"
                >
                  Append (merge columns)
                </button>
                <button
                  type="button"
                  className="btn btn-danger"
                  onClick={handleOverwrite}
                  aria-label="Replace all columns with new file"
                >
                  Overwrite (replace all)
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      {showOverwriteModal && <div className="modal-backdrop fade show"></div>}

      {/* Modal for empty headers */}
      {showEmptyHeaderModal && (
        <div
          className="modal fade show"
          style={{ display: "block" }}
          tabIndex="-1"
          role="dialog"
          aria-labelledby="empty-header-title"
          aria-describedby="empty-header-message"
        >
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title" id="empty-header-title">Sample contains empty headers</h5>
              </div>
              <div className="modal-body">
                <p id="empty-header-message">
                  {VALIDATION_ERRORS.CSV_EMPTY_HEADERS}
                </p>
              </div>
              <div className="modal-footer">
                <button
                  type="button"
                  className="btn btn-primary"
                  onClick={handleOk}
                  aria-label="Close dialog"
                >
                  Ok
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      {showEmptyHeaderModal && <div className="modal-backdrop fade show"></div>}
    </div>
  );
}
