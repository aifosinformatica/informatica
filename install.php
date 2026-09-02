<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

if (is_file(INSTALL_LOCK_FILE)) {
    http_response_code(403);
    die(
        'Este sitio ya fue instalado. Por seguridad, borrá o renombrá install.php del servidor. '
        . 'Si necesitás reinstalar desde cero, borrá config/installed.lock (perderás todos los datos).'
    );
}

/** @return array<int,string> */
function schema_statements(): array
{
    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    if ($sql === false) {
        die('No se encontró sql/schema.sql');
    }
    // Elimina comentarios de línea y separa por sentencia.
    $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    return array_values($statements);
}

function seed_categories_and_services(PDO $pdo): void
{
    // page => [ [name, [ [name, price_usd, price_type, extra_text, short_description, full_description, featured], ... ] ], ... ]
    $data = [
        'reparacion-pc' => [
            'Actualizaciones' => [
                ['Cambio de disco y/o RAM', 29.27, 'mas_insumos', '+ insumos', 'Tu PC vuelve a ser rápida, sin gastar en un equipo nuevo.', null, 1],
                ['Cambio de disco/RAM + instalación de Windows', 58.54, 'fijo', null, 'Hardware nuevo y Windows a punto, listo para usar apenas lo retirás.', null, 0],
            ],
            'Sistemas' => [
                ['Instalación de sistema operativo', 35.77, 'fijo', null, 'Windows o macOS, instalado y configurado como recién salido de fábrica.', null, 0],
            ],
            'Mantenimiento' => [
                ['Notebook', 42.28, 'fijo', null, 'Le sacamos el polvo acumulado y bajamos la temperatura: menos ruido, menos calor, más vida útil.', null, 1],
                ['PC sin gráfica dedicada', 29.27, 'fijo', null, 'Un service completo para que respire mejor y no se te apague en pleno uso.', null, 0],
                ['PC con gráfica dedicada', 42.28, 'fijo', null, 'Incluye la placa de video: la abrimos y limpiamos por dentro, no solo por fuera.', null, 0],
                ['Watercooling', 5.85, 'adicional', null, 'Si tu PC tiene refrigeración líquida, le hacemos mantenimiento aparte.', null, 0],
            ],
            'Notebooks' => [
                ['Cambio de pantalla', 61.79, 'mas_insumos', '+ repuesto', 'Rota, rayada o con líneas raras: te la cambiamos por una nueva.', null, 0],
                ['Cambio de bisagras', 74.80, 'mas_insumos', '+ repuestos', '¿Se abre sola o hace ruido al levantar la tapa? Eso tiene arreglo. Preguntanos también si la carcasa está rota.', null, 0],
            ],
            'Fuentes' => [
                ['Cambio de fuente', 29.27, 'mas_insumos', '+ fuente', '¿No prende o se apaga de la nada? Muchas veces es la fuente.', null, 0],
                ['Cambio de fuente + mantenimiento', 55.28, 'mas_insumos', '+ fuente', 'Aprovechás la visita y le hacemos también un service completo.', null, 0],
            ],
            'Diagnóstico' => [
                ['Diagnóstico técnico', 42.28, 'fijo', null,
                    'Revisamos todo y te contamos qué tiene, en criollo y sin sorpresas después.',
                    null, 1],
            ],
            'Otros servicios' => [
                ['Cambio de teclado de notebook', null, 'consultar', null, null, null, 0],
                ['Cambio de batería', null, 'consultar', null, null, null, 0],
                ['Reparación o reemplazo de conector de carga', null, 'consultar', null, null, null, 0],
                ['Conectores USB', null, 'consultar', null, null, null, 0],
                ['Problemas de encendido', null, 'consultar', null, null, null, 0],
                ['Migración de datos', null, 'consultar', null, null, null, 0],
                ['Clonado de discos', null, 'consultar', null, null, null, 0],
                ['Eliminación de malware', null, 'consultar', null, null, null, 0],
                ['Optimización de Windows', null, 'consultar', null, null, null, 0],
                ['Recuperación de información', null, 'consultar', null, null, null, 0],
                ['Configuración de impresoras', null, 'consultar', null, null, null, 0],
                ['Problemas de Wi-Fi y red', null, 'consultar', null, null, null, 0],
                ['Armado de PC', null, 'consultar', null, null, null, 0],
                ['Diagnóstico de componentes', null, 'consultar', null, null, null, 0],
            ],
        ],
        'desarrollo-web' => [
            'Paquetes' => [
                ['Presencia', 227.64, 'fijo', null,
                    'Una sola página que cuenta quién sos y hace que te escriban.',
                    'Se ve perfecta en el celular|Formulario de contacto|Botón directo a WhatsApp|Lista para aparecer en Google', 1],
                ['Negocio', 507.32, 'fijo', null,
                    'El sitio completo de tu negocio: quién sos, qué hacés y cómo te encuentran.',
                    'Se ve perfecta en el celular|Hasta 4 secciones para contar tu negocio|Formulario de contacto|Botón de WhatsApp|Preparada para Google', 1],
                ['Completo', 643.91, 'fijo', null,
                    'Para negocios con más para mostrar: más secciones y más formas de que te contacten.',
                    'Hasta 8 secciones|Dos formularios distintos|WhatsApp integrado|Base sólida para SEO|Perfecta en cualquier pantalla', 1],
            ],
            'Adicionales' => [
                ['Dominio + hosting', 55.28, 'mas_insumos', '+ dominio + hosting', 'Nos encargamos de que tunegocio.com.ar sea tuyo y esté online.', null, 0],
                ['Hosting administrado', 130.08, 'fijo', '/ año', 'Del mantenimiento técnico nos ocupamos nosotros, vos solo usalo.', null, 0],
                ['Identidad básica', 123.58, 'fijo', null, '¿Todavía no tenés nombre ni logo definidos? Arrancamos por ahí.', null, 0],
                ['Fotografías e imágenes', null, 'consultar', null, 'Fotos propias para que tu sitio no se vea con imágenes genéricas.', null, 0],
                ['Edición de contenidos', 162.60, 'fijo', null, 'Un panel simple para que vos mismo cambies textos y precios cuando quieras.', null, 0],
                ['Gestión de contactos', 292.68, 'fijo', null, 'Todas las consultas que te lleguen por la web, ordenadas en un solo lugar.', null, 0],
            ],
            'Email corporativo' => [
                ['Email corporativo', null, 'consultar', null,
                    'Te armamos el correo con tu propio dominio (Google Workspace o Microsoft 365) y lo dejamos andando en tu PC y en el celular.', null, 1],
            ],
        ],
    ];

    $catStmt = $pdo->prepare(
        'INSERT INTO service_categories (page, name, slug, sort_order) VALUES (:page, :name, :slug, :sort)'
    );
    $svcStmt = $pdo->prepare(
        'INSERT INTO services
            (category_id, name, slug, short_description, full_description, price_usd, price_type, extra_text, featured, sort_order)
         VALUES
            (:category_id, :name, :slug, :short_description, :full_description, :price_usd, :price_type, :extra_text, :featured, :sort_order)'
    );

    $catOrder = 0;
    foreach ($data as $page => $categories) {
        foreach ($categories as $catName => $services) {
            $catOrder += 10;
            $catSlug = $page . '-' . slugify($catName);
            $catStmt->execute(['page' => $page, 'name' => $catName, 'slug' => $catSlug, 'sort' => $catOrder]);
            $categoryId = (int) $pdo->lastInsertId();

            $svcOrder = 0;
            foreach ($services as [$name, $priceUsd, $priceType, $extraText, $shortDesc, $fullDesc, $featured]) {
                $svcOrder += 10;
                $svcStmt->execute([
                    'category_id' => $categoryId,
                    'name' => $name,
                    'slug' => $page . '-' . slugify($name),
                    'short_description' => $shortDesc,
                    'full_description' => $fullDesc,
                    'price_usd' => $priceUsd,
                    'price_type' => $priceType,
                    'extra_text' => $extraText,
                    'featured' => $featured,
                    'sort_order' => $svcOrder,
                ]);
            }
        }
    }
}

function seed_settings(PDO $pdo): void
{
    $defaults = [
        'nombre_comercial' => 'Servicio Técnico Caseros',
        'direccion' => 'Mitre 5761, Caseros, Buenos Aires',
        'maps_query' => 'Mitre 5761, Caseros, Buenos Aires, Argentina',
        'whatsapp' => '5491156970599',
        'whatsapp_display' => '+54 9 11 5697-0599',
        'telefono' => '+54 9 11 5697-0599',
        'email' => '',
        'instagram' => '',
        'horario' => 'Lunes a viernes de 08:00 a 20:00',
        'tiempo_estimado' => '24/48 hs',
        'dolar_mode' => 'automatico',
        'dolar_ajuste_pct' => '0',
        'dolar_manual' => '0',
        'redondeo_multiplo' => '500',
        'recargo_tarjeta_pct' => '18',
        'turno_duracion_min' => '120',
        'turno_dias_visibles' => '14',
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
    );
    foreach ($defaults as $key => $value) {
        $stmt->execute(['key' => $key, 'value' => $value]);
    }
}

/** Horario semanal default (lunes a viernes, 09:00 a 18:00) para no arrancar sin nada cargado. */
function seed_booking_schedule(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO booking_schedule (weekday, start_time, end_time) VALUES (:weekday, :start, :end)'
    );
    foreach ([1, 2, 3, 4, 5] as $weekday) { // 1=lunes .. 5=viernes
        $stmt->execute(['weekday' => $weekday, 'start' => '09:00:00', 'end' => '18:00:00']);
    }
}

$error = null;
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesión expirada, recargá la página e intentá de nuevo.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if ($username === '' || mb_strlen($username) < 3) {
            $error = 'Elegí un usuario de al menos 3 caracteres.';
        } elseif (mb_strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $pdo = db();
            try {
                // Las sentencias DDL (CREATE TABLE) hacen COMMIT implícito en MySQL/MariaDB:
                // no se pueden envolver en una transacción, así que corren aparte.
                foreach (schema_statements() as $statement) {
                    $pdo->exec($statement);
                }

                $pdo->beginTransaction();

                $existing = (int) $pdo->query('SELECT COUNT(*) FROM service_categories')->fetchColumn();
                if ($existing === 0) {
                    seed_categories_and_services($pdo);
                }
                $existingSchedule = (int) $pdo->query('SELECT COUNT(*) FROM booking_schedule')->fetchColumn();
                if ($existingSchedule === 0) {
                    seed_booking_schedule($pdo);
                }
                seed_settings($pdo);

                $adminStmt = $pdo->prepare(
                    'INSERT INTO admins (username, password_hash) VALUES (:u, :p)
                     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
                );
                $adminStmt->execute([
                    'u' => $username,
                    'p' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $pdo->commit();

                file_put_contents(
                    INSTALL_LOCK_FILE,
                    'Instalado el ' . date('Y-m-d H:i:s') . ' — admin inicial: ' . $username . "\n"
                );

                $done = true;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = IS_LOCAL
                    ? 'Error al instalar: ' . $e->getMessage()
                    : 'Ocurrió un error durante la instalación. Revisá la conexión a la base de datos.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Instalación</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
</head>
<body class="install-page">
<main class="container install-box">
    <h1>Instalación del sitio</h1>

    <?php if ($done): ?>
        <p class="alert alert--ok">
            Listo. Se crearon las tablas, se cargaron los precios de tu Idea.md y tu usuario administrador.
        </p>
        <p><a class="btn btn--primary" href="<?= e(url('/admin/login.php')) ?>">Ir a iniciar sesión</a></p>
        <p><strong>Por seguridad, borrá o renombrá <code>install.php</code> del servidor ahora.</strong></p>
    <?php else: ?>
        <p>La base de datos ya debe existir (creada por vos) y estar configurada en <code>config/.env</code>. Este paso crea las tablas, carga los precios iniciales y tu usuario administrador.</p>

        <?php if ($error): ?>
            <p class="alert alert--error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" class="form">
            <?= csrf_field() ?>
            <label>Usuario administrador
                <input type="text" name="username" required minlength="3" autocomplete="username">
            </label>
            <label>Contraseña
                <input type="password" name="password" required minlength="8" autocomplete="new-password">
            </label>
            <label>Repetir contraseña
                <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
            </label>
            <button type="submit" class="btn btn--primary">Instalar</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
