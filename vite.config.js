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
      },
      plugins: [
        {
          name: 'rewrite-asset-paths',
          generateBundle(_, bundle) {
            for (const file of Object.values(bundle)) {
              if (file.type === 'chunk') {
                file.code = file.code.replace( /"\.\/([^"]+\.js)"/g,
                                               '"./?prefix=import_mapper&page=$1"' );
              }
            }
          }
        }
      ],
    },
  },
});
