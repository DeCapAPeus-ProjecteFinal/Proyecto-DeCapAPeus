document.addEventListener("DOMContentLoaded", () => {
  const footerHTML = `
  <footer class="site-footer">
    <div class="footer-container">

      <!-- Columna 1: Nombre tienda + dirección -->
      <div class="footer-column">
        <h3 class="footer-title">DeCapAPeus</h3>
        <p>Calle Ejemplo, 123<br>08000 Barcelona, España</p>
      </div>

      <!-- Columna 2: Integración Google Maps -->
      <div class="footer-column">
        <h3 class="footer-title">Nuestra ubicación</h3>
        <div class="map-placeholder">
          <!-- Aquí irá Google Maps -->
          <p>[Mapa aquí]</p>
        </div>
      </div>

      <!-- Columna 3: Enlaces de interés -->
      <div class="footer-column">
        <h3 class="footer-title">Enlaces de interés</h3>
        <ul class="footer-links">
          <li><a href="/src/pages/sobre-nosotros.html">Sobre nosotros</a></li>
          <li><a href="/src/pages/contact.html">Formulario contacto</a></li>
          <li><a href="/src/pages/faq.html">Preguntas frecuentes</a></li>
        </ul>
      </div>

      <!-- Columna 4: Redes sociales -->
      <div class="footer-column">
        <h3 class="footer-title">Síguenos</h3>
        <ul class="footer-social">
          <li><a href="#" aria-label="Facebook">
                <img src="facebook.png" alt="" class="icono-social">
                Facebook
              </a></li>
          <li><a href="#" aria-label="Instagram">
                <img src="instagram.png" alt="" class="icono-social">
                Instagram
              </a></li>
        </ul>
      </div>

    </div>
  </footer>
`;

  document.getElementById("footer").innerHTML = footerHTML;
});
