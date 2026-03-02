import { createContext, useContext, useState } from "react";
import ConfirmModal from "../components/ConfirmModal.jsx";

const ModalContext = createContext();

export function ModalProvider({ children }) {
  const [modalConfig, setModalConfig] = useState({
    show: false,
    title: "",
    message: "",
    buttons: [],
  });

  const showModal = ({ title, message, buttons }) => {
    setModalConfig({
      show: true,
      title,
      message,
      buttons,
    });
  };

  const hideModal = () => {
    setModalConfig((prev) => ({ ...prev, show: false }));
  };

  return (
    <ModalContext.Provider value={{ showModal, hideModal }}>
      {children}
      <ConfirmModal
        title={modalConfig.title}
        message={modalConfig.message}
        buttons={modalConfig.buttons}
        show={modalConfig.show}
        onHide={hideModal}
      />
    </ModalContext.Provider>
  );
}

export function useModal() {
  const context = useContext(ModalContext);
  if (!context) {
    throw new Error("useModal must be used within a ModalProvider");
  }
  return context;
}
