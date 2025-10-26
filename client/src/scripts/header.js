document.addEventListener("DOMContentLoaded", () => {
    const headerHTML = `
        <header class="header">
            <!-- Parte superior: fondo azul -->
            <div class="header-top">
                <div class="header-left">
                    <img src="logo.png" alt="Logo" class="logo">
                    <h1 class="nombre-tienda">DeCapAPeus</h1>
                </div>
                <div class="header-right">
                    <button class="btn-taller">Taller</button>
                    <button class="btn-tienda">Tienda</button>
                    <button class="btn-idioma">
                        <img src="language.svg" alt="Idioma" class="icono-idioma">
                    </button>
                    <button class="btn-login">Iniciar sesión</button>
                    <button class="btn-carrito">
                        <img src="shopping_cart.svg" alt="Carrito" class="icono-carrito">
                    </button>
                </div>
            </div>

            <!-- Parte inferior: fondo gris casi blanco -->
            <div class="header-bottom">
                <div class="menu-col">
                    <button class="menu-btn">
                        <img src="menu.svg" alt="" class="menu-icon-left">
                        <span class="menu-text">Accesorios</span>
                        <img src="arrowDown.svg" alt="" class="menu-icon-right">
                    </button>
                    <ul class="submenu">
                        <li><a href="#">Gorras</a></li>
                        <li><a href="#">Guantes</a></li>
                        <li><a href="#">Bufandas</a></li>
                    </ul>
                </div>

                <div class="menu-col">
                    <button class="menu-btn">
                        <img src="menu.svg" alt="" class="menu-icon-left">
                        <span class="menu-text">Mochilas</span>
                        <img src="arrowDown.svg" alt="" class="menu-icon-right">
                    </button>
                    <ul class="submenu">
                        <li><a href="#">Escolares</a></li>
                        <li><a href="#">Deporte</a></li>
                        <li><a href="#">Montaña</a></li>
                    </ul>
                </div>

                <div class="menu-col">
                    <button class="menu-btn">
                        <img src="menu.svg" alt="" class="menu-icon-left">
                        <span class="menu-text">Calzado</span>
                        <img src="arrowDown.svg" alt="" class="menu-icon-right">
                    </button>
                    <ul class="submenu">
                        <li><a href="#">Deportivos</a></li>
                        <li><a href="#">Casuales</a></li>
                        <li><a href="#">Sandalias</a></li>
                    </ul>
                </div>

                <div class="buscador-container">
                    <div class="buscador-wrapper">
                        <input type="text" class="buscador" placeholder="Buscar productos...">
                        <button class="buscador-btn">
                            <img src="lupa.svg" alt="Buscar" class="lupa-icon">
                        </button>
                    </div>
                </div>
            </div>

        </header>
    `;

    document.getElementById("header").innerHTML = headerHTML;
});