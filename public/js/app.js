/* DCFinance — interaksi kecil tanpa framework */
(function () {
    'use strict';

    const DATA = window.DC_DATA || {};
    const RATES = DATA.rates || {};
    const META = DATA.cur || {};

    const meta = (c) => META[c] || [c, 2, c];
    const dec = (c) => (c === 'IDR' ? 0 : meta(c)[1]);
    const sym = (c) => (c === 'IDR' ? 'Rp' : meta(c)[0]);
    const rateOf = (c) => (c === 'IDR' ? 1 : Number(RATES[c] || 0));

    const fmtNum = (v, d) => Number(v).toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });
    const money = (v, c = 'IDR') => (v < 0 ? '-' : '') + sym(c) + ' ' + fmtNum(Math.abs(v), dec(c));
    const idr = (v) => money(v, 'IDR');
    const rateFmt = (v) => {
        v = Number(v);
        const d = v === 0 ? 2 : v < 0.01 ? 6 : v < 1 ? 4 : v < 100 ? 3 : 2;
        return fmtNum(v, d);
    };
    const cssVar = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim();

    /** "1.500.000" / "150,75" / "0,000400" -> angka */
    function parse(str) {
        if (str == null) return NaN;
        let s = String(str).replace(/[^\d.,]/g, '');
        if (!s) return NaN;
        const lastDot = s.lastIndexOf('.'), lastComma = s.lastIndexOf(',');
        if (lastDot > -1 && lastComma > -1) {
            s = lastComma > lastDot ? s.replace(/\./g, '').replace(',', '.') : s.replace(/,/g, '');
        } else if (lastComma > -1) {
            const after = s.length - lastComma - 1, intPart = s.slice(0, lastComma);
            s = (s.split(',').length === 2 && (after !== 3 || intPart === '0' || intPart === '')) ? s.replace(',', '.') : s.replace(/,/g, '');
        } else if (lastDot > -1) {
            const after = s.length - lastDot - 1, intPart = s.slice(0, lastDot);
            if (s.split('.').length > 2 || (after === 3 && intPart !== '0' && intPart !== '')) s = s.replace(/\./g, '');
        }
        return parseFloat(s);
    }
    /** angka -> teks input gaya Indonesia */
    const toInput = (v, d) => (isFinite(v) && v > 0 ? Number(v).toLocaleString('id-ID', { maximumFractionDigits: d, useGrouping: true }) : '');

    window.DC = { idr, money, rateFmt, parse, sym, dec, rateOf, cssVar };

    /** Format saat mengetik: titik ribuan, koma desimal */
    function formatTyping(input, maxDec) {
        const raw = input.value;
        const caretFromEnd = raw.length - (input.selectionStart ?? raw.length);
        const clean = raw.replace(/[^\d,]/g, '');
        let [intPart, decPart] = clean.split(',');
        intPart = (intPart || '').replace(/^0+(?=\d)/, '');
        let out = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        if (maxDec > 0 && clean.includes(',')) out += ',' + (decPart || '').slice(0, maxDec);
        input.value = out;
        const pos = Math.max(out.length - caretFromEnd, 0);
        try { input.setSelectionRange(pos, pos); } catch (e) { /* noop */ }
    }
    function currencyOf(input) {
        if (input.dataset.currencyFrom) {
            const el = document.querySelector(`[name="${input.dataset.currencyFrom}"]`);
            if (el) return el.value;
        }
        return input.dataset.currency || 'IDR';
    }
    document.querySelectorAll('[data-money]').forEach((input) => {
        input.addEventListener('input', () => {
            const maxDec = input.dataset.decimal === 'rate' ? 8 : dec(currencyOf(input));
            formatTyping(input, maxDec);
            input.dispatchEvent(new CustomEvent('money:change', { bubbles: true }));
        });
    });

    // ---------- Form transaksi ----------
    const txForm = document.querySelector('[data-tx-form]');
    if (txForm) {
        const cur = txForm.querySelector('[name="currency"]');
        const amount = txForm.querySelector('[name="amount"]');
        const rate = txForm.querySelector('[name="rate"]');
        const rateWrap = txForm.querySelector('[data-rate-wrap]');
        const rateLabel = txForm.querySelector('[data-rate-label]');
        const rateHint = txForm.querySelector('[data-rate-hint]');
        const prefix = txForm.querySelector('[data-prefix]');
        const preview = txForm.querySelector('[data-preview]');
        const previewWrap = txForm.querySelector('[data-preview-wrap]');
        const balanceHint = txForm.querySelector('[data-balance]');
        const typeHint = txForm.querySelector('[data-type-hint]');
        const curName = txForm.querySelector('[data-cur-name]');
        const balances = JSON.parse(txForm.dataset.balances || '{}');
        let rateTouched = !!txForm.dataset.editing;
        let lastCur = cur.value;

        const sync = () => {
            const c = cur.value;
            const type = txForm.querySelector('[name="type"]:checked')?.value || 'deposit';
            prefix.textContent = sym(c);
            if (curName) curName.textContent = meta(c)[2];
            const foreign = c !== 'IDR';
            rateWrap.classList.toggle('hide', !foreign);
            previewWrap.classList.toggle('hide', !foreign);
            if (rateLabel) rateLabel.textContent = 'Kurs 1 ' + c + ' dalam Rupiah';
            if (foreign && rateHint) {
                rateHint.textContent = rateOf(c) > 0 ? 'Kurs hari ini: Rp ' + rateFmt(rateOf(c)) + '. Ganti kalau kurs tukarmu berbeda.' : 'Kurs online belum tersedia — isi kurs tukarmu sendiri.';
            }
            const a = parse(amount.value), r = parse(rate.value);
            preview.textContent = (a > 0 && r > 0) ? idr(a * r) : 'Rp 0';
            const b = balances[c] || 0;
            if (balanceHint) balanceHint.textContent = 'Saldo ' + c + ' kamu: ' + money(b, c);
            if (typeHint) typeHint.textContent = type === 'deposit' ? 'Uang masuk ke tabungan' : 'Uang keluar dari tabungan';
            txForm.querySelectorAll('[data-quick]').forEach((btn) => {
                const steps = c === 'IDR' ? [50000, 100000, 500000, 1000000] : (dec(c) === 0 ? [1000, 5000, 10000, 50000] : [5, 10, 50, 100]);
                const v = steps[Number(btn.dataset.quick)];
                btn.dataset.value = v;
                btn.textContent = '+' + (c === 'IDR' ? (v >= 1e6 ? v / 1e6 + ' jt' : v / 1e3 + ' rb') : fmtNum(v, 0));
            });
            txForm.querySelectorAll('[data-pick-cur]').forEach((chip) => chip.classList.toggle('active', chip.dataset.pickCur === c));
        };
        const onCurChange = () => {
            const c = cur.value;
            const v = parse(amount.value);
            if (!isNaN(v)) amount.value = toInput(v, dec(c));
            if (c !== lastCur) rateTouched = false;
            if (!rateTouched) rate.value = rateOf(c) > 0 ? rateFmt(rateOf(c)) : '';
            lastCur = c;
            sync();
        };
        cur.addEventListener('change', onCurChange);
        rate.addEventListener('input', () => { rateTouched = true; });
        txForm.addEventListener('change', sync);
        txForm.addEventListener('money:change', sync);
        txForm.querySelectorAll('[data-pick-cur]').forEach((chip) => chip.addEventListener('click', () => {
            cur.value = chip.dataset.pickCur; onCurChange();
        }));
        txForm.querySelectorAll('[data-quick]').forEach((b) => b.addEventListener('click', () => {
            const c = cur.value;
            amount.value = toInput((parse(amount.value) || 0) + Number(b.dataset.value), dec(c));
            sync();
        }));
        if (!rate.value && cur.value !== 'IDR') rate.value = rateOf(cur.value) > 0 ? rateFmt(rateOf(cur.value)) : '';
        sync();
    }

    // ---------- Form tukar mata uang ----------
    const exForm = document.querySelector('[data-exchange-form]');
    if (exForm) {
        const fromSel = exForm.querySelector('[name="from"]');
        const toSel = exForm.querySelector('[name="to"]');
        const from = exForm.querySelector('[name="amount_from"]');
        const to = exForm.querySelector('[name="amount_to"]');
        const pFrom = exForm.querySelector('[data-prefix-from]');
        const pTo = exForm.querySelector('[data-prefix-to]');
        const eff = exForm.querySelector('[data-effective]');
        const bal = exForm.querySelector('[data-balance]');
        const balances = JSON.parse(exForm.dataset.balances || '{}');
        let toTouched = false;

        const cross = (v, a, b) => (rateOf(b) > 0 ? v * rateOf(a) / rateOf(b) : 0);
        const show = () => {
            const a = fromSel.value, b = toSel.value;
            pFrom.textContent = sym(a); pTo.textContent = sym(b);
            const f = parse(from.value), t = parse(to.value);
            eff.textContent = (f > 0 && t > 0) ? '1 ' + a + ' = ' + (b === 'IDR' ? 'Rp ' + rateFmt(t / f) : rateFmt(t / f) + ' ' + b) : '—';
            bal.textContent = 'Saldo ' + a + ': ' + money(balances[a] || 0, a);
        };
        const fill = () => {
            const f = parse(from.value);
            if (!toTouched) to.value = f > 0 ? toInput(cross(f, fromSel.value, toSel.value), dec(toSel.value)) : '';
            show();
        };
        from.addEventListener('money:change', fill);
        to.addEventListener('money:change', () => { toTouched = to.value !== ''; show(); });
        [fromSel, toSel].forEach((s) => s.addEventListener('change', () => { toTouched = false; fill(); }));
        exForm.querySelector('[data-swap]')?.addEventListener('click', () => {
            const a = fromSel.value; fromSel.value = toSel.value; toSel.value = a;
            from.value = ''; to.value = ''; toTouched = false; show();
        });
        exForm.querySelector('[data-reset-rate]')?.addEventListener('click', (e) => { e.preventDefault(); toTouched = false; fill(); });
        show();
    }

    // ---------- Konverter ----------
    document.querySelectorAll('[data-converter]').forEach((box) => {
        const amt = box.querySelector('[name="conv_amount"]');
        const a = box.querySelector('[name="conv_from"]');
        const b = box.querySelector('[name="conv_to"]');
        const out = box.querySelector('[data-conv-out]');
        const run = () => {
            const v = parse(amt.value);
            const r = rateOf(b.value) > 0 ? v * rateOf(a.value) / rateOf(b.value) : NaN;
            out.textContent = v > 0 && isFinite(r) ? money(r, b.value) : '—';
        };
        amt.addEventListener('money:change', run);
        [a, b].forEach((s) => s.addEventListener('change', run));
        box.querySelector('[data-swap]')?.addEventListener('click', () => { const x = a.value; a.value = b.value; b.value = x; run(); });
        run();
    });

    // ---------- Cari & tampilkan semua di tabel kurs ----------
    document.querySelectorAll('[data-filter-table]').forEach((input) => {
        const table = document.querySelector(input.dataset.filterTable);
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach((tr) => {
                tr.classList.toggle('hide', q ? !tr.textContent.toLowerCase().includes(q) : tr.classList.contains('more'));
            });
        });
    });
    document.querySelector('[data-show-all]')?.addEventListener('click', (e) => {
        document.querySelectorAll('tr.more').forEach((tr) => tr.classList.remove('more', 'hide'));
        e.target.remove();
    });

    // ---------- Form target ----------
    const goalForm = document.querySelector('[data-goal-form]');
    if (goalForm) {
        const cur = goalForm.querySelector('[name="target_currency"]');
        const amt = goalForm.querySelector('[name="target_amount"]');
        const deadline = goalForm.querySelector('[name="deadline"]');
        const out = goalForm.querySelector('[data-plan]');
        const prefix = goalForm.querySelector('[data-prefix]');
        const emojiPrev = goalForm.querySelector('[data-emoji-preview]');
        const sync = () => {
            const c = cur.value;
            prefix.textContent = sym(c);
            const v = parse(amt.value);
            const em = goalForm.querySelector('[name="emoji"]:checked');
            const col = goalForm.querySelector('[name="color"]:checked');
            if (emojiPrev && em) emojiPrev.textContent = em.value;
            if (emojiPrev && col) emojiPrev.style.background = col.value + '22';
            let html = '';
            if (v > 0 && c !== 'IDR' && rateOf(c) > 0) html += `<div class="kv"><span class="k">Dalam Rupiah</span><span class="v">≈ ${idr(v * rateOf(c))}</span></div>`;
            if (v > 0 && deadline.value) {
                const days = Math.ceil((new Date(deadline.value) - new Date(new Date().toDateString())) / 86400000);
                if (days > 0) {
                    html += `<div class="kv"><span class="k">Per bulan</span><span class="v">${money(v / Math.max(days / 30.4375, 1), c)}</span></div>`
                        + `<div class="kv"><span class="k">Per minggu</span><span class="v">${money(v / Math.max(days / 7, 1), c)}</span></div>`
                        + `<div class="kv"><span class="k">Per hari</span><span class="v">${money(v / days, c)}</span></div>`
                        + `<div class="hint">${days} hari lagi menuju tenggat.</div>`;
                } else {
                    html += '<div class="alert alert-red">Tanggal target sudah lewat.</div>';
                }
            }
            out.innerHTML = html || '<p class="muted small">Isi nominal &amp; tanggal target untuk melihat rencana menabungnya.</p>';
        };
        cur.addEventListener('change', () => { const v = parse(amt.value); if (!isNaN(v)) amt.value = toInput(v, dec(cur.value)); sync(); });
        goalForm.addEventListener('change', sync);
        goalForm.addEventListener('money:change', sync);
        deadline.addEventListener('input', sync);
        sync();
    }

    // ---------- Pratinjau tema di profil ----------
    document.querySelectorAll('[data-theme-pick]').forEach((radio) => radio.addEventListener('change', () => {
        const vars = JSON.parse(radio.dataset.themePick);
        Object.entries(vars).forEach(([k, v]) => document.documentElement.style.setProperty('--' + k, v));
    }));

    // ---------- Konfirmasi ----------
    document.addEventListener('submit', (e) => {
        const msg = e.target.dataset.confirm;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    // ---------- Toast ----------
    document.querySelectorAll('.toast').forEach((t) => {
        t.querySelector('button')?.addEventListener('click', () => t.remove());
        setTimeout(() => { t.style.transition = 'opacity .4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4500);
    });

    // ---------- Animasi progress ----------
    requestAnimationFrame(() => {
        document.querySelectorAll('.bar > span[data-w]').forEach((s) => { s.style.width = s.dataset.w + '%'; });
    });

    // ---------- Grafik gagal dimuat ----------
    window.addEventListener('load', () => {
        if (window.Chart) return;
        document.querySelectorAll('.chart-box').forEach((box) => {
            const d = document.createElement('div');
            d.className = 'chart-fallback';
            d.textContent = 'Grafik tidak bisa dimuat (cek koneksi internet).';
            box.appendChild(d);
        });
    });

    document.addEventListener('click', (e) => {
        document.querySelectorAll('details.menu[open]').forEach((d) => { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
})();
