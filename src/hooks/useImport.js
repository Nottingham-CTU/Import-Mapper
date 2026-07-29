/**
 * Import state management hook
 * Encapsulates file handling, validation, and import execution logic
 */

import { useState, useCallback, useRef } from "react";
import { readFileAsText } from "../utils/csvUtils";
import { validateCSVFile } from "../utils/validation";
import { mappingApi } from "../api/mappingApi";

/**
 * Custom hook for managing import state
 * @param {Object} params - Hook parameters
 * @param {Object} params.mapping - Mapping configuration
 * @param {string} params.uploadUrl - URL for the file upload endpoint
 * @param {string} params.csrfToken - CSRF token for the upload request
 * @returns {Object} Import state and control functions
 */
export function useImport({ mapping, uploadUrl, csrfToken }) {
  const [importStatus, setImportStatus] = useState("idle");
  const [isImporting, setIsImporting] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);
  const [selectedDag, setSelectedDag] = useState(null);
  const [csvPreview, setCsvPreview] = useState(null);
  const [validationErrors, setValidationErrors] = useState([]);
  const [importResults, setImportResults] = useState(null);
  const fileInputRef = useRef(null);

  /**
   * Handles file selection and validation
   */
  const handleFileSelect = useCallback(
    async (event) => {
      const file = event.target.files[0];

      if (!file) {
        setSelectedFile(null);
        setCsvPreview(null);
        setValidationErrors([]);
        return;
      }

      // Validate file
      const fileValidation = validateCSVFile(file);
      if (!fileValidation.valid) {
        setValidationErrors([fileValidation.error]);
        setSelectedFile(null);
        setCsvPreview(null);
        return;
      }

      setSelectedFile(file);

      try {
        // Read and parse CSV via backend
        const content = await readFileAsText(file);
        const result = await mappingApi.validateCsv(
          content,
          mapping?.csvFields || []
        );

        setValidationErrors(result.errors);
        setCsvPreview(
          result.errors.length === 0
            ? {
                headers: result.headers,
                rows: result.preview,
                totalRows: result.totalRows,
              }
            : null,
        );
      } catch (error) {
        setValidationErrors([error.message]);
        setCsvPreview(null);
      }
    },
    [mapping],
  );
  
  function handleDagSelect(dagName) {
   const nextDag = dagName;
   setSelectedDag(nextDag);
   setDateFormat
  }
  
  /**
   * Starts the import process by uploading the file to upload.php
   */
  const handleStartImport = useCallback(async () => {
    if (!selectedFile || validationErrors.length > 0) return;
    if (mapping?.status === "draft") return;

    setIsImporting(true);
    setImportStatus("uploading");
    setImportResults(null);

    try {
      const formData = new FormData();
      formData.append("csv_file", selectedFile);
      formData.append("mapping_id", mapping.id);
      formData.append("redcap_csrf_token", csrfToken);
      formData.append("mapping_dag", selectedDag);

      const response = await fetch(uploadUrl, { method: "POST", body: formData });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const result = await response.json();
      if (result.success) {
        setImportStatus("queued");
        setImportResults(result);
      } else {
        setImportStatus("error");
        setImportResults({
          success: false,
          error: result.errors?.join(", ") || "Failed to queue import job",
        });
      }
    } catch (error) {
      setImportStatus("error");
      setImportResults({ success: false, error: error.message || "Upload failed" });
    } finally {
      setIsImporting(false);
    }
  }, [selectedFile, selectedDag, validationErrors, mapping, uploadUrl, csrfToken]);

  /**
   * Resets import state to allow another import
   */
  const handleReset = useCallback(() => {
    setSelectedFile(null);
    setSelectedDag(null);
    setCsvPreview(null);
    setValidationErrors([]);
    setImportStatus("idle");
    setImportResults(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = "";
    }
  }, []);

  return {
    // State
    importStatus,
    isImporting,
    selectedFile,
    csvPreview,
    validationErrors,
    importResults,
    selectedDag,
    
    // Ref
    fileInputRef,

    // Actions
    handleFileSelect,
    handleDagSelect,
    handleStartImport,
    handleReset,
  };
}
