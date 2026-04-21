// Selector de iconos
document.querySelectorAll('.icon-option-grupo').forEach(option => {
    option.addEventListener('click', function () {
        document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('icono').value = this.dataset.icon;
        // Ocultar picker si está abierto
        document.getElementById('emojiPickerContainer').style.display = 'none';

        // Actualizar botón personalizado si se seleccionó un icono predefinido
        const customBtn = document.querySelector('.icon-option-custom-grupo');
        if (!customBtn.contains(this)) {
            customBtn.classList.remove('selected');
            customBtn.innerHTML = '<i class="fas fa-search"></i><span>Buscar...</span>';
        }
    });
});

// Funciones para contador de caracteres
function updateCharCounter(textarea) {
    const count = textarea.value.length;
    const max = textarea.getAttribute('maxlength');
    const counter = document.getElementById('charCounter');
    counter.textContent = `${count} / ${max}`;

    // Cambiar color según proximidad al límite
    if (count > max * 0.9) {
        counter.style.color = '#e74c3c';
    } else if (count > max * 0.7) {
        counter.style.color = '#f39c12';
    } else {
        counter.style.color = '#95a5a6';
    }
}

function handlePaste(event) {
    const textarea = event.target;
    const maxLength = parseInt(textarea.getAttribute('maxlength'));

    // Obtener el texto pegado
    setTimeout(() => {
        if (textarea.value.length > maxLength) {
            textarea.value = textarea.value.substring(0, maxLength);
            updateCharCounter(textarea);
            alert(`El texto ha sido recortado al límite de ${maxLength} caracteres.`);
        }
    }, 0);
}

// Funciones para selector de color
function selectPresetColor(element, color) {
    // Remover selección de todos los presets
    document.querySelectorAll('.color-preset').forEach(preset => {
        preset.classList.remove('selected');
    });

    // Seleccionar el preset clickeado
    element.classList.add('selected');

    // Actualizar el input hidden y el color personalizado
    document.getElementById('color').value = color;
    document.getElementById('customColor').value = color;
    document.getElementById('colorHex').textContent = color;
}

function selectCustomColor(color) {
    // Remover selección de todos los presets
    document.querySelectorAll('.color-preset').forEach(preset => {
        preset.classList.remove('selected');
    });

    // Actualizar valores
    document.getElementById('color').value = color;
    document.getElementById('colorHex').textContent = color;
}

// Toggle emoji picker
function toggleEmojiPicker() {
    const container = document.getElementById('emojiPickerContainer');
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
}

// Configurar emoji picker cuando se carga
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('emojiPicker');
    if (picker) {
        picker.addEventListener('emoji-click', event => {
            const emoji = event.detail.unicode;
            // Deseleccionar opciones predefinidas
            document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
            // Establecer el emoji seleccionado
            document.getElementById('icono').value = emoji;
            // Ocultar el picker
            document.getElementById('emojiPickerContainer').style.display = 'none';
            // Mostrar feedback visual permanente
            const customBtn = document.querySelector('.icon-option-custom-grupo');
            customBtn.innerHTML = `<span class="custom-icon">${emoji}</span><span class="custom-label">Cambiar</span>`;
            customBtn.classList.add('selected');
        });
    }
});

function openCreateGrupoModal() {
    document.getElementById('grupoModal').classList.add('active');
    document.getElementById('grupoForm').reset();
    document.querySelectorAll('.icon-option-grupo').forEach(o => o.classList.remove('selected'));
    document.querySelector('.icon-option-grupo[data-icon="📚"]').classList.add('selected');
    document.getElementById('icono').value = '📚';
    document.getElementById('emojiPickerContainer').style.display = 'none';
    // Resetear botón personalizado
    const customBtn = document.querySelector('.icon-option-custom-grupo');
    customBtn.innerHTML = '<i class="fas fa-search"></i><span>Buscar...</span>';
    customBtn.classList.remove('selected');

    // Resetear contador de caracteres
    document.getElementById('charCounter').textContent = '0 / 250';
    document.getElementById('charCounter').style.color = '#95a5a6';

    // Resetear selector de color al azul por defecto
    document.querySelectorAll('.color-preset').forEach(preset => {
        preset.classList.remove('selected');
    });
    // Verificamos si existe el preset default antes de seleccionarlo
    const defaultPreset = document.querySelector('.color-preset[data-color="#3498db"]');
    if (defaultPreset) defaultPreset.classList.add('selected');

    document.getElementById('color').value = '#3498db';
    const customColorInput = document.getElementById('customColor');
    if (customColorInput) customColorInput.value = '#3498db';

    document.getElementById('colorHex').textContent = '#3498db';
}

function closeCreateGrupoModal() {
    document.getElementById('grupoModal').classList.remove('active');
    document.getElementById('emojiPickerContainer').style.display = 'none';
}

document.getElementById('grupoForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'create');

    // Verificar que el icono esté establecido
    const iconoValue = document.getElementById('icono').value;
    if (!iconoValue) {
        alert('Por favor selecciona un icono');
        return;
    }

    try {
        const response = await fetch('../api/grupos/index.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error del servidor:', errorText);
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success) {
            closeCreateGrupoModal();
            // Verificar si hay periodos reales usando la variable global inyectada desde PHP
            if (typeof window.hayPeriodosReales !== 'undefined' && window.hayPeriodosReales) {
                openPeriodosModal(data.grupo_id, formData.get('nombre'));
            } else {
                // No hay periodos reales, redirigir directamente al grupo
                window.location.href = '../grupos/detalle.php?id=' + data.grupo_id;
            }
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error completo:', error);
        alert('Error al crear el grupo: ' + error.message);
    }
});

// Funciones para el modal de períodos
function openPeriodosModal(grupoId, grupoNombre) {
    const modal = document.getElementById('periodosModal');
    if (!modal) return; // Si no existe el modal (no hay periodos), no hacer nada

    document.getElementById('periodoGrupoId').value = grupoId;
    document.getElementById('periodoGrupoNombre').textContent = grupoNombre;

    // Desmarcar todos los checkboxes de períodos
    document.querySelectorAll('.periodo-checkbox').forEach(cb => {
        cb.checked = false;
    });
    modal.classList.add('active');
}

function closePeriodosModal() {
    const modal = document.getElementById('periodosModal');
    if (!modal) return;

    const grupoId = document.getElementById('periodoGrupoId').value;
    modal.classList.remove('active');
    // Redirigir al grupo sin asignar periodos
    window.location.href = '../grupos/detalle.php?id=' + grupoId;
}

async function savePeriodosSelection() {
    const grupoId = document.getElementById('periodoGrupoId').value;
    const selectedPeriodos = [];

    // Obtener períodos seleccionados
    document.querySelectorAll('.periodo-checkbox:checked').forEach(cb => {
        selectedPeriodos.push(cb.value);
    });

    if (selectedPeriodos.length === 0) {
        // Si no hay períodos seleccionados, solo cerrar y redirigir
        closePeriodosModal();
        return;
    }

    try {
        const response = await fetch('../api/grupos/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'assign_periodos',
                grupo_id: grupoId,
                periodos: selectedPeriodos
            })
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = '../grupos/detalle.php?id=' + grupoId;
        } else {
            alert('Error al asignar períodos: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al asignar períodos');
    }
}

// Cerrar modal al hacer clic fuera
const periodosModal = document.getElementById('periodosModal');
if (periodosModal) {
    periodosModal.addEventListener('click', function (e) {
        if (e.target === this) {
            closePeriodosModal();
        }
    });
}

// Cerrar modal de grupo al hacer clic fuera
const grupoModal = document.getElementById('grupoModal');
if (grupoModal) {
    grupoModal.addEventListener('click', function (e) {
        if (e.target === this) {
            closeCreateGrupoModal();
        }
    });
}
