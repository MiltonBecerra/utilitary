#!/bin/bash

echo "🚀 Iniciando servidor Puppeteer para Ripley..."

cd "C:\xampp\htdocs\utilitary\puppeteer-server"

# Iniciar el servidor en segundo plano
echo "🌐 Iniciando servidor en http://localhost:3001"
echo "Presiona Ctrl+C para detener el servidor"
echo "=========================================="

export NODE_ENV=production
export PORT=3001
export API_KEY=utilitary-secret-key-2024

# Iniciar servidor con Node.js directamente
node server.js