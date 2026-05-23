/* SysMarket — JS global del frontend */

// ── Toast helper ────────────────────────────────────────────────
function smToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `sm-toast ${type}`;
  const icon = type === 'success' ? 'check-circle-fill' :
               type === 'error'   ? 'exclamation-circle-fill' : 'info-circle-fill';
  t.innerHTML = `<i class="bi bi-${icon}"></i><span>${msg}</span>`;
  document.body.appendChild(t);
  setTimeout(() => {
    t.style.transition = 'opacity .3s, transform .3s';
    t.style.opacity = '0';
    t.style.transform = 'translateX(100%)';
    setTimeout(() => t.remove(), 350);
  }, 2800);
}

// ── Carrito: agregar producto vía AJAX ──────────────────────────
async function smAddToCart(productId, btn) {
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i>'; }
  try {
    const fd = new FormData();
    fd.append('id_producto', productId);
    fd.append('cantidad', 1);
    const res = await fetch('/api/cart_add.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      smToast('Producto agregado al carrito', 'success');
      const badge = document.querySelector('.sm-cart-badge');
      if (badge) badge.textContent = data.cart_count;
      else if (data.cart_count > 0) {
        const cartBtn = document.querySelector('.sm-cart-btn');
        if (cartBtn) {
          const span = document.createElement('span');
          span.className = 'sm-cart-badge';
          span.textContent = data.cart_count;
          cartBtn.appendChild(span);
        }
      }
    } else {
      smToast(data.msg || 'Error al agregar', 'error');
    }
  } catch (e) {
    smToast('Inicia sesión para comprar', 'error');
    setTimeout(() => location.href = '/login.php', 1200);
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Agregar';
    }
  }
}

// ── Carrito: actualizar cantidad ────────────────────────────────
async function smCartUpdate(productId, delta) {
  const fd = new FormData();
  fd.append('id_producto', productId);
  fd.append('delta', delta);
  const res = await fetch('/api/cart_update.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.ok) location.reload();
  else smToast(data.msg || 'Error', 'error');
}

// ── Carrito: eliminar ───────────────────────────────────────────
async function smCartRemove(productId) {
  if (!confirm('¿Quitar este producto del carrito?')) return;
  const fd = new FormData();
  fd.append('id_producto', productId);
  const res = await fetch('/api/cart_remove.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.ok) location.reload();
}

// ── Activar animaciones stagger al cargar ───────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sm-stagger').forEach(el => {
    el.querySelectorAll(':scope > *').forEach((c, i) => {
      c.style.animationDelay = (i * 0.06) + 's';
    });
  });
});
