import { moduleObj } from "../App";

/**
 * API Response Error
 * Thrown when API calls fail or return error responses
 */
export class ApiError extends Error {
  constructor(message, originalError = null, response = null) {
    super(message);
    this.name = "ApiError";
    this.originalError = originalError;
    this.response = response;
  }
}

/**
 * Validates that a response has the expected structure
 * @param {Object} response - The API response
 * @param {Array<string>} requiredFields - Fields that must exist in response
 * @throws {ApiError} if validation fails
 */
function validateResponse(response, requiredFields = []) {
  if (!response || typeof response !== "object") {
    throw new ApiError("Invalid response format: expected an object");
  }

  for (const field of requiredFields) {
    if (!(field in response)) {
      throw new ApiError(
        `Invalid response format: missing required field '${field}'`
      );
    }
  }

  return response;
}

/**
 * Centralized API client for Import Mapper
 * Provides consistent error handling and response validation
 */
export const mappingApi = {
  /**
   * Get all mappings for the current project
   * @returns {Promise<Array>} Array of mapping objects
   */
  async getMappings() {
    try {
      const response = await moduleObj.ajax("get_mappings", {});
      validateResponse(response, ["mappings"]);
      return response.mappings;
    } catch (error) {
      console.error("Failed to get mappings:", error);
      throw new ApiError(
        "Failed to load mappings. Please refresh the page and try again.",
        error
      );
    }
  },

  /**
   * Get a single mapping by ID
   * @param {string} id - The mapping ID
   * @returns {Promise<Object>} The mapping object with structureChanged flag
   */
  async getMapping(id) {
    try {
      const response = await moduleObj.ajax("get_mapping", { id });

      if (response.success === false) {
        throw new ApiError(
          response.errors ? response.errors.join("\n") : "Failed to load mapping",
          null,
          response
        );
      }

      validateResponse(response, ["mapping"]);

      // Include structureChanged and lockedByImport flags from backend response
      const mapping = response.mapping;
      mapping.structureChanged = response.structureChanged || false;
      mapping.lockedByImport   = response.lockedByImport   || false;

      return mapping;
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to get mapping:", error);
      throw new ApiError(
        "Failed to load mapping. Please try again.",
        error
      );
    }
  },

  /**
   * Save a new mapping
   * @param {Object} mappingData - The mapping data to save
   * @returns {Promise<Object>} Response with created mapping ID
   */
  async saveMapping(mappingData) {
    try {
      const response = await moduleObj.ajax("save_mapping", mappingData);

      if (response.success === false) {
        throw new ApiError(
          response.errors
            ? "The mapping could not be saved. Please review the following:\n\n" + response.errors.join("\n")
            : "Failed to save mapping",
          null,
          response
        );
      }

      validateResponse(response, ["id"]);
      return response;
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to save mapping:", error);
      throw new ApiError(
        "Failed to save mapping. Please try again.",
        error
      );
    }
  },

  /**
   * Update an existing mapping
   * @param {Object} mappingData - The mapping data to update (must include id)
   * @returns {Promise<Object>} Response object
   */
  async updateMapping(mappingData) {
    try {
      if (!mappingData.id) {
        throw new ApiError("Mapping ID is required for update");
      }

      const response = await moduleObj.ajax("update_mapping", mappingData);

      if (response.success === false) {
        throw new ApiError(
          response.errors
            ? "The mapping could not be saved. Please review the following:\n\n" + response.errors.join("\n")
            : "Failed to update mapping",
          null,
          response
        );
      }

      return response;
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to update mapping:", error);
      throw new ApiError(
        "Failed to update mapping. Please try again.",
        error
      );
    }
  },

  /**
   * Update mapping status only, without triggering full validation
   * Used to mark a mapping as draft when project structure has changed
   * @param {string} id - The mapping ID
   * @param {string} status - The new status ('draft' or 'final')
   * @returns {Promise<Object>} Response object
   */
  async updateMappingStatus(id, status) {
    try {
      const response = await moduleObj.ajax("update_mapping_status", { id, status });

      if (response.success === false) {
        throw new ApiError(
          response.errors ? response.errors.join("\n") : "Failed to update mapping status",
          null,
          response
        );
      }

      return response;
    } catch (error) {
      if (error instanceof ApiError) throw error;
      console.error("Failed to update mapping status:", error);
      throw new ApiError("Failed to update mapping status. Please try again.", error);
    }
  },

  /**
   * Delete a mapping
   * @param {string} id - The mapping ID to delete
   * @returns {Promise<Object>} Response object
   */
  async deleteMapping(id) {
    try {
      const response = await moduleObj.ajax("delete_mapping", { id });

      if (response.success === false) {
        throw new ApiError(
          response.errors ? response.errors.join("\n") : "Failed to delete mapping",
          null,
          response
        );
      }

      return response;
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to delete mapping:", error);
      throw new ApiError(
        "Failed to delete mapping. Please try again.",
        error
      );
    }
  },

  /**
   * Get the current project structure
   * @returns {Promise<Object>} The project structure object
   */
  async getProjectStructure() {
    try {
      const response = await moduleObj.ajax("get_project_structure");

      if (response.success === false) {
        throw new ApiError(
          response.errors ? response.errors.join("\n") : "Failed to load project structure",
          null,
          response
        );
      }

      validateResponse(response, ["projectStructure"]);
      return response.projectStructure;
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to get project structure:", error);
      throw new ApiError(
        "Failed to load project structure. Please refresh the page and try again.",
        error
      );
    }
  },

  /**
   * Get the current project structure hash
   * @returns {Promise<string>} The project structure hash
   */
  async getProjectStructureHash() {
    try {
      const response = await moduleObj.ajax("get_project_structure_hash", {});

      if (response.success === false) {
        throw new ApiError(
          response.errors ? response.errors.join("\n") : "Failed to get project structure hash",
          null,
          response
        );
      }

      validateResponse(response, ["hash"]);
      return response.hash;
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to get project structure hash:", error);
      throw new ApiError(
        "Failed to get project structure hash. Please try again.",
        error
      );
    }
  },

  /**
   * Validate CSV content and get preview
   * @param {string} csvContent - The CSV file content
   * @param {string[]} requiredFields - Optional array of required field names
   * @returns {Promise<Object>} Preview data with headers, rows, totalRows, and errors
   */
  async validateCsv(csvContent, requiredFields = []) {
    try {
      const response = await moduleObj.ajax("validate_csv", {
        csvContent,
        requiredFields,
      });

      if (!response.success) {
        return {
          headers: [],
          preview: [],
          totalRows: 0,
          errors: response.errors || ["Failed to validate CSV"],
        };
      }

      validateResponse(response, ["headers", "preview", "totalRows"]);
      return {
        headers: response.headers,
        preview: response.preview,
        totalRows: response.totalRows,
        errors: response.errors || [],
      };
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to validate CSV:", error);
      throw new ApiError(
        "Failed to validate CSV. Please try again.",
        error
      );
    }
  },

  /**
   * Preview the result of a PHP preg_replace on a sample value
   * @param {string} pattern - The regex pattern (e.g. /(\w)\w+/)
   * @param {string} replacement - The replacement string (e.g. $1)
   * @param {string} value - The sample value to apply the regex to
   * @returns {Promise<string>} The transformed result string
   * @throws {ApiError} if the pattern is invalid or the request fails
   */
  async previewRegex(pattern, replacement, value) {
    try {
      const response = await moduleObj.ajax("preview_regex", { pattern, replacement, value });

      if (response.success === false) {
        throw new ApiError(
          response.errors ? response.errors.join("\n") : "Invalid regex pattern",
          null,
          response
        );
      }

      validateResponse(response, ["result"]);
      return response.result;
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to preview regex:", error);
      throw new ApiError(
        "Failed to preview regex. Please try again.",
        error
      );
    }
  },

  async getLogs() {
    try {
      const response = await moduleObj.ajax("get_logs", {});
      validateResponse(response, ["logs"]);
      return response.logs;
    } catch (error) {
      if (error instanceof ApiError) throw error;
      throw new ApiError("Failed to load import logs. Please try again.", error);
    }
  },

  async getActiveJobs() {
    try {
      const response = await moduleObj.ajax("get_active_jobs", {});
      validateResponse(response, ["jobs"]);
      return response.jobs;
    } catch (error) {
      if (error instanceof ApiError) throw error;
      throw new ApiError("Failed to load active jobs. Please try again.", error);
    }
  },

  /**
   * Copy a mapping
   * @param {string} id - The mapping ID to copy
   * @returns {Promise<Object>} Response with the new mapping object
   */
  async copyMapping(id) {
    try {
      const response = await moduleObj.ajax("copy_mapping", { id });

      if (response.success === false) {
        throw new ApiError(
          response.errors ? response.errors.join("\n") : "Failed to copy mapping",
          null,
          response
        );
      }

      validateResponse(response, ["mapping"]);
      return response;
    } catch (error) {
      if (error instanceof ApiError) throw error;

      console.error("Failed to copy mapping:", error);
      throw new ApiError(
        "Failed to copy mapping. Please try again.",
        error
      );
    }
  },

  async cancelImportJob(jobId) {
    try {
      const response = await moduleObj.ajax("cancel_import_job", { jobId });
      if (response.success === false) {
        throw new ApiError(
          response.errors?.join(", ") || "Failed to cancel import job",
          null,
          response
        );
      }
      return response;
    } catch (error) {
      if (error instanceof ApiError) throw error;
      throw new ApiError("Failed to cancel import job. Please try again.", error);
    }
  },

};

/**
 * Helper function to handle API errors in components
 * Shows user-friendly error messages via modal
 * @param {ApiError} error - The API error
 * @param {Function} showModal - Modal display function
 * @param {Function} [onClose] - Optional callback when modal closes
 */
export function handleApiError(error, showModal, onClose = null) {
  const message =
    error instanceof ApiError
      ? error.message
      : "An unexpected error occurred. Please try again.";

  showModal({
    title: "Error",
    message: message,
    buttons: [
      {
        label: "OK",
        className: "btn-primary",
        onClick: onClose,
      },
    ],
  });
}
