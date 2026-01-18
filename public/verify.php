<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';

use CCE\Certificate;

$codigo = $_GET['code'] ?? $_POST['code'] ?? '';
$busquedaRealizada = !empty($codigo);
$cert = null;

if ($busquedaRealizada) {
    $certificate = new Certificate($pdo);
    $cert = $certificate->getByCodigo($codigo);

    // Registrar verificación si se encontró
    if ($cert) {
        $certificate->registrarVerificacion(
            $cert['id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Certificado - CCE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ===== MENÚ DE NAVEGACIÓN FIJO ===== */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
            color: #2c3e50;
        }
        
        .nav-logo i {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-menu {
            display: flex;
            gap: 8px;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            color: #5a6c7d;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: #f0f2f5;
            color: #667eea;
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .nav-link i {
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .top-nav {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        
        /* Espacio para el menú fijo */
        body {
            padding-top: 80px;
        }
        
        /* Estilos del formulario de búsqueda */
        .search-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .search-card h2 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .search-card p {
            color: #7f8c8d;
            margin-bottom: 25px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            max-width: 450px;
            margin: 0 auto;
        }
        
        .search-form input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
            text-transform: uppercase;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-form input::placeholder {
            text-transform: none;
        }
        
        .search-form button {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 500px) {
            .search-form {
                flex-direction: column;
            }
            
            .search-form button {
                justify-content: center;
            }
        }
        
        /* Estilos mejorados para resultados */
        .verify-card {
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .verify-card.error {
            border-left: 5px solid #e74c3c;
        }
        
        .verify-card.error .verify-icon {
            color: #e74c3c;
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .verify-card.error h2 {
            color: #e74c3c;
        }
        
        .verify-card.error p {
            color: #666;
            margin: 10px 0;
        }
        
        .code-searched {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 15px;
            font-family: monospace;
            font-size: 16px;
            color: #495057;
        }
    </style>
</head>
<body>
    <!-- Menú de Navegación -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="estudiantes.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <li><a href="verify.php" class="nav-link active"><i class="fas fa-search"></i> Verificar</a></li>
        </ul>
    </nav>

    <div class="container">
        <header>
            <img src="../assets/logos/logo-cce.png" alt="Logo CCE" class="logo" onerror="this.style.display='none'">
            <h1>Verificación de Certificado</h1>
        </header>

        <div class="main-content">
            <!-- Formulario de búsqueda siempre visible -->
            <div class="search-card">
                <h2><i class="fas fa-search"></i> Buscar Certificado</h2>
                <p>Ingresa el código del certificado para verificar su autenticidad</p>
                <form class="search-form" method="GET" action="verify.php">
                    <input type="text" name="code" placeholder="Ej: CCE-A1B2C3D4" 
                           value="<?= htmlspecialchars($codigo) ?>" required
                           pattern="[A-Za-z0-9\-]+" title="Solo letras, números y guiones">
                    <button type="submit">
                        <i class="fas fa-search"></i> Verificar
                    </button>
                </form>
            </div>

            <?php if ($busquedaRealizada): ?>
                <?php if ($cert): ?>
                    <div class="card verify-card success">
                        <div class="verify-icon">✓</div>
                        <h2>Certificado Válido</h2>
                        
                        <div class="cert-info">
                            <div class="info-row">
                                <span class="label">Código:</span>
                                <span class="value"><?= htmlspecialchars($cert['codigo']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Nombre:</span>
                                <span class="value"><?= htmlspecialchars($cert['nombre']) ?></span>
                            </div>
                            <?php if (!empty($cert['razon'])): ?>
                            <div class="info-row">
                                <span class="label">Razón:</span>
                                <span class="value"><?= nl2br(htmlspecialchars($cert['razon'])) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span class="label">Fecha de Certificación:</span>
                                <span class="value"><?= date('d/m/Y', strtotime($cert['fecha'])) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Emitido el:</span>
                                <span class="value"><?= date('d/m/Y H:i', strtotime($cert['fecha_creacion'])) ?></span>
                            </div>
                        </div>

                        <div class="cert-downloads">
                            <h3>Descargar Certificado</h3>
                            <div class="download-buttons">
                                <?php if (!empty($cert['archivo_imagen'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($cert['archivo_imagen']) ?>" 
                                   class="btn btn-secondary" download>
                                    <i class="fas fa-image"></i> Descargar Imagen (PNG)
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($cert['archivo_pdf'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($cert['archivo_pdf']) ?>" 
                                   class="btn btn-primary" download>
                                    <i class="fas fa-file-pdf"></i> Descargar PDF
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card verify-card error">
                        <div class="verify-icon"><i class="fas fa-times-circle"></i></div>
                        <h2>Certificado No Encontrado</h2>
                        <p>El código ingresado no es válido o no se han encontrado existencias.</p>
                        <p>Por favor, verifica que el código esté escrito correctamente.</p>
                        <div class="code-searched">
                            Código buscado: <strong><?= htmlspecialchars($codigo) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="back-link">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
            </div>
        </div>

        <footer>
            <p>&copy; <?= date('Y') ?> Casa de la Cultura CCE. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
