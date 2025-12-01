// upload.js
// Validación cliente para el formulario de subida de ficheros de productos
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("uploadForm");
  const fileInput = document.getElementById("productFile");
  const info = document.getElementById("uploadInfo");

  if (!form || !fileInput || !info) return;

  const MAX_SIZE = 5 * 1024 * 1024; // 5 MB
  const ALLOWED = ["xlsx", "xls", "csv"];

  function getExt(name) {
    return (name.split(".").pop() || "").toLowerCase();
  }

  function showMessage(msg, type = "info") {
    // type: 'info' | 'error' | 'success'
    info.textContent = msg;
    info.className = `upload-info ${type}`;
  }

  fileInput.addEventListener("change", () => {
    const f = fileInput.files[0];
    if (!f) {
      showMessage("No se ha seleccionado ningún fichero.", "info");
      return;
    }

    const ext = getExt(f.name);
    if (!ALLOWED.includes(ext)) {
      showMessage("Extensión no permitida. Usa .xlsx, .xls o .csv.", "error");
      fileInput.value = "";
      return;
    }

    if (f.size > MAX_SIZE) {
      showMessage("El fichero supera el tamaño máximo de 5 MB.", "error");
      fileInput.value = "";
      return;
    }

    showMessage(
      `Fichero listo: ${f.name} (${(f.size / 1024).toFixed(1)} KB)`,
      "success"
    );
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const f = fileInput.files[0];
    if (!f) {
      showMessage("Selecciona un fichero antes de subir.", "error");
      return;
    }

    const ext = getExt(f.name);
    if (!ALLOWED.includes(ext)) {
      showMessage("Extensión no permitida. Usa .xlsx, .xls o .csv.", "error");
      return;
    }

    if (f.size > MAX_SIZE) {
      showMessage("El fichero supera el tamaño máximo de 5 MB.", "error");
      return;
    }

    // Preparar envío AJAX
    showMessage("Subiendo archivo... Por favor espera.", "info");
    const formData = new FormData(form);
    const resultDiv = document.getElementById("uploadResult");
    resultDiv.innerHTML = ""; // Limpiar resultados anteriores

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: formData,
      });

      // Intentar parsear JSON incluso si el status no es 200 (para leer errores del backend)
      let data;
      try {
        data = await response.json();
      } catch (err) {
        throw new Error("Respuesta del servidor no válida (no es JSON).");
      }

      if (!response.ok) {
        throw new Error(data.error || `Error del servidor: ${response.status}`);
      }

      // Éxito: Renderizar resultados
      showMessage("Proceso completado.", "success");
      renderResults(data, resultDiv);

    } catch (error) {
      console.error(error);
      showMessage(error.message, "error");
    }
  });

  function renderResults(data, container) {
    let html = `
      <div class="upload-summary">
        <h3>Resumen de Importación</h3>
        <ul>
          <li><strong>Importados (Nuevos):</strong> ${data.imported}</li>
          <li><strong>Actualizados:</strong> ${data.updated || 0}</li>
          <li><strong>Ignorados:</strong> ${data.ignored}</li>
        </ul>
      </div>
    `;

    // Tabla de Actualizados
    if (data.updates && data.updates.length > 0) {
      html += `
        <div class="upload-details">
          <h4>Productos Actualizados</h4>
          <table>
            <thead>
              <tr>
                <th>SKU</th>
                <th>Nombre</th>
              </tr>
            </thead>
            <tbody>
              ${data.updates.map(u => `
                <tr>
                  <td>${u.sku}</td>
                  <td>${u.nom}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    // Tabla de Errores
    if (data.errors && data.errors.length > 0) {
      html += `
        <div class="upload-details error-details">
          <h4>Errores / Ignorados</h4>
          <table>
            <thead>
              <tr>
                <th>Fila</th>
                <th>Motivo</th>
              </tr>
            </thead>
            <tbody>
              ${data.errors.map(e => `
                <tr>
                  <td>${e.row}</td>
                  <td>${e.reason}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    container.innerHTML = html;
  }
});
