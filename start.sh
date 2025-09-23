#!/bin/bash

# Inicia el servidor Vite en segundo plano
echo "Iniciando Vite..."
(cd client && npm run dev) &

# Inicia Docker Compose
echo "Iniciando Docker Compose..."
(cd server && docker-compose up)