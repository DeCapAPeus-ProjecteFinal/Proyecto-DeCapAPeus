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

    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get("id");

    if (productId) {
      getDBProduct(productId).then((product) => renderProductPage(product));
    }
  } catch (error) {
    console.error(error);
  }
}
function renderFeaturedProduct(product) {
  if (!product || product.destacado !== true) return; // Solo si es destacado

  // Contenedor donde irá el producto destacado
  const featured = document.querySelector(".producto-destacado-content");
  if (!featured) return;

  featured.innerHTML = `
      <div class="texto">
        <h1>${product.nom}</h1>
        <p>${product.descripcio}</p>
        <button class="btn"><a href="http://localhost/pages/product-info.html?id=${product.id}">Comprar</a></button>
      </div>
      <div class="imagen">
        <img src="${product.img}" alt="${product.nom}" />
      </div>
    `;
}

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
        <button class="btn"><a href="http://localhost/pages/product-info.html?id=${product.id}">Comprar</a></button>
      </div>
    `;

  grid.appendChild(div);
}

function renderProducts(products) {
  const grid = document.querySelector(".novedades-grid");
  if (!grid) return;

  // 1. Filtrar los NO destacados
  const noDestacados = products.filter((p) => !p.destacado);

  // 2. Limitar a 4
  const primeros4 = noDestacados.slice(0, 4);

  // 3. Renderizar
  primeros4.forEach((p) => renderProduct(p));
}

function renderProductPage(product) {
  if (!product) return;

  const container = document.querySelector(".producto-page");
  if (!container) return;

  // Calcular valoración promedio
  const valoraciones = product.valoraciones || [];
  const promedio = valoraciones.length
    ? (valoraciones.reduce((a, b) => a + b, 0) / valoraciones.length).toFixed(1)
    : "0";

  // Renderizar estrellas
  let estrellas = "";
  for (let i = 0; i < 5; i++) {
    estrellas += i < Math.round(promedio) ? "★" : "☆";
  }

  // Renderizado del producto
  container.innerHTML = `
    <div class="producto-detalle">
      <div class="imagen">
        <img src="${product.img}" alt="${product.nom}" />
      </div>
      <div class="info">
        <h1>${product.nom}</h1>
        <p>${product.descripcio}</p>
        <p><strong>Precio:</strong> ${product.preu}€</p>
        <p class="valoracion">Valoración: ${estrellas} (${promedio})</p>
        <button class="btn">Comprar</button>
      </div>
    </div>

    <div class="comentarios">
      <h2>Comentarios</h2>
      ${
        valoraciones.length > 0
          ? valoraciones
              .map(
                (v, i) => `
        <div class="comentario">
          <p>Usuario ${i + 1}: ${"★".repeat(v)}${"☆".repeat(5 - v)} (${v})</p>
        </div>
      `
              )
              .join("")
          : "<p>No hay comentarios aún.</p>"
      }
    </div>
  `;
}

async function getDBProducts() {
  const response = await fetch(URL + PRODUCTS);
  if (!response.ok) throw new Error("Productos no encontrados");
  return await response.json();
}

// pillar solo un producto
async function getDBProduct(productId) {
  const response = await fetch(URL + PRODUCTS + "/" + productId);
  if (!response.ok) throw new Error("Producto no encontrado");
  return await response.json();
}
