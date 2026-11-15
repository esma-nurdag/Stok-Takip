        </main>
        <footer>
            <div class="footer-icerik">
                <span><strong>Stok-Takip</strong> — AI Stok Takip ve Envanter Operasyon Platformu</span>
                <span class="footer-ayirici">•</span>
                <span>Yerel Veritabanı: <code>data/veritabani.json</code> &amp; <code>$_SESSION</code></span>
            </div>
        </footer>
    </div>
    <script>
        function temaButonMetniGuncelle() {
            const btn = document.getElementById('temaBtn');
            if (!btn) return;
            const karanlikMi = document.documentElement.classList.contains('dark-mode');
            btn.textContent = karanlikMi ? '☀️' : '🌙';
            btn.title = karanlikMi ? 'Aydınlık Temaya Geç' : 'Karanlık Temaya Geç';
        }
        function temaDegistir() {
            document.documentElement.classList.toggle('dark-mode');
            const karanlikMi = document.documentElement.classList.contains('dark-mode');
            localStorage.setItem('recurra_tema', karanlikMi ? 'karanlik' : 'aydinlik');
            temaButonMetniGuncelle();
        }
        function yeniKategoriKutusuGoster(deger) {
            const kutu = document.getElementById('yeni_kategori_kapsayici');
            const input = document.getElementById('yeni_kategori');
            if (!kutu || !input) return;
            if (deger === '__yeni__') {
                kutu.style.display = 'block';
                input.focus();
            } else {
                kutu.style.display = 'none';
                input.value = '';
            }
        }
        function topluStokKutusuAc(id) {
            const form = document.getElementById('toplu-stok-' + id);
            if (!form) return;
            form.classList.toggle('gizli');
            if (!form.classList.contains('gizli')) {
                const inp = form.querySelector('input[name="degisim"]');
                if (inp) {
                    inp.focus();
                    inp.select();
                }
            }
        }
        function hazirUrunSec(butonEl) {
            const oge = butonEl.closest('.sevkiyat-oge');
            if (!oge) return;
            const acikMi = oge.classList.contains('acik');
            const liste = oge.closest('.sevkiyat-listesi');
            if (liste) {
                liste.querySelectorAll('.sevkiyat-oge.acik').forEach(function (diger) {
                    if (diger !== oge) diger.classList.remove('acik');
                });
            }
            oge.classList.toggle('acik', !acikMi);
        }
        function akordiyonAcKapat(baslikEl) {
            const grup = baslikEl.closest('.akordiyon-grup');
            if (!grup) return;
            grup.classList.toggle('acik');
        }
        temaButonMetniGuncelle();

        document.addEventListener('keydown', function (e) {
            if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes((e.target.tagName || '').toUpperCase())) {
                const filtreInput = document.getElementById('barkod-arama');
                const ustInput = document.getElementById('ust-barkod-arama');
                const aramaInput = (filtreInput && filtreInput.offsetParent !== null) ? filtreInput : ustInput;
                if (aramaInput) {
                    e.preventDefault();
                    aramaInput.focus();
                    aramaInput.select();
                }
            }
        });

        (function () {
            const ANAHTAR = 'stokScrollKonumu';

            function metinYaz(id, deger) {
                const el = document.getElementById(id);
                if (el && deger !== undefined) el.textContent = deger;
            }

            document.querySelectorAll('form[action="stok_guncelle.php"]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    const satir = form.closest('tr[data-urun-id]');

                    if (satir && window.fetch) {
                        e.preventDefault();
                        const formData = new FormData(form);
                        fetch('stok_guncelle.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (yanit) {
                            if (!yanit || !yanit.basarili) {
                                form.submit();
                                return;
                            }
                            const u = yanit.urun;
                            const s = yanit.sayac;

                            const adetEl = satir.querySelector('.adet-sayi');
                            if (adetEl) {
                                adetEl.textContent = u.adet;
                                adetEl.classList.remove('stok-anlik-vurgu');
                                void adetEl.offsetWidth;
                                adetEl.classList.add('stok-anlik-vurgu');
                            }

                            const eksiBtn = satir.querySelector('.stok-btn.eksi');
                            if (eksiBtn) {
                                eksiBtn.disabled = (u.adet <= 0);
                            }

                            const degerEl = satir.querySelector('.satir-deger');
                            if (degerEl) degerEl.textContent = u.satirDeger;

                            const karEl = satir.querySelector('.satir-kar');
                            if (karEl) karEl.textContent = u.satirKar;

                            const rozetEl = satir.querySelector('.durum-rozet');
                            if (rozetEl) {
                                rozetEl.textContent = u.durum;
                                rozetEl.className = 'rozet durum-rozet ' + u.durumSinif;
                            }

                            const qmEl = satir.querySelector('.qm-grafik');
                            if (qmEl) {
                                qmEl.className = 'qm-grafik ' + u.durumSinif;
                            }

                            const trendEl = satir.querySelector('.trend-deger');
                            if (trendEl) {
                                trendEl.className = 'trend-deger ' + u.durumSinif;
                            }

                            satir.className = 'satir-' + u.durumSinif;

                            if (form.classList.contains('toplu-stok-form')) {
                                form.classList.add('gizli');
                            }

                            if (s) {
                                metinYaz('sayac-toplam', s.toplam);
                                metinYaz('sayac-toplam-adet', 'Toplam ' + s.toplam_adet + ' adet birim');
                                metinYaz('sayac-stokta', s.Stokta);
                                metinYaz('sayac-azaldi', s.Azaldi);
                                metinYaz('sayac-tukendi', s.Tukendi);
                                metinYaz('sayac-maliyet', s.maliyet);
                                metinYaz('sayac-deger', s.deger);
                                metinYaz('sayac-kar', s.net_kar);
                                metinYaz('sayac-kar-oran', '+%' + s.kar_marji + ' kâr oranı');
                            }
                        })
                        .catch(function () {
                            form.submit();
                        });
                        return;
                    }

                    const idInput = form.querySelector('input[name="id"]');
                    sessionStorage.setItem(ANAHTAR, JSON.stringify({
                        sayfa: location.pathname + location.search,
                        id: idInput ? idInput.value : null,
                        y: window.scrollY
                    }));
                });
            });

            const kayit = sessionStorage.getItem(ANAHTAR);
            if (kayit) {
                sessionStorage.removeItem(ANAHTAR);
                try {
                    const veri = JSON.parse(kayit);
                    if (veri.sayfa === location.pathname + location.search) {
                        window.scrollTo(0, veri.y);
                    }
                } catch (e) {}
            }
        })();
    </script>
</body>
</html>
