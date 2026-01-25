#!/bin/bash

echo "🧪 Probando servidor Puppeteer de Ripley..."

# Verificar si el servidor está corriendo
if curl -s http://localhost:3001/health > /dev/null; then
    echo "✅ Servidor Puppeteer está corriendo en localhost:3001"
else
    echo "❌ Servidor Puppeteer no está corriendo. Por favor inicia el servidor primero:"
    echo "   cd puppeteer-server && node server.js"
    exit 1
fi

echo ""
echo "🔍 Probando scraping de la URL del adaptador Apple..."

# Hacer la prueba con curl
URL="https://simple.ripley.com.pe/adaptador-apple-20w-2065356902093p?color_80=blanco&s=mdco"

response=$(curl -s -X POST http://localhost:3001/scrape/ripley \
  -H "Content-Type: application/json" \
  -H "X-API-Key: utilitary-secret-key-2024" \
  -d "{\"url\":\"$URL\"}")

echo "📦 Respuesta del servidor:"
echo "$response" | jq . 2>/dev/null || echo "$response"

echo ""
echo "📊 Extracción de precios:"
echo "$response" | jq -r '.data[0] | "Título: \(.name)\nTienda: \(.store)\nPrecio Público: \(.public_price // "N/A")\nPrecio Tarjeta: \(.card_price // "N/A")\nImagen: \(.image)"' 2>/dev/null || echo "No se pudo extraer información estructurada"

echo ""
echo "✅ Prueba completada."