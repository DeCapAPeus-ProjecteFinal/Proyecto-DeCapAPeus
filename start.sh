#!/bin/bash

# Abre una terminal nueva y ejecuta Vite
gnome-terminal -- bash -c "cd client && npm run dev; exec bash"

# Inicia Docker Compose
echo "Iniciando Docker Compose..."
(cd server && docker-compose up)