const URL = "http://localhost:3000";
const PRODUCTS = "/productes";

document.addEventListener("DOMContentLoaded", () => {
  init();
});

async function init() {
  try {
    const grid = document.querySelector(".novedades-grid");
    grid.innerHTML = ""; // limpiar productos estáticos

    const products = await getDBProducts();
    renderProducts(products);

    const destacado = products.find((p) => p.destacado === true);
    renderFeaturedProduct(destacado);
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
        <button class="btn">Comprar</button>
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
        <button class="btn">Comprar</button>
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
