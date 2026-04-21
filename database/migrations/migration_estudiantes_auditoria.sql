CREATE TABLE IF NOT EXISTS `estudiantes_auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `estudiante_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(50) NOT NULL COMMENT 'creacion, actualizacion, eliminacion, restauracion',
  `detalles` longtext DEFAULT NULL COMMENT 'JSON con los campos modificados en formato {campo: {old: X, new: Y}}',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
