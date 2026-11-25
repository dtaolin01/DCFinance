/* DCFinance — interaksi kecil tanpa framework */
(function () {
    'use strict';

    const idr = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const aud = (v) => 'A$ ' + Number(v).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const rateFmt = (v) => Number(v).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    window.DC = { idr, aud, rateFmt };

    /** "1.500.000" / "150,75" -> angka */
    function parse(str) {
        if (str == null) return NaN;
        let s = String(str).replace(/[^\d.,]/g, '');
        if (!s) return NaN;
        const lastDot = s.lastIndexOf('.'), lastComma = s.lastIndexOf(',');
        if (lastDot > -1 && lastComma > -1) {
            s = lastComma > lastDot ? s.replace(/\./g, '').replace(',', '.') : s.replace(/,/g, '');
        } else if (lastComma > -1) {
            s = (s.length - lastComma - 1) <= 2 && s.split(',').length === 2 ? s.replace(',', '.') : s.replace(/,/g, '');
        } else if (lastDot > -1) {
            if (s.split('.').length > 2 || s.length - lastDot - 1 === 3) s = s.replace(/\./g, '');
        }
        return parseFloat(s);
    }
    window.DC.parse = parse;

    /** Format saat mengetik: titik ribuan, koma desimal (maks 2) bila diizinkan */
    function formatTyping(input, allowDecimal) {
        const raw = input.value;
        const caretFromEnd = raw.length - (input.selectionStart ?? raw.length);
        let clean = raw.replace(/[^\d,]/g, '');
        let [intPart, decPart] = clean.split(',');
        intPart = (intPart || '').replace(/^0+(?=\d)/, '');
        let out = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        if (allowDecimal && clean.includes(',')) out += ',' + (decPart || '').slice(0, 2);
        input.value = out;
        const pos = Math.max(out.length - caretFromEnd, 0);
        try { input.setSelectionRange(pos, pos); } catch (e) { /* noop */ }
    }

    function currencyOf(input) {
        if (input.dataset.currencyFrom) {
            const checked = document.querySelector(`input[name="${input.dataset.currencyFrom}"]:checked`);
            if (checked) return checked.value === 'AUD_IDR' ? 'AUD' : (checked.value === 'IDR_AUD' ? 'IDR' : checked.value);
        }
        return input.dataset.currency || 'IDR';
    }

    function bindMoney(input) {
        input.addEventListener('input', () => {
            formatTyping(input, input.dataset.decimal === 'always' || currencyOf(input) === 'AUD');
            input.dispatchEvent(new CustomEvent('money:change', { bubbles: true }));
        });
    }
    document.querySelectorAll('[data-money]').forEach(bindMoney);

    // ---------- Form transaksi ----------
    const txForm = document.querySelector('[data-tx-form]');
    if (txForm) {
        const amount = txForm.querySelector('[name="amount"]');
        const rate = txForm.querySelector('[name="rate"]');
        const rateWrap = txForm.querySelector('[data-rate-wrap]');
        const prefix = txForm.querySelector('[data-prefix]');
        const preview = txForm.querySelector('[data-preview]');
        const previewWrap = txForm.querySelector('[data-preview-wrap]');
        const balanceHint = txForm.querySelector('[data-balance]');
        const typeHint = txForm.querySelector('[data-type-hint]');
        const method = txForm.querySelector('[name="method"]');
        const balances = JSON.parse(txForm.dataset.balances || '{}');

        const sync = () => {
            const cur = txForm.querySelector('[name="currency"]:checked')?.value || 'IDR';
            const type = txForm.querySelector('[name="type"]:checked')?.value || 'deposit';
            prefix.textContent = cur === 'AUD' ? 'A$' : 'Rp';
            rateWrap.classList.toggle('hide', cur !== 'AUD');
            const a = parse(amount.value), r = parse(rate.value);
            if (cur === 'AUD') {
                previewWrap.classList.remove('hide');
                preview.textContent = (a > 0 && r > 0) ? idr(a * r) : 'Rp 0';
            } else {
                previewWrap.classList.add('hide');
            }
            if (balanceHint) {
                const b = balances[cur] || 0;
                balanceHint.textContent = 'Saldo ' + cur + ' kamu: ' + (cur === 'AUD' ? aud(b) : idr(b));
            }
            if (typeHint) typeHint.textContent = type === 'deposit' ? 'Uang masuk ke tabungan' : 'Uang keluar dari tabungan';
        };
        txForm.addEventListener('change', (e) => {
            if (e.target.name === 'currency') {
                // ubah format angka sesuai mata uang
                const v = parse(amount.value);
                if (!isNaN(v)) {
                    amount.value = e.target.value === 'AUD'
                        ? v.toLocaleString('id-ID', { maximumFractionDigits: 2 })
                        : Math.round(v).toLocaleString('id-ID');
                }
                if (method && e.target.value === 'AUD' && !txForm.dataset.editing) method.value = 'cash';
            }
            sync();
        });
        txForm.addEventListener('money:change', sync);
        sync();

        txForm.querySelectorAll('[data-quick]').forEach((b) => b.addEventListener('click', () => {
            const cur = txForm.querySelector('[name="currency"]:checked')?.value || 'IDR';
            const add = parseFloat(b.dataset[cur === 'AUD' ? 'aud' : 'idr']);
            const v = (parse(amount.value) || 0) + add;
            amount.value = cur === 'AUD' ? v.toLocaleString('id-ID', { maximumFractionDigits: 2 }) : Math.round(v).toLocaleString('id-ID');
            sync();
        }));
    }

    // ---------- Form tukar mata uang ----------
    const exForm = document.querySelector('[data-exchange-form]');
    if (exForm) {
        const rate = parseFloat(exForm.dataset.rate);
        const from = exForm.querySelector('[name="amount_from"]');
        const to = exForm.querySelector('[name="amount_to"]');
        const pFrom = exForm.querySelector('[data-prefix-from]');
        const pTo = exForm.querySelector('[data-prefix-to]');
        const eff = exForm.querySelector('[data-effective]');
        const bal = exForm.querySelector('[data-balance]');
        const balances = JSON.parse(exForm.dataset.balances || '{}');
        let toTouched = false;

        const dir = () => exForm.querySelector('[name="direction"]:checked')?.value || 'IDR_AUD';
        const fill = () => {
            const d = dir();
            const f = parse(from.value);
            if (!toTouched && f > 0) {
                const v = d === 'IDR_AUD' ? f / rate : f * rate;
                to.value = d === 'IDR_AUD' ? v.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : Math.round(v).toLocaleString('id-ID');
            }
            show();
        };
        const show = () => {
            const d = dir();
            pFrom.textContent = d === 'IDR_AUD' ? 'Rp' : 'A$';
            pTo.textContent = d === 'IDR_AUD' ? 'A$' : 'Rp';
            to.dataset.currency = d === 'IDR_AUD' ? 'AUD' : 'IDR';
            from.dataset.currency = d === 'IDR_AUD' ? 'IDR' : 'AUD';
            const f = parse(from.value), t = parse(to.value);
            const idrAmt = d === 'IDR_AUD' ? f : t, audAmt = d === 'IDR_AUD' ? t : f;
            eff.textContent = (idrAmt > 0 && audAmt > 0) ? 'Rp ' + rateFmt(idrAmt / audAmt) : '—';
            const cur = d === 'IDR_AUD' ? 'IDR' : 'AUD';
            bal.textContent = 'Saldo ' + cur + ': ' + (cur === 'AUD' ? aud(balances.AUD || 0) : idr(balances.IDR || 0));
        };
        from.addEventListener('money:change', fill);
        to.addEventListener('money:change', () => { toTouched = to.value !== ''; show(); });
        exForm.querySelectorAll('[name="direction"]').forEach((r) => r.addEventListener('change', () => {
            from.value = ''; to.value = ''; toTouched = false; show();
        }));
        exForm.querySelector('[data-reset-rate]')?.addEventListener('click', () => { toTouched = false; fill(); });
        show();
    }

    // ---------- Konverter kecil ----------
    document.querySelectorAll('[data-converter]').forEach((box) => {
        const rate = parseFloat(box.dataset.rate);
        const a = box.querySelector('[name="conv_aud"]');
        const i = box.querySelector('[name="conv_idr"]');
        a.addEventListener('money:change', () => { const v = parse(a.value); i.value = v > 0 ? Math.round(v * rate).toLocaleString('id-ID') : ''; });
        i.addEventListener('money:change', () => { const v = parse(i.value); a.value = v > 0 ? (v / rate).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : ''; });
    });

    // ---------- Pratinjau target ----------
    const goalForm = document.querySelector('[data-goal-form]');
    if (goalForm) {
        const rate = parseFloat(goalForm.dataset.rate);
        const amt = goalForm.querySelector('[name="target_amount"]');
        const deadline = goalForm.querySelector('[name="deadline"]');
        const out = goalForm.querySelector('[data-plan]');
        const prefix = goalForm.querySelector('[data-prefix]');
        const emojiPrev = goalForm.querySelector('[data-emoji-preview]');
        const sync = () => {
            const cur = goalForm.querySelector('[name="target_currency"]:checked')?.value || 'IDR';
            prefix.textContent = cur === 'AUD' ? 'A$' : 'Rp';
            const v = parse(amt.value);
            const em = goalForm.querySelector('[name="emoji"]:checked');
            const col = goalForm.querySelector('[name="color"]:checked');
            if (emojiPrev && em) { emojiPrev.textContent = em.value; }
            if (emojiPrev && col) { emojiPrev.style.background = col.value + '22'; }
            let html = '';
            if (v > 0 && cur === 'AUD') html += `<div>≈ <b>${idr(v * rate)}</b> dengan kurs hari ini</div>`;
            if (v > 0 && deadline.value) {
                const days = Math.ceil((new Date(deadline.value) - new Date(new Date().toDateString())) / 86400000);
                if (days > 0) {
                    const per = (x) => cur === 'AUD' ? aud(x) : idr(x);
                    html += `<div>Butuh sekitar <b>${per(v / Math.max(days / 30.4375, 1))}</b>/bulan · <b>${per(v / Math.max(days / 7, 1))}</b>/minggu · <b>${per(v / days)}</b>/hari <span class="muted">(${days} hari lagi)</span></div>`;
                } else {
                    html += '<div class="text-red">Tanggal target sudah lewat.</div>';
                }
            }
            out.innerHTML = html || '<span class="muted">Isi nominal & tanggal target untuk melihat rencana menabungnya.</span>';
        };
        goalForm.addEventListener('change', sync);
        goalForm.addEventListener('money:change', sync);
        deadline.addEventListener('input', sync);
        sync();
    }

    // ---------- Konfirmasi hapus ----------
    document.addEventListener('submit', (e) => {
        const msg = e.target.dataset.confirm;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    // ---------- Toast ----------
    document.querySelectorAll('.toast').forEach((t) => {
        t.querySelector('button')?.addEventListener('click', () => t.remove());
        setTimeout(() => { t.style.transition = 'opacity .4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4200);
    });

    // ---------- Animasi progress ----------
    requestAnimationFrame(() => {
        document.querySelectorAll('.bar > span[data-w]').forEach((s) => { s.style.width = s.dataset.w + '%'; });
    });

    // tutup menu details saat klik di luar
    document.addEventListener('click', (e) => {
        document.querySelectorAll('details.menu[open]').forEach((d) => { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
})();
