import { Outlet, createRootRoute } from "@tanstack/react-router";
import Header from "../components/Header.jsx";
import ErrorBoundary from "../components/ErrorBoundary";

export const Route = createRootRoute({
  component: () => {
    return (
      <ErrorBoundary>
        <div className="col col-xxl-10 fs-6 me-2">
          <Header />
          <Outlet />
        </div>
      </ErrorBoundary>
    );
  },
});
