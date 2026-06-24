import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

// El frontend corre en el puerto 3000 dentro del contenedor; el navegador entra
// por el 8099 publicado en el host.
//
// Proxy: todo lo que empiece con /api se reenvia al backend (servicio "api" de
// docker-compose). Asi el navegador ve UN solo origen (localhost:8099) y la
// cookie de sesion HttpOnly viaja en cada request sin necesitar CORS.
export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 3000,
    // Alineamos el websocket de HMR al puerto publicado en el host.
    hmr: { clientPort: 8099 },
    proxy: {
      "/api": {
        target: "http://api:80",
        changeOrigin: true,
      },
    },
  },
});
