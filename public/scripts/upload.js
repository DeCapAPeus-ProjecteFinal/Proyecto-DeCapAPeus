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

  form.addEventListener("submit", (e) => {
    const f = fileInput.files[0];
    if (!f) {
      e.preventDefault();
      showMessage("Selecciona un fichero antes de subir.", "error");
      return;
    }

    const ext = getExt(f.name);
    if (!ALLOWED.includes(ext)) {
      e.preventDefault();
      showMessage("Extensión no permitida. Usa .xlsx, .xls o .csv.", "error");
      return;
    }

    if (f.size > MAX_SIZE) {
      e.preventDefault();
      showMessage("El fichero supera el tamaño máximo de 5 MB.", "error");
      return;
    }

    // Dejar que el formulario se envíe normalmente.
    showMessage("Subiendo archivo... Por favor espera.", "info");
  });
});
