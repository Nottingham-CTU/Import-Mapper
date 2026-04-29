import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import {
  RouterProvider,
  createRouter,
  createHashHistory,
} from "@tanstack/react-router";
import { routeTree } from "./routeTree.gen";
import { ModalProvider } from "./contexts/ModalContext.jsx";

const hashHistory = createHashHistory();

const router = createRouter({ routeTree, history: hashHistory });

const App = () => {
  return (
    <ModalProvider>
      <RouterProvider router={router} />
    </ModalProvider>
  );
};
const container = document.getElementById("import-wrangler");
const root = createRoot(container);
root.render(
  <StrictMode>
    <App />
  </StrictMode>,
);

export const moduleObj = window.IMPORT_WRANGLER.moduleObj;
export const csrfToken = window.IMPORT_WRANGLER.csrfToken;
export const canModifyMappings = window.IMPORT_WRANGLER.canModifyMappings;
