# Proyecto: Web comercial para servicio técnico, desarrollo web y servicios informáticos

Desarrollar una web pequeña, extremadamente moderna, tecnológica y orientada a conversión para un negocio local de servicios informáticos ubicado en:

**Mitre 5761, Caseros, Buenos Aires, Argentina**

WhatsApp / teléfono:

**+54 9 11 5697-0599**

Horario:

**Lunes a viernes de 08:00 a 20:00**

El negocio presta principalmente:

1. reparación y actualización de PCs y notebooks;
2. desarrollo de páginas web;
3. correo corporativo y pequeños servicios IT.

El público principal son particulares, profesionales, emprendedores y pequeños comercios de Caseros, El Palomar y localidades cercanas.

---

# OBJETIVO

La web no debe ser una landing page interminable ni tampoco un sitio corporativo grande.

Crear un sitio pequeño, rápido y muy orientado a ventas.

La mayoría de las visitas llegará desde:

- Google Ads;
- Meta / Instagram Ads;
- búsquedas en Google;
- Google Maps;
- Instagram;
- recomendaciones.

Muchos usuarios ya habrán visto previamente una publicidad y entrarán al sitio buscando validar:

- que la empresa realmente existe;
- dónde está;
- qué servicios ofrece;
- cuánto aproximadamente cuesta;
- cuánto demora;
- cómo trabaja;
- si parece confiable;
- cómo contactarla.

Toda la experiencia debe responder esas preguntas rápidamente.

---

# ARQUITECTURA DEL SITIO

Crear una arquitectura corta:

## /

Home.

Debe explicar en pocos segundos:

- qué hacemos;
- dónde estamos;
- principales servicios;
- algunos precios;
- tiempos de trabajo;
- formas de pago;
- reseñas;
- ubicación;
- CTA a WhatsApp.

NO hacer una home excesivamente larga.

---

## /reparacion-pc

Página corta y fácil de escanear: un **listado de precios**, no un texto largo explicando cada servicio.

Formato: categoría + ítem + precio (+ un detalle de una línea si hace falta, nunca un párrafo justificando por qué el servicio es importante).

El usuario tiene que poder ver "cuánto cuesta lo que necesito" en segundos.

Incluir también preguntas frecuentes cortas y CTA.

Esta página debe poder utilizarse directamente como destino de campañas publicitarias.

---

## /desarrollo-web

Página específica para:

- páginas web;
- landing pages;
- sitios comerciales;
- hosting;
- dominios;
- correo corporativo;
- mantenimiento;
- intranets pequeñas.

También debe poder funcionar como landing independiente para campañas.

---

## /servicio

Página breve de confianza.

Incluir:

- ubicación;
- horarios;
- tiempos habituales;
- cómo trabajamos;
- diagnóstico;
- medios de pago;
- preguntas frecuentes;
- reseñas;
- mapa.

---

## /contacto

Incluir:

- WhatsApp;
- teléfono;
- dirección;
- horario;
- formulario;
- mapa;
- Instagram.

---

# HOME

Diseñar una home corta y muy visual.

## Hero

Ejemplo conceptual:

### Tecnología que funciona. Sin vueltas.

Reparamos y mejoramos PCs y notebooks, desarrollamos páginas web y resolvemos problemas informáticos para particulares y pequeños negocios.

**Caseros · El Palomar y alrededores**

Botones:

**Consultar por WhatsApp**

**Ver servicios y precios**

Mostrar de manera visible:

**Trabajos habituales en 24/48 hs.**

---

# BLOQUE RÁPIDO DE CONFIANZA

Mostrar 4 datos de manera muy visual:

### 24/48 hs
Tiempo habitual de trabajos.

### Caseros
Mitre 5761.

### Presupuesto claro
Sabés cuánto vas a pagar antes de reparar.

### Atención directa
Sin intermediarios.

---

# SERVICIO TÉCNICO (listado de precios de /reparacion-pc)

No son secciones con texto explicativo: es un **listado de precios** por categoría. Nombre del servicio, detalle de una línea si hace falta, y precio. El USD es la referencia interna que guarda el sistema (ver [SISTEMA DE PRECIOS](#sistema-de-precios)); lo que ve el visitante es el precio en pesos ya redondeado.

## Actualizaciones

| Servicio | Detalle | Precio | USD ref. |
|---|---|---|---|
| Cambio de disco y/o RAM | + insumos | $45.000 | USD 29,27 |
| Cambio de disco/RAM + instalación de Windows | | $90.000 | USD 58,54 |

## Sistemas

| Servicio | Detalle | Precio | USD ref. |
|---|---|---|---|
| Instalación de sistema operativo | Windows o macOS | $55.000 | USD 35,77 |

## Mantenimiento

| Servicio | Detalle | Precio | USD ref. |
|---|---|---|---|
| Notebook | Limpieza + pasta térmica | $65.000 | USD 42,28 |
| PC sin gráfica dedicada | Limpieza + pasta térmica | $45.000 | USD 29,27 |
| PC con gráfica dedicada | Limpieza + placa gráfica + pasta térmica | $65.000 | USD 42,28 |
| Watercooling | Adicional | +$9.000 | USD 5,85 |

## Notebooks

| Servicio | Detalle | Precio | USD ref. |
|---|---|---|---|
| Cambio de pantalla | Mano de obra | $95.000 + repuesto | USD 61,79 |
| Cambio de bisagras | Mano de obra. Consultar también por reparación de bisagras/carcasa | $115.000 + repuestos | USD 74,80 |

## Fuentes

| Servicio | Detalle | Precio | USD ref. |
|---|---|---|---|
| Cambio de fuente | | $45.000 + fuente | USD 29,27 |
| Cambio de fuente + mantenimiento | | $85.000 + fuente | USD 55,28 |

## Diagnóstico

| Servicio | Detalle | Precio | USD ref. |
|---|---|---|---|
| Diagnóstico técnico | Se descuenta del valor de la reparación si se hace el trabajo con nosotros. Se bonifica si el equipo no tiene reparación razonable. | $65.000 | USD 42,28 |

Esa frase del diagnóstico es la única que amerita texto completo (no tabla): debe sonar transparente, no a letra chica.

## Otros servicios (Consultar)

Sin precio fijo, botón "Consultar":

- cambio de teclado de notebook;
- cambio de batería;
- reparación o reemplazo de conector de carga;
- conectores USB;
- problemas de encendido;
- migración de datos;
- clonado de discos;
- eliminación de malware;
- optimización de Windows;
- recuperación de información;
- configuración de impresoras;
- problemas de Wi-Fi y red;
- armado de PC;
- diagnóstico de componentes.

Cada uno se puede activar/desactivar desde administración.

---

# DESARROLLO WEB

Crear tres paquetes principales.

No llamarles obligatoriamente "Básico", "Profesional" y "Premium".

Buscar nombres comerciales modernos y claros.

Ejemplo:

## Presencia

Landing page.

Incluye:

- página de una sola sección larga;
- diseño responsive;
- formulario de contacto al email;
- botón de WhatsApp;
- configuración SEO básica.

Precio actual:

**$350.000**

USD inicial:

**USD 227,64**

---

## Negocio

Sitio de hasta 4 secciones/páginas principales.

Incluye:

- diseño responsive;
- hasta 4 secciones principales;
- formulario de contacto al email;
- WhatsApp;
- SEO técnico básico.

Precio actual:

**$780.000**

USD inicial:

**USD 507,32**

---

## Completo

Sitio de hasta 8 secciones.

Incluye:

- hasta 8 secciones principales;
- dos formularios;
- integración WhatsApp;
- estructura SEO;
- diseño responsive.

Precio actual:

**$990.000**

USD inicial:

**USD 643,91**

---

# ADICIONALES WEB

### Dominio + hosting

Registro de dominio, contratación y configuración inicial:

**$85.000 + dominio + hosting**

USD inicial:

**USD 55,28**

---

### Hosting administrado

**$200.000 por año**

USD inicial:

**USD 130,08 / año**

---

### Identidad básica

Nombre, logo y estilos iniciales para un negocio que todavía no tenga identidad.

**$190.000**

USD inicial:

**USD 123,58**

---

### Fotografías e imágenes

Mostrar:

**Consultar**

---

### Edición de contenidos

Crear pequeña intranet para modificar contenido del sitio.

**$250.000**

USD inicial:

**USD 162,60**

---

### Gestión de contactos

Intranet para almacenar y administrar consultas recibidas desde formularios.

**$450.000**

USD inicial:

**USD 292,68**

---

# EMAIL CORPORATIVO

El correo corporativo debe tener bastante presencia dentro de servicios para empresas.

Ejemplo:

"Dejá de usar tunegocio@gmail.com."

"Usá vos@tunegocio.com.ar."

Ofrecer:

- dominio;
- configuración DNS;
- cuentas;
- Google Workspace;
- Microsoft 365;
- configuración en PC y celular;
- migración de correo cuando corresponda.

El precio debe ser "Consultar", ya que puede depender del proveedor y cantidad de cuentas.

---

# SISTEMA DE PRECIOS

CRÍTICO.

Los precios NO deben almacenarse en pesos argentinos como valor principal.

Guardar:

`price_usd`

tipo:

`DECIMAL(10,2)`

Los precios iniciales USD fueron calculados tomando como referencia el dólar MEP del 31/08/2026.

La cotización utilizada inicialmente es aproximadamente:

**1 USD = ARS 1.537,49**

---

# COTIZACIÓN AUTOMÁTICA

Utilizar como referencia:

**Dólar MEP / Bolsa - precio de venta**

API recomendada:

DolarApi.

Endpoint:

`https://dolarapi.com/v1/dolares/bolsa`

La consulta debe realizarla el backend PHP.

NO consultar la API externa directamente en cada carga de página.

Implementar caché.

Por ejemplo:

- consultar como máximo cada 30 o 60 minutos;
- guardar última cotización válida;
- guardar fecha/hora;
- utilizarla hasta obtener una nueva.

Si la API falla:

NUNCA dejar de mostrar precios.

Usar la última cotización válida almacenada.

---

# CONFIGURACIÓN DEL DÓLAR

Desde administración permitir definir:

## Tipo de cotización

- automático MEP;
- cotización manual.

## Ajuste porcentual

Ejemplo:

MEP:

$1.537

Ajuste:

+3%

Cotización utilizada:

$1.583

Esto permite aplicar un pequeño margen sin modificar todos los servicios.

Guardar:

- cotización API;
- cotización efectiva;
- porcentaje de ajuste;
- última actualización;
- modo automático/manual.

---

# REDONDEO

Los precios públicos NO deben mostrarse con números poco comerciales como:

"$45.132"

Implementar redondeo configurable.

Inicialmente redondear al múltiplo de:

**$500**

(billete más chico con el que se trabaja; no tiene sentido mostrar precios más finos que eso).

El redondeo es siempre **hacia arriba, a favor del negocio** — nunca hacia abajo.

Ejemplo con cotización efectiva $1.583:

USD 29,27 × 1.583 = ARS 46.335,91 → se muestra **$46.500** (múltiplo de 500 más cercano hacia arriba).

La base USD permanece sin redondear; es la que se guarda en `services.price_usd` y la que mantiene los precios actualizados aunque el administrador no toque nada. El administrador puede, si quiere, cambiar el precio en USD de cualquier servicio en cualquier momento desde la intranet.

Nota: por el redondeo hacia arriba, el precio en pesos del día 1 puede quedar hasta $500 por encima de las cifras "redondas" listadas en este documento (que fueron calculadas al revés, de pesos a USD). Es esperable, no es un error.

---

# VISUALIZACIÓN DE PRECIOS

Mostrar principalmente pesos.

Ejemplo:

### Cambio de disco / RAM

**$45.000**

+ repuestos

No mostrar permanentemente el precio USD al cliente salvo que se considere útil.

Puede mostrarse en pequeño:

"Precio actualizado según cotización."

Agregar opcionalmente:

"Precios actualizados hoy."

---

# ADMINISTRACIÓN

Crear:

`/admin`

Múltiples administradores permitidos (ABM completo), todos con el mismo nivel de acceso.

No implementar roles ni permisos diferenciados entre administradores.

`/admin` **no debe indexarse**: bloquear en `robots.txt` (`Disallow: /admin`) y agregar `<meta name="robots" content="noindex, nofollow">` en todas sus páginas.

## Usuarios

ABM de administradores (crear, editar, deshabilitar, eliminar). Resguardos obligatorios:

- un administrador no puede deshabilitarse ni eliminarse a sí mismo;
- no puede quedar cero administradores activos.

Cada administrador puede además editar sus propios datos (usuario, email, teléfono, contraseña) desde una pantalla de "Mi perfil", separada del ABM — el cambio de contraseña propio pide confirmar la contraseña actual.

## Sesiones y auditoría

La sesión del panel se guarda en base de datos (no en archivos), separada de la sesión del sitio público, para poder:

- listar las sesiones activas (usuario, IP, navegador, última actividad);
- cerrar cualquier sesión de forma remota (borrar la fila es suficiente para forzar el cierre de sesión en ese navegador).

Se registra además un log de auditoría de accesos: ingresos exitosos, ingresos fallidos y cierres de sesión, con fecha, usuario, IP y navegador.

## Turnos

Ver [SISTEMA DE TURNOS](#sistema-de-turnos): horario semanal, bloqueos y lista
de turnos reservados (con cancelación), todo desde `/admin`.

## Backup rápido

Agregar un botón **"Exportar todo (SQL)"** dentro de la administración que genere y descargue un dump completo de la base de datos (equivalente a `mysqldump`), para poder hacer un backup manual en cualquier momento sin depender de tener acceso al hosting/cPanel.

---

# SERVICIOS

El administrador puede:

- crear;
- editar;
- ocultar;
- ordenar;
- destacar.

Campos:

- categoría;
- nombre;
- slug;
- descripción breve;
- descripción completa;
- price_usd;
- tipo de precio;
- texto adicional;
- destacado;
- visible;
- orden.

Tipos de precio:

- precio fijo;
- desde;
- adicional;
- consultar;
- precio + insumos;
- incluido en combo.

---

# CONFIGURACIÓN GENERAL

Editar:

- nombre comercial;
- dirección;
- teléfono;
- WhatsApp;
- Instagram;
- email;
- horarios;
- Google Maps;
- tiempo estimado de trabajos;
- textos principales de la home.

Datos iniciales:

Dirección:

**Mitre 5761, Caseros, Buenos Aires**

WhatsApp:

**+54 9 11 5697-0599**

Horario:

**Lunes a viernes de 08:00 a 20:00**

Tiempo habitual:

**24/48 hs**

---

# MEDIOS DE PAGO

Mostrar claramente:

## Sin recargo

- efectivo;
- transferencia;
- Mercado Pago pagando con dinero disponible en cuenta.

## Con recargo (18%)

Cualquier otro medio de pago tiene el mismo recargo: tarjeta de crédito/débito directa, o Mercado Pago pagando con tarjeta (crédito o débito) en vez de saldo en cuenta.

**18% de recargo**

También existen:

**planes de cuotas**

No mostrar una cantidad determinada de cuotas hasta tener configurado cada plan.

Texto conceptual:

"También podés pagar con tarjeta de crédito (o Mercado Pago con tarjeta). Consultanos por cuotas."

---

# CALCULADORA DE TARJETA

El sistema debe estar preparado para calcular automáticamente, para cualquier medio con recargo:

`precio_contado × 1.18`

Mostrar opcionalmente:

"Con recargo (tarjeta / MP con tarjeta) en 1 pago: $XXX"

El porcentaje debe ser configurable desde administración y aplica por igual a tarjeta directa y a Mercado Pago con tarjeta (no son dos porcentajes distintos, salvo que más adelante se quiera diferenciarlos).

Valor inicial:

**18%**

Los planes de cuotas deben poder agregarse más adelante.

---

# FORMULARIOS

Formularios muy cortos.

Servicio técnico:

- nombre;
- WhatsApp;
- equipo;
- problema;
- servicio opcional.

Desarrollo web:

- nombre;
- negocio;
- WhatsApp;
- email;
- qué necesita.

CTA al finalizar:

WhatsApp.

Guardar también las consultas en MySQL.

---

# PROTECCIÓN DE DATOS PERSONALES

El sitio recolecta datos personales (nombre, WhatsApp, email) a través de los formularios. En Argentina esto está alcanzado por la **Ley 25.326 de Protección de Datos Personales**.

Implementar como mínimo:

- página `/politica-de-privacidad` con: qué datos se recolectan, para qué se usan (solo para contactar al usuario), cuánto tiempo se conservan, y que no se comparten con terceros;
- checkbox de consentimiento en cada formulario ("Acepto la política de privacidad"), obligatorio para poder enviar;
- link a la política de privacidad visible en el footer de todo el sitio;
- posibilidad de que el usuario pida la baja de sus datos (alcanza con que sea vía WhatsApp/email, no hace falta un flujo automatizado).

No hace falta un aviso de cookies estilo GDPR si no se usan cookies de tracking de terceros sin consentimiento; si más adelante se agrega Meta Pixel/Google Analytics, sumar un aviso simple de cookies.

---

# SISTEMA DE TURNOS

El negocio no es un local a la calle: se atiende únicamente con turno previo.
`/turnos` permite reservar uno online, además del canal de WhatsApp habitual.

Flujo del cliente:

- inicia sesión con **Google** (Authorization Code flow implementado a mano,
  sin SDK — ver `includes/google_oauth.php`; no hay tabla de usuarios propia,
  el cliente se identifica por el `sub` de Google);
- elige un horario disponible, dentro del horario semanal que carga el admin;
- confirma con WhatsApp de contacto y un motivo opcional;
- recibe un mail de confirmación, y puede cancelar sus propios turnos desde
  "Mis turnos" en la misma página.

Disponibilidad (`includes/booking.php`):

- horario semanal recurrente, con posibilidad de varios rangos por día (ej.
  mañana y tarde);
- bloqueos puntuales sobre ese horario (feriados, imprevistos), por fecha
  completa o por rango horario;
- duración de turno configurable desde Configuración (minutos) — **no se
  puede cambiar mientras haya turnos reservados a futuro**, para no dejar
  reservas ya confirmadas en un estado inconsistente;
- un slot solo se ofrece si no se solapa con ningún bloqueo ni con ninguna
  reserva ya existente (comparación por rango, no por igualdad exacta de
  horario, para que convivan turnos creados con duraciones distintas).

Administración:

- horario semanal (`/admin/booking-schedule.php`) y bloqueos
  (`/admin/booking-blocks.php`), ambos con alta/baja;
- lista de turnos reservados (`/admin/bookings.php`), con cancelación (libera
  el horario al instante);
- el admin puede cancelar cualquier turno; el cliente solo los suyos.

Mail: cada turno nuevo avisa por SMTP (Gmail con contraseña de aplicación —
ver `config/.env.example`) tanto al admin (`setting('email')`) como al
cliente, con un cliente SMTP propio (`includes/mailer.php`), sin librerías.

Pago: no se cobra todavía. `bookings.payment_status` (`simulado` / `pendiente`
/ `pagado`) deja preparado el modelo para cuando se pida pago real más
adelante — hoy todas las reservas quedan como `simulado` (confirmadas al
instante, sin pago), sin necesidad de rehacer el esquema el día que se
implemente el cobro.

---

# WHATSAPP

WhatsApp debe ser el CTA principal.

Cada servicio debe generar mensajes contextuales.

Ejemplo:

"Hola, quería consultar por cambio de disco/RAM."

Para web:

"Hola, quería consultar por una página web para mi negocio."

Evitar mensajes genéricos siempre que sea posible.

---

# DISEÑO

La identidad todavía no está definida.

Crear una identidad provisional extremadamente moderna.

Sensación:

- empresa tecnológica;
- precisión;
- velocidad;
- confianza;
- cercanía.

No parecer:

- casa de computación de los 2000;
- gamer;
- hacker;
- cyberpunk exagerado;
- plantilla de WordPress;
- empresa multinacional impersonal.

---

# PALETA

Utilizar inicialmente:

Fondo:

`#080B12`

Superficie:

`#101521`

Superficie elevada:

`#182131`

Azul:

`#2F80FF`

Turquesa:

`#32D6C5`

Texto:

`#F5F7FA`

Centralizar absolutamente todos los colores mediante variables CSS.

La identidad deberá poder cambiarse posteriormente sin reconstruir la web.

---

# ESTÉTICA

Utilizar:

- gradientes sutiles;
- transparencias;
- profundidad;
- glassmorphism muy moderado;
- bordes sutiles;
- iluminación;
- pequeñas partículas o elementos tecnológicos;
- microinteracciones;
- transiciones suaves;
- animaciones de entrada;
- navbar moderna;
- iconografía minimalista.

Las animaciones deben aportar calidad percibida.

NO convertir la página en una demo de animaciones.

---

# MOBILE FIRST

Diseñar primero para teléfonos.

Prioridad:

375px–430px.

En móvil deben estar siempre muy accesibles:

- precio;
- WhatsApp;
- ubicación;
- tiempo;
- servicio.

Implementar una barra CTA inferior móvil si mejora la conversión.

Ejemplo:

[ WhatsApp ] [ Llamar ]

---

# SEO LOCAL

Optimizar específicamente para búsquedas como:

- reparación PC Caseros;
- servicio técnico PC Caseros;
- reparación notebook Caseros;
- técnico PC Caseros;
- servicio técnico El Palomar;
- reparación notebook El Palomar;
- cambio SSD notebook Caseros;
- mantenimiento notebook Caseros;
- desarrollo web Caseros;
- páginas web Caseros;
- diseño web para comercios;
- páginas web para emprendedores.

Utilizar lenguaje natural.

NO repetir keywords artificialmente.

---

# SCHEMA.ORG

Implementar JSON-LD.

Utilizar cuando corresponda:

- LocalBusiness;
- ComputerRepair;
- ProfessionalService;
- Service;
- Offer;
- PostalAddress;
- OpeningHoursSpecification;
- Review;
- AggregateRating.

No inventar información.

---

# GOOGLE MAPS

Incluir mapa en `/servicio` y `/contacto`.

Dirección:

Mitre 5761, Caseros, Buenos Aires.

Mostrar también botón:

**Cómo llegar**

---

# RESEÑAS

Integrar o preparar sección para Google Reviews.

Mientras no exista integración automática, permitir cargar reseñas reales desde administración.

Campos:

- nombre;
- puntuación;
- texto;
- fecha;
- enlace;
- visible.

No crear testimonios falsos.

---

# SEO TÉCNICO

Implementar:

- HTML semántico;
- title;
- meta description;
- canonical;
- Open Graph;
- sitemap.xml;
- robots.txt;
- JSON-LD;
- URLs limpias;
- alt en imágenes;
- headings correctos;
- páginas rápidas;
- breadcrumbs donde correspondan.

---

# PUBLICIDAD Y ANALÍTICA

Preparar eventos para:

- WhatsApp;
- teléfono;
- formulario;
- Maps;
- Instagram;
- consulta de servicio;
- consulta de desarrollo web.

Permitir agregar:

- Google Analytics;
- Google Tag Manager;
- Meta Pixel.

Configurarlos desde variables/configuración.

---

# STACK

Hosting convencional sin Node.js.

Obligatorio:

- PHP 8.1+;
- MySQL / MariaDB;
- PDO;
- HTML5;
- CSS moderno;
- JavaScript vanilla.

No requerir en producción:

- Node.js;
- npm;
- yarn;
- pnpm;
- Vite;
- React;
- Next.js.

El proyecto debe poder desplegarse copiando archivos al hosting y configurando MySQL.

---

# DEPENDENCIAS

Minimizar dependencias externas.

Preferir:

- PHP propio;
- JavaScript propio;
- CSS propio.

No incorporar frameworks grandes si no son necesarios.

---

# BASE DE DATOS

Como mínimo:

`admins`

`service_categories`

`services`

`settings`

`exchange_rates`

`reviews`

`contact_requests`

`admin_sessions`

`login_audit`

`booking_schedule`

`booking_blocks`

`bookings`

Opcional:

`payment_plans`

---

# INSTALACIÓN

El proyecto debe poder instalarse solo, con la mínima intervención manual posible.

Pasos manuales (los únicos que hace el administrador):

1. crear la base de datos vacía en MySQL/MariaDB;
2. crear un usuario de MySQL con permisos sobre esa base (no usar el usuario root de MySQL para la conexión de la app en producción);
3. completar host, nombre de base, usuario y contraseña en un archivo de configuración local no versionado en git.

A partir de ahí, todo lo demás debe ser automático:

- un script de instalación (`install.php` o similar) crea todas las tablas (`admins`, `service_categories`, `services`, `settings`, `exchange_rates`, `reviews`, `contact_requests`, etc.);
- carga los datos iniciales: configuración general (dirección, WhatsApp, horarios), categorías y servicios con sus precios en USD, cotización inicial;
- pide crear el usuario administrador (usuario/contraseña) en el propio instalador, nunca hardcodeado;
- una vez instalado, el instalador se bloquea solo (no debe poder correr dos veces ni quedar accesible en producción).

Las credenciales de conexión a MySQL (host, usuario, contraseña) se guardan siempre en un archivo de configuración fuera del control de versiones (por ejemplo `config/.env`, ignorado por git) — nunca en este documento ni en el repositorio.

---

# SEGURIDAD

Implementar:

- PDO;
- prepared statements;
- password_hash;
- password_verify;
- CSRF;
- escape HTML;
- validación servidor;
- sesiones seguras;
- regeneración de ID;
- HttpOnly;
- SameSite;
- Secure en HTTPS;
- rate limiting de login;
- headers de seguridad.

---

# FILOSOFÍA UX

Cada página debe responder rápidamente:

1. ¿Hacen lo que necesito?
2. ¿Cuánto cuesta?
3. ¿Dónde están?
4. ¿Cuánto tarda?
5. ¿Puedo confiar?
6. ¿Cómo los contacto?

Si una sección no ayuda a responder alguna de esas preguntas o a mejorar SEO/conversión, probablemente no sea necesaria.

---

# PRIORIDADES

En orden:

1. generar consultas;
2. WhatsApp;
3. experiencia móvil;
4. precios claros;
5. confianza;
6. SEO local;
7. velocidad;
8. estética;
9. facilidad de administración.

La página debe verse excepcionalmente moderna, pero seguir siendo una web comercial simple.

La frase que debe guiar el proyecto es:

**"Toda la información que necesitás para decidir si nos escribís, sin hacerte perder tiempo."**