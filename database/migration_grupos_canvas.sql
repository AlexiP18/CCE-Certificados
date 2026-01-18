-- Migración para agregar configuración de lienzo y variables habilitadas por grupo

USE cce_certificados;

-- Agregar campos para control de tamaños y posición de firma
ALTER TABLE grupos 
ADD COLUMN tamanio_qr INT DEFAULT 200 AFTER posicion_qr_y,
ADD COLUMN posicion_firma_x INT DEFAULT 800 AFTER tamanio_qr,
ADD COLUMN posicion_firma_y INT DEFAULT 850 AFTER posicion_firma_x,
ADD COLUMN tamanio_firma INT DEFAULT 150 AFTER posicion_firma_y,
ADD COLUMN variables_habilitadas JSON DEFAULT '["nombre","razon","qr","firma"]' AFTER tamanio_firma;

-- Comentarios explicativos
-- tamanio_qr: Tamaño del código QR en píxeles (ancho y alto)
-- posicion_firma_x: Posición horizontal de la firma en la plantilla
-- posicion_firma_y: Posición vertical de la firma en la plantilla
-- tamanio_firma: Ancho de la imagen de firma (el alto se ajusta proporcionalmente)
-- variables_habilitadas: Array JSON con las variables que se mostrarán en el certificado
--   Opciones: "nombre", "razon", "qr", "firma", "fecha"
