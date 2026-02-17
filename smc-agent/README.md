# SMC Agent (Windows Tray)

Agent local para ejecutar el llenado de carrito en la PC del usuario.

## Estado actual
- MVP inicial con app de bandeja (system tray).
- Polling a backend para recibir jobs.
- Soporte de llenado de carrito para Plaza Vea.
- Navegador visible (`headless=false`) y opcion para dejarlo abierto.

## Requisitos
- Windows 10/11
- Node.js 20+

## Instalacion (desarrollo)
```bash
cd smc-agent
npm install
npm run start
```

## Generar instalador Windows (.exe)
```bash
cd smc-agent
npm install
npm run build:win
```

Salida esperada:
- `smc-agent/dist/SMC Agent Setup <version>.exe`

## Configuracion
En primer inicio se crea:
- `%APPDATA%/SMC Agent/config.json`

Ejemplo:
```json
{
  "serverBaseUrl": "http://127.0.0.1:8000",
  "apiToken": "",
  "deviceId": "auto-generated",
  "pollSeconds": 8,
  "keepBrowserOpen": true,
  "headless": false
}
```

Notas:
- `apiToken` es obligatorio para consumir endpoints.
- El token debe coincidir con `SMC_AGENT_API_TOKEN` en el backend Laravel.
- Si `keepBrowserOpen=true` y `headless=false`, el navegador se queda abierto hasta que el usuario lo cierre.

## Endpoints esperados en backend
El agente espera estos endpoints (por implementar/completar en Laravel):

- `GET /api/smc/agent/jobs/next?device_id=...`
  - Response: `{ "job": null }` o `{ "job": { "id": 123, "store": "plaza_vea", "items": [...] } }`

- `POST /api/smc/agent/jobs/{id}/status`
  - Body: `{ "device_id": "...", "stage": "started|progress|completed|failed", ... }`

## Flujo
1. El agente consulta jobs pendientes.
2. Si llega un job de `plaza_vea`, abre Chrome local con Playwright.
3. Agrega productos usando `addToCartLink` de API de Plaza Vea.
4. Reporta progreso y resultado al backend.

## Proximo paso
- Implementar endpoints API en Laravel para pairing de dispositivo, cola de jobs y estado en vivo.
