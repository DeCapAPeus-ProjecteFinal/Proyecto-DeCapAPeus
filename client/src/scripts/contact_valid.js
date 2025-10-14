const form = document.getElementById("contactForm");
const successMessage = document.getElementById("success-message");

form.addEventListener("submit", async (event) => {
  event.preventDefault();

  // Campos
  const nombre = document.getElementById("name");
  const apellidos = document.getElementById("surname");
  const email = document.getElementById("email");
  const telefono = document.getElementById("tlf");
  const mensaje = document.getElementById("text");
  const terminos = document.getElementById("terms");

  let valido = true;

  limpiarErrores();
  ocultarMensaje();

  // Validaciones cliente
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

  if (!valido) return; // Si hay errores, no enviar al servidor

  // Enviar datos al servidor (fetch)
  const datos = new FormData(form);
  try {
    const respuesta = await fetch("http://localhost:8080/contact_valid.php", {
      method: "POST",
      body: new FormData(form),
    });
    const resultado = await respuesta.json();

    limpiarErrores();
    ocultarMensaje();

    if (resultado.errores) {
      for (const campo in resultado.errores) {
        const input = document.getElementById(campoMap[campo]);
        if (input) mostrarError(input, resultado.errores[campo]);
      }
    } else if (resultado.exito) {
      mostrarMensaje(resultado.exito);
      form.reset();
    }
  } catch (err) {
    console.error("Error al conectar con el servidor:", err);
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
  setTimeout(() => {
    successMessage.style.display = "none";
  }, 3000);
}

function ocultarMensaje() {
  successMessage.style.display = "none";
}

// Validación en tiempo real
const campoMap = {
  nombre: "name",
  apellidos: "surname",
  email: "email",
  telefono: "tlf",
  mensaje: "text",
  terminos: "terms",
};

for (const key in campoMap) {
  const input = document.getElementById(campoMap[key]);
  if (!input) continue;
  const errorDiv = input.closest(".form-group").querySelector(".error");
  if (key === "terminos") {
    input.addEventListener("change", () => {
      if (input.checked) errorDiv.textContent = "";
    });
  } else {
    input.addEventListener("input", () => {
      if (input.value.trim() !== "") {
        errorDiv.textContent = "";
        input.classList.remove("error-border");
      }
    });
  }
}
