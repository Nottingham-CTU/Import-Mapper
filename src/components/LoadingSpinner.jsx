/**
 * Loading spinner component
 * Displays a loading indicator with optional message
 */

function LoadingSpinner({
  size = "md",
  message = "Loading...",
  inline = false,
  className = "",
}) {
  const sizeClass = {
    sm: "spinner-border-sm",
    md: "",
    lg: "spinner-border-lg",
  }[size];

  if (inline) {
    return (
      <div className={`d-inline-flex align-items-center ${className}`}>
        <div
          className={`spinner-border ${sizeClass} me-2`}
          role="status"
          aria-label={message}
        >
          <span className="visually-hidden">{message}</span>
        </div>
        {message && <span aria-live="polite">{message}</span>}
      </div>
    );
  }

  return (
    <div className={`text-center p-4 ${className}`} role="status" aria-live="polite">
      <div className={`spinner-border ${sizeClass}`} aria-label={message}>
        <span className="visually-hidden">{message}</span>
      </div>
      {message && <div className="mt-2">{message}</div>}
    </div>
  );
}

export default LoadingSpinner;
