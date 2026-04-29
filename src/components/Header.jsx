import { Link, useRouterState } from "@tanstack/react-router";
import { canModifyMappings } from "../App.jsx";

export default function Header() {
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const isHome = pathname === "/";
  return (
    <ul className="nav d-flex justify-content-between border-bottom align-items-center pb-2">
      <div className="d-flex justify-content-start align-items-center">
        <li className="nav-item">
          <h4 className="fw-semibold mb-0">Import Mapper</h4>
        </li>
        <li className="nav-item">
          <Link className="nav-link" to={"/"}>
            Mappings
          </Link>
        </li>
        <li className="nav-item">
          <Link className="nav-link" to={"/Imports"}>
            Imports
          </Link>
        </li>
      </div>
      {canModifyMappings && isHome && (
        <li className="nav-item">
          <Link to={"/mapping"} id={null}>
            <button className="btn btn-success">
              <span className="d-none d-lg-inline">New mapping</span>
              <span className="d-inline d-lg-none">New</span>
            </button>
          </Link>
        </li>
      )}
    </ul>
  );
}
