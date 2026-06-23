/**
 * SysMarket v2 — Social Commerce JavaScript
 * Maneja AJAX para likes, comentarios y publicaciones.
 */
(function () {
    'use strict';

    function showToast(message, type) {
        type = type || 'info';
        const colors = { success: '#10B981', error: '#EC4899', info: '#3B82F6' };
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            background: ${colors[type] || colors.info};
            color: white; padding: 12px 20px; border-radius: 10px;
            font-weight: 600; box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            opacity: 0; transform: translateY(-10px);
            transition: opacity 0.3s, transform 0.3s;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    async function postForm(url, payload) {
        const formData = new FormData();
        Object.keys(payload).forEach(k => formData.append(k, payload[k]));
        const r = await fetch(url, { method: 'POST', body: formData });
        const data = await r.json();
        return { ok: r.ok, status: r.status, data };
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // ====== Botón de like ======
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.sm-like-btn');
        if (!btn) return;
        e.preventDefault();
        const productoId = parseInt(btn.dataset.productoId, 10);
        if (!productoId) return;

        btn.disabled = true;
        const { ok, status, data } = await postForm('/api/like_toggle.php', {
            producto_id: productoId
        });
        btn.disabled = false;

        if (!ok) {
            if (status === 401) {
                showToast('Inicia sesión para dar like', 'error');
                setTimeout(() => window.location.href = '/login.php', 1200);
            } else {
                showToast(data.msg || 'Error al procesar el like', 'error');
            }
            return;
        }

        const countEl = btn.querySelector('.like-count');
        if (countEl) countEl.textContent = data.total;

        if (data.liked) {
            btn.classList.add('liked');
            showToast('Te gusta este producto', 'success');
        } else {
            btn.classList.remove('liked');
            showToast('Like eliminado', 'info');
        }
    });

    // ====== Formulario de comentario ======
    const frmComentario = document.getElementById('frmComentario');
    if (frmComentario) {
        frmComentario.addEventListener('submit', async function (e) {
            e.preventDefault();
            const fd = new FormData(frmComentario);
            const payload = {
                producto_id: parseInt(fd.get('producto_id'), 10),
                rating:      parseInt(fd.get('rating'), 10),
                comentario:  fd.get('comentario').trim()
            };

            if (!payload.rating) {
                showToast('Selecciona una calificación', 'error');
                return;
            }
            if (payload.comentario.length < 5) {
                showToast('El comentario es muy corto', 'error');
                return;
            }

            const btn = frmComentario.querySelector('button[type=submit]');
            btn.disabled = true;
            const original = btn.innerHTML;
            btn.textContent = 'Publicando...';

            const { ok, status, data } = await postForm('/api/comment_add.php', payload);
            btn.disabled = false;
            btn.innerHTML = original;

            if (!ok) {
                if (status === 401) {
                    showToast('Inicia sesión para comentar', 'error');
                    setTimeout(() => window.location.href = '/login.php', 1200);
                } else {
                    showToast(data.msg || 'No se pudo publicar', 'error');
                }
                return;
            }

            showToast('Reseña publicada', 'success');
            frmComentario.reset();

            const lista = document.getElementById('reviewList');
            if (lista) {
                const c = data.comentario;
                const fullStars = c.rating;
                const emptyStars = 5 - fullStars;
                let starsHtml = '';
                for (let i = 0; i < fullStars; i++) starsHtml += '<i class="bi bi-star-fill"></i>';
                for (let i = 0; i < emptyStars; i++) starsHtml += '<i class="bi bi-star"></i>';

                const el = document.createElement('div');
                el.className = 'sm-review-item';
                el.innerHTML = `
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-semibold">${escapeHtml(c.user_nombre)} ${escapeHtml(c.user_apellido)}</span>
                        <span class="small text-secondary">${escapeHtml(c.creado_en)}</span>
                    </div>
                    <div class="sm-stars mb-2">${starsHtml}</div>
                    <p class="text-secondary mb-0">${escapeHtml(c.comentario)}</p>
                `;
                lista.prepend(el);

                // Quitar mensaje "aún no hay reseñas" si existe
                const empty = lista.querySelector('.empty-state');
                if (empty) empty.remove();
            }
        });
    }

    // ====== Formulario de publicación ======
    const frmPub = document.getElementById('frmPublicacion');
    if (frmPub) {
        frmPub.addEventListener('submit', async function (e) {
            e.preventDefault();
            const fd = new FormData(frmPub);
            const payload = {
                titulo:      fd.get('titulo').trim(),
                contenido:   fd.get('contenido').trim(),
                producto_id: fd.get('producto_id') || ''
            };

            if (payload.titulo.length < 5) {
                showToast('El título es muy corto', 'error');
                return;
            }
            if (payload.contenido.length < 10) {
                showToast('El contenido es muy corto', 'error');
                return;
            }

            const btn = frmPub.querySelector('button[type=submit]');
            btn.disabled = true;
            const original = btn.innerHTML;
            btn.textContent = 'Publicando...';

            const { ok, data } = await postForm('/api/post_add.php', payload);
            btn.disabled = false;
            btn.innerHTML = original;

            if (!ok) {
                showToast(data.msg || 'No se pudo publicar', 'error');
                return;
            }

            showToast('Publicación creada', 'success');
            setTimeout(() => window.location.reload(), 800);
        });
    }
})();
