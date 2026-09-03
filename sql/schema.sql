-- Esquema de referencia. install.php lo aplica automáticamente: no hace falta
-- correrlo a mano salvo que quieras importarlo directo (phpMyAdmin, HeidiSQL, etc.)
-- La base de datos debe crearse ANTES, con charset utf8mb4 (ver doc/Idea.md > INSTALACIÓN).

-- Varios administradores permitidos, todos con el mismo nivel de acceso (sin roles/permisos diferenciados).
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(160) NULL,
    phone VARCHAR(40) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sesiones del panel de administración, guardadas en la base (no en archivos) para poder
-- listarlas y cerrarlas de forma remota. Las sesiones del sitio público NO usan esta tabla.
CREATE TABLE IF NOT EXISTS admin_sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    admin_id INT UNSIGNED NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    payload MEDIUMTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_sessions_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Auditoría de accesos: login exitoso, login fallido y logout.
CREATE TABLE IF NOT EXISTS login_audit (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NULL,
    username_attempted VARCHAR(60) NULL,
    action ENUM('login_success','login_failed','logout') NOT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_audit_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    ip VARCHAR(45) NOT NULL PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page ENUM('reparacion-pc','desarrollo-web') NOT NULL DEFAULT 'reparacion-pc',
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    sort_order INT NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    -- Si no es NULL, esta fila es una "variante" de otro servicio (ej. "Memoria RAM" es
    -- variante de "Cambio de componentes"), con su propio precio independiente. El servicio
    -- "padre" no tiene precio propio (price_type = 'grupo') y agrupa sus variantes en la
    -- vista (ver includes/services.php > get_categories_with_services). Se admite un solo
    -- nivel de anidamiento a propósito: una variante no es una categoría nueva.
    parent_service_id INT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    short_description VARCHAR(255) NULL,
    full_description TEXT NULL,
    price_usd DECIMAL(10,2) NULL,
    price_type ENUM('fijo','desde','adicional','consultar','mas_insumos','incluido_combo','grupo') NOT NULL DEFAULT 'fijo',
    extra_text VARCHAR(160) NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_services_category FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_services_parent FOREIGN KEY (parent_service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_services_parent (parent_service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(80) NOT NULL PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exchange_rates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source ENUM('api','manual') NOT NULL DEFAULT 'api',
    rate_api DECIMAL(10,2) NULL,
    adjustment_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
    rate_effective DECIMAL(10,2) NOT NULL,
    fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    review_date DATE NULL,
    url VARCHAR(255) NULL,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    origin ENUM('reparacion-pc','desarrollo-web','contacto') NOT NULL,
    name VARCHAR(120) NOT NULL,
    whatsapp VARCHAR(40) NULL,
    email VARCHAR(160) NULL,
    business_name VARCHAR(160) NULL,
    device VARCHAR(160) NULL,
    message TEXT NULL,
    service_slug VARCHAR(180) NULL,
    consent TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('nuevo','contactado','cerrado') NOT NULL DEFAULT 'nuevo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installments TINYINT UNSIGNED NOT NULL,
    surcharge_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================================
-- Sistema de turnos (ver doc/Idea.md y el plan de implementación)
-- ==========================================================================

-- Horario semanal recurrente de atención. Puede haber varios rangos por día
-- (ej. mañana y tarde, con un corte al mediodía).
CREATE TABLE IF NOT EXISTS booking_schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- weekday: 0=domingo .. 6=sábado (igual que date('w') de PHP)
    weekday TINYINT UNSIGNED NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bloqueos puntuales sobre el horario semanal (feriados, imprevistos, etc.).
-- start_time/end_time NULL en ambos = bloquea el día completo.
CREATE TABLE IF NOT EXISTS booking_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    reason VARCHAR(160) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Turnos reservados por clientes logueados con Google (ver includes/google_oauth.php).
-- No hay tabla de usuarios propia: el cliente se identifica por google_sub y
-- los datos de contacto quedan guardados en cada turno.
-- payment_status: 'simulado' = no se cobra todavía (comportamiento actual,
-- confirma solo). 'pendiente'/'pagado' quedan reservados para cuando se pida
-- pago real (ver plan de implementación).
CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    -- NULL cuando el turno lo carga el admin manualmente (consulta que llegó
    -- por teléfono, WhatsApp o mail): no hay login de Google de por medio, así
    -- que ese turno no aparece en "Mis turnos" del cliente ni se autocancela.
    google_sub VARCHAR(255) NULL,
    source ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    name VARCHAR(160) NOT NULL,
    email VARCHAR(160) NULL,
    whatsapp VARCHAR(40) NULL,
    -- Servicio elegido por el cliente al pedir el turno (opcional: puede no saber
    -- todavía qué necesita). ON DELETE SET NULL para no perder el turno si el
    -- servicio se borra después; el nombre queda igual en el mail ya enviado.
    service_id INT UNSIGNED NULL,
    motivo VARCHAR(255) NULL,
    payment_status ENUM('simulado','pendiente','pagado') NOT NULL DEFAULT 'simulado',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_bookings_slot (date, start_time),
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
