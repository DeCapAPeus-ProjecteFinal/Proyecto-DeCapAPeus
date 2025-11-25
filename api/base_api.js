/* // añadir item
async function addDBItem(item) {
  const response = await fetch(URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(item),
  });
  if (!response.ok) throw new Error("Error al intentar añadir nuevo item");
  return await response.json();
}

// eliminar item
async function removeDBItem(itemId) {
  const response = await fetch(URL + "/" + itemId, { method: "DELETE" });
  if (!response.ok) throw new Error("Item not found");
}

// editar item completo
async function changeDBItemPUT(item) {
  const response = await fetch(URL + "/" + item.id, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(item),
  });
  if (!response.ok) throw new Error("Error al intentar cambiar item");
  return await response.json();
}

// editar item parcialmente
async function changeDBItemPATCH(item) {
  const response = await fetch(URL + "/" + item.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(item),
  });
  if (!response.ok) throw new Error("Error al intentar cambiar item");
  return await response.json();
} 
// comrpovar que el item existe
async function checkProductExists(productId) {

  const response = await fetch(`${URL + PRODUCTS}?id=${Number(productId)}`);

  if (!response.ok) {
    throw new Error("Error al comprobar si el producto existe");
  }

  const data = await response.json();

  return data.length > 0;
}  
*/
