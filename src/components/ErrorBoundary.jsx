import React from "react";

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    // Update state so the next render will show the fallback UI
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    // Log the error to console for debugging
    console.error("Error caught by boundary:", error, errorInfo);

    // Store error details in state
    this.setState({
      error: error,
      errorInfo: errorInfo,
    });
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null, errorInfo: null });
    // Reload the page to reset the app state
    window.location.href = window.location.pathname;
  };

  render() {
    if (this.state.hasError) {
      return (
        <div className="col col-xxl-6 fs-6 me-2 p-3">
          <div className="alert alert-danger" role="alert">
            <h4 className="alert-heading">Something went wrong</h4>
            <p>
              An unexpected error occurred. Please try refreshing the page or
              contact support if the problem persists.
            </p>
            {this.state.error && (
              <details className="mt-3">
                <summary style={{ cursor: "pointer" }}>Error Details</summary>
                <pre className="mt-2 p-2 bg-light border rounded">
                  <code>
                    {this.state.error.toString()}
                    {this.state.errorInfo &&
                      this.state.errorInfo.componentStack}
                  </code>
                </pre>
              </details>
            )}
            <hr />
            <div className="d-flex gap-2">
              <button className="btn btn-primary" onClick={this.handleReset}>
                Reload Page
              </button>
              <a href="/" className="btn btn-secondary">
                Go to Dashboard
              </a>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
