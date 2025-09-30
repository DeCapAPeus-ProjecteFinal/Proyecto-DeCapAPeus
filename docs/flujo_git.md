# Flujo de trabajo Git para el proyecto DeCapAPeus

Este documento describe cómo organizar las ramas y el flujo de trabajo para el proyecto.

---

## 1. Ramas principales

- **main**
  - Rama de producción estable.
  - Solo se hace merge de código probado y aprobado.
  - No se hacen commits directos (excepto hotfix urgentes).

- **develop**
  - Rama de integración.
  - Aquí se fusionan las ramas de características (`feature/*`) cuando están listas.
  - Desde `develop` se hace merge a `main` para entregas estables.

---

## 2. Ramas de trabajo

- **feature/nombre_tarea**
  - Cada tarea se desarrolla en su propia rama desde `develop`.
  - Ejemplos:
    - `feature/pagina-inicio`
    - `feature/formulario-contacto`
  - Una vez terminada → Pull Request a `develop`.

- **hotfix/nombre**
  - Para correcciones urgentes directamente en `main`.

---

## 3. Flujo de trabajo (Git Flow simplificado)

1. **Crear la rama `develop` desde `main`:**
```bash
git checkout main
git pull origin main
git checkout -b develop
git push -u origin develop
```
2. **Crear la rama `feature` desde `develop`:**
```bash
git checkout develop
git pull origin develop
git checkout -b feature/nombre_tarea
```
3. **Trabajar en la rama `feature`:**
```bash
git add .
git commit -m "Descripción de la tarea"
git push -u origin feature/nombre_tarea
```
4. **Pull request a `develop`:**
- Revisar cambios y comprobar antes de hacer merge

5. **Merge de `develop` a `main` cuando la iteración está completa:**
```bash
git checkout main
git merge develop
git tag v1.0  # opcional
git push origin main --tags
```
