import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { tanstackRouter } from "@tanstack/router-plugin/vite";

export default defineConfig({
  plugins: [tanstackRouter(), react()],
  base: "./",
  build: {
    manifest: true,
    sourcemap: process.env.NODE_ENV === "development",
    rollupOptions: {
      input: {
        main: "./src/App.jsx",
      },
      output: {
        entryFileNames: "[name]-[hash].js",
        chunkFileNames: "[name]-[hash].js",
        assetFileNames: "[name]-[hash].[ext]",
        manualChunks: {
          react: ["react", "react-dom"],
        },
      },
    },
  },
});
