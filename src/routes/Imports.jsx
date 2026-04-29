import { createFileRoute } from "@tanstack/react-router";
import { useEffect } from "react";
import { useModal } from "../contexts/ModalContext.jsx";
import { useImportJobs } from "../hooks/useImportJobs";

export const Route = createFileRoute("/Imports")({
  component: Imports,
});

function formatDateTime(val) {
  if (!val) return "—";
  const d = new Date(val);
  return isNaN(d.getTime()) ? val : d.toLocaleString();
}

function Imports() {
  const {
    logs,
    activeJobs,
    loading,
    pendingCancel,
    loadAll,
    cancelJob,
    stopPolling,
  } = useImportJobs();
  const { showModal } = useModal();

  useEffect(() => {
    loadAll();
    return () => stopPolling();
  }, []);

  function handleShowDetails(log) {
    showModal({
      title: `Import Details - ${log.mappingName}`,
      message: (
        <div>
          <p>
            <strong>Queued:</strong> {formatDateTime(log.queuedAt)}
          </p>
          <p>
            <strong>Started:</strong> {formatDateTime(log.startedAt)}
          </p>
          <p>
            <strong>Finished:</strong> {formatDateTime(log.timestamp)}
          </p>
          <p>
            <strong>User:</strong> {log.username}
          </p>
          <p>
            <strong>Job ID:</strong> {log.jobId}
          </p>
          <p>
            <strong>Status:</strong>{" "}
            {log.status === "completed" &&
              log.outcome === "clean" &&
              "Completed"}
            {log.status === "completed" &&
              log.outcome === "errors" &&
              "Completed with errors"}
            {log.status === "completed" &&
              log.outcome === "warnings" &&
              "Completed with warnings"}
            {log.status === "failed" && "Failed"}
            {log.status === "cancelled" && "Cancelled by user"}
          </p>

          {(log.status === "completed" ||
            log.status === "failed" ||
            log.status === "cancelled") && (
            <>
              <p>
                <strong>Rows Processed:</strong> {log.rowsProcessed ?? 0} of{" "}
                {log.totalRows ?? 0}
              </p>
              <p>
                <strong>Items Updated:</strong> {log.itemsUpdated ?? 0}
              </p>
              <p>
                <strong>Records Updated:</strong> {log.recordsUpdated ?? 0}
              </p>
            </>
          )}

          {(log.mappingErrorCount > 0 ||
            log.csvStructureErrorCount > 0 ||
            log.csvDataErrorCount > 0 ||
            log.transformationErrorCount > 0 ||
            log.redcapSaveErrorCount > 0) && (
            <>
              <hr />
              <h6 className="text-danger">Errors:</h6>
              <ul className="mb-0">
                {log.mappingErrorCount > 0 && (
                  <li>Mapping errors: {log.mappingErrorCount}</li>
                )}
                {log.csvStructureErrorCount > 0 && (
                  <li>CSV structure errors: {log.csvStructureErrorCount}</li>
                )}
                {log.csvDataErrorCount > 0 && (
                  <li>CSV data errors: {log.csvDataErrorCount}</li>
                )}
                {log.transformationErrorCount > 0 && (
                  <li>Transformation errors: {log.transformationErrorCount}</li>
                )}
                {log.redcapSaveErrorCount > 0 && (
                  <li>REDCap save errors: {log.redcapSaveErrorCount}</li>
                )}
              </ul>
            </>
          )}
        </div>
      ),
      buttons: [{ label: "Close", className: "btn-primary" }],
    });
  }

  function handleCancel(job) {
    showModal({
      title: "Cancel Import",
      message: (
        <p>
          Are you sure you want to cancel the import for{" "}
          <strong>{job.mappingName}</strong>?{" "}
          {job.status === "in_progress"
            ? "The import will be stopped after the current row. Rows already saved to REDCap cannot be automatically undone."
            : "The import is queued and will be removed immediately."}
        </p>
      ),
      buttons: [
        {
          label: "Cancel Import",
          className: "btn-danger",
          onClick: () => confirmCancel(job),
        },
        { label: "Keep", className: "btn-secondary" },
      ],
    });
  }

  async function confirmCancel(job) {
    try {
      await cancelJob(job.jobId);
    } catch (error) {
      console.error("Error cancelling import:", error);
      showModal({
        title: "Cancel Failed",
        message: <p>{error.message || "Failed to cancel import."}</p>,
        buttons: [{ label: "Close", className: "btn-primary" }],
      });
    }
  }
  function StatusBadge({ status, outcome }) {
    if (status === "in_progress")
      return (
        <span className="badge rounded-pill border border-2 border-primary bg-white text-dark">
          In Progress
        </span>
      );
    if (status === "queued")
      return (
        <span className="badge rounded-pill border border-2 border-secondary text-dark">
          Queued
        </span>
      );
    if (status === "cancelled")
      return (
        <span className="badge rounded-pill border border-2 border-warning bg-white text-dark">
          Cancelled
        </span>
      );
    if (status === "failed")
      return (
        <span className="badge rounded-pill border border-2 border-danger bg-white text-dark">
          Failed
        </span>
      );
    if (status === "completed") {
      if (outcome === "errors")
        return (
          <span className="badge rounded-pill border border-2 border-warning bg-white text-dark">
            Completed with errors
          </span>
        );
      if (outcome === "warnings")
        return (
          <span className="badge rounded-pill border border-2 border-info bg-white text-dark">
            Completed with warnings
          </span>
        );
      return (
        <span className="badge rounded-pill border border-2 border-success bg-white text-dark ">
          Completed
        </span>
      );
    }
    return (
      <span className="badge rounded-pill border border-2 border-light bg-white text-dark">
        {status}
      </span>
    );
  }

  function ActiveProgress({ job }) {
    const isIndeterminate = job.totalRows === 0;
    const pct = isIndeterminate
      ? null
      : Math.round((job.rowsProcessed / job.totalRows) * 100);
    const label = isIndeterminate
      ? "Processing\u2026"
      : `${job.rowsProcessed}\u00a0/\u00a0${job.totalRows} rows`;
    const isWaiting = job.chunkCompletedAt != null;
    return (
      <div>
        <div className="fw-semibold mb-1">{job.mappingName}</div>
        <div className="progress" style={{ height: "0.75rem" }}>
          {isIndeterminate ? (
            <div
              className="progress-bar progress-bar-striped progress-bar-animated"
              role="progressbar"
              style={{ width: "100%" }}
              aria-valuemin={0}
              aria-valuemax={100}
            />
          ) : (
            <div
              className="progress-bar progress-bar-striped progress-bar-animated"
              role="progressbar"
              style={{ width: `${pct}%` }}
              aria-valuenow={pct}
              aria-valuemin={0}
              aria-valuemax={100}
            />
          )}
        </div>
        <div className="small text-muted mt-1">
          {label}
          {isWaiting && (
            <span>
              {" "}
              (Processing in batches &mdash; next batch starting soon&hellip;)
            </span>
          )}
        </div>
      </div>
    );
  }

  const inProgressJob =
    activeJobs.find((j) => j.status === "in_progress") ?? null;

  // Merge active jobs and terminal log entries into one list sorted by queuedAt desc.
  // Active jobs use jobId as key; log entries use id.
  const allRows = [
    ...activeJobs.map((j) => ({ ...j, _type: "active" })),
    ...logs.map((l) => ({ ...l, _type: "log" })),
  ].sort((a, b) => new Date(b.queuedAt) - new Date(a.queuedAt));

  return (
    <div>
      <div className="mt-2 mb-3 d-flex align-items-center">
        <h5 className="text-secondary-emphasis mb-0">Imports</h5>
      </div>

      {inProgressJob && (
        <div className="mb-3">
          <ActiveProgress job={inProgressJob} />
        </div>
      )}

      {loading && <p>Loading imports...</p>}

      {!loading && logs.length === 0 && activeJobs.length === 0 && (
        <p className="text-muted">No imports found.</p>
      )}

      {!loading && (activeJobs.length > 0 || logs.length > 0) && (
        <div className="table-responsive">
          <table className="table table-striped table-hover">
            <thead>
              <tr>
                <th>Mapping Name</th>
                <th>Queued</th>
                <th>Started</th>
                <th>Finished</th>
                <th>User</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {allRows.map((row) =>
                row._type === "active" ? (
                  <tr key={row.jobId}>
                    <td>{row.mappingName}</td>
                    <td>{formatDateTime(row.queuedAt)}</td>
                    <td>{formatDateTime(row.startedAt)}</td>
                    <td>—</td>
                    <td>{row.username || "—"}</td>
                    <td>
                      <StatusBadge status={row.status} />
                    </td>
                    <td>
                      <button
                        className="btn btn-danger btn-sm"
                        onClick={() => handleCancel(row)}
                        disabled={row.cancelling || pendingCancel[row.jobId]}
                      >
                        {row.cancelling || pendingCancel[row.jobId]
                          ? "Cancelling\u2026"
                          : "Cancel"}
                      </button>
                    </td>
                  </tr>
                ) : (
                  <tr key={row.id}>
                    <td>{row.mappingName}</td>
                    <td>{formatDateTime(row.queuedAt)}</td>
                    <td>{formatDateTime(row.startedAt)}</td>
                    <td>{formatDateTime(row.timestamp)}</td>
                    <td>{row.username}</td>
                    <td>
                      <StatusBadge status={row.status} outcome={row.outcome} />
                    </td>
                    <td>
                      <button
                        className="btn btn-secondary btn-sm"
                        onClick={() => handleShowDetails(row)}
                        title="View details"
                      >
                        Details
                      </button>
                    </td>
                  </tr>
                ),
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
