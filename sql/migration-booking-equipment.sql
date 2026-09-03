-- Migración para instalaciones existentes. Ejecutar una sola vez.
-- Modelo: un equipo puede tener muchos turnos; las fotos pertenecen a cada ingreso.
CREATE TABLE IF NOT EXISTS customer_equipment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_google_sub VARCHAR(255) NULL,
    equipment_type ENUM('notebook','escritorio','otro') NULL,
    operating_system ENUM('windows','macos','linux','otro','no_sabe') NULL,
    disk_type ENUM('hdd','ssd_sata','ssd_nvme','otro','no_sabe') NULL,
    ram_type VARCHAR(40) NULL,
    ram_amount VARCHAR(40) NULL,
    cpu VARCHAR(160) NULL,
    brand VARCHAR(100) NULL,
    model VARCHAR(160) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_equipment_owner (owner_google_sub)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE bookings
    ADD COLUMN equipment_id INT UNSIGNED NULL AFTER service_id,
    ADD INDEX idx_bookings_equipment (equipment_id),
    ADD CONSTRAINT fk_bookings_equipment FOREIGN KEY (equipment_id) REFERENCES customer_equipment(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS booking_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(80) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_photos_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking_photos_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
