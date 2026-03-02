import { useEffect, useRef } from "react";

export default function ConfirmModal({
  id = "confirm-modal",
  title,
  message,
  buttons = [],
  show,
  onHide,
}) {
  const modalRef = useRef(null);
  const bsModalRef = useRef(null);
  const onHideRef = useRef(onHide);

  // Keep onHide ref up to date without triggering effect
  useEffect(() => {
    onHideRef.current = onHide;
  }, [onHide]);

  // Initialize modal once on mount
  useEffect(() => {
    if (!modalRef.current) return;

    const bootstrap = window.bootstrap;
    bsModalRef.current = new bootstrap.Modal(modalRef.current, {
      backdrop: "static",
      keyboard: false,
    });

    // Handle modal hide event using ref to avoid stale closure
    const handleHidden = () => {
      if (onHideRef.current) {
        onHideRef.current();
      }
    };

    modalRef.current.addEventListener("hidden.bs.modal", handleHidden);

    // Cleanup on unmount only
    return () => {
      if (modalRef.current) {
        modalRef.current.removeEventListener("hidden.bs.modal", handleHidden);
      }
      if (bsModalRef.current) {
        bsModalRef.current.dispose();
        bsModalRef.current = null;
      }
    };
  }, []); // Only run once on mount

  // Separate effect to handle show/hide
  useEffect(() => {
    if (!bsModalRef.current) return;

    if (show) {
      bsModalRef.current.show();
    } else {
      bsModalRef.current.hide();
    }
  }, [show]);

  const handleButtonClick = (onClick) => {
    if (onClick) {
      onClick();
    }
    // Modal will auto-hide via data-bs-dismiss="modal"
  };

  return (
    <div
      className="modal fade"
      id={id}
      tabIndex="-1"
      ref={modalRef}
      data-bs-backdrop="static"
      data-bs-keyboard="false"
    >
      <div className="modal-dialog">
        <div className="modal-content">
          {title && (
            <div className="modal-header">
              <h1 className="modal-title fs-5">{title}</h1>
            </div>
          )}
          {message && <div className="modal-body" style={{ whiteSpace: "pre-wrap" }}>{message}</div>}
          {buttons.length > 0 && (
            <div className="modal-footer">
              {buttons.map((button, index) => (
                <button
                  key={index}
                  type="button"
                  className={`btn ${button.className || "btn-primary"}`}
                  data-bs-dismiss="modal"
                  onClick={() => handleButtonClick(button.onClick)}
                >
                  {button.label}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
