(function () {
  'use strict';
  const root = document.querySelector('.sim-pos-template'); if (!root) return;
  const data = window.SIM_POS_DATA || { categories: [], assets: {} };
  const assets = data.assets || {};
  const categories = (data.categories || []).map(c => ({ ...c, items: (c.items || []).map(i => ({ ...i, meta: null })) }));
  const money = n => 'Rp ' + Math.round(Number(n || 0)).toLocaleString('id-ID');
  const $ = sel => document.querySelector(sel);
  const $$ = sel => Array.from(document.querySelectorAll(sel));
  const esc = str => String(str ?? '').replace(/[&<>'"]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[m]));
  const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  const has = (s, arr) => arr.some(x => norm(s).includes(norm(x)));
  const findCat = id => categories.find(c => Number(c.id) === Number(id)) || categories[0] || null;
  const isChickenCat = cat => cat && has(cat.name + ' ' + cat.slug, ['ayam', 'chicken', 'crispy', 'crips']);
  const iconBase = (() => new URL('../tabler-sprite.svg', document.currentScript?.src || window.location.href).href)();
  const mapIconName = (name) => {
    let clean = String(name || '').replace(/^ti ti-/, '').replace(/^ti-/, '');
    if (clean.endsWith('-filled')) clean = `filled-${clean.slice(0, -7)}`;
    if (clean === 'cash-register') clean = 'cash-banknote';
    return clean;
  };
  const svgIcon = (name, cls = '') => `<svg class="${['sim-icon', cls].filter(Boolean).join(' ')}" width="1.25em" height="1.25em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="display:inline-block;vertical-align:-0.125em;flex:0 0 auto"><use href="${iconBase}#tabler-${mapIconName(name)}"></use></svg>`;

  const cartRows = $('#cartRows'), emptyCart = $('#emptyCart'), cartTableWrap = $('#cartTableWrap');
  const itemCount = $('#itemCount'), subtotalText = $('#subtotalText'), totalText = $('#totalText'), taxPreview = $('#taxPreview'), changeText = $('#changeText'), cartJson = $('#cartJson');
  const discountAmount = $('#discountAmount'), paidAmount = $('#paidAmount'), paymentMethod = $('#paymentMethod');
  const activeCategoryLabel = $('#activeCategoryLabel'), visibleProductInfo = $('#visibleProductInfo');
  const productGrid = $('#productGrid'), searchInput = $('#posSearch'), flowBar = $('#flowBar'), flowBack = $('#flowBack'), resetFlow = $('#resetFlow'), posMessage = $('#posMessage');
  const posActionFooter = $('#posActionFooter');
  let cart = [];
  let state = { catId: categories[0]?.id || null, step: 'parts', part: null, style: null, sauce: null, rice: null };

  const partDefs = [
    { key: 'dada', label: 'Dada', img: assets.dada, match: ['dada'] },
    { key: 'paha_atas', label: 'Paha Atas', img: assets.paha_atas, match: ['paha atas', 'atas'] },
    { key: 'paha_bawah', label: 'Paha Bawah', img: assets.paha_bawah, match: ['paha bawah', 'bawah'] },
    { key: 'sayap', label: 'Sayap', img: assets.sayap, match: ['sayap', 'wing'] },
    { key: 'crispy', label: 'Chicken Crispy', img: assets.original, match: ['chicken crips', 'chicken crisp', 'ayam crispy', 'crispy', 'original'] }
  ];
  const sauceDefs = [
    { key: 'keju', label: 'Keju', img: assets.keju, match: ['keju', 'cheese'] },
    { key: 'lada_hitam', label: 'Lada Hitam', img: assets.lada_hitam, match: ['lada hitam', 'black pepper', 'blackpepper'] },
    { key: 'teriyaki', label: 'Teriyaki', img: assets.teriyaki, match: ['teriyaki'] },
    { key: 'sadis', label: 'Sadis / Pedas', img: assets.sadis, match: ['sadis', 'geprek', 'pedas', 'spicy'] },
    { key: 'bbq', label: 'BBQ Spicy', img: assets.bbq, match: ['bbq'] },
    { key: 'mentai', label: 'Mentai / Mayo', img: assets.mentai, match: ['mentai', 'mayo', 'mayonnaise'] }
  ];

  function meta(item) {
    if (item.meta) return item.meta;
    const t = norm(`${item.full_name || ''} ${item.name || ''} ${item.product_name || ''} ${item.variant_name || ''}`);
    let part = partDefs.find(p => has(t, p.match));
    if (has(t, ['paha atas'])) part = partDefs[1];
    if (has(t, ['paha bawah'])) part = partDefs[2];
    const sauce = sauceDefs.find(s => has(t, s.match));
    const style = (sauce || has(t, ['saus', 'celup', 'plus sauce'])) && !has(t, ['original tanpa saus']) ? 'sauce' : 'original';
    let rice = null;
    if (has(t, ['tanpa nasi', 'no rice'])) rice = 0;
    else if (has(t, ['plus nasi', '+ nasi', 'pakai nasi', 'nasi'])) rice = 1;
    item.meta = { text: t, part: part?.key || 'crispy', partLabel: part?.label || 'Chicken Crispy', style, sauce: sauce?.key || null, sauceLabel: sauce?.label || '', rice };
    return item.meta;
  }
  categories.forEach(c => c.items.forEach(meta));

  function currentCat() { return findCat(state.catId); }
  function setMessage(msg, type = 'info') {
    if (!posMessage) return;
    if (!msg) { posMessage.style.display = 'none'; posMessage.innerHTML = ''; return; }
    posMessage.style.display = 'block';
    posMessage.className = 'sim-pos-message mb-3 ' + type;
    posMessage.innerHTML = msg;
  }
  function setActiveCat(id) {
    state = { catId: Number(id), step: 'parts', part: null, style: null, sauce: null, rice: null };
    $$('.sim-pos-tabs li').forEach(t => t.classList.toggle('active', Number(t.dataset.cat) === Number(id)));
    renderMain();
  }
  function stepIndex() { return ['parts', 'style', 'sauce', 'rice'].indexOf(state.step); }
  function renderFlow() {
    const cat = currentCat();
    if (!flowBar) return;
    if (!isChickenCat(cat)) { flowBar.innerHTML = ''; if (flowBack) flowBack.style.display = 'none'; return; }
    const steps = [['parts', 'Bagian Ayam'], ['style', 'Original / Saus'], ['sauce', 'Pilih Saus'], ['rice', 'Nasi']];
    const idx = stepIndex();
    flowBar.innerHTML = '<div class="k2-flow">' + steps.map((s, i) => `<span class="${i === idx ? 'active' : (i < idx ? 'done' : '')}">${esc(s[1])}</span>`).join('') + '</div>';
    if (flowBack) flowBack.style.display = idx > 0 ? 'inline-flex' : 'none';
  }
  function optionCard({ cls = '', attrs = '', img = '', label = '', sub = '', price = '', disabled = false }) {
    return `<button type="button" class="sim-kasir2-card ${cls}" ${attrs} ${disabled ? 'disabled' : ''}>
      <span class="sim-kasir2-img"><img src="${esc(img || assets.dummy || '')}" alt="${esc(label)}" onerror="this.src='${esc(assets.dummy || '')}'"></span>
      <span class="sim-kasir2-cat">${esc(sub || "Lumero Menu")}</span>
      <strong title="${esc(label)}">${esc(label)}</strong>
      <span class="sim-kasir2-bottom"><b>${price ? esc(price) : ''}</b><em>${disabled ? 'Tidak tersedia' : 'Pilih'}</em></span>
    </button>`;
  }
  function productCard(item) {
    const disabled = Number(item.price || 0) <= 0;
    return optionCard({
      cls: 'choose-variant',
      attrs: `data-id="${Number(item.variant_id)}"`,
      img: item.image,
      label: item.name || item.full_name,
      sub: 'Sekali klik masuk keranjang',
      price: money(item.price),
      disabled
    });
  }
  function renderSimple(cat) {
    const q = norm(searchInput?.value || '');
    const items = (cat.items || []).filter(i => !q || norm(`${i.full_name} ${i.name}`).includes(q));
    if (activeCategoryLabel) activeCategoryLabel.textContent = cat.name;
    if (visibleProductInfo) visibleProductInfo.textContent = ' | ' + items.length + ' varian tersedia';
    renderFlow();
    setMessage('');
    productGrid.innerHTML = items.length
      ? items.map(productCard).join('')
      : '<div class="sim-empty-panel">Tidak ada varian yang cocok dengan pencarian.</div>';
  }
  function chickenItems() { return (currentCat()?.items || []).filter(i => Number(i.price || 0) > 0); }
  function availableParts() {
    const items = chickenItems();
    const out = [];
    partDefs.forEach(p => {
      const count = items.filter(i => meta(i).part === p.key).length;
      if (count > 0) out.push({ ...p, count });
    });
    return out;
  }
  function availableStyles() {
    const items = chickenItems().filter(i => meta(i).part === state.part);
    const hasOriginal = items.some(i => meta(i).style === 'original');
    const hasSauce = items.some(i => meta(i).style === 'sauce');
    const arr = [];
    if (hasOriginal) arr.push({ key: 'original', label: 'Original', img: assets.original, sub: 'Tanpa saus tambahan' });
    if (hasSauce) arr.push({ key: 'sauce', label: 'Plus Saus', img: assets.sauce, sub: 'Pilih saus favorit' });
    return arr;
  }
  function availableSauces() {
    const items = chickenItems().filter(i => meta(i).part === state.part && meta(i).style === 'sauce');
    const arr = [];
    sauceDefs.forEach(s => {
      const count = items.filter(i => meta(i).sauce === s.key).length;
      if (count > 0) arr.push({ ...s, count });
    });
    return arr;
  }
  function matchingRiceOptions() {
    let items = chickenItems().filter(i => meta(i).part === state.part && meta(i).style === state.style);
    if (state.style === 'sauce') items = items.filter(i => meta(i).sauce === state.sauce);
    const no = items.find(i => meta(i).rice === 0) || items.find(i => meta(i).rice === null);
    const yes = items.find(i => meta(i).rice === 1);
    const opts = [];
    if (no) opts.push({ key: 0, label: 'Tanpa Nasi', img: assets.rice_no, item: no, sub: 'Item langsung masuk keranjang' });
    if (yes) opts.push({ key: 1, label: 'Plus Nasi', img: assets.rice_yes, item: yes, sub: 'Item langsung masuk keranjang' });
    return opts;
  }
  function renderChicken() {
    const cat = currentCat();
    if (activeCategoryLabel) activeCategoryLabel.textContent = cat.name;
    renderFlow();
    setMessage('');
    if (state.step === 'parts') {
      const parts = availableParts();
      if (visibleProductInfo) visibleProductInfo.textContent = ' | pilih bagian ayam terlebih dahulu';
      productGrid.innerHTML = parts.length
        ? parts.map(p => optionCard({ cls: 'choose-part', attrs: `data-part="${p.key}"`, img: p.img, label: p.label, sub: `${p.count} varian tersedia` })).join('')
        : '<div class="sim-empty-panel">Belum ada varian ayam pada kategori ini.</div>';
      return;
    }
    if (state.step === 'style') {
      const styles = availableStyles();
      if (visibleProductInfo) visibleProductInfo.textContent = ' | ' + (partDefs.find(p => p.key === state.part)?.label || 'Bagian ayam');
      productGrid.innerHTML = styles.map(s => optionCard({ cls: 'choose-style', attrs: `data-style="${s.key}"`, img: s.img, label: s.label, sub: s.sub })).join('');
      return;
    }
    if (state.step === 'sauce') {
      const sauces = availableSauces();
      if (visibleProductInfo) visibleProductInfo.textContent = ' | pilih saus';
      productGrid.innerHTML = sauces.length
        ? sauces.map(s => optionCard({ cls: 'choose-sauce', attrs: `data-sauce="${s.key}"`, img: s.img, label: s.label, sub: `${s.count} varian tersedia` })).join('')
        : '<div class="sim-empty-panel">Saus untuk pilihan ini belum tersedia.</div>';
      return;
    }
    if (state.step === 'rice') {
      const opts = matchingRiceOptions();
      if (visibleProductInfo) visibleProductInfo.textContent = ' | pilih nasi lalu masuk keranjang';
      productGrid.innerHTML = opts.length
        ? opts.map(o => optionCard({ cls: 'choose-rice', attrs: `data-rice="${o.key}" data-variant="${Number(o.item.variant_id)}"`, img: o.img, label: o.label, sub: o.item.name, price: money(o.item.price) })).join('')
        : '<div class="sim-empty-panel">Varian final belum cocok. Gunakan pencarian produk atau cek master produk.</div>';
      if (!opts.length) {
        const fallback = chickenItems().filter(i => meta(i).part === state.part && meta(i).style === state.style && (state.style !== 'sauce' || meta(i).sauce === state.sauce));
        if (fallback.length) productGrid.innerHTML += fallback.map(productCard).join('');
      }
    }
  }
  function renderMain() {
    const cat = currentCat();
    if (!cat) return;
    if (isChickenCat(cat)) renderChicken(); else renderSimple(cat);
  }
  function goBack() {
    if (state.step === 'rice') { state.step = state.style === 'sauce' ? 'sauce' : 'style'; state.rice = null; }
    else if (state.step === 'sauce') { state.step = 'style'; state.sauce = null; }
    else if (state.step === 'style') { state.step = 'parts'; state.style = null; state.part = null; }
    renderMain();
  }
  function addItem(item) {
    const found = cart.find(i => Number(i.variant_id) === Number(item.variant_id));
    if (found) {
      found.qty += 1;
    } else {
      cart.push({
        variant_id: Number(item.variant_id),
        name: item.name || item.full_name,
        product_name: item.product_name || '',
        variant_name: item.variant_name || '',
        sku: item.sku || '',
        price: Number(item.price || 0),
        image: item.image || assets.dummy || '',
        qty: 1
      });
    }
    renderCart();
    setMessage(`<strong>${esc(item.name || item.full_name)}</strong> berhasil masuk keranjang.`, 'success');
  }
  function addVariantById(id) {
    let found = null;
    categories.some(c => {
      found = (c.items || []).find(i => Number(i.variant_id) === Number(id));
      return !!found;
    });
    if (found) addItem(found);
  }
  function calc() {
    const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const disc = Math.min(Number(discountAmount?.value || 0), subtotal);
    const tax = 0;
    const total = Math.max(0, subtotal - disc + tax);
    const totalQty = cart.reduce((s, i) => s + i.qty, 0);
    if (subtotalText) subtotalText.textContent = money(subtotal);
    if (totalText) totalText.textContent = money(total);
    if (taxPreview) taxPreview.textContent = money(tax);
    if (changeText) changeText.textContent = money(Math.max(0, Number(paidAmount?.value || 0) - total));
    if (itemCount) itemCount.textContent = totalQty;
    if (cartJson) cartJson.value = JSON.stringify(cart.map(i => ({ variant_id: i.variant_id, qty: i.qty })));
    const ic2 = document.querySelector('#itemCount2'); if (ic2) ic2.textContent = totalQty;
    const tt2 = document.querySelector('#totalText2'); if (tt2) tt2.textContent = money(total);
    const pay = paymentMethod ? paymentMethod.value : 'cash';
    const paidSec = document.getElementById('simPaidSection') || document.querySelector('.sim-paid-section');
    const qrisBox = document.getElementById('simQrisBox') || document.querySelector('.sim-qris-section');
    if (paidSec) paidSec.style.display = (pay === 'cash') ? '' : 'none';
    if (qrisBox) qrisBox.style.display = (pay === 'qris') ? 'block' : 'none';
  }
  function lineMetaText(item) {
    const metaParts = [];
    if (item.product_name) metaParts.push(item.product_name);
    if (item.variant_name && item.variant_name !== item.product_name) metaParts.push(item.variant_name);
    if (item.sku) metaParts.push(`SKU ${item.sku}`);
    return metaParts.join(' | ');
  }
  function renderCart() {
    if (!cart.length) {
      if (emptyCart) emptyCart.style.display = 'block';
      if (cartTableWrap) cartTableWrap.style.display = 'none';
      if (cartRows) cartRows.innerHTML = '';
      calc();
      return;
    }
    if (emptyCart) emptyCart.style.display = 'none';
    if (cartTableWrap) cartTableWrap.style.display = 'block';
    if (cartRows) cartRows.innerHTML = cart.map((i, idx) => {
      return `<div class="sim-cart-item">
        <div class="sim-cart-item-left">
          <img class="sim-cart-thumb" src="${esc(i.image || assets.dummy || '')}" alt="${esc(i.name)}" onerror="this.src='${esc(assets.dummy || '')}'">
          <div class="sim-cart-item-text">
            <span class="sim-cart-item-name">${esc(i.name)}</span>
            <span class="sim-cart-item-price">${money(i.price)}</span>
          </div>
        </div>
        <div class="sim-cart-item-right">
          <button type="button" class="sim-qty-btn sim-qty-minus" data-minus="${idx}">−</button>
          <span class="sim-qty-val">${i.qty}</span>
          <button type="button" class="sim-qty-btn sim-qty-plus" data-plus="${idx}">+</button>
        </div>
      </div>`;
    }).join('');
    calc();
  }
  function initFooterAutoHide() {
    if (!posActionFooter) return;
    const mobileMedia = window.matchMedia('(max-width: 991.98px)');
    let lastY = window.scrollY || 0;
    let ticking = false;
    const update = () => {
      const y = window.scrollY || 0;
      if (!mobileMedia.matches) {
        posActionFooter.classList.remove('is-auto-hidden');
        lastY = y;
        return;
      }
      const delta = y - lastY;
      if (y <= 8 || delta < -4) {
        posActionFooter.classList.remove('is-auto-hidden');
      } else if (delta > 6) {
        posActionFooter.classList.add('is-auto-hidden');
      }
      lastY = y;
    };
    window.addEventListener('scroll', () => {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(() => {
        update();
        ticking = false;
      });
    }, { passive: true });
    window.addEventListener('resize', () => {
      if (!mobileMedia.matches) posActionFooter.classList.remove('is-auto-hidden');
    });
    update();
  }

  $$('.sim-pos-tabs li').forEach(tab => tab.addEventListener('click', () => setActiveCat(tab.dataset.cat)));
  productGrid?.addEventListener('click', e => {
    const btn = e.target.closest('button'); if (!btn) return;
    if (btn.classList.contains('choose-part')) { state.part = btn.dataset.part; state.step = 'style'; renderMain(); return; }
    if (btn.classList.contains('choose-style')) { state.style = btn.dataset.style; state.step = state.style === 'sauce' ? 'sauce' : 'rice'; renderMain(); return; }
    if (btn.classList.contains('choose-sauce')) { state.sauce = btn.dataset.sauce; state.step = 'rice'; renderMain(); return; }
    if (btn.classList.contains('choose-rice')) { addVariantById(btn.dataset.variant); state = { ...state, step: 'parts', part: null, style: null, sauce: null, rice: null }; renderMain(); return; }
    if (btn.classList.contains('choose-variant')) { addVariantById(btn.dataset.id); return; }
  });
  flowBack?.addEventListener('click', goBack);
  resetFlow?.addEventListener('click', () => { state = { catId: state.catId, step: 'parts', part: null, style: null, sauce: null, rice: null }; if (searchInput) searchInput.value = ''; renderMain(); });
  searchInput?.addEventListener('input', () => { const cat = currentCat(); if (isChickenCat(cat) && norm(searchInput.value)) { renderSimple(cat); } else renderMain(); });
  document.addEventListener('click', e => {
    const plus = e.target.closest('[data-plus]'), minus = e.target.closest('[data-minus]'), remove = e.target.closest('[data-remove]');
    if (plus) {
      const idx = Number(plus.dataset.plus);
      if (cart[idx]) { cart[idx].qty += 1; renderCart(); }
    }
    if (minus) {
      const idx = Number(minus.dataset.minus);
      if (cart[idx]) { cart[idx].qty -= 1; if (cart[idx].qty <= 0) cart.splice(idx, 1); renderCart(); }
    }
    if (remove) {
      const idx = Number(remove.dataset.remove);
      if (cart[idx]) { cart.splice(idx, 1); renderCart(); }
    }
  });
  ['#clearCart', '#clearCart2', '#resetOrder'].forEach(sel => $(sel)?.addEventListener('click', () => { cart = []; renderCart(); }));
  $('#resetDiscount')?.addEventListener('click', () => { if (discountAmount) discountAmount.value = 0; calc(); });
  $$('.payment-item[data-pay]').forEach(btn => btn.addEventListener('click', () => {
    $$('.payment-item[data-pay]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (paymentMethod) paymentMethod.value = btn.dataset.pay;
    if (paidAmount && btn.dataset.pay !== 'cash') paidAmount.value = '';
    calc();
  }));
  discountAmount?.addEventListener('input', calc);
  paidAmount?.addEventListener('input', calc);
  let pendingCheckoutEvent = null;
  let verifiedMember = null;

  $('#checkoutForm')?.addEventListener('submit', e => {
    e.preventDefault();
    if (!cart.length) {
      alert('Keranjang masih kosong.');
      return;
    }
    calc();

    pendingCheckoutEvent = {
      form: e.currentTarget,
      submitBtn: e.currentTarget.querySelector('button[type="submit"]') || document.querySelector('.sim-checkout-btn')
    };

    // Open Member Check Modal
    verifiedMember = null;
    const phoneInput = document.getElementById('memberCheckPhone');
    const resultDiv = document.getElementById('memberCheckResult');
    const confirmBtn = document.getElementById('btnConfirmMemberCheckout');
    if (phoneInput) phoneInput.value = '';
    if (resultDiv) resultDiv.className = 'd-none';
    if (confirmBtn) confirmBtn.classList.add('d-none');

    const memberModalEl = document.getElementById('simPosMemberModal');
    if (memberModalEl && window.bootstrap) {
      const modal = new bootstrap.Modal(memberModalEl);
      modal.show();
      setTimeout(() => phoneInput?.focus(), 300);
    } else {
      executePosCheckout(null);
    }
  });

  const btnCheckMember = document.getElementById('btnCheckMember');
  const phoneInput = document.getElementById('memberCheckPhone');
  const resultDiv = document.getElementById('memberCheckResult');
  const confirmBtn = document.getElementById('btnConfirmMemberCheckout');

  function performMemberCheck() {
    const phone = phoneInput?.value.trim();
    if (!phone) {
      alert('Masukkan nomor HP/WhatsApp member.');
      return;
    }
    btnCheckMember.disabled = true;
    btnCheckMember.innerHTML = 'Mencari...';

    const basePath = window.location.pathname.replace(/\/pos\/?.*$/, '');
    const targetUrl = basePath + '/pos/check-member?phone=' + encodeURIComponent(phone);

    fetch(targetUrl, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json())
      .then(data => {
        btnCheckMember.disabled = false;
        btnCheckMember.innerHTML = 'Cek Member';
        if (data.success && data.found && data.member) {
          verifiedMember = data.member;
          resultDiv.className = 'alert alert-success border-0 shadow-sm p-3 my-3';
          resultDiv.innerHTML = `
          <div class="d-flex align-items-center justify-content-between mb-1">
            <strong class="fs-14">${data.member.name}</strong>
            <span class="badge bg-success">Member Valid</span>
          </div>
          <div class="small text-muted mb-1">No HP: ${data.member.phone}</div>
          <div class="small fw-bold text-dark">Poin Aktif: <span class="text-success">${data.member.points} Poin</span></div>
        `;
          confirmBtn.classList.remove('d-none');
        } else {
          verifiedMember = null;
          resultDiv.className = 'alert alert-warning border-0 shadow-sm p-3 my-3';
          resultDiv.innerHTML = `
          <div class="fw-bold small text-dark mb-1"><i class="ti ti-alert-circle"></i> Member Tidak Ditemukan</div>
          <div class="small text-muted">Nomor belum terdaftar. Anda tetap dapat melanjutkan tanpa member.</div>
        `;
          confirmBtn.classList.add('d-none');
        }
      })
      .catch(err => {
        btnCheckMember.disabled = false;
        btnCheckMember.innerHTML = 'Cek Member';
        alert('Terjadi kesalahan koneksi saat mengecek member.');
      });
  }

  btnCheckMember?.addEventListener('click', performMemberCheck);
  phoneInput?.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      performMemberCheck();
    }
  });

  confirmBtn?.addEventListener('click', () => {
    const modalEl = document.getElementById('simPosMemberModal');
    if (modalEl && window.bootstrap) bootstrap.Modal.getInstance(modalEl)?.hide();
    executePosCheckout(verifiedMember);
  });

  document.getElementById('btnSkipMemberCheckout')?.addEventListener('click', () => {
    const modalEl = document.getElementById('simPosMemberModal');
    if (modalEl && window.bootstrap) bootstrap.Modal.getInstance(modalEl)?.hide();
    executePosCheckout(null);
  });

  function executePosCheckout(memberData) {
    if (!pendingCheckoutEvent) return;
    const form = pendingCheckoutEvent.form;
    const submitBtn = pendingCheckoutEvent.submitBtn;
    const origText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Memproses Transaksi...';
    }

    const formData = new FormData(form);
    formData.append('ajax', '1');
    if (memberData && memberData.id) {
      formData.append('member_id', memberData.id);
      formData.append('customer_phone', memberData.phone || '');
    }

    fetch(form.action || window.location.href, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }
        if (data.success && data.qris_url) {
          showQrisModal(data.qris_url, data.qris_string, data.order_number, data.grand_total, data.receipt_url);
        } else if (data.success && data.receipt_url) {
          showReceiptPopupModal(data.receipt_url, data.order_number);
        } else {
          alert('Gagal memproses transaksi: ' + (data.message || 'Error tidak diketahui'));
        }
      })
      .catch(err => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }
        alert('Terjadi kesalahan koneksi saat memproses transaksi.');
      });
  }

  window.showReceiptPopupModal = function (receiptUrl, orderNo) {
    const modalEl = document.getElementById('simPosReceiptModal');
    const frame = document.getElementById('simReceiptFrame');
    const orderBadge = document.getElementById('posReceiptOrderNo');
    if (orderBadge && orderNo) orderBadge.textContent = orderNo;
    if (frame && receiptUrl) {
      const embedUrl = receiptUrl + (receiptUrl.includes('?') ? '&' : '?') + 'embed=1';
      frame.src = embedUrl;
    }
    if (modalEl && window.bootstrap) {
      const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
      bsModal.show();
    }
  };

  window.printSimReceipt = function () {
    const frame = document.getElementById('simReceiptFrame');
    if (frame && frame.contentWindow) {
      try {
        frame.contentWindow.focus();
        frame.contentWindow.print();
      } catch (err) {
        if (frame.src) window.open(frame.src, '_blank');
      }
    }
  };

  window.resetPosCartAfterOrder = function () {
    cart = [];
    renderCart();
    const modalEl = document.getElementById('simPosReceiptModal');
    if (modalEl && window.bootstrap) {
      try {
        const bsModal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        if (bsModal) bsModal.hide();
      } catch (e) { }
    }
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    const paidInput = document.getElementById('paidAmount');
    if (paidInput) paidInput.value = '';
    const notesInput = document.querySelector('textarea[name="notes"]');
    if (notesInput) notesInput.value = '';
    calc();
  };

  function showQrisModal(qrUrl, qrString, orderNo, totalAmount, receiptUrl) {
    let oldModal = document.getElementById('simQrisDirectModal');
    if (oldModal) oldModal.remove();

    const formattedAmount = money(totalAmount);
    const modalHtml = `
      <div id="simQrisDirectModal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.75);z-index:999999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
        <div style="background:#fff;border-radius:18px;max-width:420px;width:94%;padding:28px;text-align:center;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);position:relative;animation:fadeInDown 0.25s ease;">
          <h4 style="margin:0 0 6px;font-weight:700;color:#1e293b;font-size:20px;">Scan Pembayaran QRIS</h4>
          <p style="margin:0 0 16px;color:#64748b;font-size:14px;">Order <strong style="color:#0f172a;">#${esc(orderNo)}</strong> — <span style="color:#2563eb;font-weight:700;">${formattedAmount}</span></p>
          
          <div style="background:#f8fafc;border:2px dashed #cbd5e1;border-radius:14px;padding:16px;display:inline-block;margin-bottom:12px;">
            <img src="${esc(qrUrl)}" alt="QR Code QRIS" style="width:230px;height:230px;display:block;margin:0 auto;border-radius:8px;">
          </div>

          ${qrUrl ? `
          <div style="margin-bottom:16px;">
            <button type="button" id="btnCopyQrisString" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;margin-right:6px;">
              📋 Salin URL Gambar QR (Untuk Simulator)
            </button>
            <a href="https://simulator.sandbox.midtrans.com/qris/index" target="_blank" style="display:inline-block;background:#e0e7ff;color:#3730a3;text-decoration:none;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;">
              🚀 Buka Simulator
            </a>
          </div>
          ` : ''}

          <p style="font-size:13px;color:#475569;margin-bottom:20px;line-height:1.4;">
            Silakan scan QR Code di atas menggunakan aplikasi e-Wallet / M-Banking. Untuk pengetesan Sandbox, klik <strong>Salin URL Gambar QR</strong> lalu paste di Simulator Midtrans.
          </p>

          <div style="display:flex;gap:10px;">
            <button type="button" id="btnQrisFinish" style="flex:1;background:#2563eb;color:#fff;border:none;padding:12px;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">
              Selesai / Cetak Struk
            </button>
            <button type="button" id="btnQrisCancel" style="background:#f1f5f9;color:#475569;border:none;padding:12px 16px;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">
              Tutup
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    if (qrUrl) {
      const copyBtn = document.getElementById('btnCopyQrisString');
      if (copyBtn) {
        copyBtn.addEventListener('click', () => {
          navigator.clipboard?.writeText(qrUrl);
          copyBtn.textContent = '✅ URL QR Disalin!';
          setTimeout(() => { copyBtn.textContent = '📋 Salin URL Gambar QR (Untuk Simulator)'; }, 2500);
        });
      }
    }

    document.getElementById('btnQrisFinish').addEventListener('click', () => {
      const el = document.getElementById('simQrisDirectModal');
      if (el) el.remove();
      showReceiptPopupModal(receiptUrl, orderNo);
    });
    document.getElementById('btnQrisCancel').addEventListener('click', () => {
      const el = document.getElementById('simQrisDirectModal');
      if (el) el.remove();
      showReceiptPopupModal(receiptUrl, orderNo);
    });
  }
  $('#btnFullscreen')?.addEventListener('click', () => { if (!document.fullscreenElement) document.documentElement.requestFullscreen?.(); else document.exitFullscreen?.(); });
  if (window.jQuery && jQuery.fn.select2) { jQuery('.select').select2({ minimumResultsForSearch: Infinity, width: '100%' }); }
  function clock() { const d = new Date(); const t = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, '.'); const el = $('#posTopClock'); if (el) el.textContent = t; }
  clock();
  setInterval(clock, 1000);
  initFooterAutoHide();
  renderCart();
  renderMain();
})();
