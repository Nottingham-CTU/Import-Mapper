import { WIZARD_STEPS, ARIA_LABELS } from "../constants.js";

export default function StepIndicator({ currentStep }) {
  const steps = [
    { number: WIZARD_STEPS.NAME, title: "Name" },
    { number: WIZARD_STEPS.UPLOAD, title: "Upload" },
    { number: WIZARD_STEPS.MAP, title: "Map" },
    { number: WIZARD_STEPS.MATCH, title: "Match" },
  ];

  function StepListItem({ step, title }) {
    let linkClass = "d-flex align-items-center" + " ";
    let badgeClass = "badge me-1 fs-6" + " ";
    let status = "";

    if (step < currentStep) {
      badgeClass += "bg-success";
      linkClass += "text-body";
      status = "completed";
    } else if (step === currentStep) {
      linkClass += "fw-semibold";
      badgeClass += "text-bg-primary";
      status = "current";
    } else if (step > currentStep) {
      linkClass += "disabled";
      badgeClass += "text-bg-secondary";
      status = "upcoming";
    }

    const ariaLabel = status === "current"
      ? `${title} - ${ARIA_LABELS.CURRENT_STEP}`
      : `${title} - ${status}`;

    return (
      <li className="d-flex align-items-center">
        <span
          className={linkClass}
          aria-label={ariaLabel}
          aria-current={status === "current" ? "step" : undefined}
        >
          <span className={badgeClass} aria-hidden="true">
            {step >= currentStep ? step : <i className="fas fa-check"></i>}
          </span>
          {title}
        </span>
      </li>
    );
  }

  return (
    <nav aria-label={ARIA_LABELS.STEP_INDICATOR}>
      <ol className="d-flex justify-content-evenly gap-2" role="list">
        {steps.map(({ number, title }) => (
          <StepListItem key={number} step={number} title={title} />
        ))}
      </ol>
    </nav>
  );
}
