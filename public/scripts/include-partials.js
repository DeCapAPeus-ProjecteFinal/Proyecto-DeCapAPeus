// Carga client-side de partials HTML en #header y #footer
async function includePartial(selector, url) {
  try {
    const res = await fetch(url, { cache: "no-store" });
    if (!res.ok) throw new Error(`Failed to load ${url}: ${res.status}`);
    const html = await res.text();
    const el = document.querySelector(selector);
    if (el) el.innerHTML = html;
  } catch (e) {
    console.error(e);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  includePartial("#header", "/includes/header.html");
  includePartial("#footer", "/includes/footer.html");
});
