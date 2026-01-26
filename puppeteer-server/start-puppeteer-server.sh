#!/bin/bash

# Script para iniciar el servidor Puppeteer de Ripley
# Usar: ./start-puppeteer-server.sh

echo "🚀 Iniciando servidor Puppeteer para Ripley..."

# Verificar si Node.js está instalado
if ! command -v node &> /dev/null; then
    echo "❌ Node.js no está instalado. Por favor instala Node.js 16+"
    exit 1
fi

# Verificar si estamos en el directorio correcto
if [ ! -f "package.json" ]; then
    echo "❌ package.json no encontrado. Ejecuta desde el directorio puppeteer-server/"
    exit 1
fi

# Instalar dependencias si no existen
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependencias..."
    npm install
fi

# Iniciar el servidor
echo "🌐 Iniciando servidor en http://localhost:3001"
echo "📊 Health check: http://localhost:3001/health"
echo "🔑 API Key: utilitary-secret-key-2024"
echo ""
echo "Presiona Ctrl+C para detener el servidor"
echo "=========================================="

# Variables de entorno
export NODE_ENV=production
export PORT=3001
export API_KEY=utilitary-secret-key-2024

# Iniciar servidor
npm start