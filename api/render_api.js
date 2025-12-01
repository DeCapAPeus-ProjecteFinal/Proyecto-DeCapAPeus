const URL = "http://localhost:3000";
const PRODUCTS = "/productes";

document.addEventListener("DOMContentLoaded", () => {
  init();
});

async function init() {
  try {
    const products = await getDBProducts();
    renderProducts(products);

    const destacado = products.find((p) => p.destacado === true);
    renderFeaturedProduct(destacado);

    assignBuyListeners();

    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get("id");

    if (productId) {
      const product = await getDBProduct(productId);
      renderProductPage(product);
    }
  } catch (error) {
    console.error(error);
  }
}

/* ============================
   PRODUCTO DESTACADO
============================ */
function renderFeaturedProduct(product) {
  if (!product || product.destacado !== true) return;

  const featured = document.querySelector(".producto-destacado-content");
  if (!featured) return;

  featured.innerHTML = `
    <div class="texto">
      <h1>${product.nom}</h1>
      <p>${product.descripcio}</p>
      <button class="btn btn-buy" data-id="${product.id}">Comprar</button>
    </div>
    <div class="imagen">
      <img src="${product.img}" alt="${product.nom}" />
    </div>
  `;
}

/* ============================
   PRODUCTO INDIVIDUAL EN LISTADO
============================ */
function renderProduct(product) {
  const grid = document.querySelector(".novedades-grid");
  if (!grid) return;

  const div = document.createElement("div");
  div.classList.add("producto");

  div.innerHTML = `
    <img src="${product.img}" alt="${product.nom}" />
    <div class="producto-info">
      <h3>${product.nom}</h3>
      <p>${product.descripcio}</p>
      <button class="btn btn-buy" data-id="${product.id}">Comprar</button>
    </div>
  `;

  grid.appendChild(div);
}

/* ============================
   LISTADO 4 PRODUCTOS
============================ */
function renderProducts(products) {
  const grid = document.querySelector(".novedades-grid");
  if (!grid) return;

  const noDestacados = products.filter((p) => !p.destacado);
  const primeros4 = noDestacados.slice(0, 4);

  primeros4.forEach((p) => renderProduct(p));
}

/* ============================
   PÁGINA DE PRODUCTO
============================ */
function renderProductPage(product) {
  if (!product) return;

  const container = document.querySelector(".producto-page");
  if (!container) return;

  container.innerHTML = `
    <div class="producto-detalle">
      <div class="imagen">
        <img src="${product.img}" alt="${product.nom}" />
      </div>
      <div class="info">
        <h1>${product.nom}</h1>
        <p>${product.descripcio}</p>
        <p><strong>Precio:</strong> ${product.preu}€</p>
        <button class="btn">Comprar</button>
        <button id="btn-like" class="btn-heart">🤍</button>
        <span id="like-count">0</span>
      </div>
    </div>

    <div class="comentarios">
      <h2>Comentarios</h2>

      <div id="comments-list">
        <p>Cargando comentarios...</p>
      </div>

      <h3>Dejar un comentario</h3>
      <form id="comment-form">
        <textarea name="comment" placeholder="Escribe tu comentario..." required></textarea>

        <label>Puntuación:</label>
        <div id="star-rating">
          <span class="star" data-value="1">☆</span>
          <span class="star" data-value="2">☆</span>
          <span class="star" data-value="3">☆</span>
          <span class="star" data-value="4">☆</span>
          <span class="star" data-value="5">☆</span>
        </div>
        <input type="hidden" name="rating" value="5" />

        <button type="submit" class="btn">Enviar</button>
      </form>
    </div>
  `;

  // Cargar comentarios existentes
  loadComments(product.id);

  // Iniciar polling inteligente basado en timestamp
  startTimestampPolling(product.id);

  // Cargar y manejar "Me gusta"
  loadLikeCount(product.id);
  checkUserLikeStatus(product.id);
  enableLikeButton(product.id);

  // Enviar comentario nuevo
  enableCommentForm(product.id);
  enableStarRating();
}

/* ============================
   OBTENER PRODUCTOS DB JSON SERVER
============================ */
async function getDBProducts() {
  const response = await fetch(URL + PRODUCTS);
  if (!response.ok) throw new Error("Productos no encontrados");
  return await response.json();
}

async function getDBProduct(productId) {
  const response = await fetch(URL + PRODUCTS + "/" + productId);
  if (!response.ok) throw new Error("Producto no encontrado");
  return await response.json();
}

/* ============================
  LEER COOKIE
============================ */
function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(";").shift();
  return null;
}

/* ============================
  CARGAR COMENTARIOS
============================ */
let currentProductId = null; // Guardamos el producto actual
let lastCommentsTimestamp = 0;
let timestampPollingInterval = null;

async function loadComments(productId) {
  currentProductId = productId;

  const box = document.querySelector("#comments-list");
  box.innerHTML = "<p>Cargando comentarios...</p>";

  const res = await fetch(
    `/api/comments_api.php?action=list&productId=${productId}`
  );
  const data = await res.json();

  // Asegurarse de que sea un array
  const comments = Array.isArray(data) ? data : [];

  if (!comments.length) {
    box.innerHTML = "<p>No hay comentarios aún.</p>";
    return;
  }

  const loggedUserId = parseInt(getCookie("user_id") || 0);

  box.innerHTML = comments
    .map((c) => {
      const isOwner = c.user_id === loggedUserId;
      const starsHtml = renderStars(c.rating);

      return `
      <div class="comentario" data-id="${c.id}">
        <p><strong>${c.username}</strong> — ${c.date}</p>
        <div class="stars">${starsHtml}</div>
        <p class="text">${c.text}</p>

        ${
          isOwner
            ? `
          <div class="comentario-actions">
            <button class="btn-edit" data-id="${c.id}">Editar</button>
            <button class="btn-delete" data-id="${c.id}">Eliminar</button>
          </div>
        `
            : ""
        }
      </div>
      `;
    })
    .join("");

  enableCommentActions();
}

// ------------------------------------------------------------
// Enviar comentario
// ------------------------------------------------------------
function enableCommentForm(productId) {
  const form = document.querySelector("#comment-form");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const button = form.querySelector("button");
    button.disabled = true;
    button.textContent = "Enviando...";

    const payload = {
      productId: productId,
      comment: form.comment.value.trim(),
      rating: parseInt(form.rating.value),
    };

    try {
      const res = await fetch("/api/comments_api.php?action=add", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (data.error) {
        alert(data.error);
        return;
      }

      form.reset();
      loadComments(productId);
    } catch (err) {
      alert("Error de conexió amb el servidor.");
      console.error(err);
    } finally {
      button.disabled = false;
      button.textContent = "Enviar";
    }
  });
}

//-------------------------------------------------//
//--Crear escuchadores para los botnoes de compra--//
//-------------------------------------------------//
function assignBuyListeners() {
  const buttons = document.querySelectorAll(".btn-buy");
  buttons.forEach((btn) => {
    if (btn.classList.contains("js-listener-attached")) return;

    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      if (id) {
        window.location.href = `http://localhost/pages/product-info.html?id=${id}`;
      }
    });

    btn.classList.add("js-listener-attached");
  });
}

/* ============================
   LISTENERS PARA EDITAR Y ELIMINAR
============================ */
function enableCommentActions() {
  // ELIMINAR
  document.querySelectorAll(".btn-delete").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;

      if (!confirm("¿Seguro que quieres eliminar este comentario?")) return;

      const res = await fetch("/api/comments_api.php?action=delete", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
      });

      const data = await res.json();
      if (data.success) loadComments(currentProductId);
      else alert(data.error);
    });
  });

  // EDITAR
  document.querySelectorAll(".btn-edit").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      const commentDiv = document.querySelector(`.comentario[data-id="${id}"]`);
      const textP = commentDiv.querySelector(".text");
      const currentText = textP.textContent.trim();
      const currentRating = commentDiv.dataset.rating || 5; // si guardamos rating en data-attribute

      // Crear formulario de edición
      const formHtml = `
        <div class="edit-form">
          <textarea name="edit-text">${currentText}</textarea>
          <label>Puntuación:</label>
          <select name="edit-rating">
            <option value="5" ${
              currentRating == 5 ? "selected" : ""
            }>★★★★★ (5)</option>
            <option value="4" ${
              currentRating == 4 ? "selected" : ""
            }>★★★★☆ (4)</option>
            <option value="3" ${
              currentRating == 3 ? "selected" : ""
            }>★★★☆☆ (3)</option>
            <option value="2" ${
              currentRating == 2 ? "selected" : ""
            }>★★☆☆☆ (2)</option>
            <option value="1" ${
              currentRating == 1 ? "selected" : ""
            }>★☆☆☆☆ (1)</option>
          </select>
          <button type="button" class="btn-save">Guardar</button>
          <button type="button" class="btn-cancel">Cancelar</button>
        </div>
      `;

      // Ocultar texto original y mostrar formulario
      textP.style.display = "none";
      commentDiv.querySelector(".comentario-actions").style.display = "none";
      commentDiv.insertAdjacentHTML("beforeend", formHtml);

      const formDiv = commentDiv.querySelector(".edit-form");
      const textarea = formDiv.querySelector("textarea");
      const select = formDiv.querySelector("select");

      // Cancelar
      formDiv.querySelector(".btn-cancel").addEventListener("click", () => {
        textarea.remove();
        select.remove();
        formDiv.remove();
        textP.style.display = "block";
        commentDiv.querySelector(".comentario-actions").style.display = "block";
      });

      // Guardar cambios
      formDiv.querySelector(".btn-save").addEventListener("click", async () => {
        const nuevoTexto = textarea.value.trim();
        const nuevoRating = parseInt(select.value);

        if (!nuevoTexto) {
          alert("El comentario no puede estar vacío.");
          return;
        }

        const res = await fetch("/api/comments_api.php?action=update", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id, text: nuevoTexto, rating: nuevoRating }),
        });

        const data = await res.json();
        if (data.success) {
          loadComments(currentProductId);
        } else {
          alert(data.error);
        }
      });
    });
  });
}

/* ============================
   "ME GUSTA" - CORAZÓN
============================ */

async function loadLikeCount(productId) {
  const res = await fetch(
    `/api/likes_api.php?productId=${productId}&action=count`
  );
  const data = await res.json();

  if (data.likes !== undefined) {
    document.querySelector("#like-count").textContent = data.likes;
  }
}

async function checkUserLikeStatus(productId) {
  const res = await fetch(
    `/api/likes_api.php?productId=${productId}&action=check`
  );
  const data = await res.json();

  const button = document.querySelector("#btn-like");
  if (!button) return;

  if (data.liked) {
    button.textContent = "❤️"; // Corazón lleno
  } else {
    button.textContent = "🤍"; // Corazón vacío
  }
}

function enableLikeButton(productId) {
  const button = document.querySelector("#btn-like");
  if (!button) {
    console.error("Botón #btn-like no encontrado.");
    return;
  }

  button.addEventListener("click", async () => {
    const res = await fetch("/api/likes_api.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        productId: productId,
      }),
    });

    const data = await res.json();

    if (data.success) {
      document.querySelector("#like-count").textContent = data.likes;
      // Alternar visualmente el corazón
      if (data.action === "added") {
        button.textContent = "❤️"; // Lleno
      } else if (data.action === "removed") {
        button.textContent = "🤍"; // Vacío
      }
    } else {
      console.error("Error en la API de likes:", data);
    }
  });
}

/* ============================
   POLLING INTELIGENTE POR TIMESTAMP
============================ */

function startTimestampPolling(productId) {
  if (timestampPollingInterval) {
    clearInterval(timestampPollingInterval);
  }

  timestampPollingInterval = setInterval(async () => {
    try {
      const res = await fetch(
        `/api/check_comments_update.php?lastTimestamp=${lastCommentsTimestamp}&productId=${productId}`
      );
      const data = await res.json();

      if (data.changed) {
        console.log("Archivo de comentarios modificado, recargando...");
        lastCommentsTimestamp = data.timestamp;
        loadComments(productId);
      } else {
        // Opcional: actualizar timestamp si no cambió
        lastCommentsTimestamp = data.timestamp;
      }
    } catch (err) {
      console.error("Error en polling de comentarios:", err);
    }
  }, 5000); // Cada 5 segundos
}

function stopTimestampPolling() {
  if (timestampPollingInterval) {
    clearInterval(timestampPollingInterval);
    timestampPollingInterval = null;
  }
}

/* ============================
   ESTRELLAS
============================ */

function renderStars(rating) {
  let stars = "";
  for (let i = 1; i <= 5; i++) {
    stars += `<span class="star-display ${i <= rating ? "filled" : ""}">${
      i <= rating ? "★" : "☆"
    }</span>`;
  }
  return stars;
}

function enableStarRating() {
  const stars = document.querySelectorAll("#star-rating .star");
  const input = document.querySelector('input[name="rating"]');

  stars.forEach((star) => {
    star.addEventListener("click", () => {
      const value = parseInt(star.dataset.value);
      input.value = value;

      stars.forEach((s, index) => {
        if (index < value) {
          s.textContent = "★";
          s.classList.add("filled");
        } else {
          s.textContent = "☆";
          s.classList.remove("filled");
        }
      });
    });

    star.addEventListener("mouseover", () => {
      const value = parseInt(star.dataset.value);

      stars.forEach((s, index) => {
        if (index < value) {
          s.textContent = "★";
          s.classList.add("filled");
        } else {
          s.textContent = "☆";
          s.classList.remove("filled");
        }
      });
    });
  });

  document.querySelector("#star-rating").addEventListener("mouseleave", () => {
    const currentValue = parseInt(input.value);
    stars.forEach((s, index) => {
      if (index < currentValue) {
        s.textContent = "★";
        s.classList.add("filled");
      } else {
        s.textContent = "☆";
        s.classList.remove("filled");
      }
    });
  });
}
