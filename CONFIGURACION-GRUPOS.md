# Configuración de Grupos - Sistema de Certificados CCE

## 📋 Características de Configuración por Grupo

Cada grupo puede tener su propia configuración personalizada de certificados, incluyendo:

### 🎨 Lienzo Interactivo de Posicionamiento
- **Arrastrar y soltar**: Mueve visualmente los elementos (Nombre, QR, Firma) en la plantilla
- **Sincronización automática**: Las coordenadas se actualizan en tiempo real
- **Vista previa en vivo**: Visualiza exactamente dónde aparecerá cada elemento

### 📄 Plantilla Personalizada
- Sube una imagen personalizada para cada grupo (PNG/JPG)
- Tamaño recomendado: 1600x1120px
- Se guarda como `grupo_{id}_template.ext`

### ☑️ Variables Habilitadas
Selecciona qué elementos mostrar en el certificado:
- 👤 **Nombre**: Nombre del participante
- 📝 **Razón**: Descripción del certificado
- 📱 **Código QR**: Para verificación
- ✍️ **Firma**: Firma y cargo del firmante
- 📅 **Fecha**: Fecha de emisión

### ✍️ Configuración de Firma
- **Nombre del firmante**: Ej: Dr. Juan Pérez
- **Cargo**: Ej: Director de la Casa de la Cultura
- **Imagen de firma**: Sube una firma escaneada (PNG transparente recomendado)
- **Tamaño de firma**: Define el ancho en píxeles (alto proporcional)
- **Posicionamiento**: X, Y en la plantilla

### 📏 Tamaños Configurables
- **Tamaño del QR**: De 50 a 400 píxeles
- **Tamaño de firma**: De 50 a 400 píxeles (ancho)

### 🎨 Estilo del Texto
- **Fuente**: Arial, Helvetica, Times New Roman, Georgia, Courier New, Verdana
- **Tamaño de fuente**: 20 a 100 puntos
- **Color del texto**: Selector de color visual

### 📍 Posicionamiento Preciso
Define coordenadas exactas para cada elemento:
- **Nombre**: Posición X, Y
- **QR**: Posición X, Y
- **Firma**: Posición X, Y

## 🚀 Cómo Usar

### 1. Acceder a la Configuración
1. Ve a **Grupos** desde el menú principal
2. Haz clic en el botón **⚙️ Configurar** del grupo que deseas personalizar

### 2. Configurar el Lienzo
1. **Arrastra** los marcadores de colores en el lienzo:
   - 🔵 **NOMBRE** (azul): Posición del nombre del participante
   - 🟢 **QR** (verde): Posición del código QR
   - 🟣 **FIRMA** (morado): Posición de la firma
2. Las coordenadas se actualizan automáticamente mientras arrastras
3. También puedes ingresar valores numéricos manualmente en los campos

### 3. Personalizar la Plantilla
1. En la sección **📄 Plantilla del Certificado**:
   - Haz clic o arrastra una imagen
   - Formatos soportados: PNG, JPG
   - La plantilla se mostrará en el lienzo de posicionamiento

### 4. Habilitar Variables
1. En la sección **☑️ Variables Habilitadas**:
   - Marca las casillas de los elementos que quieres mostrar
   - Los elementos no marcados no aparecerán en el certificado

### 5. Configurar Firma
1. En la sección **✍️ Firma**:
   - Ingresa el nombre del firmante
   - Ingresa el cargo
   - Opcionalmente, sube una imagen de firma
   - Ajusta el tamaño de la firma

### 6. Ajustar Estilos
1. En **🎨 Estilo del Texto**:
   - Selecciona la fuente
   - Ajusta el tamaño
   - Elige el color del texto

### 7. Definir Tamaños
1. En **📏 Tamaños**:
   - Establece el tamaño del código QR
   - (El tamaño de la firma se configura en la sección de Firma)

### 8. Guardar
1. Haz clic en **💾 Guardar Configuración**
2. La configuración se aplicará a todos los certificados generados para este grupo

## 📊 Estructura de Datos

### Base de Datos
Los siguientes campos se agregaron a la tabla `grupos`:

```sql
-- Plantilla y contenido
plantilla VARCHAR(255)              -- Archivo de plantilla personalizada
razon_defecto TEXT                  -- Texto predeterminado de razón
firma_nombre VARCHAR(255)           -- Nombre del firmante
firma_cargo VARCHAR(255)            -- Cargo del firmante
firma_imagen VARCHAR(255)           -- Archivo de imagen de firma

-- Estilos
fuente_nombre VARCHAR(100)          -- Fuente del texto
tamanio_fuente INT                  -- Tamaño de fuente
color_texto VARCHAR(7)              -- Color en formato hex

-- Posiciones (coordenadas en píxeles)
posicion_nombre_x INT               -- X del nombre
posicion_nombre_y INT               -- Y del nombre
posicion_qr_x INT                   -- X del QR
posicion_qr_y INT                   -- Y del QR
posicion_firma_x INT                -- X de la firma
posicion_firma_y INT                -- Y de la firma

-- Tamaños
tamanio_qr INT                      -- Tamaño del QR en px
tamanio_firma INT                   -- Ancho de la firma en px

-- Variables
variables_habilitadas JSON          -- Array de variables activas
```

### Archivos Generados
- **Plantillas**: `assets/templates/grupo_{id}_template.{ext}`
- **Firmas**: `assets/firmas/grupo_{id}_firma.{ext}`

## 🔄 Flujo de Generación de Certificados

1. Usuario genera un certificado para un grupo específico
2. Sistema carga configuración global de `configuracion_plantillas`
3. Sistema carga configuración del grupo y **sobrescribe** los valores globales
4. Se genera el certificado usando:
   - Plantilla del grupo (si existe) o plantilla global
   - Posiciones definidas para el grupo
   - Estilos del grupo
   - Solo las variables habilitadas
   - Firma del grupo (si existe)

## 💡 Consejos

### Para Mejores Resultados
1. **Plantillas**:
   - Usa imágenes de alta resolución (1600x1120px)
   - Formato PNG para mejor calidad
   - Deja espacios claros para nombre, QR y firma

2. **Firmas**:
   - Usa PNG con fondo transparente
   - Escanea la firma en alta resolución
   - Ajusta el tamaño para que sea visible pero no domine

3. **Posicionamiento**:
   - Usa el lienzo visual para posicionar elementos
   - Verifica que no se superpongan
   - Considera márgenes de seguridad

4. **Variables**:
   - Habilita solo las necesarias
   - Si no usas firma, desmárcala
   - Ajusta posiciones según variables habilitadas

## 🔧 Valores Predeterminados

Si no configuras un grupo, usará estos valores:

```javascript
{
  fuente_nombre: 'Arial',
  tamanio_fuente: 48,
  color_texto: '#000000',
  posicion_nombre_x: 400,
  posicion_nombre_y: 300,
  posicion_qr_x: 920,
  posicion_qr_y: 419,
  posicion_firma_x: 800,
  posicion_firma_y: 850,
  tamanio_qr: 200,
  tamanio_firma: 150,
  variables_habilitadas: ['nombre', 'razon', 'qr', 'firma', 'fecha']
}
```

## 📝 Notas Técnicas

- Las coordenadas se calculan desde la esquina superior izquierda (0,0)
- El lienzo se escala automáticamente al tamaño de la ventana
- Las posiciones se guardan en coordenadas reales (1600x1120)
- La conversión a coordenadas de pantalla se hace automáticamente
- Las variables habilitadas se guardan como JSON array

## 🐛 Solución de Problemas

### La plantilla no se carga
- Verifica que el archivo sea PNG o JPG
- Asegúrate de que el tamaño no sea excesivo (< 5MB)
- Revisa permisos de carpeta `assets/templates/`

### Los elementos no aparecen en el certificado
- Verifica que las variables estén habilitadas (☑️)
- Revisa que las coordenadas estén dentro del lienzo
- Asegúrate de haber guardado la configuración

### El drag & drop no funciona
- Recarga la página (F5)
- Verifica que la imagen de plantilla se haya cargado
- Usa Chrome o Firefox para mejor compatibilidad
