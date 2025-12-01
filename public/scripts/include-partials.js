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

document.addEventListener("DOMContentLoaded", async () => {
  await includePartial("#header", "/includes/header.php");
  includePartial("#footer", "/includes/footer.html");

  // Handle login/profile button click
  const loginBtn = document.querySelector(".btn-login");
  if (loginBtn) {
    loginBtn.addEventListener("click", () => {
      let url = loginBtn.dataset.url;
      if (url && url.includes("/auth/login.php")) {
        const currentUrl = encodeURIComponent(
          window.location.pathname + window.location.search
        );
        url += `?redirect_to=${currentUrl}`;
      }
      if (url) {
        window.location.href = url;
      }
    });
  }
});
