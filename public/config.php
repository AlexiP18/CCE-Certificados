<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';

// Obtener configuración actual
$stmt = $pdo->query("SELECT * FROM configuracion_plantillas WHERE activa = 1 LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    die("No hay plantilla activa configurada");
}

$templatePath = '../assets/templates/' . $config['archivo_plantilla'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Plantilla - CCE Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .config-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .config-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-top: 20px;
        }
        
        .preview-section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            position: relative;
        }
        
        .template-container {
            position: relative;
            display: inline-block;
            max-width: 100%;
            border: 2px solid #ddd;
            background: white;
        }
        
        .template-image {
            max-width: 100%;
            height: auto;
            display: block;
        }
        
        .marker {
            position: absolute;
            cursor: move;
            z-index: 10;
        }
        
        .marker-name {
            /* El marcador de nombre indica la esquina superior izquierda del texto */
            width: 20px;
            height: 20px;
            background: rgba(52, 152, 219, 0.9);
            border: 3px solid #2980b9;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .marker-name::before {
            content: '└';
            font-size: 16px;
            line-height: 1;
        }
        
        .marker-qr {
            /* El marcador de QR indica el centro del QR */
            transform: translate(-50%, -50%);
            width: 150px;
            height: 150px;
            background: rgba(46, 204, 113, 0.3);
            border: 3px solid #27ae60;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #27ae60;
            font-weight: bold;
        }
        
        .marker-label {
            position: absolute;
            top: -25px;
            left: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            white-space: nowrap;
        }
        
        .marker-qr .marker-label {
            left: 50%;
            transform: translateX(-50%);
        }
        
        .controls-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .coordinate-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            margin: 25px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .instructions {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .instructions strong {
            color: #2980b9;
        }
        
        @media (max-width: 1200px) {
            .config-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container config-container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> Configuración de Plantilla</h1>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>

        <div id="message" class="alert"></div>

        <div class="instructions">
            <strong><i class="fas fa-clipboard"></i> Instrucciones Rápidas:</strong>
            <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                <li><strong>Marcador Azul (└):</strong> Arrastra para posicionar la <u>esquina superior izquierda</u> del texto del nombre</li>
                <li><strong>Marcador Verde (QR):</strong> Arrastra para posicionar el <u>centro</u> del código QR (150x150 px)</li>
                <li><strong>Ajuste fino:</strong> Usa los campos numéricos de Posición X/Y para precisión exacta</li>
                <li><strong>Vista previa:</strong> Haz clic en "Ver Vista Previa" para generar un certificado de ejemplo</li>
                <li><strong>Guardar:</strong> Haz clic en "💾 Guardar Configuración" para aplicar los cambios</li>
            </ul>
            <div style="margin-top: 10px; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                <strong><i class="fas fa-lightbulb"></i> Importante:</strong> El marcador azul indica donde EMPIEZA el texto (alineado a la izquierda). El marcador verde indica el CENTRO del QR.
            </div>
        </div>

        <form id="configForm">
            <div class="config-grid">
                <!-- Vista previa -->
                <div class="preview-section">
                    <h3>Vista Previa de Plantilla</h3>
                    <div class="template-container" id="templateContainer">
                        <img src="<?php echo $templatePath; ?>" alt="Plantilla" class="template-image" id="templateImage">
                        
                        <!-- Marcador de Nombre -->
                        <div class="marker marker-name" id="markerNombre" 
                             style="left: <?php echo $config['posicion_nombre_x']; ?>px; 
                                    top: <?php echo $config['posicion_nombre_y']; ?>px;">
                            <span class="marker-label">NOMBRE</span>
                            N
                        </div>
                        
                        <!-- Marcador de QR -->
                        <div class="marker marker-qr" id="markerQR" 
                             style="left: <?php echo $config['posicion_qr_x']; ?>px; 
                                    top: <?php echo $config['posicion_qr_y']; ?>px;">
                            <span class="marker-label">CÓDIGO QR</span>
                            QR
                        </div>
                    </div>
                </div>

                <!-- Controles -->
                <div class="controls-section">
                    <h3>Configuración</h3>
                    
                    <input type="hidden" name="config_id" value="<?php echo $config['id']; ?>">
                    
                    <!-- Configuración del Nombre -->
                    <div class="section-title">🔤 Configuración del Nombre</div>
                    
                    <div class="form-group">
                        <label>Fuente</label>
                        <select name="fuente_nombre" id="fuente_nombre">
                            <option value="">Sistema (por defecto)</option>
                            <?php
                            $fontsDir = '../assets/fonts/';
                            if (is_dir($fontsDir)) {
                                $fonts = array_diff(scandir($fontsDir), ['.', '..']);
                                foreach ($fonts as $font) {
                                    $fontName = pathinfo($font, PATHINFO_FILENAME);
                                    $selected = ($config['fuente_nombre'] === $fontName) ? 'selected' : '';
                                    echo "<option value='$fontName' $selected>$fontName</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tamaño de Fuente (px)</label>
                        <input type="number" name="tamanio_fuente" id="tamanio_fuente" 
                               value="<?php echo $config['tamanio_fuente']; ?>" min="10" max="200">
                    </div>
                    
                    <div class="form-group">
                        <label>Color del Texto</label>
                        <input type="color" name="color_texto" id="color_texto" 
                               value="<?php echo $config['color_texto']; ?>">
                    </div>
                    
                    <div class="coordinate-group">
                        <div class="form-group">
                            <label>Posición X</label>
                            <input type="number" name="posicion_nombre_x" id="posicion_nombre_x" 
                                   value="<?php echo $config['posicion_nombre_x']; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Posición Y</label>
                            <input type="number" name="posicion_nombre_y" id="posicion_nombre_y" 
                                   value="<?php echo $config['posicion_nombre_y']; ?>" min="0">
                        </div>
                    </div>

                    <!-- Configuración del QR -->
                    <div class="section-title">📱 Configuración del Código QR</div>
                    
                    <div class="coordinate-group">
                        <div class="form-group">
                            <label>Posición X</label>
                            <input type="number" name="posicion_qr_x" id="posicion_qr_x" 
                                   value="<?php echo $config['posicion_qr_x']; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Posición Y</label>
                            <input type="number" name="posicion_qr_y" id="posicion_qr_y" 
                                   value="<?php echo $config['posicion_qr_y']; ?>" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Posición del QR en el Certificado</label>
                        <select name="posicion_qr" id="posicion_qr">
                            <option value="top-left" <?php echo ($config['posicion_qr'] === 'top-left') ? 'selected' : ''; ?>>Superior Izquierda</option>
                            <option value="top-right" <?php echo ($config['posicion_qr'] === 'top-right') ? 'selected' : ''; ?>>Superior Derecha</option>
                            <option value="bottom-left" <?php echo ($config['posicion_qr'] === 'bottom-left') ? 'selected' : ''; ?>>Inferior Izquierda</option>
                            <option value="bottom-right" <?php echo ($config['posicion_qr'] === 'bottom-right') ? 'selected' : ''; ?>>Inferior Derecha</option>
                            <option value="center" <?php echo ($config['posicion_qr'] === 'center') ? 'selected' : ''; ?>>Centro</option>
                        </select>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">💾 Guardar Configuración</button>
                        <button type="button" class="btn btn-secondary" onclick="location.reload()">🔄 Resetear</button>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee;">
                        <h4 style="margin-bottom: 10px;">🔍 Vista Previa del Certificado</h4>
                        <button type="button" class="btn btn-primary" onclick="generatePreview()" style="width: 100%;">
                            Ver Vista Previa
                        </button>
                        <div id="previewResult" style="margin-top: 15px;"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Elementos
        const markerNombre = document.getElementById('markerNombre');
        const markerQR = document.getElementById('markerQR');
        const templateContainer = document.getElementById('templateContainer');
        const templateImage = document.getElementById('templateImage');
        const form = document.getElementById('configForm');
        const message = document.getElementById('message');

        // Inputs
        const inputNombreX = document.getElementById('posicion_nombre_x');
        const inputNombreY = document.getElementById('posicion_nombre_y');
        const inputQRX = document.getElementById('posicion_qr_x');
        const inputQRY = document.getElementById('posicion_qr_y');

        // Esperar a que la imagen se cargue para obtener dimensiones reales
        let imageScale = { x: 1, y: 1 };
        templateImage.addEventListener('load', function() {
            calculateImageScale();
            positionMarkersFromInputs();
        });

        // Calcular escala si la imagen ya está cargada
        if (templateImage.complete) {
            calculateImageScale();
            positionMarkersFromInputs();
        }

        function calculateImageScale() {
            const displayedWidth = templateImage.clientWidth;
            const displayedHeight = templateImage.clientHeight;
            const realWidth = templateImage.naturalWidth;
            const realHeight = templateImage.naturalHeight;
            
            imageScale.x = realWidth / displayedWidth;
            imageScale.y = realHeight / displayedHeight;
            
            console.log('Image scale:', imageScale);
            console.log('Displayed:', displayedWidth, 'x', displayedHeight);
            console.log('Real:', realWidth, 'x', realHeight);
        }

        function positionMarkersFromInputs() {
            // Posicionar marcadores según valores de inputs (coordenadas reales)
            updateMarkerPosition(markerNombre, inputNombreX.value, inputNombreY.value);
            updateMarkerPosition(markerQR, inputQRX.value, inputQRY.value);
        }

        // Hacer los marcadores arrastrables
        makeDraggable(markerNombre, inputNombreX, inputNombreY);
        makeDraggable(markerQR, inputQRX, inputQRY);

        // Actualizar posición de marcadores cuando se cambian los inputs
        inputNombreX.addEventListener('input', () => updateMarkerPosition(markerNombre, inputNombreX.value, inputNombreY.value));
        inputNombreY.addEventListener('input', () => updateMarkerPosition(markerNombre, inputNombreX.value, inputNombreY.value));
        inputQRX.addEventListener('input', () => updateMarkerPosition(markerQR, inputQRX.value, inputQRY.value));
        inputQRY.addEventListener('input', () => updateMarkerPosition(markerQR, inputQRX.value, inputQRY.value));

        // Recalcular escala y reposicionar marcadores cuando se redimensiona la ventana
        window.addEventListener('resize', function() {
            calculateImageScale();
            positionMarkersFromInputs();
        });

        function makeDraggable(element, inputX, inputY) {
            let isDragging = false;
            let startX, startY, initialLeft, initialTop;

            element.addEventListener('mousedown', startDrag);
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', stopDrag);

            function startDrag(e) {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                
                const rect = element.getBoundingClientRect();
                const containerRect = templateContainer.getBoundingClientRect();
                
                // Para el marcador de nombre: esquina superior izquierda
                // Para el marcador de QR: centro (por eso suma rect.width / 2)
                const isQRMarker = element.classList.contains('marker-qr');
                
                if (isQRMarker) {
                    initialLeft = rect.left - containerRect.left + rect.width / 2;
                    initialTop = rect.top - containerRect.top + rect.height / 2;
                } else {
                    // Marcador de nombre - esquina superior izquierda
                    initialLeft = rect.left - containerRect.left;
                    initialTop = rect.top - containerRect.top;
                }
                
                element.style.cursor = 'grabbing';
            }

            function drag(e) {
                if (!isDragging) return;

                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;

                let newLeft = initialLeft + deltaX;
                let newTop = initialTop + deltaY;

                // Limitar a los bordes de la imagen (en píxeles de pantalla)
                const containerRect = templateContainer.getBoundingClientRect();
                newLeft = Math.max(0, Math.min(newLeft, templateImage.clientWidth));
                newTop = Math.max(0, Math.min(newTop, templateImage.clientHeight));

                element.style.left = newLeft + 'px';
                element.style.top = newTop + 'px';

                // Convertir a coordenadas reales de la imagen y actualizar inputs
                const realX = Math.round(newLeft * imageScale.x);
                const realY = Math.round(newTop * imageScale.y);
                
                inputX.value = realX;
                inputY.value = realY;
                
                console.log('Display position:', newLeft, newTop, '-> Real position:', realX, realY);
            }

            function stopDrag() {
                if (isDragging) {
                    isDragging = false;
                    element.style.cursor = 'move';
                }
            }
        }

        function updateMarkerPosition(marker, realX, realY) {
            // Convertir coordenadas reales a coordenadas de pantalla
            const displayX = realX / imageScale.x;
            const displayY = realY / imageScale.y;
            
            marker.style.left = displayX + 'px';
            marker.style.top = displayY + 'px';
            
            const markerType = marker.classList.contains('marker-qr') ? 'QR (centro)' : 'Nombre (esquina)';
            console.log(markerType, '- Real position:', realX, realY, '-> Display position:', displayX, displayY);
        }

        // Enviar formulario
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(form);

            try {
                const response = await fetch('save_config.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('✓ Configuración guardada correctamente', 'success');
                } else {
                    showMessage('✗ Error: ' + result.error, 'error');
                }
            } catch (error) {
                showMessage('✗ Error al guardar la configuración', 'error');
            }
        });

        function showMessage(text, type) {
            message.textContent = text;
            message.className = 'alert alert-' + type;
            message.style.display = 'block';

            setTimeout(() => {
                message.style.display = 'none';
            }, 5000);
        }

        // Generar vista previa
        async function generatePreview() {
            const previewResult = document.getElementById('previewResult');
            previewResult.innerHTML = '<p style="text-align: center; color: #666;">⏳ Generando vista previa...</p>';

            try {
                const response = await fetch('preview.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'nombre=NOMBRE DE EJEMPLO'
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error response:', errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                }

                const result = await response.json();
                console.log('Preview result:', result);

                if (result.success) {
                    // Agregar timestamp para evitar cache
                    const imageUrl = result.preview + '?t=' + Date.now();
                    previewResult.innerHTML = `
                        <div style="border: 2px solid #ddd; padding: 10px; background: white; border-radius: 4px; margin-top: 10px;">
                            <img src="${imageUrl}" alt="Vista Previa" style="width: 100%; height: auto; border-radius: 4px;" 
                                 onerror="this.parentElement.innerHTML='<p style=\\'color: red;\\'>Error al cargar la imagen: ${result.preview}</p>'">
                            <p style="text-align: center; margin-top: 10px; font-size: 12px; color: #666;">
                                ✓ Vista previa con la configuración actual
                            </p>
                        </div>
                    `;
                } else {
                    previewResult.innerHTML = `<p style="color: red;">✗ Error: ${result.error}</p>`;
                }
            } catch (error) {
                console.error('Preview error:', error);
                previewResult.innerHTML = `<p style="color: red;">✗ Error al generar vista previa: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>
