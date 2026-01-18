<?php
require_once '../config/database.php';

$pdo = getConnection();

// Obtener grupo_id si se pasa por parámetro
$grupo_id = $_GET['grupo'] ?? null;

// Obtener información del grupo si se especifica
$grupo = null;
if ($grupo_id) {
    $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ? AND activo = 1");
    $stmt->execute([$grupo_id]);
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener periodos según si hay grupo o no
if ($grupo_id) {
    // Si hay grupo, obtener solo los periodos asignados a ese grupo
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COUNT(DISTINCT cp.categoria_id) as total_categorias
        FROM periodos p
        INNER JOIN grupo_periodos gp ON p.id = gp.periodo_id AND gp.grupo_id = ? AND gp.activo = 1
        LEFT JOIN categoria_periodos cp ON p.id = cp.periodo_id AND cp.activo = 1
        WHERE p.activo = 1
        GROUP BY p.id
        ORDER BY p.fecha_inicio DESC
    ");
    $stmt->execute([$grupo_id]);
} else {
    // Si no hay grupo, obtener todos los periodos
    $stmt = $pdo->query("
        SELECT p.*, 
               COUNT(DISTINCT cp.categoria_id) as total_categorias
        FROM periodos p
        LEFT JOIN categoria_periodos cp ON p.id = cp.periodo_id AND cp.activo = 1
        WHERE p.activo = 1
        GROUP BY p.id
        ORDER BY p.fecha_inicio DESC
    ");
}
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Períodos - Sistema de Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            color: #2c3e50;
            font-size: 36px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .periodos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .periodo-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 6px solid var(--periodo-color);
        }
        
        .periodo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .periodo-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .periodo-title {
            flex: 1;
        }
        
        .periodo-title h3 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 5px;
        }
        
        .periodo-title p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .periodo-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .btn-icon.edit {
            background: #3498db;
            color: white;
        }
        
        .btn-icon.edit:hover {
            background: #2980b9;
        }
        
        .btn-icon.delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-icon.delete:hover {
            background: #c0392b;
        }
        
        .btn-icon.assign {
            background: #9b59b6;
            color: white;
        }
        
        .btn-icon.assign:hover {
            background: #8e44ad;
        }
        
        .periodo-dates {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .periodo-dates i {
            color: var(--periodo-color);
        }
        
        .periodo-dates span {
            color: #495057;
            font-size: 14px;
            font-weight: 500;
        }
        
        .periodo-stats {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .periodo-stats strong {
            color: #2c3e50;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            color: #7f8c8d;
        }
        
        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 35px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .modal-header h2 {
            color: #2c3e50;
            font-size: 24px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.2s;
        }
        
        .close-modal:hover {
            color: #e74c3c;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .date-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .color-picker {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .color-picker input[type="color"] {
            width: 70px;
            height: 50px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            cursor: pointer;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #7f8c8d;
        }
        
        /* Modal de asignación de grupos */
        .grupos-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }
        
        .grupo-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        
        .grupo-item:hover {
            background: #f8f9fa;
            border-color: #3498db;
        }
        
        .grupo-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
        }
        
        .grupo-icon {
            font-size: 24px;
            margin-right: 12px;
        }
        
        .grupo-info {
            flex: 1;
        }
        
        .grupo-info h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 2px;
        }
        
        .grupo-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #e3f2fd;
            color: #2196f3;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .periodos-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                gap: 20px;
            }
            
            .date-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-calendar-alt"></i> Gestión de Períodos</h1>
                <?php if ($grupo): ?>
                    <p style="color: #7f8c8d; margin-top: 8px;">
                        Grupo: <strong><?= htmlspecialchars($grupo['nombre']) ?></strong>
                    </p>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <a href="<?= $grupo_id ? 'grupo_detalle.php?id=' . $grupo_id : 'index.php' ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button onclick="openCreateModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Período
                </button>
            </div>
        </div>

        <?php if (count($periodos) > 0): ?>
            <div class="periodos-grid">
                <?php foreach ($periodos as $periodo): ?>
                    <div class="periodo-card" style="--periodo-color: <?= $periodo['color'] ?>">
                        <div class="periodo-header">
                            <div class="periodo-title">
                                <h3><?= htmlspecialchars($periodo['nombre']) ?></h3>
                                <?php if ($periodo['descripcion']): ?>
                                    <p><?= htmlspecialchars($periodo['descripcion']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="periodo-actions">
                                <?php if ($grupo_id): ?>
                                    <button onclick="openAssignModal(<?= $periodo['id'] ?>, '<?= htmlspecialchars($periodo['nombre']) ?>', <?= $grupo_id ?>)" class="btn-icon assign" title="Asignar categorías">
                                        <i class="fas fa-folder"></i>
                                    </button>
                                <?php endif; ?>
                                <button onclick="editPeriodo(<?= $periodo['id'] ?>)" class="btn-icon edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deletePeriodo(<?= $periodo['id'] ?>)" class="btn-icon delete" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="periodo-dates">
                            <i class="fas fa-calendar-check"></i>
                            <span>
                                <?= date('d/m/Y', strtotime($periodo['fecha_inicio'])) ?>
                                <i class="fas fa-arrow-right" style="margin: 0 8px;"></i>
                                <?= date('d/m/Y', strtotime($periodo['fecha_fin'])) ?>
                            </span>
                        </div>
                        
                        <div class="periodo-stats">
                            <i class="fas fa-folder"></i>
                            <span><strong><?= $periodo['total_categorias'] ?></strong> categorías asignadas<?= $grupo_id ? ' en este grupo' : '' ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon"><i class="fas fa-calendar-times"></i></div>
                <h3>No hay períodos creados</h3>
                <p>Crea tu primer período académico para organizar los grupos</p>
                <button onclick="openCreateModal()" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Crear Primer Período
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal crear/editar período -->
    <div id="periodoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Período</h2>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            <form id="periodoForm">
                <input type="hidden" id="periodoId" name="id">
                
                <div class="form-group">
                    <label>Nombre del Período *</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: Enero-Abril 2025">
                </div>
                
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea id="descripcion" name="descripcion" placeholder="Descripción opcional del período"></textarea>
                </div>
                
                <div class="date-group">
                    <div class="form-group">
                        <label>Fecha Inicio *</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Fin *</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Color</label>
                    <div class="color-picker">
                        <input type="color" id="color" name="color" value="#3498db">
                        <span id="colorValue">#3498db</span>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn btn-cancel">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal asignar categorías -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Asignar Categorías</h2>
                <button class="close-modal" onclick="closeAssignModal()">×</button>
            </div>
            <p style="color: #7f8c8d; margin-bottom: 20px;">Selecciona las categorías para el período <strong id="periodoNombre"></strong></p>
            
            <div class="grupos-list" id="categoriasList">
                <!-- Se llenará dinámicamente -->
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeAssignModal()" class="btn btn-cancel">Cerrar</button>
                <button type="button" onclick="saveAssignments()" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Asignaciones
                </button>
            </div>
        </div>
    </div>

    <!-- Modal copiar categorías de período anterior -->
    <div id="copyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Copiar Categorías</h2>
                <button class="close-modal" onclick="closeCopyModal()">×</button>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Selecciona el período de origen:</label>
                <select id="periodoOrigenSelect" style="width: 100%; padding: 12px; border: 2px solid #ecf0f1; border-radius: 10px; font-size: 15px;">
                    <option value="">-- Selecciona un período --</option>
                </select>
            </div>
            
            <div id="categoriasOrigenContainer" style="display: none;">
                <p style="color: #7f8c8d; margin-bottom: 15px;">Selecciona las categorías que deseas copiar al nuevo período:</p>
                <div class="grupos-list" id="categoriasOrigenList">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeCopyModal()" class="btn btn-cancel">Cancelar</button>
                <button type="button" onclick="copiarCategorias()" class="btn btn-primary" id="btnCopiar" style="display: none;">
                    <i class="fas fa-copy"></i> Copiar Seleccionadas
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentPeriodoId = null;
        let currentGrupoId = <?= $grupo_id ?? 'null' ?>;
        let categoriasAsignadas = {};
        
        // Color picker preview
        document.getElementById('color').addEventListener('input', function() {
            document.getElementById('colorValue').textContent = this.value;
        });
        
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Período';
            document.getElementById('periodoForm').reset();
            document.getElementById('periodoId').value = '';
            document.getElementById('colorValue').textContent = '#3498db';
            document.getElementById('periodoModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('periodoModal').classList.remove('active');
        }
        
        async function editPeriodo(id) {
            try {
                const response = await fetch(`api_periodos.php?action=list`);
                const data = await response.json();
                
                if (data.success) {
                    const periodo = data.periodos.find(p => p.id == id);
                    if (periodo) {
                        document.getElementById('modalTitle').textContent = 'Editar Período';
                        document.getElementById('periodoId').value = periodo.id;
                        document.getElementById('nombre').value = periodo.nombre;
                        document.getElementById('descripcion').value = periodo.descripcion || '';
                        document.getElementById('fecha_inicio').value = periodo.fecha_inicio;
                        document.getElementById('fecha_fin').value = periodo.fecha_fin;
                        document.getElementById('color').value = periodo.color;
                        document.getElementById('colorValue').textContent = periodo.color;
                        document.getElementById('periodoModal').classList.add('active');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar el período');
            }
        }
        
        async function deletePeriodo(id) {
            if (!confirm('¿Estás seguro de eliminar este período? Los grupos asignados no se eliminarán.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                const response = await fetch('api_periodos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Período eliminado correctamente');
                    location.reload();
                } else {
                    alert(data.message || 'Error al eliminar el período');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al eliminar el período');
            }
        }
        
        document.getElementById('periodoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const periodoId = document.getElementById('periodoId').value;
            formData.append('action', periodoId ? 'update' : 'create');
            
            // Enviar grupo_id si está disponible (para crear la relación grupo_periodos)
            if (currentGrupoId) {
                formData.append('grupo_id', currentGrupoId);
            }
            
            try {
                const response = await fetch('api_periodos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (periodoId) {
                        alert('Período actualizado correctamente');
                        location.reload();
                    } else {
                        // Es un nuevo período, verificar si hay períodos anteriores con categorías
                        if (currentGrupoId) {
                            const hayCategorias = await verificarPeriodosConCategorias(data.id);
                            
                            if (hayCategorias) {
                                const copiar = confirm('Período creado correctamente.\n\n¿Deseas copiar categorías de un período anterior a este nuevo período?');
                                
                                if (copiar) {
                                    await openCopyCategoriasModal(data.id);
                                    return;
                                }
                            } else {
                                alert('Período creado correctamente.');
                            }
                        } else {
                            alert('Período creado correctamente.');
                        }
                        location.reload();
                    }
                } else {
                    alert(data.message || 'Error al guardar el período');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar el período');
            }
        });
        
        async function openAssignModal(periodoId, periodoNombre, grupoId) {
            if (!grupoId) {
                alert('Debes especificar un grupo. Accede desde la página del grupo.');
                return;
            }
            
            currentPeriodoId = periodoId;
            currentGrupoId = grupoId;
            document.getElementById('periodoNombre').textContent = periodoNombre;
            
            try {
                const response = await fetch(`api_periodos.php?action=get_categorias&periodo_id=${periodoId}&grupo_id=${grupoId}`);
                const data = await response.json();
                
                if (data.success) {
                    const categoriasList = document.getElementById('categoriasList');
                    categoriasList.innerHTML = '';
                    categoriasAsignadas = {};
                    
                    data.categorias.forEach(categoria => {
                        categoriasAsignadas[categoria.id] = categoria.asignado;
                        
                        const div = document.createElement('div');
                        div.className = 'grupo-item';
                        div.innerHTML = `
                            <input type="checkbox" 
                                   id="categoria_${categoria.id}" 
                                   data-categoria-id="${categoria.id}"
                                   ${categoria.asignado ? 'checked' : ''}>
                            <span class="grupo-icon">${categoria.icono}</span>
                            <div class="grupo-info">
                                <h4>${categoria.nombre}</h4>
                                ${categoria.asignado ? '<span class="grupo-badge">Asignado</span>' : ''}
                            </div>
                        `;
                        categoriasList.appendChild(div);
                    });
                    
                    document.getElementById('assignModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar las categorías');
            }
        }
        
        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('active');
        }
        
        async function saveAssignments() {
            const checkboxes = document.querySelectorAll('#categoriasList input[type="checkbox"]');
            const promises = [];
            
            for (const checkbox of checkboxes) {
                const categoriaId = checkbox.dataset.categoriaId;
                const checked = checkbox.checked;
                const wasAssigned = categoriasAsignadas[categoriaId];
                
                // Solo hacer cambios si el estado cambió
                if (checked !== wasAssigned) {
                    const formData = new FormData();
                    formData.append('action', 'asignar_categoria');
                    formData.append('periodo_id', currentPeriodoId);
                    formData.append('categoria_id', categoriaId);
                    formData.append('asignar', checked ? '1' : '0');
                    
                    promises.push(
                        fetch('api_periodos.php', {
                            method: 'POST',
                            body: formData
                        })
                    );
                }
            }
            
            try {
                await Promise.all(promises);
                alert('Asignaciones guardadas correctamente');
                location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar las asignaciones');
            }
        }
        
        // Verificar si hay períodos anteriores con categorías asignadas
        async function verificarPeriodosConCategorias(nuevoPeriodoIdExcluir) {
            try {
                const response = await fetch('api_periodos.php?action=list');
                const data = await response.json();
                
                if (data.success && data.periodos) {
                    // Buscar si hay algún período (diferente al nuevo) con categorías > 0
                    return data.periodos.some(periodo => 
                        periodo.id != nuevoPeriodoIdExcluir && 
                        parseInt(periodo.total_categorias) > 0
                    );
                }
                return false;
            } catch (error) {
                console.error('Error verificando períodos:', error);
                return false;
            }
        }
        
        let nuevoPeriodoId = null;
        
        async function openCopyCategoriasModal(periodoId) {
            nuevoPeriodoId = periodoId;
            
            // Cargar lista de períodos del grupo actual
            try {
                let url = 'api_periodos.php?action=list';
                if (currentGrupoId) {
                    url += `&grupo_id=${currentGrupoId}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('periodoOrigenSelect');
                    select.innerHTML = '<option value="">-- Selecciona un período --</option>';
                    
                    data.periodos.forEach(periodo => {
                        if (periodo.id != periodoId) {
                            const option = document.createElement('option');
                            option.value = periodo.id;
                            option.textContent = `${periodo.nombre} (${periodo.total_categorias} categorías)`;
                            select.appendChild(option);
                        }
                    });
                    
                    document.getElementById('copyModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar los períodos');
            }
        }
        
        function closeCopyModal() {
            document.getElementById('copyModal').classList.remove('active');
            document.getElementById('categoriasOrigenContainer').style.display = 'none';
            document.getElementById('btnCopiar').style.display = 'none';
        }
        
        document.getElementById('periodoOrigenSelect').addEventListener('change', async function() {
            const periodoOrigenId = this.value;
            
            if (!periodoOrigenId) {
                document.getElementById('categoriasOrigenContainer').style.display = 'none';
                document.getElementById('btnCopiar').style.display = 'none';
                return;
            }
            
            if (!currentGrupoId) {
                alert('Error: No se ha especificado un grupo');
                return;
            }
            
            try {
                const response = await fetch(`api_periodos.php?action=get_categorias&periodo_id=${periodoOrigenId}&grupo_id=${currentGrupoId}`);
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('categoriasOrigenList');
                    container.innerHTML = '';
                    
                    const categoriasAsignadas = data.categorias.filter(c => c.asignado);
                    
                    if (categoriasAsignadas.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: #7f8c8d; padding: 20px;">No hay categorías en este período</p>';
                        document.getElementById('btnCopiar').style.display = 'none';
                    } else {
                        categoriasAsignadas.forEach(categoria => {
                            const div = document.createElement('div');
                            div.className = 'grupo-item';
                            div.innerHTML = `
                                <input type="checkbox" 
                                       id="copy_cat_${categoria.id}" 
                                       data-categoria-id="${categoria.id}"
                                       checked>
                                <span class="grupo-icon">${categoria.icono}</span>
                                <div class="grupo-info">
                                    <h4>${categoria.nombre}</h4>
                                </div>
                            `;
                            container.appendChild(div);
                        });
                        document.getElementById('btnCopiar').style.display = 'inline-flex';
                    }
                    
                    document.getElementById('categoriasOrigenContainer').style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar las categorías');
            }
        });
        
        async function copiarCategorias() {
            const checkboxes = document.querySelectorAll('#categoriasOrigenList input[type="checkbox"]:checked');
            
            if (checkboxes.length === 0) {
                alert('Debes seleccionar al menos una categoría para copiar');
                return;
            }
            
            const promises = [];
            
            checkboxes.forEach(checkbox => {
                const categoriaId = checkbox.dataset.categoriaId;
                const formData = new FormData();
                formData.append('action', 'asignar_categoria');
                formData.append('periodo_id', nuevoPeriodoId);
                formData.append('categoria_id', categoriaId);
                formData.append('asignar', '1');
                
                promises.push(
                    fetch('api_periodos.php', {
                        method: 'POST',
                        body: formData
                    })
                );
            });
            
            try {
                await Promise.all(promises);
                alert(`${checkboxes.length} categoría(s) copiada(s) correctamente al nuevo período`);
                location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert('Error al copiar las categorías');
            }
        }
    </script>
</body>
</html>
