# Draft: Utilitario - Alertas de Divisas

## Contexto
- Repo: Laravel 8 (segun AGENTS.md), con assets Node (Laravel Mix) y jobs.

## Requisitos (confirmados por el usuario)
- Ocultar en la UI el texto/tiempo de "ultima actualizacion" del card donde se muestra el precio (no mostrar el tiempo de actualizacion).
- Guardar historial de precios por casa de cambio **solo cuando el precio cambie**; si hace falta, crear tabla nueva y correr migraciones.
- En el formulario de alerta: agregar opcion para alertar cuando el precio cambie **hacia arriba** o **hacia abajo** (independiente del precio objetivo).
  - Ejemplo: si alerta objetivo es 4.30 (baja) y ademas esta marcada "cuando baje", entonces si precio cambia 4.45 -> 4.44 debe alertar.
- Actualizar el job que actualiza precios y envia alertas para soportar la nueva opcion (cambio arriba/abajo).
- Frecuencia: si es recurrente, limitar a **2 dias** alertando; luego dejar de alertar y cambiar el estado de la alerta.
- Si la condicion se cumple y la frecuencia es recurrente: al ingresar al utilitario debe aparecer un popup informando cumplimiento; botones para "eliminar alerta" o "desactivar".
- En la tabla de "alertas activas" no funciona el boton "ordenable" (sorting).
- Para alertas por WhatsApp: al crear la **primera** alerta con WhatsApp, pedir al usuario que escriba al numero de la empresa para registrarlo; si el numero ya existe en BD, no pedirlo.

## Suposiciones (pendientes de validar)
- "Precio" podria referirse a compra/venta/promedio; la direccion (arriba/abajo) depende de cual campo se monitorea.
- "2 dias" podria medirse desde el primer disparo (trigger) o desde la creacion de la alerta.

## Decisiones tecnicas (pendientes)
- Diseno de tabla de historial (keys, indices, deduplicacion por precio igual, campo monitoreado: buy/sell).
- Modelo de estado para alertas (activa/desactivada/expirada por limite de 2 dias).
- Registro WhatsApp: como se identifica "numero existente" (tabla de contactos, user_phone, etc.).

## Preguntas abiertas
- Campo de precio a evaluar (compra/venta/ambos) y moneda/par (si aplica).
- Definicion exacta del limite "2 dias" en recurrente.
- Numero de WhatsApp de la empresa y donde se muestra el CTA (popup, banner, modal).

---

## Entrevista (progreso)

### Item 1 (enfoque actual): ocultar "Ultima actualizacion"
- Usuario pide: quitar/ocultar en el card del precio la linea/tiempo de "Ultima actualizacion" (no mostrar tiempo de actualizacion).

### Item 2 (enfoque actual): nueva condicion "precio sube / baja" (sin objetivo)
- Usuario pide: en el formulario agregar opcion para alertar cuando el precio cambie hacia arriba o hacia abajo, independiente del objetivo.
  - Ejemplo: alerta creada con precio 4.45, objetivo 4.30 (baja), y ademas marcada "cuando cambie para abajo"; si el precio cambia 4.45 -> 4.44 debe alertar.
- Implica: agregar nuevo tipo/flag de condicion adicional a la existente (arriba/abajo por objetivo), y soportarlo en el job que evalua alertas.

#### Pregunta del usuario
- "Cuando se crea el alerta se guarda el precio en el que esta la casa de cambio seleccionado?"

#### Nota para planificacion
- Para implementar "sube/baja" se necesita un *precio base* por alerta (ej. precio al momento de crear o el ultimo precio notificado) para poder detectar la direccion del cambio. Eso puede requerir nuevos campos en `alerts` (p.ej. `last_seen_price`, `last_seen_at`, o `baseline_price_*`).

#### Decision confirmada (por el usuario)
- La nueva opcion "si se mueve el precio" debe usar la misma logica actual del campo `condition`:
  - Si el usuario selecciona "suba" (por encima) se usa el precio asociado a esa condicion (hoy el job compara contra `buy_price`).
  - Si selecciona "baje" (por debajo) se usa el precio asociado a esa condicion (hoy el job compara contra `sell_price`).
- En otras palabras: no crear un selector extra de compra/venta; mantener el comportamiento actual y solo agregar el nuevo modo de disparo.

---

## Nuevo requerimiento (actual)

### Tabla ordenable de alertas
- Usuario solicita: "hacer la tabla donde se muestran las alertas ordenables".
- Confirmado por el usuario: objetivo = utilitario **alerta de ofertas** (tabla "Mis alertas").

## Pregunta abierta para cerrar alcance
- Definir alcance UX: desktop tabla solamente o tambien orden para cards mobile.

## Scope boundaries
- INCLUDE: UI (card + popup), backend (historial + job alertas), BD (migracion si aplica), fixing sorting tabla.
- EXCLUDE (no confirmado): redisenos grandes de UI fuera del utilitario; cambios de proveedor WhatsApp.

---

## Research Findings (repo)

### Modulo Currency Alert (paths verificados)
- `C:\xampp\htdocs\utilitary\routes\web.php` - carga rutas del modulo y redirecciona al utilitario.
- `C:\xampp\htdocs\utilitary\routes\modules\currency_alert.php` - rutas CRUD de alertas de divisas.
- `C:\xampp\htdocs\utilitary\app\Modules\Utilities\CurrencyAlert\Http\Controllers\CurrencyAlertController.php` - index + store/edit/update/destroy; maneja user/guest; prefill `lastPhone`/`lastEmail`.
- `C:\xampp\htdocs\utilitary\resources\views\modules\currency_alert\index.blade.php` - UI del utilitario (cards de casas de cambio, "Mis Alertas Activas", modal de detalle, JS inline).

### Precio / historial actual
- Tabla/modelo existente para precios: `exchange_rates` (modelo `app/Models/ExchangeRate.php`) con `buy_price` y `sell_price`.
- En UI se muestra "Ultima actualizacion" usando el `created_at` del latest rate (en `index.blade.php`).

### Job / scheduler
- `C:\xampp\htdocs\utilitary\app\Jobs\CheckAlertsJob.php` - evalua alertas vs ultimo `ExchangeRate`; aplica cooldown + limites diarios; envia notificaciones.
- Scheduler / scraping: (segun exploracion) `app/Console/Kernel.php` + `app/Console/Commands/ScrapeExchangeRates.php` + `app/Modules/Core/Services/ScrapingService.php`.

### Sorting "ordenable"
- La tabla "Mis Alertas Activas" muestra UI de ordenamiento, pero no se observa logica de sorting implementada en el JS inline (parece ser el bug).

### WhatsApp (estado actual)
- Scripts Node/Playwright (verificados):
  - `C:\xampp\htdocs\utilitary\scripts\whatsapp_server.js`
  - `C:\xampp\htdocs\utilitary\scripts\whatsapp_send.js`
- Gating por plan Pro: `app/Models/User.php` (metodo `canUseWhatsApp(...)`).
- En `CheckAlertsJob` la ruta WhatsApp aparece como placeholder (no se confirma envio real desde PHP; existe fallback a email).

### Migraciones relevantes (verificadas via glob)
- `C:\xampp\htdocs\utilitary\database\migrations\2025_12_05_184711_create_alerts_table.php`
- `C:\xampp\htdocs\utilitary\database\migrations\2025_12_05_184618_create_exchange_rates_table.php`
- `C:\xampp\htdocs\utilitary\database\migrations\2025_12_05_184551_create_exchange_sources_table.php`
- `C:\xampp\htdocs\utilitary\database\migrations\2026_01_08_133432_add_contact_phone_to_alerts_table.php`
- `C:\xampp\htdocs\utilitary\database\migrations\2025_12_09_130000_create_offer_alerts_table.php`
- `C:\xampp\htdocs\utilitary\database\migrations\2025_12_12_200000_add_channel_to_offer_alerts_table.php`

### Infra de tests (verificado)
- PHPUnit configurado en `C:\xampp\htdocs\utilitary\phpunit.xml`.
- Hay tests Feature; existe cobertura de currency alerts en `tests/Feature/CurrencyAlertAjaxTest.php` (segun exploracion).
