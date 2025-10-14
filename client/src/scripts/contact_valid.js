const form = document.getElementById("contactForm");
const successMessage = document.getElementById("success-message");

form.addEventListener("submit", function (event) {
  event.preventDefault();

  const nombre = document.getElementById("name");
  const apellidos = document.getElementById("surname");
  const email = document.getElementById("email");
  const telefono = document.getElementById("tlf");
  const mensaje = document.getElementById("text");
  const terminos = document.getElementById("terms");

  let valido = true;

  limpiarErrores();
  ocultarMensaje();

  // Validaciones
  if (nombre.value.trim() === "") {
    mostrarError(nombre, "Por favor, ingrese su nombre.");
    valido = false;
  }

  if (apellidos.value.trim() === "") {
    mostrarError(apellidos, "Por favor, ingrese sus apellidos.");
    valido = false;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (email.value.trim() === "") {
    mostrarError(email, "Por favor, ingrese su email.");
    valido = false;
  } else if (!emailRegex.test(email.value.trim())) {
    mostrarError(email, "Por favor, ingrese un email válido.");
    valido = false;
  }

  const telefonoRegex = /^\d{9}$/;
  if (telefono.value.trim() === "") {
    mostrarError(telefono, "Por favor, ingrese su teléfono.");
    valido = false;
  } else if (!telefonoRegex.test(telefono.value.trim())) {
    mostrarError(telefono, "Debe tener 9 dígitos numéricos.");
    valido = false;
  }

  if (mensaje.value.trim() === "") {
    mostrarError(mensaje, "Por favor, ingrese su mensaje.");
    valido = false;
  }

  if (!terminos.checked) {
    mostrarError(terminos, "Debe aceptar los términos y condiciones.");
    valido = false;
  }

  // Si todo está correcto
  if (valido) {
    mostrarMensaje("✅ ¡Formulario enviado con éxito!");
    form.reset();
    limpiarErrores();
  }
});

// ---- Funciones auxiliares ----
function mostrarError(input, mensaje) {
  const formGroup = input.closest(".form-group");
  const error = formGroup.querySelector(".error");
  if (error) error.textContent = mensaje;
  input.classList.add("error-border");
}

function limpiarErrores() {
  document.querySelectorAll(".error").forEach((e) => (e.textContent = ""));
  document
    .querySelectorAll(".error-border")
    .forEach((el) => el.classList.remove("error-border"));
}

function mostrarMensaje(texto) {
  successMessage.textContent = texto;
  successMessage.style.display = "block";

  // Desaparece automáticamente después de 3 segundos
  setTimeout(() => {
    successMessage.style.display = "none";
  }, 3000);
}

function ocultarMensaje() {
  successMessage.style.display = "none";
}

// ---- Validación en tiempo real ----
const campos = ["nombre", "apellidos", "email", "telefono", "mensaje"];
campos.forEach((campo) => {
  const input = document.getElementById(campo);
  input.addEventListener("input", () => {
    const formGroup = input.closest(".form-group");
    const error = formGroup.querySelector(".error");
    if (input.value.trim() !== "") {
      error.textContent = "";
      input.classList.remove("error-border");
    }
  });
});

document.getElementById("terminos").addEventListener("change", (e) => {
  const formGroup = e.target.closest(".form-group");
  const error = formGroup.querySelector(".error");
  if (e.target.checked) {
    error.textContent = "";
  }
});
