# 📋 Guía de Configuración de Plantilla

## ⚙️ Configurar Posición del Nombre y QR

El sistema ahora incluye una interfaz visual para configurar dónde se mostrará el nombre del certificado y el código QR.

### Acceder a la Configuración

1. Ve a la página principal: `http://localhost/cce-certificados/public/`
2. Haz clic en el botón **"⚙️ Configurar Plantilla"**
3. Se abrirá la interfaz de configuración

### Uso de la Interfaz

#### Vista Previa Interactiva
- En el lado izquierdo verás la plantilla de certificado cargada
- **Marcador Azul (N)**: Indica dónde se colocará el NOMBRE
- **Marcador Verde (QR)**: Indica dónde se colocará el CÓDIGO QR

#### Mover los Marcadores
Hay dos formas de ajustar las posiciones:

**Opción 1: Arrastrar y Soltar**
- Haz clic en el marcador (azul o verde)
- Mantén presionado el botón del mouse
- Arrastra el marcador a la posición deseada
- Suelta el botón
- Los valores en los campos se actualizarán automáticamente

**Opción 2: Valores Numéricos**
- Ingresa directamente los valores de X e Y en los campos
- El marcador se moverá automáticamente a esa posición

### Opciones de Configuración

#### 🔤 Configuración del Nombre
- **Fuente**: Selecciona una fuente personalizada o usa la del sistema
- **Tamaño de Fuente**: Ajusta el tamaño en píxeles (10-200)
- **Color del Texto**: Selecciona el color usando el selector
- **Posición X**: Coordenada horizontal
- **Posición Y**: Coordenada vertical

#### 📱 Configuración del Código QR
- **Posición X**: Coordenada horizontal del QR
- **Posición Y**: Coordenada vertical del QR
- **Posición del QR**: Alineación general (superior/inferior, izquierda/derecha, centro)

### Guardar Cambios

1. Ajusta las posiciones como desees
2. Opcionalmente, haz clic en **"Ver Vista Previa"** para generar un certificado de ejemplo
3. Haz clic en **"💾 Guardar Configuración"**
4. Los cambios se aplicarán inmediatamente a todos los certificados nuevos

### Resetear Configuración

- Si quieres descartar los cambios, haz clic en **"🔄 Resetear"**
- Esto recargará la página con la última configuración guardada

### Sistema de Coordenadas

```
(0,0) ─────────────────────→ X
  │
  │     PLANTILLA
  │
  │
  ↓
  Y
```

- **X = 0**: Borde izquierdo de la imagen
- **Y = 0**: Borde superior de la imagen
- Los valores aumentan hacia la derecha (X) y hacia abajo (Y)

### Consejos

✅ **Usa la vista previa** antes de guardar para ver cómo quedará el certificado

✅ **Arrastra los marcadores** para posicionamiento rápido y visual

✅ **Ajusta finamente** con los campos numéricos si necesitas precisión exacta

✅ **Prueba diferentes fuentes** para ver cuál se adapta mejor a tu diseño

✅ **El QR siempre tendrá 150x150 píxeles**, ten esto en cuenta al posicionarlo

### Agregar Fuentes Personalizadas

1. Coloca archivos `.ttf` u `.otf` en la carpeta: `assets/fonts/`
2. Recarga la página de configuración
3. La nueva fuente aparecerá en el selector "Fuente"

### Solución de Problemas

**El texto no se ve donde lo coloqué:**
- Verifica que las coordenadas X e Y estén dentro de los límites de la imagen
- Recuerda que el texto se alinea al centro en la posición especificada

**La fuente no se carga:**
- Asegúrate de que el archivo `.ttf` o `.otf` esté en `assets/fonts/`
- Verifica que el nombre del archivo no tenga espacios o caracteres especiales

**El QR no aparece:**
- Verifica que las coordenadas no sean negativas
- Asegúrate de que la posición tenga espacio suficiente (al menos 150x150 px)

### Valores Recomendados

Para una plantilla A4 horizontal (3508x2480 px):

**Nombre centrado:**
- X: 1754 (centro horizontal)
- Y: 800-1200 (dependiendo del diseño)

**QR en esquina inferior derecha:**
- X: 3300
- Y: 2300

**QR en esquina inferior izquierda:**
- X: 200
- Y: 2300
