<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Certificado - CCE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
    <link rel="stylesheet" href="<?= $cssPath ?>/verify/index.css">
</head>
<body>
    <!-- Menú de Navegación -->
    <nav class="top-nav">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>CCE Certificados</span>
        </div>
        <ul class="nav-menu">
            <li><a href="<?= $basePath ?>/dashboard/index.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="<?= $basePath ?>/estudiantes/index.php" class="nav-link"><i class="fas fa-users"></i> Estudiantes</a></li>
            <li><a href="<?= $basePath ?>/auth/verify.php" class="nav-link active"><i class="fas fa-search"></i> Verificar</a></li>
        </ul>
    </nav>

    <div class="container">
        <header>
            <img src="<?= $basePath ?>/../assets/logos/logo-cce.png" alt="Logo CCE" class="logo" onerror="this.style.display='none'">
            <h1>Verificación de Certificado</h1>
        </header>

        <div class="main-content">
            <!-- Formulario de búsqueda siempre visible -->
            <div class="search-card">
                <h2><i class="fas fa-search"></i> Buscar Certificado</h2>
                <p>Ingresa el código del certificado para verificar su autenticidad</p>
                <form class="search-form" method="GET" action="auth/verify.php">
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
                                <a href="<?= $basePath ?>/../uploads/<?= htmlspecialchars($cert['archivo_imagen']) ?>" 
                                   class="btn btn-secondary" download>
                                    <i class="fas fa-image"></i> Descargar Imagen (PNG)
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($cert['archivo_pdf'])): ?>
                                <a href="<?= $basePath ?>/../uploads/<?= htmlspecialchars($cert['archivo_pdf']) ?>" 
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
                <a href="<?= $basePath ?>/dashboard/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
            </div>
        </div>

        <footer>
            <p>&copy; <?= date('Y') ?> Casa de la Cultura CCE. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
