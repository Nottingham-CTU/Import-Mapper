import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import MappingCard from "../components/MappingCard.jsx";
import { mappingApi, handleApiError } from "../api/mappingApi";
import { useModal } from "../contexts/ModalContext.jsx";

export const Route = createFileRoute("/")({
  component: Index,
});

function Index() {
  const [mappings, setMappings] = useState([]);
  const [loading, setLoading] = useState(true);
  const { showModal } = useModal();

  useEffect(() => {
    getMappings();
  }, []);

  async function getMappings() {
    setLoading(true);
    try {
      const mappings = await mappingApi.getMappings();
      setMappings(mappings);
    } catch (error) {
      handleApiError(error, showModal);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <h5 className="text-secondary-emphasis mt-2 mb-3">Mappings</h5>
      {loading && <p>Loading mappings...</p>}
      {!mappings.length && !loading && (
        <p>There are no saved mappings for this project.</p>
      )}
      {mappings.map((mapping) => (
        <MappingCard
          mapping={mapping}
          key={mapping.id}
          onDelete={getMappings}
        />
      ))}
    </div>
  );
}
