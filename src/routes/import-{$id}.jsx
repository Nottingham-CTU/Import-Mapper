import {
  createFileRoute,
  Link,
  useNavigate,
} from "@tanstack/react-router";
import { useState, useEffect, useRef } from "react";
import { csrfToken, moduleObj } from "../App";
import ConfirmModal from "../components/ConfirmModal.jsx";
import { useModal } from "../contexts/ModalContext.jsx";
import { mappingApi, handleApiError } from "../api/mappingApi";
import { useImport } from "../hooks/useImport";

export const Route = createFileRoute("/import-{$id}")({
  component: RouteComponent,
});

function RouteComponent() {
  const { id } = Route.useParams();
  const navigate = useNavigate();
  const { showModal } = useModal();
  const hasInitiatedDraftUpdate = useRef(false);
  const [mapping, setMapping] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showStructureChangedModal, setShowStructureChangedModal] =
    useState(false);

  const hasStructureChanged = mapping?.structureChanged ?? false;

  const {
    importStatus,
    isImporting,
    selectedFile,
    csvPreview,
    validationErrors,
    importResults,
    fileInputRef,
    handleFileSelect,
    handleStartImport,
    handleReset,
  } = useImport({
    mapping,
    uploadUrl: moduleObj.getUrl("pages/upload.php"),
    csrfToken,
  });

  // Load data when ID changes
  useEffect(() => {
    loadMapping();
  }, [id]);

  async function loadMapping() {
    try {
      const mapping = await mappingApi.getMapping(id);
      setMapping(mapping);
    } catch (error) {
      handleApiError(error, showModal, () => navigate({ to: "/" }));
    } finally {
      setLoading(false);
    }
  }

  // Check for structure changes and update mapping status if needed
  useEffect(() => {
    if (
      hasStructureChanged &&
      mapping &&
      mapping.status !== "draft" &&
      !hasInitiatedDraftUpdate.current
    ) {
      hasInitiatedDraftUpdate.current = true;
      mappingApi
        .updateMappingStatus(mapping.id, "draft")
        .then(() => {
          setMapping((prev) => ({ ...prev, status: "draft" }));
          setShowStructureChangedModal(true);
        })
        .catch((error) => {
          console.error("Failed to update mapping status:", error);
          setShowStructureChangedModal(true);
        });
    }
  }, [hasStructureChanged, mapping]);

  if (loading) {
    return <div className="p-3">Loading mapping...</div>;
  }

  if (!mapping) {
    return <div className="p-3">Mapping not found</div>;
  }

  const structureChangedMessage = (
    <>
      <p>
        The REDCap project structure has changed since the mapping "
        <strong>{mapping?.name || ""}</strong>" was last saved.
      </p>
      <p>
        This mapping has been marked as <strong>draft</strong> and must be
        reviewed before importing.
      </p>
      <div className="alert alert-warning" role="alert">
        <strong>Changes may include:</strong>
        <ul className="mb-0">
          <li>Added, removed or renamed events</li>
          <li>Added, removed or renamed forms</li>
          <li>Added, removed or renamed fields</li>
          <li>Changes to repeating events or forms</li>
        </ul>
      </div>
    </>
  );

  return (
    <>
      <div className="p-3">
        <h5 className="text-secondary-emphasis mb-3">
          Import Data: {mapping.name}
        </h5>

        {importStatus === "idle" && (
          <>
            <div className="mb-3">
              <label htmlFor="csv" className="form-label">
                Upload CSV file
              </label>
              <input
                ref={fileInputRef}
                id="csv"
                name="csv"
                type="file"
                accept=".csv,text/csv"
                onChange={handleFileSelect}
                className="form-control"
                disabled={isImporting}
              />
              <div className="form-text">
                Upload the CSV file that you want to import data from.
              </div>
            </div>

            {validationErrors.length > 0 && (
              <div className="alert alert-danger" role="alert">
                <strong>CSV validation errors:</strong>
                <ul className="mb-0 mt-2">
                  {validationErrors.map((error) => (
                    <li key={error}>{error}</li>
                  ))}
                </ul>
              </div>
            )}

            {csvPreview && validationErrors.length === 0 && (
              <div className="mb-3">
                <h6>CSV Preview ({csvPreview.totalRows} rows)</h6>
                <div className="table-responsive">
                  <table className="table table-sm table-bordered">
                    <thead>
                      <tr>
                        {csvPreview.headers.map((header) => (
                          <th key={header}>{header}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {csvPreview.rows.map((row, rowIndex) => (
                        <tr key={`row-${rowIndex}`}>
                          {row.map((cell, cellIndex) => (
                            <td key={`cell-${rowIndex}-${cellIndex}`}>{cell}</td>
                          ))}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                {csvPreview.totalRows > 5 && (
                  <small className="text-muted">
                    Showing first 5 rows. {csvPreview.totalRows - 5} more rows
                    will be imported.
                  </small>
                )}
              </div>
            )}

            <div className="d-flex gap-2">
              <button
                className="btn btn-secondary"
                onClick={() => navigate({ to: "/" })}
                disabled={isImporting}
              >
                Cancel
              </button>
              <button
                className="btn btn-success"
                onClick={handleStartImport}
                disabled={
                  !selectedFile || validationErrors.length > 0 || isImporting
                }
              >
                {isImporting ? "Uploading..." : "Start Import"}
              </button>
            </div>
          </>
        )}

        {importStatus === "uploading" && (
          <div className="alert alert-info" role="alert">
            <div className="d-flex align-items-center">
              <div
                className="spinner-border spinner-border-sm me-2"
                role="status"
              >
                <span className="visually-hidden">Uploading...</span>
              </div>
              <span>Uploading file, please wait...</span>
            </div>
          </div>
        )}

        {importStatus === "queued" && (
          <div className="alert alert-success" role="alert">
            <h5 className="alert-heading">Import Queued</h5>
            <p>
              Your import has been queued and will be processed shortly
              (usually within 1 minute).
            </p>
            <p className="mb-2">
              You can leave this page.{" "}
              <Link to="/Imports">View imports</Link> to track progress.
            </p>
            <hr />
            <div className="d-flex gap-2">
              <button
                className="btn btn-secondary btn-sm"
                onClick={handleReset}
              >
                Import Another File
              </button>
              <Link to="/" className="btn btn-secondary btn-sm">
                Back to Mappings
              </Link>
            </div>
          </div>
        )}

        {importStatus === "error" && (
          <div>
            <div className="alert alert-danger" role="alert">
              <h5 className="alert-heading">Upload Failed</h5>
              <p>{importResults?.error || "An unexpected error occurred."}</p>
            </div>
            <div className="d-flex gap-2">
              <button
                className="btn btn-primary"
                onClick={() => navigate({ to: "/" })}
              >
                Back to Mappings
              </button>
              <button className="btn btn-secondary" onClick={handleReset}>
                Try Again
              </button>
            </div>
          </div>
        )}
      </div>

      <ConfirmModal
        id="structure-changed-modal"
        title="⚠️ Project Structure Changed"
        message={structureChangedMessage}
        buttons={[
          {
            label: "Cancel",
            className: "btn-secondary",
            onClick: () => navigate({ to: "/" }),
          },
          {
            label: "Review Mapping Now",
            className: "btn-primary",
            onClick: () => navigate({ to: "/mapping{-$id}", params: { id } }),
          },
        ]}
        show={showStructureChangedModal}
        onHide={() => setShowStructureChangedModal(false)}
      />
    </>
  );
}
