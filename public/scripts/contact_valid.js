const form = document.getElementById("contactForm");

// Mapeo de campos y sus inputs
const campos = {
  nombre: document.getElementById("name"),
  apellidos: document.getElementById("surname"),
  email: document.getElementById("email"),
  telefono: document.getElementById("tlf"),
  mensaje: document.getElementById("text"),
  terminos: document.getElementById("terms"),
  comprobar: document.getElementById("compro"),
};

// Escuchar cambios en cada campo para limpiar errores
for (const key in campos) {
  const input = campos[key];
  if (!input) continue;

  if (key === "terminos") {
    input.addEventListener("change", () => {
      limpiarError(input);
    });
  } else {
    input.addEventListener("input", () => {
      limpiarError(input);
    });
  }
}

// Al enviar el formulario
form.addEventListener("submit", (event) => {
  event.preventDefault();
  let valido = true;

  // Limpiar errores previos
  Object.values(campos).forEach(limpiarError);

  // Validaciones
  if (!campos.comprobar.checked) {
    if (campos.nombre.value.trim() === "") {
      mostrarError(campos.nombre, "Por favor, ingrese su nombre.");
      valido = false;
    }
    if (campos.apellidos.value.trim() === "") {
      mostrarError(campos.apellidos, "Por favor, ingrese sus apellidos.");
      valido = false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (campos.email.value.trim() === "") {
      mostrarError(campos.email, "Por favor, ingrese su email.");
      valido = false;
    } else if (!emailRegex.test(campos.email.value.trim())) {
      mostrarError(campos.email, "Por favor, ingrese un email válido.");
      valido = false;
    }

    const telefonoRegex = /^\d{9}$/;
    if (campos.telefono.value.trim() === "") {
      mostrarError(campos.telefono, "Por favor, ingrese su teléfono.");
      valido = false;
    } else if (!telefonoRegex.test(campos.telefono.value.trim())) {
      mostrarError(campos.telefono, "Debe tener 9 dígitos numéricos.");
      valido = false;
    }

    if (campos.mensaje.value.trim() === "") {
      mostrarError(campos.mensaje, "Por favor, ingrese su mensaje.");
      valido = false;
    }
    if (!campos.terminos.checked) {
      mostrarError(campos.terminos, "Debe aceptar los términos y condiciones.");
      valido = false;
    }

    // Si todo es válido → redirigir
    if (valido) {
      form.submit();
    }
  } else {
    form.submit();
  }
});

// ---- Funciones auxiliares ----
function mostrarError(input, mensaje) {
  const formGroup = input.closest(".form-group");
  const error = formGroup.querySelector(".error");
  if (error) error.textContent = mensaje;
  input.classList.add("error-border");
}

function limpiarError(input) {
  const formGroup = input.closest(".form-group");
  const error = formGroup.querySelector(".error");
  if (error) error.textContent = "";
  input.classList.remove("error-border");
}
