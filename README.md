# Proyecto DeCapAPeus

Proyecto de tienda online de complementos y taller de zapatería.

## Enlaces Importantes

- **Entregable (Sprint 2):** [Ver en GitHub](https://github.com/DeCapAPeus-ProjecteFinal/Proyecto-DeCapAPeus/tree/Sprint2)
- **Gestión del Proyecto (Kanban):** [GitHub Projects](https://github.com/orgs/DeCapAPeus-ProjecteFinal/projects/1)

## Despliegue

El proyecto está contenerizado con Docker. Para iniciarlo:

```bash
docker compose up -d
```

Esto levantará:
- Servidor Web (Apache/PHP) en el puerto 80.
- JSON Server (API de productos) en el puerto 3000 (accesible internamente y proxyado por Apache).
