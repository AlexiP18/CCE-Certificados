// Formulario de generación de certificados
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('certificateForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Solo ejecutar si estamos en la página de certificados
    if (!form || !submitBtn) {
        // Cargar lista de certificados si existe el contenedor
        loadCertificates();
        return;
    }
    
    const btnText = submitBtn.querySelector('.btn-text');
    const loader = submitBtn.querySelector('.loader');
    const resultDiv = document.getElementById('result');

    // Establecer fecha actual por defecto
    const fechaInput = document.getElementById('fecha');
    if (fechaInput && !fechaInput.value) {
        fechaInput.value = new Date().toISOString().split('T')[0];
    }

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Deshabilitar botón y mostrar loader
            submitBtn.disabled = true;
            btnText.textContent = 'Generando...';
            loader.style.display = 'inline-block';
            resultDiv.style.display = 'none';

            try {
                const formData = new FormData(form);
                const response = await fetch('generate.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <h3>✓ Certificado Generado Exitosamente</h3>
                        <p><strong>Código:</strong> ${result.codigo}</p>
                        <p><strong>URL de Verificación:</strong></p>
                        <input type="text" value="${result.url_verificacion}" 
                               onclick="this.select()" readonly 
                               style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px;">
                        <div class="result-links">
                            <a href="../uploads/${result.imagen}" class="btn btn-secondary" download>
                                📄 Descargar Imagen
                            </a>
                            <a href="../uploads/${result.pdf}" class="btn btn-primary" download>
                                📋 Descargar PDF
                            </a>
                            <a href="verify.php?code=${result.codigo}" class="btn btn-secondary" target="_blank">
                                🔍 Ver Certificado
                            </a>
                        </div>
                    `;
                    
                    // Limpiar formulario
                    form.reset();
                    fechaInput.value = new Date().toISOString().split('T')[0];
                    
                    // Recargar lista de certificados
                    loadCertificates();
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <h3>✗ Error al Generar Certificado</h3>
                        <p>${result.error || 'Error desconocido'}</p>
                    `;
                }

                resultDiv.style.display = 'block';

            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <h3>✗ Error de Conexión</h3>
                    <p>No se pudo conectar con el servidor. Por favor, verifica tu conexión.</p>
                    <p><small>${error.message}</small></p>
                `;
                resultDiv.style.display = 'block';
            } finally {
                // Rehabilitar botón
                submitBtn.disabled = false;
                btnText.textContent = 'Generar Certificado';
                loader.style.display = 'none';
            }
        });
    }

    // Cargar lista de certificados
    loadCertificates();
});

async function loadCertificates() {
    const listDiv = document.getElementById('certificatesList');
    if (!listDiv) return;

    try {
        const response = await fetch('list.php?limit=10');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            listDiv.innerHTML = result.data.map(cert => `
                <div class="cert-item">
                    <div class="cert-item-header">
                        <span class="cert-code">${cert.codigo}</span>
                        <span class="cert-date">${formatDate(cert.fecha)}</span>
                    </div>
                    <div class="cert-name">${escapeHtml(cert.nombre)}</div>
                    <div class="cert-reason">${escapeHtml(cert.razon)}</div>
                    <div style="margin-top: 10px;">
                        <a href="verify.php?code=${cert.codigo}" 
                           class="btn btn-secondary" 
                           style="padding: 8px 15px; font-size: 0.9em;"
                           target="_blank">
                            Ver Certificado
                        </a>
                    </div>
                </div>
            `).join('');
        } else {
            listDiv.innerHTML = '<p class="loading">No hay certificados generados aún</p>';
        }
    } catch (error) {
        listDiv.innerHTML = '<p class="loading">Error al cargar certificados</p>';
        console.error('Error:', error);
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
