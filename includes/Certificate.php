<?php
/**
 * Clase para generar certificados con QR
 */

namespace CCE;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Intervention\Image\ImageManager;
use TCPDF;

class Certificate {
    private $pdo;
    private $config;
    private $uploadPath;
    private $imageManager;
    private $grupoInfo = null;
    private $categoriaInfo = null;
    private $grupoId = null;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->uploadPath = dirname(__DIR__) . '/uploads/';
        
        // Crear instancia de ImageManager con driver GD
        $this->imageManager = new ImageManager(['driver' => 'gd']);
        
        $this->loadConfig();
    }
    
    /**
     * Cargar configuración de plantilla activa (global)
     * Si no hay plantilla global, se inicializan valores por defecto
     * La plantilla puede venir después del grupo o categoría
     */
    private function loadConfig() {
        $stmt = $this->pdo->query("SELECT * FROM configuracion_plantillas WHERE activa = 1 LIMIT 1");
        $this->config = $stmt->fetch();
        
        // Si no hay plantilla global, inicializar con valores por defecto
        // La plantilla puede venir del grupo o categoría después
        if (!$this->config) {
            $this->config = [
                'archivo_plantilla' => null,
                'posicion_nombre_x' => 540,
                'posicion_nombre_y' => 380,
                'posicion_razon_x' => 540,
                'posicion_razon_y' => 450,
                'posicion_fecha_x' => 540,
                'posicion_fecha_y' => 520,
                'posicion_qr_x' => 900,
                'posicion_qr_y' => 40,
                'posicion_firma_x' => 540,
                'posicion_firma_y' => 580,
                'fuente_nombre' => 'times',
                'fuente_razon' => 'times',
                'fuente_fecha' => 'times',
                'tamanio_fuente' => 36,
                'tamanio_razon' => 24,
                'tamanio_fecha' => 18,
                'tamanio_qr' => 100,
                'tamanio_firma' => 80,
                'color_texto' => '#000000',
                'color_razon' => '#333333',
                'color_fecha' => '#666666',
                'razon_defecto' => '',
                'formato_fecha' => 'd/m/Y',
                'variables_habilitadas' => 'nombre,razon,fecha,qr',
                'ancho_razon' => 600,
                'lineas_razon' => 0, // 0 = auto, >0 = número fijo de líneas del canvas
                'alineacion_razon' => 'justified', // left, center, right, justified
                // Configuración de destacado
                'destacado_habilitado' => 0,
                'destacado_tipo' => 'icono',
                'destacado_icono' => 'estrella',
                'destacado_imagen' => null,
                'destacado_posicion_x' => 50,
                'destacado_posicion_y' => 50,
                'destacado_tamanio' => 100
            ];
        }
    }
    
    /**
     * Cargar configuración específica del grupo (sobrescribe la configuración global)
     */
    private function loadGrupoConfig($grupoId) {
        error_log("=== INICIANDO loadGrupoConfig para grupo $grupoId ===");
        $stmt = $this->pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
        $stmt->execute([$grupoId]);
        $grupo = $stmt->fetch();
        
        if ($grupo) {
            // Guardar info del grupo para variables de razón
            $this->grupoInfo = $grupo;
            
            // Guardar el ID del grupo para buscar plantilla activa
            $this->grupoId = $grupoId;
            
            // Debug: registrar valores del grupo
            error_log("=== Configuración del Grupo $grupoId ===");
            error_log("tamanio_fuente: " . ($grupo['tamanio_fuente'] ?? 'NULL'));
            error_log("tamanio_qr: " . ($grupo['tamanio_qr'] ?? 'NULL'));
            error_log("variables_habilitadas: " . ($grupo['variables_habilitadas'] ?? 'NULL'));
            
            // Buscar plantilla activa en el slider de plantillas CON su configuración
            $stmtPlantilla = $this->pdo->prepare("
                SELECT archivo, posicion_nombre_x, posicion_nombre_y, posicion_razon_x, posicion_razon_y,
                       posicion_fecha_x, posicion_fecha_y, posicion_qr_x, posicion_qr_y,
                       posicion_firma_x, posicion_firma_y, fuente_nombre, fuente_razon, fuente_fecha,
                       tamanio_fuente, tamanio_razon, tamanio_fecha, tamanio_qr, tamanio_firma,
                       color_texto, color_razon, color_fecha, razon_defecto, formato_fecha,
                       variables_habilitadas, ancho_razon, lineas_razon, alineacion_razon,
                       destacado_habilitado, destacado_tipo, destacado_icono, destacado_imagen,
                       destacado_posicion_x, destacado_posicion_y, destacado_tamanio
                FROM grupo_plantillas WHERE grupo_id = ? AND es_activa = 1 LIMIT 1
            ");
            $stmtPlantilla->execute([$grupoId]);
            $plantillaActiva = $stmtPlantilla->fetch();
            
            error_log("Buscando plantilla activa para grupo $grupoId: " . ($plantillaActiva ? 'ENCONTRADA' : 'NO ENCONTRADA'));
            if ($plantillaActiva) {
                error_log("plantillaActiva['ancho_razon'] = " . ($plantillaActiva['ancho_razon'] ?? 'NOT SET'));
            }
            
            if ($plantillaActiva) {
                // Usar plantilla del slider (ruta relativa desde uploads/grupos)
                $this->config['archivo_plantilla'] = 'grupos/' . $grupoId . '/' . $plantillaActiva['archivo'];
                $this->config['plantilla_desde_uploads'] = true;
                error_log("Usando plantilla del slider: " . $this->config['archivo_plantilla']);
                
                // Cargar configuración específica de la plantilla (Opción 2)
                if (isset($plantillaActiva['posicion_nombre_x']) && $plantillaActiva['posicion_nombre_x'] !== null) {
                    $this->config['posicion_nombre_x'] = (int)$plantillaActiva['posicion_nombre_x'];
                }
                if (isset($plantillaActiva['posicion_nombre_y']) && $plantillaActiva['posicion_nombre_y'] !== null) {
                    $this->config['posicion_nombre_y'] = (int)$plantillaActiva['posicion_nombre_y'];
                }
                if (isset($plantillaActiva['posicion_razon_x']) && $plantillaActiva['posicion_razon_x'] !== null) {
                    $this->config['posicion_razon_x'] = (int)$plantillaActiva['posicion_razon_x'];
                }
                if (isset($plantillaActiva['posicion_razon_y']) && $plantillaActiva['posicion_razon_y'] !== null) {
                    $this->config['posicion_razon_y'] = (int)$plantillaActiva['posicion_razon_y'];
                }
                if (isset($plantillaActiva['posicion_fecha_x']) && $plantillaActiva['posicion_fecha_x'] !== null) {
                    $this->config['posicion_fecha_x'] = (int)$plantillaActiva['posicion_fecha_x'];
                }
                if (isset($plantillaActiva['posicion_fecha_y']) && $plantillaActiva['posicion_fecha_y'] !== null) {
                    $this->config['posicion_fecha_y'] = (int)$plantillaActiva['posicion_fecha_y'];
                }
                if (isset($plantillaActiva['posicion_qr_x']) && $plantillaActiva['posicion_qr_x'] !== null) {
                    $this->config['posicion_qr_x'] = (int)$plantillaActiva['posicion_qr_x'];
                }
                if (isset($plantillaActiva['posicion_qr_y']) && $plantillaActiva['posicion_qr_y'] !== null) {
                    $this->config['posicion_qr_y'] = (int)$plantillaActiva['posicion_qr_y'];
                }
                if (isset($plantillaActiva['posicion_firma_x']) && $plantillaActiva['posicion_firma_x'] !== null) {
                    $this->config['posicion_firma_x'] = (int)$plantillaActiva['posicion_firma_x'];
                }
                if (isset($plantillaActiva['posicion_firma_y']) && $plantillaActiva['posicion_firma_y'] !== null) {
                    $this->config['posicion_firma_y'] = (int)$plantillaActiva['posicion_firma_y'];
                }
                if (isset($plantillaActiva['fuente_nombre']) && $plantillaActiva['fuente_nombre'] !== '' && $plantillaActiva['fuente_nombre'] !== null) {
                    $this->config['fuente_nombre'] = $plantillaActiva['fuente_nombre'];
                }
                if (isset($plantillaActiva['fuente_razon']) && $plantillaActiva['fuente_razon'] !== '' && $plantillaActiva['fuente_razon'] !== null) {
                    $this->config['fuente_razon'] = $plantillaActiva['fuente_razon'];
                }
                if (isset($plantillaActiva['fuente_fecha']) && $plantillaActiva['fuente_fecha'] !== '' && $plantillaActiva['fuente_fecha'] !== null) {
                    $this->config['fuente_fecha'] = $plantillaActiva['fuente_fecha'];
                }
                if (isset($plantillaActiva['tamanio_fuente']) && $plantillaActiva['tamanio_fuente'] !== null) {
                    $this->config['tamanio_fuente'] = (int)$plantillaActiva['tamanio_fuente'];
                }
                if (isset($plantillaActiva['tamanio_razon']) && $plantillaActiva['tamanio_razon'] !== null) {
                    $this->config['tamanio_razon'] = (int)$plantillaActiva['tamanio_razon'];
                }
                if (isset($plantillaActiva['tamanio_fecha']) && $plantillaActiva['tamanio_fecha'] !== null) {
                    $this->config['tamanio_fecha'] = (int)$plantillaActiva['tamanio_fecha'];
                }
                if (isset($plantillaActiva['tamanio_qr']) && $plantillaActiva['tamanio_qr'] !== null) {
                    $this->config['tamanio_qr'] = (int)$plantillaActiva['tamanio_qr'];
                }
                if (isset($plantillaActiva['tamanio_firma']) && $plantillaActiva['tamanio_firma'] !== null) {
                    $this->config['tamanio_firma'] = (int)$plantillaActiva['tamanio_firma'];
                }
                if (isset($plantillaActiva['color_texto']) && $plantillaActiva['color_texto'] !== '' && $plantillaActiva['color_texto'] !== null) {
                    $this->config['color_texto'] = $plantillaActiva['color_texto'];
                }
                if (isset($plantillaActiva['color_razon']) && $plantillaActiva['color_razon'] !== '' && $plantillaActiva['color_razon'] !== null) {
                    $this->config['color_razon'] = $plantillaActiva['color_razon'];
                }
                if (isset($plantillaActiva['color_fecha']) && $plantillaActiva['color_fecha'] !== '' && $plantillaActiva['color_fecha'] !== null) {
                    $this->config['color_fecha'] = $plantillaActiva['color_fecha'];
                }
                if (isset($plantillaActiva['razon_defecto']) && $plantillaActiva['razon_defecto'] !== '' && $plantillaActiva['razon_defecto'] !== null) {
                    $this->config['razon_defecto'] = $plantillaActiva['razon_defecto'];
                }
                if (isset($plantillaActiva['formato_fecha']) && $plantillaActiva['formato_fecha'] !== '' && $plantillaActiva['formato_fecha'] !== null) {
                    $this->config['formato_fecha'] = $plantillaActiva['formato_fecha'];
                }
                if (isset($plantillaActiva['variables_habilitadas']) && $plantillaActiva['variables_habilitadas'] !== '' && $plantillaActiva['variables_habilitadas'] !== null) {
                    $this->config['variables_habilitadas'] = $plantillaActiva['variables_habilitadas'];
                }
                if (isset($plantillaActiva['ancho_razon']) && $plantillaActiva['ancho_razon'] !== null) {
                    $this->config['ancho_razon'] = (int)$plantillaActiva['ancho_razon'];
                    error_log("=== ANCHO_RAZON cargado desde plantilla: " . $this->config['ancho_razon'] . " ===");
                } else {
                    error_log("=== ANCHO_RAZON NO encontrado en plantilla, usando default ===");
                }
                if (isset($plantillaActiva['lineas_razon']) && $plantillaActiva['lineas_razon'] !== null) {
                    $this->config['lineas_razon'] = (int)$plantillaActiva['lineas_razon'];
                    error_log("=== LINEAS_RAZON cargado desde plantilla: " . $this->config['lineas_razon'] . " ===");
                }
                if (isset($plantillaActiva['alineacion_razon']) && $plantillaActiva['alineacion_razon'] !== null) {
                    $this->config['alineacion_razon'] = $plantillaActiva['alineacion_razon'];
                    error_log("=== ALINEACION_RAZON cargado desde plantilla: " . $this->config['alineacion_razon'] . " ===");
                }
                
                // Cargar configuración de destacado
                if (isset($plantillaActiva['destacado_habilitado'])) {
                    $this->config['destacado_habilitado'] = (int)$plantillaActiva['destacado_habilitado'];
                }
                if (isset($plantillaActiva['destacado_tipo']) && $plantillaActiva['destacado_tipo'] !== null) {
                    $this->config['destacado_tipo'] = $plantillaActiva['destacado_tipo'];
                }
                if (isset($plantillaActiva['destacado_icono']) && $plantillaActiva['destacado_icono'] !== null) {
                    $this->config['destacado_icono'] = $plantillaActiva['destacado_icono'];
                }
                if (isset($plantillaActiva['destacado_imagen']) && $plantillaActiva['destacado_imagen'] !== null) {
                    $this->config['destacado_imagen'] = $plantillaActiva['destacado_imagen'];
                }
                if (isset($plantillaActiva['destacado_posicion_x']) && $plantillaActiva['destacado_posicion_x'] !== null) {
                    $this->config['destacado_posicion_x'] = (int)$plantillaActiva['destacado_posicion_x'];
                }
                if (isset($plantillaActiva['destacado_posicion_y']) && $plantillaActiva['destacado_posicion_y'] !== null) {
                    $this->config['destacado_posicion_y'] = (int)$plantillaActiva['destacado_posicion_y'];
                }
                if (isset($plantillaActiva['destacado_tamanio']) && $plantillaActiva['destacado_tamanio'] !== null) {
                    $this->config['destacado_tamanio'] = (int)$plantillaActiva['destacado_tamanio'];
                }
                
                error_log("=== Configuración cargada desde grupo_plantillas ===");
            } elseif (isset($grupo['plantilla']) && $grupo['plantilla'] !== '') {
                // Fallback a plantilla antigua
                $this->config['archivo_plantilla'] = $grupo['plantilla'];
                $this->config['plantilla_desde_uploads'] = false;
            }
            if (isset($grupo['fuente_nombre']) && $grupo['fuente_nombre'] !== '') {
                $this->config['fuente_nombre'] = $grupo['fuente_nombre'];
            }
            if (isset($grupo['tamanio_fuente']) && $grupo['tamanio_fuente'] !== null) {
                $this->config['tamanio_fuente'] = (int)$grupo['tamanio_fuente'];
            }
            if (isset($grupo['color_texto']) && $grupo['color_texto'] !== '') {
                $this->config['color_texto'] = $grupo['color_texto'];
            }
            if (isset($grupo['posicion_nombre_x']) && $grupo['posicion_nombre_x'] !== null) {
                $this->config['posicion_nombre_x'] = (int)$grupo['posicion_nombre_x'];
            }
            if (isset($grupo['posicion_nombre_y']) && $grupo['posicion_nombre_y'] !== null) {
                $this->config['posicion_nombre_y'] = (int)$grupo['posicion_nombre_y'];
            }
            if (isset($grupo['posicion_qr_x']) && $grupo['posicion_qr_x'] !== null) {
                $this->config['posicion_qr_x'] = (int)$grupo['posicion_qr_x'];
            }
            if (isset($grupo['posicion_qr_y']) && $grupo['posicion_qr_y'] !== null) {
                $this->config['posicion_qr_y'] = (int)$grupo['posicion_qr_y'];
            }
            if (isset($grupo['posicion_firma_x']) && $grupo['posicion_firma_x'] !== null) {
                $this->config['posicion_firma_x'] = (int)$grupo['posicion_firma_x'];
            }
            if (isset($grupo['posicion_firma_y']) && $grupo['posicion_firma_y'] !== null) {
                $this->config['posicion_firma_y'] = (int)$grupo['posicion_firma_y'];
            }
            if (isset($grupo['tamanio_qr']) && $grupo['tamanio_qr'] !== null) {
                $this->config['tamanio_qr'] = (int)$grupo['tamanio_qr'];
            }
            if (isset($grupo['tamanio_firma']) && $grupo['tamanio_firma'] !== null) {
                $this->config['tamanio_firma'] = (int)$grupo['tamanio_firma'];
            }
            if (isset($grupo['variables_habilitadas']) && $grupo['variables_habilitadas'] !== '') {
                $this->config['variables_habilitadas'] = $grupo['variables_habilitadas'];
            }
            if (isset($grupo['firma_nombre']) && $grupo['firma_nombre'] !== '') {
                $this->config['firma_nombre'] = $grupo['firma_nombre'];
            }
            if (isset($grupo['firma_cargo']) && $grupo['firma_cargo'] !== '') {
                $this->config['firma_cargo'] = $grupo['firma_cargo'];
            }
            if (isset($grupo['firma_imagen']) && $grupo['firma_imagen'] !== '') {
                $this->config['firma_imagen'] = $grupo['firma_imagen'];
            }
        }
    }
    
    /**
     * Cargar configuración específica de la categoría (tiene prioridad sobre el grupo)
     */
    private function loadCategoriaConfig($categoriaId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, g.plantilla as grupo_plantilla, g.firma_imagen as grupo_firma_imagen
            FROM categorias c
            INNER JOIN grupos g ON c.grupo_id = g.id
            WHERE c.id = ? AND c.activo = 1
        ");
        $stmt->execute([$categoriaId]);
        $categoria = $stmt->fetch();
        
        if ($categoria) {
            // Guardar info de la categoría para variables de razón
            $this->categoriaInfo = $categoria;
        }
        
        if ($categoria && $categoria['usar_plantilla_propia'] == 1) {
            error_log("=== Configuración de la Categoría $categoriaId (Plantilla Propia) ===");
            error_log("plantilla_archivo: " . ($categoria['plantilla_archivo'] ?? 'NULL'));
            error_log("plantilla_tamanio_fuente: " . ($categoria['plantilla_tamanio_fuente'] ?? 'NULL'));
            error_log("plantilla_tamanio_qr: " . ($categoria['plantilla_tamanio_qr'] ?? 'NULL'));
            error_log("plantilla_variables_habilitadas: " . ($categoria['plantilla_variables_habilitadas'] ?? 'NULL'));
            
            // Verificar si la categoría tiene una plantilla propia VÁLIDA (que exista físicamente)
            $tieneTemplateValida = false;
            if (isset($categoria['plantilla_archivo']) && $categoria['plantilla_archivo'] !== '' && $categoria['plantilla_archivo'] !== null) {
                $categoriaTemplatePath = dirname(__DIR__) . '/uploads/categorias/' . $categoria['plantilla_archivo'];
                if (file_exists($categoriaTemplatePath)) {
                    $this->config['archivo_plantilla'] = 'categorias/' . $categoria['plantilla_archivo'];
                    $this->config['plantilla_desde_uploads'] = true;
                    $tieneTemplateValida = true;
                    error_log("Usando plantilla de categoría: categorias/" . $categoria['plantilla_archivo']);
                } else {
                    error_log("Plantilla de categoría no encontrada: $categoriaTemplatePath, usando configuración del grupo");
                }
            } else {
                error_log("Categoría sin plantilla propia, usando configuración del grupo");
            }
            
            // Solo aplicar la configuración de la categoría si tiene plantilla propia válida
            // Si no tiene plantilla válida, mantener TODA la configuración del grupo
            if (!$tieneTemplateValida) {
                // Solo cargar la razón por defecto de la categoría si existe (útil para personalizar mensaje por categoría)
                if (isset($categoria['plantilla_razon_defecto']) && $categoria['plantilla_razon_defecto'] !== '') {
                    $this->config['razon_defecto'] = $categoria['plantilla_razon_defecto'];
                    error_log("Usando razón por defecto de categoría: " . $categoria['plantilla_razon_defecto']);
                }
                // Para firma: si la categoría tiene una propia, usarla
                if (isset($categoria['plantilla_archivo_firma']) && $categoria['plantilla_archivo_firma'] !== '' && $categoria['plantilla_archivo_firma'] !== null) {
                    $this->config['firma_imagen'] = $categoria['plantilla_archivo_firma'];
                }
                return; // No aplicar más configuración de la categoría
            }
            
            // A partir de aquí, la categoría SÍ tiene plantilla válida - aplicar su configuración
            
            if (isset($categoria['plantilla_fuente']) && $categoria['plantilla_fuente'] !== '') {
                $this->config['fuente_nombre'] = $categoria['plantilla_fuente'];
            }
            if (isset($categoria['plantilla_tamanio_fuente']) && $categoria['plantilla_tamanio_fuente'] !== null) {
                $this->config['tamanio_fuente'] = (int)$categoria['plantilla_tamanio_fuente'];
            }
            if (isset($categoria['plantilla_color_texto']) && $categoria['plantilla_color_texto'] !== '') {
                $this->config['color_texto'] = $categoria['plantilla_color_texto'];
            }
            if (isset($categoria['plantilla_pos_nombre_x']) && $categoria['plantilla_pos_nombre_x'] !== null) {
                $this->config['posicion_nombre_x'] = (int)$categoria['plantilla_pos_nombre_x'];
            }
            if (isset($categoria['plantilla_pos_nombre_y']) && $categoria['plantilla_pos_nombre_y'] !== null) {
                $this->config['posicion_nombre_y'] = (int)$categoria['plantilla_pos_nombre_y'];
            }
            if (isset($categoria['plantilla_pos_qr_x']) && $categoria['plantilla_pos_qr_x'] !== null) {
                $this->config['posicion_qr_x'] = (int)$categoria['plantilla_pos_qr_x'];
            }
            if (isset($categoria['plantilla_pos_qr_y']) && $categoria['plantilla_pos_qr_y'] !== null) {
                $this->config['posicion_qr_y'] = (int)$categoria['plantilla_pos_qr_y'];
            }
            if (isset($categoria['plantilla_pos_firma_x']) && $categoria['plantilla_pos_firma_x'] !== null) {
                $this->config['posicion_firma_x'] = (int)$categoria['plantilla_pos_firma_x'];
            }
            if (isset($categoria['plantilla_pos_firma_y']) && $categoria['plantilla_pos_firma_y'] !== null) {
                $this->config['posicion_firma_y'] = (int)$categoria['plantilla_pos_firma_y'];
            }
            if (isset($categoria['plantilla_tamanio_qr']) && $categoria['plantilla_tamanio_qr'] !== null) {
                $this->config['tamanio_qr'] = (int)$categoria['plantilla_tamanio_qr'];
            }
            if (isset($categoria['plantilla_tamanio_firma']) && $categoria['plantilla_tamanio_firma'] !== null) {
                $this->config['tamanio_firma'] = (int)$categoria['plantilla_tamanio_firma'];
            }
            if (isset($categoria['plantilla_variables_habilitadas']) && $categoria['plantilla_variables_habilitadas'] !== '' && $categoria['plantilla_variables_habilitadas'] !== null) {
                $this->config['variables_habilitadas'] = $categoria['plantilla_variables_habilitadas'];
            }
            if (isset($categoria['plantilla_firma_nombre']) && $categoria['plantilla_firma_nombre'] !== '') {
                $this->config['firma_nombre'] = $categoria['plantilla_firma_nombre'];
            }
            if (isset($categoria['plantilla_firma_cargo']) && $categoria['plantilla_firma_cargo'] !== '') {
                $this->config['firma_cargo'] = $categoria['plantilla_firma_cargo'];
            }
            // Para firma: si la categoría no tiene una propia, usar la del grupo
            if (isset($categoria['plantilla_archivo_firma']) && $categoria['plantilla_archivo_firma'] !== '' && $categoria['plantilla_archivo_firma'] !== null) {
                $this->config['firma_imagen'] = $categoria['plantilla_archivo_firma'];
            } elseif (isset($categoria['grupo_firma_imagen']) && $categoria['grupo_firma_imagen'] !== '') {
                error_log("Categoría sin firma propia, usando firma del grupo: " . $categoria['grupo_firma_imagen']);
                $this->config['firma_imagen'] = $categoria['grupo_firma_imagen'];
            }
            
            // Posiciones de razón y fecha
            if (isset($categoria['plantilla_pos_razon_x']) && $categoria['plantilla_pos_razon_x'] !== null) {
                $this->config['posicion_razon_x'] = (int)$categoria['plantilla_pos_razon_x'];
            }
            if (isset($categoria['plantilla_pos_razon_y']) && $categoria['plantilla_pos_razon_y'] !== null) {
                $this->config['posicion_razon_y'] = (int)$categoria['plantilla_pos_razon_y'];
            }
            if (isset($categoria['plantilla_pos_fecha_x']) && $categoria['plantilla_pos_fecha_x'] !== null) {
                $this->config['posicion_fecha_x'] = (int)$categoria['plantilla_pos_fecha_x'];
            }
            if (isset($categoria['plantilla_pos_fecha_y']) && $categoria['plantilla_pos_fecha_y'] !== null) {
                $this->config['posicion_fecha_y'] = (int)$categoria['plantilla_pos_fecha_y'];
            }
            
            // Configuración de Razón
            if (isset($categoria['plantilla_razon_defecto']) && $categoria['plantilla_razon_defecto'] !== '') {
                $this->config['razon_defecto'] = $categoria['plantilla_razon_defecto'];
            }
            if (isset($categoria['plantilla_tamanio_razon']) && $categoria['plantilla_tamanio_razon'] !== null) {
                $this->config['tamanio_razon'] = (int)$categoria['plantilla_tamanio_razon'];
            }
            if (isset($categoria['plantilla_color_razon']) && $categoria['plantilla_color_razon'] !== '') {
                $this->config['color_razon'] = $categoria['plantilla_color_razon'];
            }
            
            // Configuración de Fecha
            if (isset($categoria['plantilla_formato_fecha']) && $categoria['plantilla_formato_fecha'] !== '') {
                $this->config['formato_fecha'] = $categoria['plantilla_formato_fecha'];
            }
            if (isset($categoria['plantilla_fecha_especifica']) && $categoria['plantilla_fecha_especifica'] !== null) {
                $this->config['fecha_especifica'] = $categoria['plantilla_fecha_especifica'];
            }
            if (isset($categoria['plantilla_tamanio_fecha']) && $categoria['plantilla_tamanio_fecha'] !== null) {
                $this->config['tamanio_fecha'] = (int)$categoria['plantilla_tamanio_fecha'];
            }
            if (isset($categoria['plantilla_color_fecha']) && $categoria['plantilla_color_fecha'] !== '') {
                $this->config['color_fecha'] = $categoria['plantilla_color_fecha'];
            }
        } else {
            error_log("=== Categoría $categoriaId NO usa plantilla propia, heredando del grupo ===");
        }
    }
    
    /**
     * Generar código único para el certificado
     */
    public function generateCode() {
        return 'CCE-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    }
    
    /**
     * Crear certificado completo
     */
    public function create($data) {
        try {
            // Orden de carga de configuración: Global -> Grupo -> Categoría
            // Si hay grupo_id, cargar configuración del grupo
            if (!empty($data['grupo_id'])) {
                error_log("=== CREAR CERTIFICADO - Grupo ID: " . $data['grupo_id'] . " ===");
                $this->loadGrupoConfig($data['grupo_id']);
            } else {
                error_log("=== CREAR CERTIFICADO - SIN GRUPO (usando config global) ===");
            }
            
            // Si hay categoria_id, cargar configuración de la categoría (sobrescribe la del grupo)
            if (!empty($data['categoria_id'])) {
                error_log("=== CREAR CERTIFICADO - Categoría ID: " . $data['categoria_id'] . " ===");
                $this->loadCategoriaConfig($data['categoria_id']);
            }
            
            // Verificar que hay una plantilla configurada (de grupo, categoría o global)
            if (empty($this->config['archivo_plantilla'])) {
                throw new \Exception("No hay plantilla configurada. Configure una plantilla en el grupo o categoría.");
            }
            
            // Si la razón está vacía, intentar usar razón por defecto
            if (empty($data['razon'])) {
                if (!empty($this->config['razon_defecto'])) {
                    // Usar razón por defecto configurada
                    $data['razon'] = $this->config['razon_defecto'];
                    error_log("Usando razón por defecto de configuración: " . $data['razon']);
                } else {
                    // Verificar si razon está habilitada en las variables
                    $variablesHabilitadas = [];
                    if (!empty($this->config['variables_habilitadas'])) {
                        $varsStr = $this->config['variables_habilitadas'];
                        if (is_string($varsStr)) {
                            $decoded = json_decode($varsStr, true);
                            if (is_array($decoded)) {
                                $variablesHabilitadas = $decoded;
                            }
                        } elseif (is_array($varsStr)) {
                            $variablesHabilitadas = $varsStr;
                        }
                    }
                    
                    // Si razon está habilitada pero no hay razon_defecto, crear una razón genérica
                    if (in_array('razon', $variablesHabilitadas)) {
                        $razonGenerica = "Por su participación";
                        if (!empty($this->categoriaInfo['nombre'])) {
                            $razonGenerica .= " en " . $this->categoriaInfo['nombre'];
                        }
                        if (!empty($this->grupoInfo['nombre'])) {
                            $razonGenerica .= " - " . $this->grupoInfo['nombre'];
                        }
                        $data['razon'] = $razonGenerica;
                        error_log("Usando razón genérica: " . $data['razon']);
                    }
                }
            }
            
            // Reemplazar variables en la razón antes de validar
            if (!empty($data['razon'])) {
                $data['razon'] = $this->replaceRazonVariables($data['razon'], $data);
            }
            
            // Validar datos
            $this->validateData($data);
            
            // Generar código único
            $codigo = $data['codigo'] ?? $this->generateCode();
            
            // Verificar que no exista
            $stmt = $this->pdo->prepare("SELECT id FROM certificados WHERE codigo = ?");
            $stmt->execute([$codigo]);
            if ($stmt->fetch()) {
                throw new \Exception("El código ya existe");
            }
            
            // Generar imagen del certificado
            $imagePath = $this->generateImage($data, $codigo);
            
            // Generar PDF
            $pdfPath = $this->generatePDF($imagePath, $codigo);
            
            // Inicializar historial de fechas de generación
            $fechasGeneracion = json_encode([date('Y-m-d H:i:s')]);
            
            // Guardar en base de datos (permitir estado personalizado para flujos de aprobación)
            $estadoCert = $data['estado'] ?? 'activo';
            $stmt = $this->pdo->prepare("
                INSERT INTO certificados (codigo, nombre, razon, fecha, archivo_imagen, archivo_pdf, grupo_id, categoria_id, fechas_generacion, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $codigo,
                $data['nombre'],
                $data['razon'],
                $data['fecha'],
                basename($imagePath),
                basename($pdfPath),
                $data['grupo_id'] ?? null,
                $data['categoria_id'] ?? null,
                $fechasGeneracion,
                $estadoCert
            ]);
            
            $certificado_id = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'certificado_id' => $certificado_id,
                'codigo' => $codigo,
                'nombre' => $data['nombre'],
                'imagen' => basename($imagePath),
                'pdf' => basename($pdfPath),
                'archivo_pdf' => 'uploads/' . basename($pdfPath),
                'archivo_imagen' => 'uploads/' . basename($imagePath),
                'url_verificacion' => 'verify.php?code=' . $codigo,
                'debug_config' => [
                    'grupo_id' => $data['grupo_id'] ?? 'null',
                    'categoria_id' => $data['categoria_id'] ?? 'null',
                    'archivo_plantilla' => $this->config['archivo_plantilla'] ?? 'null',
                    'tamanio_fuente' => $this->config['tamanio_fuente'] ?? 'null',
                    'tamanio_qr' => $this->config['tamanio_qr'] ?? 'null',
                    'posicion_qr_x' => $this->config['posicion_qr_x'] ?? 'null',
                    'posicion_qr_y' => $this->config['posicion_qr_y'] ?? 'null',
                    'posicion_firma_x' => $this->config['posicion_firma_x'] ?? 'null',
                    'posicion_firma_y' => $this->config['posicion_firma_y'] ?? 'null',
                    'firma_imagen' => $this->config['firma_imagen'] ?? 'null',
                    'variables_habilitadas' => $this->config['variables_habilitadas'] ?? 'null'
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar datos de entrada
     */
    private function validateData($data) {
        if (empty($data['nombre'])) {
            throw new \Exception("El nombre es requerido");
        }
        
        // Verificar si la razón está habilitada en las variables
        $variablesHabilitadas = [];
        if (!empty($this->config['variables_habilitadas'])) {
            $varsStr = $this->config['variables_habilitadas'];
            if (is_string($varsStr)) {
                $decoded = json_decode($varsStr, true);
                if (is_array($decoded)) {
                    $variablesHabilitadas = $decoded;
                }
            } elseif (is_array($varsStr)) {
                $variablesHabilitadas = $varsStr;
            }
        }
        
        // Solo validar razón si está habilitada como variable
        $razonHabilitada = empty($variablesHabilitadas) || in_array('razon', $variablesHabilitadas);
        if ($razonHabilitada && empty($data['razon'])) {
            throw new \Exception("La razón es requerida");
        }
        
        if (empty($data['fecha'])) {
            throw new \Exception("La fecha es requerida");
        }
    }
    
    /**
     * Reemplazar variables en el texto de la razón
     * Variables disponibles: {grupo}, {categoria}, {nombre}, {fecha}
     */
    private function replaceRazonVariables($razon, $data) {
        // Obtener fecha formateada usando el mismo formato que la variable de fecha del lienzo
        $fechaEspecifica = $this->config['fecha_especifica'] ?? null;
        $fechaEmision = !empty($fechaEspecifica) ? $fechaEspecifica : ($data['fecha_emision'] ?? $data['fecha'] ?? date('Y-m-d'));
        $formatoFecha = $this->config['formato_fecha'] ?? 'd de F de Y';
        $fechaFormateada = $this->formatearFecha($fechaEmision, $formatoFecha);
        
        $variables = [
            '{grupo}' => $this->grupoInfo['nombre'] ?? '',
            '{categoria}' => $this->categoriaInfo['nombre'] ?? '',
            '{nombre}' => $data['nombre'] ?? '',
            '{fecha}' => $fechaFormateada,
        ];
        
        return str_replace(array_keys($variables), array_values($variables), $razon);
    }
    
    /**
     * Generar imagen del certificado
     */
    private function generateImage($data, $codigo) {
        // Determinar ruta de la plantilla
        if (isset($this->config['plantilla_desde_uploads']) && $this->config['plantilla_desde_uploads']) {
            // Plantilla del slider (desde uploads)
            $templatePath = dirname(__DIR__) . '/uploads/' . $this->config['archivo_plantilla'];
        } else {
            // Plantilla tradicional (desde assets/templates)
            $templatePath = dirname(__DIR__) . '/assets/templates/' . $this->config['archivo_plantilla'];
        }
        
        // Verificar que existe la plantilla
        if (!file_exists($templatePath)) {
            throw new \Exception("Plantilla no encontrada: " . $this->config['archivo_plantilla']);
        }
        
        // Verificar que es una imagen válida
        $mimeType = mime_content_type($templatePath);
        $validMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
        if (!in_array($mimeType, $validMimes)) {
            throw new \Exception("El archivo de plantilla '$templatePath' no es una imagen válida. Tipo detectado: $mimeType");
        }
        
        // Cargar plantilla
        try {
            $img = $this->imageManager->make($templatePath);
        } catch (\Exception $e) {
            throw new \Exception("Error al cargar la plantilla: " . $e->getMessage());
        }
        
        // Obtener dimensiones reales de la plantilla
        $realWidth = $img->width();
        $realHeight = $img->height();
        
        error_log("=== Dimensiones de Plantilla ===");
        error_log("Dimensiones reales: {$realWidth}x{$realHeight}");
        
        // Las coordenadas guardadas en la base de datos ya corresponden a las posiciones reales
        // en la imagen, por lo que NO se aplica escalado. El editor guarda las posiciones
        // basándose en las dimensiones reales de la imagen cargada.
        $scaleX = 1.0;
        $scaleY = 1.0;
        
        // Verificar variables habilitadas
        $variablesHabilitadas = ['nombre', 'razon', 'qr', 'firma', 'fecha']; // Por defecto, todas habilitadas
        $rawVariables = $this->config['variables_habilitadas'] ?? null;
        
        error_log("=== Variables Habilitadas DEBUG ===");
        error_log("Raw variables_habilitadas: " . var_export($rawVariables, true));
        
        if (!empty($rawVariables) && $rawVariables !== 'null') {
            $decoded = json_decode($rawVariables, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $variablesHabilitadas = $decoded;
                error_log("Variables decodificadas exitosamente: " . json_encode($variablesHabilitadas));
            } else {
                error_log("json_decode falló o retornó array vacío, usando valores por defecto");
            }
        } else {
            error_log("variables_habilitadas vacío o null, usando valores por defecto");
        }
        
        // Debug: registrar configuración final
        error_log("=== Configuración Final para Generación ===");
        error_log("archivo_plantilla: " . ($this->config['archivo_plantilla'] ?? 'NULL'));
        error_log("tamanio_fuente: " . ($this->config['tamanio_fuente'] ?? 'NULL'));
        error_log("tamanio_qr: " . ($this->config['tamanio_qr'] ?? 'NULL'));
        error_log("posicion_qr_x: " . ($this->config['posicion_qr_x'] ?? 'NULL'));
        error_log("posicion_qr_y: " . ($this->config['posicion_qr_y'] ?? 'NULL'));
        error_log("posicion_firma_x: " . ($this->config['posicion_firma_x'] ?? 'NULL'));
        error_log("posicion_firma_y: " . ($this->config['posicion_firma_y'] ?? 'NULL'));
        error_log("firma_imagen: " . ($this->config['firma_imagen'] ?? 'NULL'));
        error_log("variables_habilitadas FINAL: " . json_encode($variablesHabilitadas));
        
        // Agregar nombre si está habilitado
        if (in_array('nombre', $variablesHabilitadas)) {
            $fontPath = $this->getFontPath($this->config['fuente_nombre']);
            
            // Debug: Verificar fuente
            if ($fontPath) {
                $fontMime = @mime_content_type($fontPath);
                $validFontMimes = [
                    'application/octet-stream', 
                    'application/x-font-ttf', 
                    'font/ttf', 
                    'font/otf', 
                    'application/x-font-otf',
                    'font/sfnt', // TrueType/OpenType
                    'application/x-font-truetype',
                    'application/font-sfnt'
                ];
                if ($fontMime && !in_array($fontMime, $validFontMimes)) {
                    error_log("Advertencia: archivo de fuente con tipo MIME inesperado: $fontMime en $fontPath");
                    $fontPath = null; // No usar esta fuente
                }
            }
            
            // Escalar tamaño de fuente y posición
            $tamanioFuente = (int)(($this->config['tamanio_fuente'] ?? 48) * $scaleY);
            $colorTexto = $this->config['color_texto'] ?? '#000000';
            $nombreX = (int)((int)$this->config['posicion_nombre_x'] * $scaleX);
            $nombreY = (int)((int)$this->config['posicion_nombre_y'] * $scaleY);
            
            // Aplicar formato de nombre según configuración
            $formatoNombre = $this->config['formato_nombre'] ?? 'mayusculas';
            $nombreFormateado = trim($data['nombre']);
            
            switch ($formatoNombre) {
                case 'mayusculas':
                    $nombreFormateado = mb_strtoupper($nombreFormateado, 'UTF-8');
                    break;
                case 'capitalizado':
                    $nombreFormateado = mb_convert_case($nombreFormateado, MB_CASE_TITLE, 'UTF-8');
                    break;
                case 'minusculas':
                    $nombreFormateado = mb_strtolower($nombreFormateado, 'UTF-8');
                    break;
                default:
                    $nombreFormateado = mb_strtoupper($nombreFormateado, 'UTF-8');
            }
            
            error_log("Aplicando nombre - Formato: $formatoNombre, Tamaño fuente escalado: $tamanioFuente, Posición escalada: X=$nombreX, Y=$nombreY");
            
            $img->text($nombreFormateado, 
                $nombreX, 
                $nombreY, 
                function($font) use ($fontPath, $tamanioFuente, $colorTexto) {
                    // Solo usar fuente personalizada si existe el archivo
                    if ($fontPath && file_exists($fontPath)) {
                        try {
                            $font->file($fontPath);
                            error_log("Fuente personalizada cargada: $fontPath");
                        } catch (\Exception $e) {
                            // Si falla cargar la fuente, usar la por defecto
                            error_log("No se pudo cargar fuente personalizada: " . $e->getMessage());
                        }
                    } else {
                        error_log("Usando fuente por defecto de GD (no se encontró fuente personalizada)");
                    }
                    // Aplicar tamaño y color
                    $font->size($tamanioFuente);
                    $font->color($colorTexto);
                    $font->align('left');  // Alineación desde el inicio (izquierda)
                    $font->valign('top');   // Alineación desde arriba
                }
            );
        }
        
        // Generar código QR si está habilitado
        if (in_array('qr', $variablesHabilitadas)) {
            $qrPath = $this->generateQR($codigo);
            
            // Verificar que el QR es una imagen válida
            if (!file_exists($qrPath)) {
                throw new \Exception("Archivo QR no fue generado correctamente");
            }
            $qrMime = mime_content_type($qrPath);
            if ($qrMime !== 'image/png') {
                throw new \Exception("El QR generado no es un PNG válido. Tipo: $qrMime, Ruta: $qrPath");
            }
            
            $qrImg = $this->imageManager->make($qrPath);
            
            // Redimensionar QR según configuración (escalado)
            $tamanioQr = (int)(($this->config['tamanio_qr'] ?? 200) * $scaleX);
            error_log("Redimensionando QR a: {$tamanioQr}x{$tamanioQr} (escalado)");
            $qrImg->resize($tamanioQr, $tamanioQr);
            
            // Insertar QR en certificado usando coordenadas escaladas
            // Las coordenadas son el centro del QR, ajustamos para que sea la esquina superior izquierda
            $qrX = (int)(((int)$this->config['posicion_qr_x'] * $scaleX) - ($tamanioQr / 2));
            $qrY = (int)(((int)$this->config['posicion_qr_y'] * $scaleY) - ($tamanioQr / 2));
            
            error_log("Insertando QR en posición escalada: X=$qrX, Y=$qrY");
            $img->insert($qrImg, 'top-left', $qrX, $qrY);
            
            // Limpiar QR temporal
            @unlink($qrPath);
        }
        
        // Insertar imagen de firma si está habilitada y existe
        if (in_array('firma', $variablesHabilitadas) && !empty($this->config['firma_imagen'])) {
            // Buscar firma primero en assets/templates/ (categorías) y luego en assets/firmas/ (grupos)
            $firmaFileName = $this->config['firma_imagen'];
            $firmaPath = dirname(__DIR__) . '/assets/templates/' . $firmaFileName;
            
            if (!file_exists($firmaPath)) {
                $firmaPath = dirname(__DIR__) . '/assets/firmas/' . $firmaFileName;
            }
            
            if (file_exists($firmaPath)) {
                error_log("Insertando imagen de firma desde: $firmaPath");
                
                $firmaImg = $this->imageManager->make($firmaPath);
                
                // Redimensionar firma según configuración escalada (solo ancho, alto proporcional)
                $tamanioFirma = (int)(($this->config['tamanio_firma'] ?? 150) * $scaleX);
                error_log("Redimensionando firma a ancho: $tamanioFirma (escalado, alto proporcional)");
                $firmaImg->resize($tamanioFirma, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                
                // Insertar firma en certificado usando coordenadas escaladas
                // Las coordenadas son el centro de la firma, ajustamos para esquina superior izquierda
                $firmaX = (int)(((int)$this->config['posicion_firma_x'] * $scaleX) - ($firmaImg->width() / 2));
                $firmaY = (int)(((int)$this->config['posicion_firma_y'] * $scaleY) - ($firmaImg->height() / 2));
                
                error_log("Insertando firma en posición escalada: X=$firmaX, Y=$firmaY");
                $img->insert($firmaImg, 'top-left', $firmaX, $firmaY);
            } else {
                error_log("Advertencia: Archivo de firma no encontrado en ninguna ruta: $firmaFileName");
            }
        }
        
        // Insertar razón si está habilitada
        if (in_array('razon', $variablesHabilitadas) && !empty($data['razon'])) {
            error_log("=== RAZÓN DEBUG ===");
            error_log("Texto: " . substr($data['razon'], 0, 50) . "...");
            
            // Usar fuente específica de razón si está configurada
            $fontPath = $this->getFontPath($this->config['fuente_razon'] ?? $this->config['fuente_nombre'] ?? '');
            
            // Los valores configurados son para una imagen de 1920x1080 (tamaño de referencia)
            // Necesitamos escalarlos al tamaño real de la imagen actual
            $tamanioRazonConfig = (int)($this->config['tamanio_razon'] ?? 24);
            $tamanioRazon = (int)($tamanioRazonConfig * $scaleY);
            $colorRazon = $this->config['color_razon'] ?? '#333333';
            
            // Posición de razón - las coordenadas configuradas son relativas a 1920x1080
            $razonXConfig = (int)($this->config['posicion_razon_x'] ?? 400);
            $razonYConfig = (int)($this->config['posicion_razon_y'] ?? 360);
            $razonX = (int)($razonXConfig * $scaleX);
            $razonY = (int)($razonYConfig * $scaleY);
            
            // Ancho máximo para wordwrap (escalado)
            $anchoRazonConfig = (int)($this->config['ancho_razon'] ?? 600);
            $anchoRazon = (int)($anchoRazonConfig * $scaleX);
            
            // Número de líneas configurado desde el canvas (0 = auto)
            $lineasRazonConfig = (int)($this->config['lineas_razon'] ?? 0);
            // Alineación del texto de razón
            $alineacionRazon = $this->config['alineacion_razon'] ?? 'justified';
            
            error_log("Config: posX=$razonXConfig, posY=$razonYConfig, tamaño=$tamanioRazonConfig, ancho=$anchoRazonConfig, lineas=$lineasRazonConfig, alineacion=$alineacionRazon");
            error_log("Escala: scaleX=$scaleX, scaleY=$scaleY");
            error_log("Escalado: posX=$razonX, posY=$razonY, tamaño=$tamanioRazon, ancho=$anchoRazon");
            error_log("Fuente: $fontPath");
            
            // Dividir el texto en líneas según el ancho configurado
            // Si lineas_razon > 0, ajustar el ancho para forzar ese número de líneas
            $lineas = $this->wordwrapText($data['razon'], $fontPath, $tamanioRazon, $anchoRazon, $lineasRazonConfig);
            
            error_log("Líneas generadas: " . count($lineas) . " (esperadas: " . ($lineasRazonConfig > 0 ? $lineasRazonConfig : 'auto') . ")");
            foreach ($lineas as $i => $linea) {
                error_log("  Línea $i: " . $linea);
            }
            
            // Calcular altura de línea (aproximadamente 1.3 del tamaño de fuente)
            $lineHeight = (int)($tamanioRazon * 1.3);
            
            // Dibujar texto con alineación configurada
            $this->drawJustifiedText(
                $img, 
                $lineas, 
                $razonX, 
                $razonY, 
                $anchoRazon, 
                $fontPath, 
                $tamanioRazon, 
                $colorRazon, 
                $lineHeight,
                true, // última línea según alineación base si es justificado
                $alineacionRazon
            );
            
            error_log("=== FIN RAZÓN DEBUG ===");
        }
        
        // Insertar fecha si está habilitada
        if (in_array('fecha', $variablesHabilitadas)) {
            // Usar fecha específica si está configurada, sino usar la fecha de emisión
            $fechaEspecifica = $this->config['fecha_especifica'] ?? null;
            $fechaEmision = !empty($fechaEspecifica) ? $fechaEspecifica : ($data['fecha_emision'] ?? date('Y-m-d'));
            
            // Usar formato de fecha configurado
            $formatoFecha = $this->config['formato_fecha'] ?? 'd de F de Y';
            $fecha = $this->formatearFecha($fechaEmision, $formatoFecha);
            error_log("Insertando fecha: $fecha (formato: $formatoFecha)");
            
            $fontPathFecha = $this->getFontPath($this->config['fuente_fecha'] ?? '');
            // Usar tamaño y color específicos de fecha si están configurados
            $tamanioFecha = (int)(($this->config['tamanio_fecha'] ?? 20) * $scaleY);
            $colorFecha = $this->config['color_fecha'] ?? '#333333';
            
            // Posición de fecha desde configuración (escalada)
            $fechaX = (int)(($this->config['posicion_fecha_x'] ?? 400) * $scaleX);
            $fechaY = (int)(($this->config['posicion_fecha_y'] ?? 420) * $scaleY);
            
            error_log("Insertando fecha en posición escalada: X=$fechaX, Y=$fechaY, tamaño=$tamanioFecha, color=$colorFecha");
            
            $img->text($fecha, $fechaX, $fechaY, function($font) use ($fontPathFecha, $tamanioFecha, $colorFecha) {
                if ($fontPathFecha && file_exists($fontPathFecha)) {
                    $font->file($fontPathFecha);
                }
                $font->size($tamanioFecha);
                $font->color($colorFecha);
                $font->align('left');
                $font->valign('top');
            });
        }
        
        // Agregar sticker de destacado si está habilitado y el estudiante es destacado
        if (in_array('destacado', $variablesHabilitadas) || 
            (isset($this->config['destacado_habilitado']) && $this->config['destacado_habilitado'])) {
            
            // Verificar si el estudiante está marcado como destacado
            $esDestacado = isset($data['es_destacado']) && $data['es_destacado'];
            
            if ($esDestacado) {
                $this->agregarStickerDestacado($img, $scaleX, $scaleY);
            }
        }
        
        // Guardar imagen
        $filename = 'cert_' . $codigo . '.png';
        $outputPath = $this->uploadPath . $filename;
        $img->save($outputPath, 90);
        
        return $outputPath;
    }
    
    /**
     * Agregar sticker de destacado al certificado
     */
    private function agregarStickerDestacado($img, $scaleX, $scaleY) {
        $tipo = $this->config['destacado_tipo'] ?? 'icono';
        $tamanio = (int)(($this->config['destacado_tamanio'] ?? 100) * $scaleX);
        $posX = (int)(($this->config['destacado_posicion_x'] ?? 50) * $scaleX);
        $posY = (int)(($this->config['destacado_posicion_y'] ?? 50) * $scaleY);
        
        error_log("=== agregarStickerDestacado ===");
        error_log("Tipo: $tipo");
        error_log("Tamaño: $tamanio (original: " . ($this->config['destacado_tamanio'] ?? 100) . ")");
        error_log("PosX: $posX, PosY: $posY");
        error_log("Icono config: " . ($this->config['destacado_icono'] ?? 'no definido'));
        error_log("Imagen config: " . ($this->config['destacado_imagen'] ?? 'no definida'));
        
        // Determinar ruta del sticker
        if ($tipo === 'imagen' && !empty($this->config['destacado_imagen'])) {
            // Imagen personalizada
            $stickerPath = dirname(__DIR__) . '/uploads/stickers/' . $this->config['destacado_imagen'];
            error_log("Usando imagen personalizada: $stickerPath");
        } else {
            // Icono predeterminado
            $icono = $this->config['destacado_icono'] ?? 'estrella';
            $stickerPath = dirname(__DIR__) . '/assets/stickers/' . $icono . '.png';
            error_log("Usando icono predeterminado: $stickerPath");
        }
        
        if (!file_exists($stickerPath)) {
            error_log("Sticker de destacado no encontrado: $stickerPath");
            return;
        }
        
        try {
            $sticker = $this->imageManager->make($stickerPath);
            
            // Redimensionar manteniendo proporción
            $sticker->resize($tamanio, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            // Calcular posición (centrada en el punto configurado)
            $stickerWidth = $sticker->width();
            $stickerHeight = $sticker->height();
            $insertX = $posX - ($stickerWidth / 2);
            $insertY = $posY - ($stickerHeight / 2);
            
            // Insertar sticker
            $img->insert($sticker, 'top-left', (int)$insertX, (int)$insertY);
            
            error_log("Sticker de destacado agregado en X=$posX, Y=$posY, tamaño=$tamanio");
        } catch (\Exception $e) {
            error_log("Error al agregar sticker de destacado: " . $e->getMessage());
        }
    }
    
    /**
     * Generar código QR
     */
    private function generateQR($codigo) {
        $url = BASE_URL . '/verify.php?code=' . $codigo;
        
        $options = new QROptions([
            'version'    => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => QRCode::ECC_L,
            'scale'      => 10,
            'imageBase64' => false, // Asegurar que devuelva datos binarios, no base64
        ]);
        
        $qrcode = new QRCode($options);
        $qrPath = $this->uploadPath . 'qr_temp_' . $codigo . '.png';
        
        // Obtener los datos del QR
        $qrData = $qrcode->render($url);
        
        // Si el resultado es base64, decodificarlo
        if (strpos($qrData, 'data:image') === 0) {
            // Es una cadena data URI, extraer los datos
            $qrData = substr($qrData, strpos($qrData, ',') + 1);
            $qrData = base64_decode($qrData);
        }
        
        file_put_contents($qrPath, $qrData);
        
        // Verificar que se generó correctamente
        if (!file_exists($qrPath) || filesize($qrPath) == 0) {
            throw new \Exception("Error al generar archivo QR");
        }
        
        return $qrPath;
    }
    
    /**
     * Dividir texto en líneas según ancho máximo
     * Usa GD para calcular el ancho real del texto con la fuente especificada
     * Nota: Se aplica un factor de tolerancia para compensar diferencias
     * entre las métricas de fuentes del navegador y PHP/GD
     * @param string $text Texto a dividir
     * @param string $fontPath Ruta a la fuente TTF
     * @param int $fontSize Tamaño de fuente
     * @param int $maxWidth Ancho máximo en píxeles
     * @param int $lineasEsperadas Número de líneas esperadas desde el canvas (0=auto)
     */
    private function wordwrapText($text, $fontPath, $fontSize, $maxWidth, $lineasEsperadas = 0) {
        $words = explode(' ', $text);
        $totalWords = count($words);
        
        // Si se especifica número de líneas y hay suficientes palabras, distribuir equilibradamente
        if ($lineasEsperadas > 0 && $totalWords >= $lineasEsperadas) {
            error_log("Certificate WordWrap: Modo líneas fijas - lineasEsperadas=$lineasEsperadas, totalWords=$totalWords");
            
            // Distribuir palabras equilibradamente entre las líneas
            $wordsPerLine = ceil($totalWords / $lineasEsperadas);
            $lines = [];
            
            for ($i = 0; $i < $lineasEsperadas; $i++) {
                $start = $i * $wordsPerLine;
                $lineWords = array_slice($words, $start, $wordsPerLine);
                if (!empty($lineWords)) {
                    $lines[] = implode(' ', $lineWords);
                }
            }
            
            error_log("Certificate WordWrap: Distribución equilibrada - " . count($lines) . " líneas generadas");
            
            return $lines;
        }
        
        // Modo estándar: usar ancho máximo para dividir
        $lines = [];
        $currentLine = '';
        
        // Factor de tolerancia estándar
        $toleranceFactor = 0.92;
        $effectiveMaxWidth = $maxWidth * $toleranceFactor;
        
        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            
            // Calcular ancho del texto
            $textWidth = $this->getTextWidth($testLine, $fontPath, $fontSize);
            
            if ($textWidth > $effectiveMaxWidth && $currentLine !== '') {
                // La línea actual supera el ancho máximo, guardar línea anterior
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }
        
        // Agregar la última línea
        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }
        
        return $lines;
    }
    
    /**
     * Dibujar texto con alineación configurable usando TCPDF + ImageMagick
     * TCPDF genera PDF con la alineación especificada, ImageMagick lo convierte a imagen
     * @param string $alignment - Alineación: 'left', 'center', 'right', 'justified'
     */
    private function drawJustifiedText($img, $lines, $x, $y, $maxWidth, $fontPath, $fontSize, $color, $lineHeight, $isLastLineLeft = true, $alignment = 'justified') {
        // Unir las líneas en un solo texto
        $text = implode(' ', $lines);
        $text = trim($text);
        
        if (empty($text)) {
            return $y;
        }
        
        $numLines = count($lines);
        
        // Contar palabras promedio por línea para decidir si justificar
        $totalWords = count(explode(' ', $text));
        $avgWordsPerLine = $numLines > 0 ? $totalWords / $numLines : $totalWords;
        
        // Determinar alineación TCPDF según el parámetro
        // TCPDF usa: L=left, C=center, R=right, J=justified
        $tcpdfAlignment = 'L'; // Por defecto izquierda
        switch ($alignment) {
            case 'left':
                $tcpdfAlignment = 'L';
                break;
            case 'center':
                $tcpdfAlignment = 'C';
                break;
            case 'right':
                $tcpdfAlignment = 'R';
                break;
            case 'justified':
                // Solo justificar si hay suficientes palabras por línea
                $minWordsForJustify = 4;
                $tcpdfAlignment = ($avgWordsPerLine >= $minWordsForJustify) ? 'J' : 'L';
                break;
        }
        
        $shouldJustify = ($tcpdfAlignment === 'J');
        
        error_log("drawJustifiedText: alignment=$alignment, tcpdfAlignment=$tcpdfAlignment, avgWordsPerLine=$avgWordsPerLine, shouldJustify=" . ($shouldJustify ? 'true' : 'false'));
        
        // Crear PDF temporal con TCPDF
        $pdf = new \TCPDF('L', 'px', array($maxWidth + 10, $numLines * $lineHeight + 50), true, 'UTF-8', false);
        $pdf->SetCreator('CCE');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        
        // Configurar fuente
        $fontName = 'helvetica';
        if ($fontPath && file_exists($fontPath)) {
            try {
                $fontName = \TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32);
                if (!$fontName) {
                    $fontName = 'helvetica';
                }
            } catch (\Exception $e) {
                $fontName = 'helvetica';
            }
        }
        
        // Convertir color hex a RGB
        $r = $g = $b = 0;
        if (preg_match('/^#?([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})$/i', $color, $matches)) {
            $r = hexdec($matches[1]);
            $g = hexdec($matches[2]);
            $b = hexdec($matches[3]);
        }
        
        $pdf->SetFont($fontName, '', $fontSize * 0.75);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetXY(0, 0);
        
        // La alineación principal ya está determinada en $tcpdfAlignment
        $mainAlign = $tcpdfAlignment;
        
        // Si usamos justificado y hay más de una línea, la última línea va a la izquierda
        // Para otras alineaciones (left, center, right), todas las líneas usan la misma alineación
        if ($isLastLineLeft && $numLines > 1 && $shouldJustify) {
            // Separar: líneas justificadas + última línea alineada a la izquierda
            $linesJustified = array_slice($lines, 0, -1);
            $lastLine = end($lines);
            
            // Texto justificado (todas las líneas excepto la última)
            $textJustified = implode(' ', $linesJustified);
            if (!empty($textJustified)) {
                $pdf->MultiCell($maxWidth, $lineHeight * 0.75, $textJustified, 0, 'J', false, 1, 0, 0, true, 0, false, true, 0, 'T', false);
            }
            
            // Última línea alineada a la izquierda
            if (!empty($lastLine)) {
                $pdf->MultiCell($maxWidth, $lineHeight * 0.75, $lastLine, 0, 'L', false, 1, 0, $pdf->GetY(), true, 0, false, true, 0, 'T', false);
            }
        } else {
            // MultiCell con la alineación configurada (L, C, R, o J si hay suficientes palabras)
            $pdf->MultiCell($maxWidth, $lineHeight * 0.75, $text, 0, $mainAlign, false, 1, 0, 0, true, 0, false, true, 0, 'T', false);
        }
        
        // Crear imagen del PDF
        $tempPdfPath = sys_get_temp_dir() . '/justify_' . uniqid() . '.pdf';
        $tempImgPath = sys_get_temp_dir() . '/justify_img_' . uniqid() . '.png';
        $pdf->Output($tempPdfPath, 'F');
        
        $converted = false;
        $imgHeight = $numLines * $lineHeight;
        
        // Determinar si debemos hacer trim completo o solo vertical
        // Para center y right, NO hacer trim horizontal para preservar la alineación
        $shouldTrimHorizontal = ($alignment === 'left' || $alignment === 'justified');
        
        // Intentar con extensión Imagick primero
        if (class_exists('Imagick')) {
            try {
                $imagick = new \Imagick();
                // Usar 96 DPI para conversión pt->px (96 DPI es el estándar de pantalla)
                $imagick->setResolution(96, 96);
                $imagick->readImage($tempPdfPath . '[0]');
                $imagick->setImageFormat('png');
                $imagick->setImageBackgroundColor(new \ImagickPixel('transparent'));
                $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
                
                if ($shouldTrimHorizontal) {
                    // Trim completo para left y justified
                    $imagick->trimImage(0);
                    $imagick->setImagePage(0, 0, 0, 0);
                    
                    // Escalar imagen para que el ancho coincida exactamente con maxWidth en píxeles
                    $currentWidth = $imagick->getImageWidth();
                    if ($currentWidth > 0 && abs($currentWidth - $maxWidth) > 2) {
                        $scale = $maxWidth / $currentWidth;
                        $newHeight = (int)($imagick->getImageHeight() * $scale);
                        $imagick->resizeImage((int)$maxWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);
                    }
                } else {
                    // Para center y right: NO hacer trim para preservar la alineación de TCPDF
                    // TCPDF ya genera el texto centrado/derecha dentro del área maxWidth
                    // Solo necesitamos escalar al ancho deseado sin recortar
                    
                    $currentWidth = $imagick->getImageWidth();
                    $currentHeight = $imagick->getImageHeight();
                    
                    // Escalar al ancho deseado manteniendo proporciones
                    if ($currentWidth > 0 && abs($currentWidth - $maxWidth) > 2) {
                        $scale = $maxWidth / $currentWidth;
                        $newHeight = (int)($currentHeight * $scale);
                        $imagick->resizeImage((int)$maxWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);
                    }
                }
                
                $imagick->writeImage($tempImgPath);
                $imgHeight = $imagick->getImageHeight();
                $imagick->destroy();
                $converted = true;
            } catch (\Exception $e) {
                error_log('Error Imagick extension: ' . $e->getMessage());
            }
        }
        
        // Fallback: usar ImageMagick desde línea de comandos
        if (!$converted) {
            // Para center/right, no usar -trim para preservar alineación
            $trimOption = $shouldTrimHorizontal ? '-trim' : '';
            
            // Intentar con 'magick' (ImageMagick 7+) - usar 96 DPI para conversión pt->px
            $cmd = sprintf(
                'magick -density 96 "%s[0]" %s -background transparent -alpha set "%s" 2>&1',
                $tempPdfPath,
                $trimOption,
                $tempImgPath
            );
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0) {
                // Intentar con 'convert' (ImageMagick 6)
                $cmd = sprintf(
                    'convert -density 96 "%s[0]" %s -background transparent -alpha set "%s" 2>&1',
                    $tempPdfPath,
                    $trimOption,
                    $tempImgPath
                );
                exec($cmd, $output, $returnCode);
            }
            
            if ($returnCode === 0 && file_exists($tempImgPath)) {
                $converted = true;
                // Obtener dimensiones y escalar si es necesario
                $imgInfo = @getimagesize($tempImgPath);
                if ($imgInfo) {
                    $currentWidth = $imgInfo[0];
                    $imgHeight = $imgInfo[1];
                    
                    // Escalar si el ancho no coincide
                    if ($currentWidth > 0 && abs($currentWidth - $maxWidth) > 2) {
                        $scale = $maxWidth / $currentWidth;
                        $newHeight = (int)($imgHeight * $scale);
                        $tempImgPath2 = sys_get_temp_dir() . '/justify_img2_' . uniqid() . '.png';
                        $cmd = sprintf(
                            'magick "%s" -resize %dx%d "%s" 2>&1',
                            $tempImgPath, (int)$maxWidth, $newHeight, $tempImgPath2
                        );
                        exec($cmd, $output2, $returnCode2);
                        if ($returnCode2 === 0 && file_exists($tempImgPath2)) {
                            @unlink($tempImgPath);
                            $tempImgPath = $tempImgPath2;
                            $imgHeight = $newHeight;
                        }
                    }
                }
            } else {
                error_log('ImageMagick CLI error: ' . implode("\n", $output));
            }
        }
        
        // Insertar imagen en el certificado
        if ($converted && file_exists($tempImgPath) && filesize($tempImgPath) > 0) {
            $textImg = $this->imageManager->make($tempImgPath);
            $img->insert($textImg, 'top-left', (int)$x, (int)$y);
            @unlink($tempImgPath);
            @unlink($tempPdfPath);
            return $y + $imgHeight;
        }
        
        @unlink($tempImgPath);
        @unlink($tempPdfPath);
        
        // Fallback final: dibujar línea por línea
        $currentY = $y;
        foreach ($lines as $line) {
            $img->text($line, $x, $currentY, function($font) use ($fontPath, $fontSize, $color) {
                if ($fontPath && file_exists($fontPath)) {
                    $font->file($fontPath);
                }
                $font->size($fontSize);
                $font->color($color);
                $font->align('left');
                $font->valign('top');
            });
            $currentY += $lineHeight;
        }
        
        return $currentY;
    }

    /**
     * Calcular el ancho de un texto con una fuente y tamaño específicos
     */
    private function getTextWidth($text, $fontPath, $fontSize) {
        if ($fontPath && file_exists($fontPath)) {
            // Usar imagettfbbox para calcular dimensiones precisas
            $bbox = @imagettfbbox($fontSize, 0, $fontPath, $text);
            if ($bbox !== false) {
                return abs($bbox[4] - $bbox[0]);
            }
        }
        
        // Fallback: estimación aproximada basada en caracteres
        // Asumimos que cada carácter tiene aproximadamente 0.6 del tamaño de fuente
        return strlen($text) * $fontSize * 0.6;
    }
    
    /**
     * Formatear fecha según el formato especificado
     * Soporta tokens especiales como F para nombre del mes en español
     * Escapa automáticamente palabras literales como "de"
     */
    private function formatearFecha($fecha, $formato) {
        $timestamp = strtotime($fecha);
        
        if ($timestamp === false) {
            return $fecha; // Si no se puede parsear, devolver como está
        }
        
        // Escapar palabras literales comunes antes de procesar
        // "de" debe convertirse a "\d\e" para que PHP no lo interprete
        $formatoEscapado = $formato;
        $formatoEscapado = str_replace(' de ', ' \d\e ', $formatoEscapado);
        $formatoEscapado = str_replace(' del ', ' \d\e\l ', $formatoEscapado);
        
        // Meses en español
        $mesesEspanol = [
            'January' => 'Enero',
            'February' => 'Febrero', 
            'March' => 'Marzo',
            'April' => 'Abril',
            'May' => 'Mayo',
            'June' => 'Junio',
            'July' => 'Julio',
            'August' => 'Agosto',
            'September' => 'Septiembre',
            'October' => 'Octubre',
            'November' => 'Noviembre',
            'December' => 'Diciembre'
        ];
        
        // Meses abreviados en español
        $mesesAbreviadosEspanol = [
            'Jan' => 'Ene',
            'Feb' => 'Feb',
            'Mar' => 'Mar',
            'Apr' => 'Abr',
            'May' => 'May',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Ago',
            'Sep' => 'Sep',
            'Oct' => 'Oct',
            'Nov' => 'Nov',
            'Dec' => 'Dic'
        ];
        
        // Días de la semana en español
        $diasEspanol = [
            'Sunday' => 'Domingo',
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado'
        ];
        
        // Formatear la fecha
        $fechaFormateada = date($formatoEscapado, $timestamp);
        
        // Reemplazar meses en inglés por español
        $fechaFormateada = str_replace(array_keys($mesesEspanol), array_values($mesesEspanol), $fechaFormateada);
        $fechaFormateada = str_replace(array_keys($mesesAbreviadosEspanol), array_values($mesesAbreviadosEspanol), $fechaFormateada);
        
        // Reemplazar días en inglés por español
        $fechaFormateada = str_replace(array_keys($diasEspanol), array_values($diasEspanol), $fechaFormateada);
        
        return $fechaFormateada;
    }
    
    /**
     * Obtener ruta de fuente personalizada
     */
    private function getFontPath($fontName) {
        if (empty($fontName)) {
            // Usar fuente del sistema por defecto
            return $this->getSystemFont('arial');
        }
        
        // Verificar si es un ID de fuente de la base de datos
        if (is_numeric($fontName)) {
            return $this->getFontPathById($fontName);
        }
        
        // Manejar fuentes de Google (prefijo "google:")
        if (strpos($fontName, 'google:') === 0) {
            // Extraer el nombre real de la fuente (ej: "google:Open+Sans" -> "Open Sans")
            $googleFontName = str_replace('+', ' ', substr($fontName, 7));
            
            // Buscar en BD por nombre
            $stmt = $this->pdo->prepare("SELECT * FROM fuentes_personalizadas WHERE nombre = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$googleFontName]);
            $fuente = $stmt->fetch();
            
            if ($fuente) {
                return $this->getFontPathByRecord($fuente);
            }
            
            // También intentar sin espacios como nombre_archivo
            $fontNameNoSpaces = str_replace(' ', '', $googleFontName);
            $stmt = $this->pdo->prepare("SELECT * FROM fuentes_personalizadas WHERE nombre_archivo LIKE ? AND activo = 1 LIMIT 1");
            $stmt->execute(['%' . $fontNameNoSpaces . '%']);
            $fuente = $stmt->fetch();
            
            if ($fuente) {
                return $this->getFontPathByRecord($fuente);
            }
        }
        
        // Buscar por nombre en la base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM fuentes_personalizadas WHERE nombre = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$fontName]);
        $fuente = $stmt->fetch();
        
        if ($fuente) {
            return $this->getFontPathByRecord($fuente);
        }
        
        // Buscar por nombre_archivo en la base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM fuentes_personalizadas WHERE nombre_archivo = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$fontName]);
        $fuente = $stmt->fetch();
        
        if ($fuente) {
            return $this->getFontPathByRecord($fuente);
        }
        
        // Buscar en la carpeta de fuentes del proyecto
        $fontPath = dirname(__DIR__) . '/assets/fonts/' . $fontName;
        
        // Intentar con extensión .ttf
        if (file_exists($fontPath . '.ttf')) {
            return $fontPath . '.ttf';
        }
        
        // Intentar con extensión .otf
        if (file_exists($fontPath . '.otf')) {
            return $fontPath . '.otf';
        }
        
        // Intentar sin extensión (si ya la incluye)
        if (file_exists($fontPath)) {
            return $fontPath;
        }
        
        // Si no se encuentra, usar fuente del sistema
        return $this->getSystemFont(strtolower($fontName));
    }
    
    /**
     * Obtener ruta de fuente por ID
     */
    private function getFontPathById($fontId) {
        $stmt = $this->pdo->prepare("SELECT * FROM fuentes_personalizadas WHERE id = ? LIMIT 1");
        $stmt->execute([$fontId]);
        $fuente = $stmt->fetch();
        
        if (!$fuente) {
            return $this->getSystemFont('arial');
        }
        
        return $this->getFontPathByRecord($fuente);
    }
    
    /**
     * Obtener ruta de fuente por registro de BD
     */
    private function getFontPathByRecord($fuente) {
        $fontsDir = dirname(__DIR__) . '/assets/fonts/';
        
        // Si es una fuente de Google, descargar y cachear
        if (strpos($fuente['archivo'], 'google:') === 0) {
            $googleFontName = str_replace('google:', '', $fuente['archivo']);
            $googleFontName = str_replace('+', ' ', $googleFontName);
            
            // Nombre del archivo cacheado
            $cacheFileName = 'google_' . preg_replace('/[^a-zA-Z0-9]/', '_', $googleFontName) . '.ttf';
            $cachePath = $fontsDir . $cacheFileName;
            
            // Si ya está cacheada, usarla
            if (file_exists($cachePath)) {
                return $cachePath;
            }
            
            // Descargar la fuente de Google
            $downloaded = $this->downloadGoogleFont($googleFontName, $cachePath);
            if ($downloaded && file_exists($cachePath)) {
                return $cachePath;
            }
            
            // Si falla la descarga, usar Arial
            error_log("No se pudo descargar la fuente de Google: $googleFontName");
            return $this->getSystemFont('arial');
        }
        
        // Fuente local
        $fontPath = $fontsDir . $fuente['archivo'];
        if (file_exists($fontPath)) {
            return $fontPath;
        }
        
        return $this->getSystemFont('arial');
    }
    
    /**
     * Descargar fuente de Google Fonts
     */
    private function downloadGoogleFont($fontName, $savePath) {
        try {
            // URL de la API de Google Fonts para obtener TTF
            $fontNameEncoded = urlencode($fontName);
            $apiUrl = "https://fonts.google.com/download?family={$fontNameEncoded}";
            
            // Usar un User-Agent diferente para obtener TTF
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
                    'timeout' => 30
                ]
            ]);
            
            // Intentar obtener el archivo TTF directamente desde google-webfonts-helper o similar
            // Usamos una API alternativa que provee TTF directamente
            $helperUrl = "https://gwfh.mranftl.com/api/fonts/" . strtolower(str_replace(' ', '-', $fontName)) . "?download=zip&subsets=latin&variants=regular";
            
            // Alternativa: descargar desde fonts.gstatic.com
            // Primero obtenemos el CSS de Google Fonts
            $cssUrl = "https://fonts.googleapis.com/css2?family=" . str_replace(' ', '+', $fontName) . "&display=swap";
            $contextTTF = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
                    'timeout' => 10
                ]
            ]);
            
            $css = @file_get_contents($cssUrl, false, $contextTTF);
            if ($css) {
                // Extraer URL del WOFF2 o TTF
                if (preg_match('/src:\s*url\(([^)]+\.(?:woff2|ttf))\)/i', $css, $matches)) {
                    $fontUrl = $matches[1];
                    $fontData = @file_get_contents($fontUrl, false, $contextTTF);
                    
                    if ($fontData) {
                        // Si es WOFF2, guardarlo como .woff2 y crear un enlace
                        if (strpos($fontUrl, '.woff2') !== false) {
                            $woff2Path = str_replace('.ttf', '.woff2', $savePath);
                            file_put_contents($woff2Path, $fontData);
                            // Para GD necesitamos TTF, usar fuente del sistema como fallback
                            return false;
                        }
                        
                        file_put_contents($savePath, $fontData);
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error descargando fuente de Google: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener ruta de fuente del sistema Windows
     */
    private function getSystemFont($fontName) {
        $windowsFontsPath = 'C:/Windows/Fonts/';
        
        $fontMap = [
            'arial' => 'arial.ttf',
            'helvetica' => 'arial.ttf', // Windows usa Arial en lugar de Helvetica
            'times new roman' => 'times.ttf',
            'georgia' => 'georgia.ttf',
            'courier new' => 'cour.ttf',
            'verdana' => 'verdana.ttf',
            'calibri' => 'calibri.ttf',
            'tahoma' => 'tahoma.ttf'
        ];
        
        $fontFile = $fontMap[strtolower($fontName)] ?? 'arial.ttf';
        $fontPath = $windowsFontsPath . $fontFile;
        
        if (file_exists($fontPath)) {
            return $fontPath;
        }
        
        // Fallback a arial.ttf si la fuente no existe
        $fallback = $windowsFontsPath . 'arial.ttf';
        if (file_exists($fallback)) {
            return $fallback;
        }
        
        // Último fallback: Roboto del proyecto
        $robotoPath = dirname(__DIR__) . '/assets/fonts/Roboto-Regular.ttf';
        if (file_exists($robotoPath)) {
            return $robotoPath;
        }
        
        return null;
    }
    
    /**
     * Generar PDF del certificado
     */
    private function generatePDF($imagePath, $codigo) {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configurar PDF
        $pdf->SetCreator('CCE Certificados');
        $pdf->SetAuthor('Casa de la Cultura CCE');
        $pdf->SetTitle('Certificado ' . $codigo);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        
        // Agregar página
        $pdf->AddPage();
        
        // Insertar imagen (A4 landscape: 297x210mm)
        $pdf->Image($imagePath, 0, 0, 297, 210, 'PNG', '', '', false, 300, '', false, false, 0);
        
        // Guardar PDF
        $pdfFilename = 'cert_' . $codigo . '.pdf';
        $pdfPath = $this->uploadPath . $pdfFilename;
        $pdf->Output($pdfPath, 'F');
        
        return $pdfPath;
    }
    
    /**
     * Obtener certificado por código
     */
    public function getByCodigo($codigo) {
        $stmt = $this->pdo->prepare("SELECT * FROM certificados WHERE codigo = ? AND estado = 'activo'");
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }
    
    /**
     * Regenerar imagen de un certificado existente
     */
    public function regenerate($codigo, $razonRegeneracion = '') {
        try {
            // Obtener datos del certificado
            $stmt = $this->pdo->prepare("SELECT * FROM certificados WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $cert = $stmt->fetch();
            
            if (!$cert) {
                throw new \Exception("Certificado no encontrado");
            }
            
            // Cargar configuración del grupo si existe
            if (!empty($cert['grupo_id'])) {
                $this->loadGrupoConfig($cert['grupo_id']);
            }
            
            // Cargar configuración de la categoría si existe
            if (!empty($cert['categoria_id'])) {
                $this->loadCategoriaConfig($cert['categoria_id']);
            }
            
            // Verificar que hay plantilla configurada
            if (empty($this->config['archivo_plantilla'])) {
                throw new \Exception("No hay plantilla configurada para regenerar");
            }
            
            // Buscar si el estudiante está marcado como destacado
            $esDestacado = false;
            $stmt = $this->pdo->prepare("SELECT destacado FROM estudiantes WHERE nombre = ? LIMIT 1");
            $stmt->execute([$cert['nombre']]);
            $estudiante = $stmt->fetch();
            if ($estudiante) {
                $esDestacado = (bool)$estudiante['destacado'];
            }
            
            // Preparar datos
            $data = [
                'nombre' => $cert['nombre'],
                'razon' => $cert['razon'],
                'fecha' => $cert['fecha'],
                'grupo_id' => $cert['grupo_id'],
                'categoria_id' => $cert['categoria_id'],
                'es_destacado' => $esDestacado
            ];
            
            // Regenerar imagen
            $imagePath = $this->generateImage($data, $codigo);
            
            // Actualizar historial de fechas de generación
            $fechasGeneracion = [];
            if (!empty($cert['fechas_generacion'])) {
                $fechasGeneracion = json_decode($cert['fechas_generacion'], true) ?? [];
            }
            $fechasGeneracion[] = [
                'fecha' => date('Y-m-d H:i:s'),
                'razon' => $razonRegeneracion ?: 'Regeneración manual'
            ];
            
            // Actualizar registro en BD con nueva ruta y fechas
            $stmt = $this->pdo->prepare("UPDATE certificados SET archivo_imagen = ?, fechas_generacion = ? WHERE codigo = ?");
            $stmt->execute([basename($imagePath), json_encode($fechasGeneracion), $codigo]);
            
            return [
                'success' => true,
                'codigo' => $codigo,
                'imagen_path' => $imagePath
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar verificación
     */
    public function registrarVerificacion($certificadoId, $ipAddress, $userAgent) {
        $stmt = $this->pdo->prepare("
            INSERT INTO verificaciones (certificado_id, ip_address, user_agent)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$certificadoId, $ipAddress, $userAgent]);
    }
    
    /**
     * Listar todos los certificados
     */
    public function getAll($limit = 50, $offset = 0, $grupoId = null, $categoriaId = null) {
        $sql = "
            SELECT c.*, 
                   g.nombre as grupo_nombre, g.color as grupo_color, g.icono as grupo_icono,
                   cat.nombre as categoria_nombre, cat.color as categoria_color, cat.icono as categoria_icono
            FROM certificados c
            LEFT JOIN grupos g ON c.grupo_id = g.id
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($grupoId) {
            $sql .= " AND c.grupo_id = ?";
            $params[] = $grupoId;
        }
        
        if ($categoriaId) {
            $sql .= " AND c.categoria_id = ?";
            $params[] = $categoriaId;
        }
        
        $sql .= " ORDER BY c.fecha_creacion DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}
