// Cliente del API. Centraliza el manejo del token CSRF y de los errores.
//
// Todas las llamadas van a rutas /api (mismo origen via el proxy de Vite), por
// eso la cookie de sesion viaja sola. En las mutaciones agregamos la cabecera
// X-CSRF-Token con un token que el backend solo entrega por GET /api/csrf-token
// dentro de la misma sesion.

let csrfToken = null;

async function fetchCsrfToken() {
  const res = await fetch("/api/csrf-token", { credentials: "same-origin" });
  if (!res.ok) throw new ApiError("No se pudo obtener el token CSRF", res.status);
  const data = await res.json();
  csrfToken = data.token;
  return csrfToken;
}

export class ApiError extends Error {
  constructor(message, status, errors) {
    super(message);
    this.status = status;
    this.errors = errors || null;
  }
}

async function request(path, { method = "GET", body } = {}) {
  const isMutation = method !== "GET";
  const headers = {};
  if (body !== undefined) headers["Content-Type"] = "application/json";

  if (isMutation) {
    if (!csrfToken) await fetchCsrfToken();
    headers["X-CSRF-Token"] = csrfToken;
  }

  let res = await fetch(path, {
    method,
    credentials: "same-origin",
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  // Si el token expiro o rroto (403), lo renovamos una vez y reintentamos.
  if (isMutation && res.status === 403) {
    await fetchCsrfToken();
    headers["X-CSRF-Token"] = csrfToken;
    res = await fetch(path, {
      method,
      credentials: "same-origin",
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  }

  if (res.status === 204) return null;

  let data = null;
  const text = await res.text();
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = null;
    }
  }

  if (!res.ok) {
    const message = (data && data.error) || "Error de la API";
    throw new ApiError(message, res.status, data && data.errors);
  }

  return data;
}

export const api = {
  me: () => request("/api/me"),
  register: (email, password) =>
    request("/api/register", { method: "POST", body: { email, password } }),
  login: (email, password) =>
    request("/api/login", { method: "POST", body: { email, password } }),
  logout: () => request("/api/logout", { method: "POST" }),
  listTasks: () => request("/api/tasks"),
  createTask: (title) => request("/api/tasks", { method: "POST", body: { title } }),
  updateTask: (id, changes) =>
    request(`/api/tasks/${id}`, { method: "PATCH", body: changes }),
  deleteTask: (id) => request(`/api/tasks/${id}`, { method: "DELETE" }),
};
