import { useEffect, useState } from "react";
import { api, ApiError } from "./api.js";

// Lista de tareas del usuario: crear, marcar hecha/pendiente y eliminar.
// Si una llamada devuelve 401 (sesion vencida), avisa al padre para volver al login.
export default function TaskList({ onUnauthenticated }) {
  const [tasks, setTasks] = useState([]);
  const [title, setTitle] = useState("");
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);

  function handleError(err) {
    if (err instanceof ApiError && err.status === 401) {
      onUnauthenticated();
      return;
    }
    if (err instanceof ApiError && err.errors) {
      setError(Object.values(err.errors).join(" "));
    } else {
      setError(err.message || "Error inesperado.");
    }
  }

  async function load() {
    setLoading(true);
    try {
      setTasks(await api.listTasks());
      setError(null);
    } catch (err) {
      handleError(err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function addTask(e) {
    e.preventDefault();
    const value = title.trim();
    if (!value) return;
    try {
      const created = await api.createTask(value);
      setTasks((prev) => [created, ...prev]);
      setTitle("");
      setError(null);
    } catch (err) {
      handleError(err);
    }
  }

  async function toggle(task) {
    try {
      const updated = await api.updateTask(task.id, { done: !task.done });
      setTasks((prev) => prev.map((t) => (t.id === task.id ? updated : t)));
    } catch (err) {
      handleError(err);
    }
  }

  async function remove(task) {
    try {
      await api.deleteTask(task.id);
      setTasks((prev) => prev.filter((t) => t.id !== task.id));
    } catch (err) {
      handleError(err);
    }
  }

  return (
    <section className="card">
      <h2>Mis tareas</h2>

      <form onSubmit={addTask} className="add">
        <input
          type="text"
          placeholder="Nueva tarea…"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          maxLength={255}
        />
        <button type="submit">Agregar</button>
      </form>

      {error && <p className="error">{error}</p>}

      {loading ? (
        <p>Cargando…</p>
      ) : tasks.length === 0 ? (
        <p className="empty">No tenes tareas todavia.</p>
      ) : (
        <ul className="tasks">
          {tasks.map((task) => (
            <li key={task.id} className={task.done ? "done" : ""}>
              <label>
                <input
                  type="checkbox"
                  checked={task.done}
                  onChange={() => toggle(task)}
                />
                <span>{task.title}</span>
              </label>
              <button className="link danger" onClick={() => remove(task)}>
                Eliminar
              </button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
