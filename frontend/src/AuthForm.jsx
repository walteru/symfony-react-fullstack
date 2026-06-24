import { useState } from "react";
import { api, ApiError } from "./api.js";

// Formulario combinado de login / registro. Tras autenticar, avisa al padre.
export default function AuthForm({ onAuthenticated }) {
  const [mode, setMode] = useState("login"); // "login" | "register"
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  const isRegister = mode === "register";

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      if (isRegister) {
        await api.register(email, password);
      }
      const u = await api.login(email, password);
      onAuthenticated(u);
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setError(Object.values(err.errors).join(" "));
      } else if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("No se pudo completar la operacion.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="card">
      <h2>{isRegister ? "Crear cuenta" : "Iniciar sesion"}</h2>
      <form onSubmit={handleSubmit}>
        <label>
          Email
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            autoComplete="email"
          />
        </label>
        <label>
          Contrasena
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            minLength={isRegister ? 8 : undefined}
            autoComplete={isRegister ? "new-password" : "current-password"}
          />
        </label>
        {error && <p className="error">{error}</p>}
        <button type="submit" disabled={busy}>
          {busy ? "…" : isRegister ? "Registrarme" : "Entrar"}
        </button>
      </form>

      <p className="switch">
        {isRegister ? "Ya tenes cuenta?" : "No tenes cuenta?"}{" "}
        <button
          type="button"
          className="link"
          onClick={() => {
            setError(null);
            setMode(isRegister ? "login" : "register");
          }}
        >
          {isRegister ? "Inicia sesion" : "Registrate"}
        </button>
      </p>

      <p className="hint">
        Demo: <code>demo@example.com</code> / <code>demo1234</code>
      </p>
    </section>
  );
}
