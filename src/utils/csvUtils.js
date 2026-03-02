/**
 * Lightweight CSV utility functions
 * Note: Heavy CSV parsing is done on backend via validateCsv API
 */

/**
 * Reads a file as text
 * @param {File} file - File to read
 * @returns {Promise<string>} File content as text
 */
export function readFileAsText(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = (e) => {
      resolve(e.target.result);
    };

    reader.onerror = () => {
      reject(new Error("Failed to read file. Please try again."));
    };

    reader.readAsText(file);
  });
}

/**
 * Extracts headers from CSV file (simple extraction for upload step)
 * @param {File} file - CSV file
 * @returns {Promise<string[]>} Array of header names
 */
export async function extractCSVHeaders(file) {
  const text = await file.text();
  const firstLine = text.split("\n")[0];
  return firstLine
      .split(",")
      .map((h) => h.trim().replace(/^"|"$/g, ""));
}

/**
 * Validates CSV headers for empty values
 * @param {string[]} headers - Array of header names
 * @returns {Object} { valid: boolean, emptyIndexes: number[] }
 */
export function validateHeaders(headers) {
  const emptyIndexes = headers
    .map((h, i) => (h === "" ? i + 1 : null))
    .filter(Boolean);

  return {
    valid: emptyIndexes.length === 0,
    emptyIndexes,
  };
}

/**
 * Merges two arrays of headers, removing duplicates
 * @param {string[]} existing - Existing headers
 * @param {string[]} newHeaders - New headers to merge
 * @returns {string[]} Merged unique headers
 */
export function mergeHeaders(existing, newHeaders) {
  return [...new Set([...existing, ...newHeaders])];
}

/**
 * Parses error messages from import results
 * Detects expandable errors with affected items list
 * @param {string} error - Error message
 * @returns {Object} Parsed error with type and details
 */
export function parseImportError(error) {
  // Match pattern: "description, affects rows/columns/headers: list"
  const match = error.match(/^(.+), affects (rows|columns|headers): (.+)$/);

  if (match) {
    return {
      type: "expandable",
      description: match[1],
      affectsType: match[2],
      affectedList: match[3],
    };
  }

  return {
    type: "simple",
    message: error,
  };
}
