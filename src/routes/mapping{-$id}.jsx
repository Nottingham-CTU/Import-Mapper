import {
  createFileRoute,
  useBlocker,
  useNavigate,
} from "@tanstack/react-router";
import { useEffect, useState, useRef } from "react";
import UploadStep from "../steps/UploadStep";
import StepIndicator from "../steps/StepIndicator.jsx";
import NameStep from "../steps/NameStep";
import MapStep from "../steps/MapStep";
import MatchStep from "../steps/MatchStep";
import ConfirmModal from "../components/ConfirmModal.jsx";
import { useModal } from "../contexts/ModalContext.jsx";
import { mappingApi, handleApiError } from "../api/mappingApi";

export const Route = createFileRoute("/mapping{-$id}")({
  component: Mapping,
});

function createDefaultMatching() {
  return {
    record: {
      enabled: false,
      fieldMappingId: "",
    },
    form: {
      enabled: false,
      fieldMappingIds: [],
    },
    event: {
      enabled: false,
      fieldMappingIds: [],
    },
    dag: {
      enabled: false,
      mode: "",
      dagUniqueName: "",
      csvFieldName: "",
    },
  };
}

function Mapping() {
  const { id } = Route.useParams();
  const navigate = useNavigate();
  const { showModal } = useModal();
  const [mapping, setMapping] = useState({
    id: "",
    name: "",
    csvFields: [],
    projectStructure: {
      is_longitudinal: false,
      repeating_event_names: [],
      repeating_forms_by_event: [],
      repeating_forms: [],
      all_event_names: {},
      all_form_names_by_event: {},
      all_form_names: [],
      fields_by_form: {},
      field_types_by_form: {},
      data_access_groups: {},
    },
    fieldMappings: [{}],
    matching: createDefaultMatching(),
  });
  const [loading, setLoading] = useState(true);
  const [step, setStep] = useState(1);
  const [unsavedChanges, setUnsavedChanges] = useState(false);
  const [isLocked, setIsLocked] = useState(false);
  const [hasValidationIssues, setHasValidationIssues] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [showNavigationModal, setShowNavigationModal] = useState(false);
  const [showStructureChangedModal, setShowStructureChangedModal] = useState(false);
  const hasInitiatedDraftUpdate = useRef(false);

  const newMapping = !id;

  const { proceed, reset } = useBlocker({
    shouldBlockFn: () => {
      // Don't block if we're in the middle of a save operation
      if (isSaving) return false;

      if (unsavedChanges) {
        setShowNavigationModal(true);
      }
      return unsavedChanges;
    },
    withResolver: true,
  });

  // Load mapping once on mount
  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => {
    if (!newMapping) {
      getMapping();
    } else {
      loadProjectStructure();
      setLoading(false);
    }
    // Intentionally empty: only load once on mount, newMapping/getMapping are stable
  }, []);

  useEffect(() => {
    if (
      !loading &&
      !newMapping &&
      mapping.structureChanged &&
      mapping.status !== "draft" &&
      !hasInitiatedDraftUpdate.current
    ) {
      hasInitiatedDraftUpdate.current = true;
      mappingApi
        .updateMappingStatus(mapping.id || id, "draft")
        .then(() => {
          setMapping((prev) => ({ ...prev, status: "draft" }));
          setShowStructureChangedModal(true);
        })
        .catch(() => {
          setShowStructureChangedModal(true);
        });
    } else if (
      !loading &&
      !newMapping &&
      mapping.structureChanged &&
      mapping.status === "draft" &&
      !hasInitiatedDraftUpdate.current
    ) {
      hasInitiatedDraftUpdate.current = true;
      setShowStructureChangedModal(true);
    }
  }, [loading, mapping.structureChanged, mapping.status]);

  async function loadProjectStructure() {
    try {
      const projectStructure = await mappingApi.getProjectStructure();
      setMapping((prev) => ({ ...prev, projectStructure }));
    } catch (error) {
      handleApiError(error, showModal);
    }
  }

  async function getMapping() {
    try {
      const [mappingData, projectStructure] = await Promise.all([
        mappingApi.getMapping(id),
        mappingApi.getProjectStructure(),
      ]);

      // Merge with defaults and use current structure
      setMapping((prev) => ({
        ...prev,
        ...mappingData,
        matching: mappingData.matching ?? createDefaultMatching(),
        projectStructure: projectStructure,
      }));
      setIsLocked(mappingData.lockedByImport === true);
    } catch (error) {
      handleApiError(error, showModal, () => navigate({ to: "/" }));
    } finally {
      setLoading(false);
    }
  }

  function handleBack() {
    setStep((prev) => prev - 1);
  }

  function handleContinue() {
    setStep((prev) => prev + 1);
  }

  async function handleSave() {
    setIsSaving(true);
    const response = await saveMapping("final");
    if(response == true)
       await navigate({to: "/"});
  }

  async function handleSaveAndLeave() {
    await saveMapping("draft");
    proceed();
  }

  async function handleSaveDraft() {
    await saveMapping("draft");
  }

  async function saveMapping(status) {
    try {
      // Check for active import lock before saving
      if (mapping.id || id) {
        const mappingId = mapping.id || id;
        const jobs = await mappingApi.getActiveJobs();
        const locked = jobs.some((j) => j.mappingId === mappingId);
        setIsLocked(locked);
        if (locked) {
          showModal({
            title: "Save blocked",
            message:
              "This mapping cannot be saved while an import is in progress. Please wait for the import to complete.",
            buttons: [{ label: "OK", className: "btn-primary" }],
          });
          return false;
        }
      }

      const payload = {
        name: mapping.name,
        csvFields: mapping.csvFields,
        fieldMappings: mapping.fieldMappings,
        matching: mapping.matching,
        status,
      };

      if (mapping.id || id) {
        payload.id = mapping.id || id;
        await mappingApi.updateMapping(payload);
      } else {
        const response = await mappingApi.saveMapping(payload);
        setMapping((prev) => ({ ...prev, id: response.id }));
      }

      setUnsavedChanges(false);
      return true;
    } catch (error) {
      handleApiError(error, showModal);
    }
    return false;
  }

  const structureChangedMessage = (
    <>
      <p>
        The REDCap project structure has changed since the mapping "
        <strong>{mapping.name || ""}</strong>" was last saved.
      </p>
      <p>
        This mapping has been marked as <strong>draft</strong>. Please review
        the field mappings before saving.
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
      <div>
        <h5 className="text-secondary-emphasis mt-2 mb-3">
          {newMapping ? "New mapping" : "Edit mapping"}
        </h5>
        <StepIndicator currentStep={step} />
        {!newMapping && loading && <p>Loading...</p>}
        {!loading && isLocked && (
          <div className="alert alert-warning" role="alert">
            An import is currently in progress for this mapping. Saving is disabled until the import completes.
          </div>
        )}
        {!loading && (
          <form
            onSubmit={(e) => {
              e.preventDefault();
              if (step < 4) {
                handleContinue();
              } else {
                handleSave();
              }
            }}
          >
            {step === 1 && (
              <NameStep
                value={mapping.name}
                onChange={(name) => {
                  setMapping((prev) => ({ ...prev, name }));
                  setUnsavedChanges(true);
                }}
              />
            )}
            {step === 2 && (
              <UploadStep
                value={mapping.csvFields}
                projectStructure={mapping.projectStructure}
                onChange={(csvFields) => {
                  setMapping((prev) => ({ ...prev, csvFields }));
                  setUnsavedChanges(true);
                }}
              />
            )}
            {step === 3 && (
              <MapStep
                value={mapping}
                onChange={(mappings, projectStructure, hasIssues) => {
                  setMapping((prev) => ({
                    ...prev,
                    fieldMappings: mappings,
                    projectStructure,
                  }));
                  setUnsavedChanges(true);
                  setHasValidationIssues(hasIssues);
                }}
                onProjectStructureLoad={(projectStructure, hasIssues) => {
                  setMapping((prev) => ({ ...prev, projectStructure }));
                  setHasValidationIssues(hasIssues);
                }}
              />
            )}
            {step === 4 && (
              <MatchStep
                value={mapping}
                onChange={(matching) => {
                  setMapping((prev) => ({ ...prev, matching }));
                  setUnsavedChanges(true);
                  setHasValidationIssues(false); // MatchStep auto-cleans, so no issues
                }}
              />
            )}
            <div className="d-flex justify-content-between mt-3">
              <button
                type="button"
                onClick={handleBack}
                className={`btn btn-secondary${step <= 1 ? " invisible" : ""}`}
              >
                Back
              </button>
              <div className="d-flex justify-content-end">
                <button
                  type="button"
                  className="btn btn-danger me-1"
                  onClick={() => navigate({ to: "/" })}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  className="btn btn-secondary me-1"
                  onClick={handleSaveDraft}
                  disabled={isLocked || !unsavedChanges}
                  title={
                    isLocked
                      ? "Saving is disabled while an import is in progress"
                      : !unsavedChanges
                      ? "No unsaved changes"
                      : "Save as draft without finalizing"
                  }
                >
                  Save Draft
                </button>
                <button
                  type="submit"
                  className={`btn ${step < 4 ? "btn-primary" : "btn-success"}`}
                  disabled={
                    (isLocked && step === 4) ||
                    ((step === 3 || step === 4) && hasValidationIssues)
                  }
                  title={
                    isLocked && step === 4
                      ? "Saving is disabled while an import is in progress"
                      : (step === 3 || step === 4) && hasValidationIssues
                      ? "Please fix validation errors before continuing"
                      : ""
                  }
                >
                  {step < 4 ? "Continue" : "Save"}
                </button>
              </div>
            </div>
          </form>
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
            onClick: () => {
              setShowStructureChangedModal(false);
              setStep(3);
            },
          },
        ]}
        show={showStructureChangedModal}
        onHide={() => setShowStructureChangedModal(false)}
      />

      <ConfirmModal
        id="confirm-nav-modal"
        title="Unsaved changes"
        message="Do you want to save your changes before leaving this page?"
        buttons={[
          {
            label: "Stay on page",
            className: "btn-secondary",
            onClick: () => {
              setShowNavigationModal(false);
              reset();
            },
          },
          {
            label: "Discard",
            className: "btn-danger",
            onClick: () => {
              setShowNavigationModal(false);
              proceed();
            },
          },
          {
            label: "Save draft",
            className: "btn-primary",
            onClick: handleSaveAndLeave,
          },
        ]}
        show={showNavigationModal}
        onHide={() => setShowNavigationModal(false)}
      />
    </>
  );
}
