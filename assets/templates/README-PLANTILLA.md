# Ejemplo de Plantilla de Certificado

Para que el sistema funcione correctamente, necesitas crear una plantilla de certificado.

## 📐 Especificaciones Técnicas

### Dimensiones Recomendadas:
- **Formato:** PNG o JPG
- **Orientación:** Horizontal (Landscape)
- **Tamaño en píxeles (300 DPI):** 3508 x 2480 px
- **Tamaño mínimo (150 DPI):** 1754 x 1240 px
- **Proporción:** A4 (297mm x 210mm)

## 🎨 Diseño de la Plantilla

Tu plantilla debe incluir:

1. **Bordes decorativos** (opcional)
2. **Logo de la institución** (parte superior)
3. **Título del certificado**
4. **Espacio en blanco para el NOMBRE** (el sistema lo agregará dinámicamente)
5. **Texto de la certificación** (puede estar en la plantilla)
6. **Espacio para la fecha** (opcional, puede ser dinámico)
7. **Firmas** (si son siempre las mismas)
8. **Espacio para el código QR** (esquina inferior derecha recomendada)

## 📍 Coordenadas de Posición

Por defecto, el sistema usa estas coordenadas (se pueden ajustar en la BD):

### Posición del Nombre:
- **X:** 400 (horizontal, desde la izquierda)
- **Y:** 300 (vertical, desde arriba)
- **Alineación:** Centro
- **Tamaño de fuente:** 48px
- **Color:** #000000 (negro)

### Posición del QR:
- **Posición:** bottom-right (esquina inferior derecha)
- **Offset X:** 50px (margen desde el borde)
- **Offset Y:** 50px (margen desde el borde)
- **Tamaño QR:** 150 x 150 px

## 🖼️ Ejemplo de Diseño

```
┌────────────────────────────────────────────────────────┐
│                    [LOGO CCE]                          │
│                                                        │
│            CERTIFICADO DE PARTICIPACIÓN                │
│                                                        │
│                  Se otorga a:                          │
│                                                        │
│              [NOMBRE DINÁMICO AQUÍ]                    │
│                                                        │
│              Por su participación en...                │
│                                                        │
│              Fecha: [FECHA DINÁMICA]                   │
│                                                        │
│    _______________          _______________            │
│       Firma 1                   Firma 2              │
│                                          [QR CODE]    │
└────────────────────────────────────────────────────────┘
```

## 📥 Crear una Plantilla Rápida

### Opción 1: Con Photoshop/GIMP
1. Crea un nuevo documento: 3508 x 2480 px, 300 DPI
2. Diseña tu certificado dejando espacio para el nombre
3. Exporta como PNG con calidad máxima
4. Guarda como: `default_template.png`

### Opción 2: Con Canva (Online)
1. Ve a: https://www.canva.com
2. Busca plantillas de "Certificado"
3. Elige una horizontal (landscape)
4. Personaliza con tu logo y colores
5. Descarga como PNG de alta calidad

### Opción 3: Con PowerPoint
1. Configura diapositiva tamaño A4 horizontal
2. Diseña tu certificado
3. Exporta como imagen PNG (alta calidad)

## 🔧 Ajustar Posiciones

Si el nombre o QR no aparecen en el lugar correcto, actualiza la configuración:

### Via SQL (phpMyAdmin):

```sql
UPDATE configuracion_plantillas 
SET 
    posicion_nombre_x = 400,    -- Ajustar posición horizontal del nombre
    posicion_nombre_y = 300,    -- Ajustar posición vertical del nombre
    tamanio_fuente = 48,        -- Tamaño de letra
    color_texto = '#000000',    -- Color (hexadecimal)
    posicion_qr = 'bottom-right',  -- Esquina del QR
    posicion_qr_x = 50,         -- Margen horizontal del QR
    posicion_qr_y = 50          -- Margen vertical del QR
WHERE id = 1;
```

### Guía de posiciones QR:
- `top-left` - Esquina superior izquierda
- `top-right` - Esquina superior derecha
- `bottom-left` - Esquina inferior izquierda
- `bottom-right` - Esquina inferior derecha

## 🎨 Usar Fuentes Personalizadas

1. Descarga una fuente .TTF o .OTF (ej: desde Google Fonts)
2. Sube el archivo a: `assets/fonts/MiFuente.ttf`
3. Actualiza en la base de datos:

```sql
UPDATE configuracion_plantillas 
SET fuente_nombre = 'MiFuente'
WHERE id = 1;
```

## ✅ Verificar tu Plantilla

Una vez que subas tu plantilla:

1. Colócala en: `assets/templates/default_template.png`
2. Genera un certificado de prueba
3. Verifica:
   - ¿El nombre aparece en el lugar correcto?
   - ¿El tamaño de fuente es apropiado?
   - ¿El QR está en la esquina correcta?
   - ¿El texto es legible?

## 📝 Plantilla de Ejemplo Temporal

Si no tienes una plantilla lista, puedes crear una básica:

1. Crea una imagen blanca de 1754 x 1240 px
2. Agrega texto básico con cualquier editor
3. Guarda como `default_template.png`
4. Usa esto temporalmente mientras diseñas la final

---

**Recursos útiles:**
- Google Fonts: https://fonts.google.com/
- Canva: https://www.canva.com/
- Freepik (plantillas): https://www.freepik.com/free-photos-vectors/certificate

**Nota:** La plantilla es el elemento más importante del sistema. Dedica tiempo a diseñarla bien.
