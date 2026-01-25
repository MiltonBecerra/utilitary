# 🚀 Mejoras al Sistema de Alertas de Ofertas - Ripley

## 📋 Resumen de Cambios

Se han implementado mejoras significativas al sistema de scraping de precios para Ripley, específicamente para resolver el problema con la URL del adaptador Apple que debería mostrar precios de 109 y 79 soles.

## 🔧 Cambios Implementados

### 1. ✅ Servidor Puppeteer para Fallback
- **Ubicación**: `puppeteer-server/`
- **Archivos**: `package.json`, `server.js`, `start-puppeteer-server.sh`
- **Función**: Servidor Node.js con Puppeteer para saltar bloqueos anti-bot de Ripley
- **Endpoint**: `POST http://localhost:3001/scrape/ripley`
- **API Key**: `utilitary-secret-key-2024`

### 2. ✅ Mejoras en Método scrapeRipley()
- **Selectores CSS aumentados**: 
  - Precio público: de 8 a 18 selectores
  - Precio tarjeta: de 8 a 17 selectores
- **Nuevos selectores**: Data attributes, test IDs, y selectores específicos de Ripley
- **Logging mejorado**: Detalles de precios encontrados para debugging

### 3. ✅ Validación Robusta de Precio de Tarjeta
- **Nuevo método**: `validateRipleyCardPriceImproved()`
- **4 niveles de validación**:
  1. Verificar precios extraídos
  2. Validación numérica
  3. Verificación de elementos DOM
  4. Análisis contextual del contenido

### 4. ✅ Normalización de URLs simple.ripley.com.pe
- **Mejora en método**: `normalizeRipleyUrl()`
- **Manejo de subdominios**: `simple.`, `m.`, otros → `www.`
- **Logging**: Registro de URLs normalizadas

### 5. ✅ Mensajes de Error Específicos
- **Mejoras en**: `OfferAlertController@store()`
- **Mensajes por tienda**: Explicaciones específicas para cada e-commerce
- **Contexto útil**: Explica por qué algunos productos no tienen precio con tarjeta

### 6. ✅ Logging Detallado
- **Nuevos logs**: Información contextual para debugging
- **Categorías**: `ripley_scraping_debug`, `ripley_card_validation_*`, `ripley_url_normalized`

## 🧪 URL de Prueba
```
https://simple.ripley.com.pe/adaptador-apple-20w-2065356902093p?color_80=blanco&s=mdco
```

**Precios Esperados**:
- Precio público: 109 soles
- Precio tarjeta Ripley: 79 soles

## 🚀 Cómo Usar

### Iniciar Servidor Puppeteer
```bash
cd puppeteer-server
npm install --production
node server.js
```

El servidor estará disponible en:
- Health check: http://localhost:3001/health
- Ripley endpoint: POST http://localhost:3001/scrape/ripley

### Ejemplo de Uso del Endpoint
```bash
curl -X POST http://localhost:3001/scrape/ripley \
  -H "Content-Type: application/json" \
  -H "X-API-Key: utilitary-secret-key-2024" \
  -d '{
    "url": "https://simple.ripley.com.pe/adaptador-apple-20w-2065356902093p?color_80=blanco&s=mdco"
  }'
```

## 🔍 Depuración

### Logs Disponibles
- `ripley_scraping_debug`: Información general del scraping
- `ripley_card_validation_*`: Resultados de validación de precio tarjeta
- `ripley_url_normalized`: Cambios de URL normalizados

### Archivos de Debug
- `storage/app/scrape-offer.html`: HTML guardado del último scrape
- `storage/app/scrape-offer-debug.log`: Matches de regex y datos extraídos

## 📊 Resultados Esperados

### ✅ Con las Mejoras
- **Detección correcta** de ambos precios (público y tarjeta)
- **Mensajes específicos** cuando no hay precio de tarjeta
- **Manejo transparente** de URLs simple.ripley.com.pe
- **Fallback automático** a Puppeteer cuando el scraping directo falla

### ❌ Sin las Mejoras
- **Error 403** de Ripley bloqueando el scraper
- **Null/undefined** en ambos precios
- **Mensaje genérico** de "Producto" sin información útil

## 🛠️ Configuración Adicional

### Variables de Entorno
```bash
NODE_ENV=production
PORT=3001
API_KEY=utilitary-secret-key-2024
```

### Laravel Config
```php
// config/services.php
'puppeteer' => [
    'local_api_url' => 'http://localhost:3001/scrape/ripley',
    'api_key' => 'utilitary-secret-key-2024',
],
```

## 🔄 Flujo Mejorado

1. **Intento 1**: Scraping directo con Guzzle (mejorado con más selectores)
2. **Intento 2**: Proxy externo si está configurado
3. **Intento 3**: Rotación de IPs propias si está habilitada
4. **Fallback**: Puppeteer server si los anteriores fallan

## 📈 Impacto en el Sistema

- ✅ **Mayor tasa de éxito** en scraping de Ripley
- ✅ **Mejor experiencia de usuario** con mensajes específicos
- ✅ **Reducción de falsos positivos** en precios de tarjeta
- ✅ **Debugging más fácil** con logs detallados
- ✅ **Compatibilidad** con URLs de diferentes subdominios

## 🎯 Próximos Pasos

1. **Monitorear** el rendimiento del nuevo sistema
2. **Extender** los selectores si Ripley cambia su estructura
3. **Documentar** casos específicos para otros e-commerce
4. **Optimizar** el rendimiento del servidor Puppeteer