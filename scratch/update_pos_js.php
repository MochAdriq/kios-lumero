<?php
$files = [
    'c:\xampp\htdocs\kios-lumero\public\assets\js\pos-preadmin.js',
    'c:\xampp\htdocs\kios-lumero\public\assets\js\self-order-ui.js'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    $content = file_get_contents($file);

    // 1. isIceCreamCat & Defs
    $iceDefs = <<<JS
const isIceCreamCat = cat => cat && has(cat.name + ' ' + cat.slug, ['es krim', 'ice cream', 'icecream']);
  const iceBaseDefs = [
    { key: 'vanilla', label: 'Vanilla', img: assets.dummy, match: ['vanilla'] },
    { key: 'coklat', label: 'Coklat', img: assets.dummy, match: ['coklat', 'chocolate'] },
    { key: 'strawberry', label: 'Strawberry', img: assets.dummy, match: ['strawberry'] }
  ];
  const icePackDefs = [
    { key: 'cup', label: 'Cup 14 oz', img: assets.dummy, match: ['cup 14 oz', 'cup'] },
    { key: 'cone', label: 'Cone Lumero', img: assets.dummy, match: ['lumero', 'cone'] }
  ];
  const iceToppingDefs = [
    { key: 'coklat', label: 'Topping Coklat', img: assets.dummy, match: ['topping coklat'] },
    { key: 'strawberry', label: 'Topping Strawberry', img: assets.dummy, match: ['topping strawberry'] },
    { key: 'matcha', label: 'Topping Matcha', img: assets.dummy, match: ['topping matcha'] }
  ];
JS;
    $content = preg_replace('/const isChickenCat = (.*?);/', "const isChickenCat = $1;\n  $iceDefs", $content);

    // 2. Initial state update
    $content = str_replace(
        "let state = { catId: categories[0]?.id || null, step: 'parts', part: null, style: null, sauce: null, rice: null };",
        "let state = { catId: categories[0]?.id || null, step: isIceCreamCat(categories[0]) ? 'base' : 'parts', part: null, style: null, sauce: null, rice: null, iceBase: null, icePack: null, iceTopping: null };",
        $content
    );

    // 3. Meta update
    $content = str_replace(
        "item.meta = { text: t, part: part?.key || 'crispy', partLabel: part?.label || 'Chicken Crips', style, sauce: sauce?.key || null, sauceLabel: sauce?.label || '', rice };",
        "let iceBase = iceBaseDefs.find(b => has(t, b.match));\n    let icePack = icePackDefs.find(p => has(t, p.match));\n    let iceTopping = iceToppingDefs.find(p => has(t, p.match));\n    item.meta = { text: t, part: part?.key || 'crispy', partLabel: part?.label || 'Chicken Crips', style, sauce: sauce?.key || null, sauceLabel: sauce?.label || '', rice, iceBase: iceBase?.key, icePack: icePack?.key, iceTopping: iceTopping?.key };",
        $content
    );

    // 4. setActiveCat update
    $content = str_replace(
        "state = { catId: Number(id), step: 'parts', part: null, style: null, sauce: null, rice: null };",
        "const nextCat = findCat(id); state = { catId: Number(id), step: isIceCreamCat(nextCat) ? 'base' : 'parts', part: null, style: null, sauce: null, rice: null, iceBase: null, icePack: null, iceTopping: null };",
        $content
    );

    // 5. stepIndex update
    $content = str_replace(
        "function stepIndex() { return ['parts', 'style', 'sauce', 'rice'].indexOf(state.step); }",
        "function stepIndex() { if (isIceCreamCat(currentCat())) return ['base', 'pack', 'topping'].indexOf(state.step); return ['parts', 'style', 'sauce', 'rice'].indexOf(state.step); }",
        $content
    );

    // 6. renderFlow update
    $flowReplace = <<<JS
    if (isIceCreamCat(cat)) {
      const steps = [['base', 'Rasa Base'], ['pack', 'Pilih Wadah'], ['topping', 'Pilih Topping']];
      const idx = stepIndex();
      flowBar.innerHTML = '<div class="k2-flow">' + steps.map((s, i) => `<span class="\${i === idx ? 'active' : (i < idx ? 'done' : '')}">\${esc(s[1])}</span>`).join('') + '</div>';
      if (flowBack) flowBack.style.display = idx > 0 ? 'inline-flex' : 'none';
      return;
    }
    if (!isChickenCat(cat)) { flowBar.innerHTML = ''; if (flowBack) flowBack.style.display = 'none'; return; }
JS;
    $content = str_replace(
        "if (!isChickenCat(cat)) { flowBar.innerHTML = ''; if (flowBack) flowBack.style.display = 'none'; return; }",
        $flowReplace,
        $content
    );

    // 7. renderIceCream block injection
    $renderIceCream = <<<JS
  function iceCreamItems() { return (currentCat()?.items || []).filter(i => Number(i.price || 0) > 0); }
  function availableIceBases() {
    const items = iceCreamItems();
    const out = [];
    iceBaseDefs.forEach(b => {
      const matched = items.filter(i => meta(i).iceBase === b.key);
      const count = matched.length;
      if (count > 0) {
        const disabled = matched.every(i => Number(i.ready_stock || 0) <= 0);
        out.push({ ...b, count, disabled });
      }
    });
    return out;
  }
  function availableIcePacks() {
    const items = iceCreamItems().filter(i => meta(i).iceBase === state.iceBase);
    const out = [];
    icePackDefs.forEach(p => {
      const matched = items.filter(i => meta(i).icePack === p.key);
      const count = matched.length;
      if (count > 0) {
        const disabled = matched.every(i => Number(i.ready_stock || 0) <= 0);
        let itemRef = count === 1 ? matched[0] : null;
        out.push({ ...p, count, disabled, item: itemRef });
      }
    });
    return out;
  }
  function availableIceToppings() {
    const items = iceCreamItems().filter(i => meta(i).iceBase === state.iceBase && meta(i).icePack === state.icePack);
    const out = [];
    iceToppingDefs.forEach(t => {
      const matched = items.filter(i => meta(i).iceTopping === t.key);
      const count = matched.length;
      if (count > 0) {
        const disabled = matched.every(i => Number(i.ready_stock || 0) <= 0);
        let itemRef = count === 1 ? matched[0] : null;
        out.push({ ...t, count, disabled, item: itemRef });
      }
    });
    return out;
  }
  function renderIceCream() {
    const cat = currentCat();
    if (activeCategoryLabel) activeCategoryLabel.textContent = cat.name;
    renderFlow();
    setMessage('');
    if (state.step === 'base') {
      const bases = availableIceBases();
      if (visibleProductInfo) visibleProductInfo.textContent = ' | pilih rasa dasar';
      productGrid.innerHTML = bases.length
        ? bases.map(b => optionCard({ cls: 'choose-ice-base', attrs: `data-base="\${b.key}"`, img: b.img, label: b.label, sub: b.disabled ? 'Bahan Habis' : `\${b.count} varian tersedia`, disabled: b.disabled })).join('')
        : '<div class="sim-empty-panel">Belum ada varian es krim.</div>';
      return;
    }
    if (state.step === 'pack') {
      const packs = availableIcePacks();
      if (visibleProductInfo) visibleProductInfo.textContent = ' | pilih wadah kemasan';
      productGrid.innerHTML = packs.map(p => {
        let isFinal = p.key === 'cone'; // cone has no toppings in our structure
        let attrs = `data-pack="\${p.key}"`;
        if (isFinal && p.item) attrs += ` data-variant="\${Number(p.item.variant_id)}"`;
        return optionCard({ cls: isFinal ? 'choose-ice-final' : 'choose-ice-pack', attrs: attrs, img: p.img, label: p.label, sub: p.disabled ? 'Bahan Habis' : (isFinal ? 'Klik untuk tambah ke keranjang' : 'Lanjut pilih topping'), price: isFinal ? money(p.item?.price) : '', disabled: p.disabled });
      }).join('');
      return;
    }
    if (state.step === 'topping') {
      const toppings = availableIceToppings();
      if (visibleProductInfo) visibleProductInfo.textContent = ' | pilih topping';
      productGrid.innerHTML = toppings.map(t => {
        let attrs = `data-topping="\${t.key}"`;
        if (t.item) attrs += ` data-variant="\${Number(t.item.variant_id)}"`;
        return optionCard({ cls: 'choose-ice-final', attrs: attrs, img: t.img, label: t.label, sub: t.disabled ? 'Bahan Habis' : 'Klik untuk tambah ke keranjang', price: money(t.item?.price), disabled: t.disabled });
      }).join('');
      return;
    }
  }
  function renderMain
JS;
    $content = str_replace("  function renderMain", $renderIceCream, $content);

    // 8. renderMain replace
    $content = str_replace(
        "if (isChickenCat(cat)) renderChicken(); else renderSimple(cat);",
        "if (isIceCreamCat(cat)) renderIceCream(); else if (isChickenCat(cat)) renderChicken(); else renderSimple(cat);",
        $content
    );

    // 9. goBack modify
    $goBackReplace = <<<JS
  function goBack() {
    if (isIceCreamCat(currentCat())) {
      if (state.step === 'topping') { state.step = 'pack'; state.iceTopping = null; }
      else if (state.step === 'pack') { state.step = 'base'; state.icePack = null; state.iceBase = null; }
    } else {
      if (state.step === 'rice') { state.step = state.style === 'sauce' ? 'sauce' : 'style'; state.rice = null; }
      else if (state.step === 'sauce') { state.step = 'style'; state.sauce = null; }
      else if (state.step === 'style') { state.step = 'parts'; state.style = null; state.part = null; }
    }
    renderMain();
  }
JS;
    $content = preg_replace('/function goBack\(\) \{[\s\S]*?renderMain\(\);\s*\}/', $goBackReplace, $content);

    // 10. Click Listeners modify
    $clickReplace = <<<JS
if (btn.classList.contains('choose-rice')) { addVariantById(btn.dataset.variant); state = { ...state, step: 'parts', part: null, style: null, sauce: null, rice: null }; renderMain(); return; }
    
    if (btn.classList.contains('choose-ice-base')) { state.iceBase = btn.dataset.base; state.step = 'pack'; renderMain(); return; }
    if (btn.classList.contains('choose-ice-pack')) { state.icePack = btn.dataset.pack; state.step = 'topping'; renderMain(); return; }
    if (btn.classList.contains('choose-ice-final')) { 
      addVariantById(btn.dataset.variant); 
      state = { ...state, step: 'base', iceBase: null, icePack: null, iceTopping: null }; 
      renderMain(); 
      return; 
    }
JS;
    $content = str_replace(
        "if (btn.classList.contains('choose-rice')) { addVariantById(btn.dataset.variant); state = { ...state, step: 'parts', part: null, style: null, sauce: null, rice: null }; renderMain(); return; }",
        $clickReplace,
        $content
    );

    // 11. resetFlow replace
    $content = str_replace(
        "state = { catId: state.catId, step: 'parts', part: null, style: null, sauce: null, rice: null };",
        "state = { catId: state.catId, step: isIceCreamCat(currentCat()) ? 'base' : 'parts', part: null, style: null, sauce: null, rice: null, iceBase: null, icePack: null, iceTopping: null };",
        $content
    );

    file_put_contents($file, $content);
    echo "Updated: $file\n";
}
