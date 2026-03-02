import { Link, useNavigate } from "@tanstack/react-router";
import { canModifyMappings } from "../App.jsx";
import { useModal } from "../contexts/ModalContext.jsx";
import { mappingApi, handleApiError } from "../api/mappingApi.js";

export default function MappingCard({ mapping, onDelete }) {
  const isDraft = mapping.status === "draft";
  const isLocked = mapping.lockedByImport === true;
  const navigate = useNavigate();
  const { showModal } = useModal();

  const handleCopy = async () => {
    try {
      const result = await mappingApi.copyMapping(mapping.id);
      await navigate({
        to: "/mapping{-$id}",
        params: { id: result.mapping.id },
      });
    } catch (error) {
      handleApiError(error, showModal);
    }
  };

  const handleImportClick = () => {
    navigate({ to: "/import-{$id}", params: { id: mapping.id } });
  };

  const handleDelete = async () => {
    showModal({
      title: "Confirm Delete",
      message: `Are you sure you want to delete the mapping "${mapping.name}"? This action cannot be undone.`,
      buttons: [
        {
          label: "Cancel",
          className: "btn-secondary",
        },
        {
          label: "Delete",
          className: "btn-danger",
          onClick: async () => {
            try {
              await mappingApi.deleteMapping(mapping.id);
              // Call the onDelete callback to refresh the list
              if (onDelete) {
                onDelete();
              }
            } catch (error) {
              handleApiError(error, showModal);
            }
          },
        },
      ],
    });
  };

  return (
    <div className="card mb-3">
      <div className="card-header d-flex justify-content-between align-items-center">
        <h6 className="mb-0">
          {mapping.name}
          {isDraft && (
            <span className="badge rounded-pill border border-2 border-secondary bg-white text-dark ms-2">
              Draft
            </span>
          )}
          {isLocked && (
            <span className="badge rounded-pill border border-2 border-warning bg-white text-dark ms-2">
              Importing…
            </span>
          )}
        </h6>
        <div>
          {canModifyMappings && (
            <>
              <button
                className="btn btn-danger btn-xs me-1"
                onClick={handleDelete}
                disabled={isLocked}
                title={
                  isLocked
                    ? "An import is in progress for this mapping"
                    : "Delete this mapping"
                }
              >
                Delete
              </button>
              {isLocked ? (
                <button
                  className="btn btn-secondary btn-xs me-1"
                  disabled
                  title="An import is in progress for this mapping"
                >
                  Edit
                </button>
              ) : (
                <Link to={"/mapping{-$id}"} params={{ id: mapping.id }}>
                  <button className="btn btn-secondary btn-xs me-1">
                    Edit
                  </button>
                </Link>
              )}

              <button
                className="btn btn-secondary btn-xs me-1"
                onClick={handleCopy}
                disabled={isDraft || isLocked}
                title={
                  isLocked
                    ? "An import is in progress for this mapping"
                    : isDraft
                      ? "Complete the mapping to enable copy"
                      : "Copy this mapping"
                }
              >
                Copy
              </button>
            </>
          )}
          <button
            className="btn btn-primary btn-sm"
            onClick={handleImportClick}
            disabled={isDraft}
            title={isDraft ? "Complete the mapping to enable import" : ""}
          >
            Import
          </button>
        </div>
      </div>

      <div className="card-body">
        <p>Created: {mapping.created_at}</p>
        {mapping.updated_at && <p>Updated: {mapping.updated_at}</p>}
        {isDraft && (
          <p className="text-muted mb-0">
            <small>
              This mapping is incomplete and cannot be used for imports yet.
            </small>
          </p>
        )}
      </div>
    </div>
  );
}
