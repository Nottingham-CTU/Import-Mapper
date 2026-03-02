import { useState } from "react";
import { VALIDATION, ARIA_LABELS, ARIA_DESCRIPTIONS } from "../constants";
import { validateMappingName } from "../utils/validation";

export default function NameStep({ value, onChange }) {
  const [validationError, setValidationError] = useState(null);
  const [touched, setTouched] = useState(false);

  const handleChange = (newValue) => {
    // Validate the new value
    const error = validateMappingName(newValue);
    setValidationError(error);

    // Always pass the value up (parent will handle form submission validation)
    onChange(newValue);
  };

  const handleBlur = () => {
    setTouched(true);
  };

  const showError = touched && validationError;
  const remainingChars = VALIDATION.MAX_NAME_LENGTH - value.length;

  return (
    <div className="col-8">
      <label htmlFor="map-name" className="form-label">
        Name
      </label>
      <input
        type="text"
        id="map-name"
        className={`form-control${showError ? " is-invalid" : ""}`}
        autoComplete="off"
        value={value}
        required
        maxLength={VALIDATION.MAX_NAME_LENGTH}
        onChange={(e) => handleChange(e.target.value)}
        onBlur={handleBlur}
        aria-label={ARIA_LABELS.MAPPING_NAME_INPUT}
        aria-describedby="map-name-help map-name-error"
        aria-invalid={showError ? "true" : "false"}
      />
      {showError && (
        <div id="map-name-error" className="invalid-feedback" role="alert">
          {validationError}
        </div>
      )}
      <div id="map-name-help" className="form-text">
        {ARIA_DESCRIPTIONS.MAPPING_NAME_HELP}
        {remainingChars < 50 && (
          <span className={remainingChars < 10 ? "text-warning" : ""} aria-live="polite">
            {" "}
            ({remainingChars} characters remaining)
          </span>
        )}
      </div>
    </div>
  );
}
