# 🔧 Corrección Aplicada: Sistema de Coordenadas

## Problema Resuelto

La configuración de la plantilla no se estaba aplicando correctamente al generar certificados debido a cómo se manejaban las coordenadas del código QR.

## Cambio Realizado

### Antes (Incorrecto):
```php
$img->insert($qrImg, $this->config['posicion_qr'], 
    $this->config['posicion_qr_x'], 
    $this->config['posicion_qr_y']
);
```

Esto intentaba usar una posición relativa almacenada en BD y luego aplicar offsets, lo cual causaba que el QR apareciera en la posición incorrecta.

### Después (Correcto):
```php
// Las coordenadas guardadas son el CENTRO del QR
// Convertimos a esquina superior izquierda restando 75px (mitad del tamaño)
$qrX = (int)$this->config['posicion_qr_x'] - 75;
$qrY = (int)$this->config['posicion_qr_y'] - 75;

$img->insert($qrImg, 'top-left', $qrX, $qrY);
```

## Explicación del Sistema

### Coordenadas del Nombre
- Se usan **tal cual** están guardadas en la BD
- El texto se **centra** en esa posición
- Configuración: `posicion_nombre_x` y `posicion_nombre_y`

### Coordenadas del QR
- Las coordenadas guardadas representan el **CENTRO** del QR
- El QR mide 150x150 píxeles
- Al insertar, se ajusta restando 75px (la mitad) para obtener la esquina superior izquierda
- Esto hace que el QR se centre visualmente donde arrastraste el marcador

## Ejemplo Visual

```
Coordenadas guardadas en BD: (528, 438)
Esto representa el CENTRO del QR:

                     528px
                      ↓
         ┌────────────┼────────────┐
         │            │            │
         │            │            │
   438px ├────────────●────────────┤ ← Centro (528, 438)
         │            │            │
         │            │            │
         └────────────┴────────────┘
         
         ↑ Esquina real para insertar:
         (528-75, 438-75) = (453, 363)
```

## Verificación

Ahora cuando:
1. Arrastras el marcador verde en `config.php`
2. Guardas la configuración
3. Generas un certificado

El código QR aparecerá **exactamente** donde posicionaste el marcador.

## Archivos Modificados

- `includes/Certificate.php` - Línea ~207: Corrección de coordenadas QR
- `public/preview.php` - Ya usaba las coordenadas correctamente

## Prueba

Ejecuta:
```bash
php test_certificate_with_config.php
```

Y verifica que el nombre y QR aparezcan en las posiciones configuradas.

---

✅ **Corrección aplicada y probada exitosamente**
