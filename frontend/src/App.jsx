import { useEffect, useState } from "react";
import { api, ApiError } from "./api.js";
import AuthForm from "./AuthForm.jsx";
import TaskList from "./TaskList.jsx";

export default function App() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Al cargar, preguntamos si hay sesion activa (GET /api/me). Un 401 es la via
  // normal "no logueado": mostramos el formulario en vez de tratarlo como error.
  useEffect(() => {
    api
      .me()
      .then((u) => setUser(u))
      .catch((err) => {
        if (!(err instanceof ApiError && err.status === 401)) {
          console.error("Error consultando la sesion:", err);
        }
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <main className="container">Cargando…</main>;
  }

  return (
    <main className="container">
      <header className="topbar">
        <h1>Symfony + React</h1>
        {user && (
          <div className="session">
            <span>{user.email}</span>
            <button
              onClick={async () => {
                await api.logout();
                setUser(null);
              }}
            >
              Salir
            </button>
          </div>
        )}
      </header>

      {user ? (
        <TaskList onUnauthenticated={() => setUser(null)} />
      ) : (
        <AuthForm onAuthenticated={(u) => setUser(u)} />
      )}
    </main>
  );
}
