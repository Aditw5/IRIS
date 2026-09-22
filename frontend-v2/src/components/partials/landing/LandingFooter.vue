<template>
  <footer class="ulab-footer ulab-footer-v2" aria-label="Footer U-LAB">
    <div class="footer-shell">
      <div class="footer-grid">
        <section class="footer-brand" aria-label="UMRO Laboratory">
          <a class="footer-logo" href="/" aria-label="UMRO Laboratory">
            <span class="footer-logo-mark">
              <img src="/UMRO.png" alt="" loading="lazy" decoding="async" />
            </span>
            <span class="footer-logo-copy">
              <strong>UMRO LABORATORY</strong>
              <small>Calibration, Testing &amp; Repair</small>
            </span>
          </a>

          <p>
            U-LAB merupakan singkatan dari UMRO Calibration Laboratory yang menerapkan ISO/IEC 17025:2017
            sejak tahun 2018 dan mendapat Akreditasi dari Komite Akreditasi Nasional (KAN) pada
            2 September 2019 dengan Nomor LK-284-IDN.
          </p>

          <div class="footer-credit">
            <span>
              Coded with
              <i aria-hidden="true" class="fas fa-heart"></i>
              by Adi Tiya Wiranda.
            </span>
           
          </div>

          <div class="footer-social" aria-label="Developer links">
            <a
              href="https://www.linkedin.com/in/adi-tiya-wiranda/"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="LinkedIn adi tiya wiranda"
            >
              <i aria-hidden="true" class="fab fa-linkedin-in"></i>
            </a>
            <a href="https://github.com/Aditw5" target="_blank" rel="noopener noreferrer" aria-label="GitHub Aditw5">
              <i aria-hidden="true" class="fab fa-github"></i>
            </a>
          </div>
        </section>

        <section class="footer-contact" aria-label="Hubungi Kami">
          <h2>Hubungi Kami</h2>
          <ul>
            <li>
              <i aria-hidden="true" class="fab fa-whatsapp"></i>
              <a href="https://wa.me/6281210000284" target="_blank" rel="noopener noreferrer">0812-10000-284</a>
            </li>
            <li>
              <i aria-hidden="true" class="fas fa-envelope"></i>
              <a href="mailto:lab.umro@gmail.com">lab.umro@gmail.com</a>
            </li>
            <li>
              <i aria-hidden="true" class="far fa-clock"></i>
              <span>Senin - Jumat<br />07.30 - 16.00 WIB</span>
            </li>
            <li>
              <i aria-hidden="true" class="fas fa-globe"></i>
              <a href="https://www.ulabumro.id" target="_blank" rel="noopener noreferrer">www.ulabumro.id</a>
            </li>
          </ul>
        </section>

        <section class="footer-locations" aria-label="Lokasi Kami">
          <h2>Lokasi Kami</h2>
          <div class="footer-location-list">
            <article class="footer-location-card">
              <div class="footer-location-head">
                <span>1 Jakarta</span>
                <a
                  href="https://www.google.com/maps?q=-6.111229468899915,106.78263452408453"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Buka Maps
                  <i aria-hidden="true" class="fas fa-external-link-alt"></i>
                </a>
              </div>
              <div class="footer-map-frame">
                <iframe
                  title="Peta ULAB Jakarta"
                  src="https://www.google.com/maps?q=-6.111229468899915,106.78263452408453&z=16&output=embed"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
              <p>Jl. Pluit Utara Raya No. 2B, Komplek PLTU Muara Karang, Jakarta Utara, DKI Jakarta 14450.</p>
            </article>

            <article class="footer-location-card">
              <div class="footer-location-head">
                <span>2 Gresik</span>
                <a
                  href="https://www.google.com/maps?q=-7.160993779776026,112.65988143834625"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Buka Maps
                  <i aria-hidden="true" class="fas fa-external-link-alt"></i>
                </a>
              </div>
              <div class="footer-map-frame">
                <iframe
                  title="Peta ULAB Gresik"
                  src="https://www.google.com/maps?q=-7.160993779776026,112.65988143834625&z=16&output=embed"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
              <p>Jl. Harun Tohir No. 1, Singosari, Pulopancikan, Kec. Gresik, Kabupaten Gresik, Jawa Timur 61111.</p>
            </article>
          </div>
        </section>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2026 UMRO Laboratory. All rights reserved.</p>
        <nav aria-label="Footer links">
          <a href="/privacy-policy/" target="_blank" rel="noopener">Kebijakan Privasi</a>
          <a href="/account-deletion/" target="_blank" rel="noopener">Syarat &amp; Ketentuan</a>
          <RouterLink :to="{ name: 'akreditasi' }">FAQ</RouterLink>
        </nav>
      </div>
    </div>
  </footer>
</template>

<style lang="scss">
.ulab-footer {
  --footer-line: rgba(255, 255, 255, .18);
  --footer-text: #c7d7ee;
  --footer-muted: #9fb1ca;
  --footer-panel: rgba(255, 255, 255, .055);

  padding: clamp(42px, 5.4vw, 64px) 0 26px;
  color: var(--footer-text);
  background:
    radial-gradient(circle at 20% 0%, rgba(13, 121, 176, .24), transparent 32%),
    linear-gradient(135deg, rgba(1, 20, 52, .99), rgba(3, 54, 105, .97));
  border-top: 1px solid rgba(255, 255, 255, .12);
}

.footer-shell {
  width: min(1320px, calc(100% - 48px));
  margin: 0 auto;
}

.footer-grid {
  display: grid;
  grid-template-columns: minmax(260px, .92fr) minmax(230px, .62fr) minmax(430px, 1.18fr);
  gap: clamp(28px, 5vw, 58px);
  align-items: start;
}

.footer-logo {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  color: #fff !important;
  text-decoration: none !important;
}

.footer-logo-mark {
  width: 58px;
  height: 58px;
  display: grid;
  place-items: center;
  overflow: hidden;
  border-radius: 14px;
}

.footer-logo-mark img {
  width: 58px;
  height: 58px;
  display: block;
  object-fit: contain;
  transform: scale(1.75);
  transform-origin: 50% 32%;
}

.footer-logo-copy {
  display: flex;
  flex-direction: column;
  line-height: 1.12;
}

.footer-logo-copy strong {
  color: #fff;
  font-size: 1rem;
  font-weight: 950;
}

.footer-logo-copy small {
  margin-top: 4px;
  color: #c9dcf6;
  font-size: .68rem;
  font-weight: 800;
}

.footer-brand p {
  max-width: 430px;
  margin: 20px 0 0;
  color: var(--footer-text);
  font-size: .96rem;
  line-height: 1.7;
}

.footer-credit {
  display: grid;
  gap: 5px;
  margin-top: 18px;
  color: var(--footer-muted);
  font-size: .84rem;
  font-weight: 750;
  line-height: 1.45;
}

.footer-credit i {
  color: #f4b4bc;
}

.footer-social {
  display: flex;
  gap: 10px;
  margin-top: 18px;
}

.footer-social a {
  width: 34px;
  height: 34px;
  display: inline-grid;
  place-items: center;
  border-radius: 999px;
  color: #0b2d60 !important;
  background: #fff;
  text-decoration: none !important;
  transition: transform .16s ease, background .16s ease;
}

.footer-social a:hover {
  transform: translateY(-2px);
  background: #dff3ff;
}

.ulab-footer h2 {
  margin: 0 0 18px;
  color: #fff;
  font-size: .92rem;
  font-weight: 950;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.footer-contact {
  padding-left: clamp(0px, 3vw, 36px);
  border-left: 1px solid var(--footer-line);
}

.footer-contact ul {
  display: grid;
  gap: 14px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.footer-contact li {
  display: grid;
  grid-template-columns: 24px minmax(0, 1fr);
  gap: 12px;
  align-items: start;
  color: #dceaff;
  font-size: .95rem;
  line-height: 1.45;
}

.footer-contact i {
  margin-top: 2px;
  color: #b9d9ff;
}

.footer-contact .fa-whatsapp {
  color: #25d366;
}

.footer-contact .fa-envelope {
  color: #ff746b;
}

.footer-contact a,
.footer-bottom a {
  color: #dceaff !important;
  text-decoration: none !important;
}

.footer-contact a:hover,
.footer-bottom a:hover {
  color: #74d6ff !important;
}

.footer-locations {
  padding-left: clamp(0px, 3vw, 36px);
  border-left: 1px solid var(--footer-line);
}

.footer-location-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.footer-location-card {
  min-width: 0;
}

.footer-location-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 8px;
}

.footer-location-head span {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 9px;
  color: #fff;
  background: #075eea;
  font-size: .75rem;
  font-weight: 950;
  text-transform: uppercase;
  white-space: nowrap;
}

.footer-location-head a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #9ee7ff !important;
  font-size: .72rem;
  font-weight: 850;
  text-decoration: none !important;
  white-space: nowrap;
}

.footer-location-head a:hover {
  color: #fff !important;
}

.footer-map-frame {
  height: 122px;
  overflow: hidden;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, .18);
  background: var(--footer-panel);
}

.footer-map-frame iframe {
  width: 100%;
  height: 100%;
  display: block;
  border: 0;
  filter: invert(90%) hue-rotate(180deg) saturate(.72) contrast(.98);
}

.footer-location-card p {
  margin: 10px 0 0;
  color: #d1def0;
  font-size: .86rem;
  line-height: 1.55;
}

.footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-top: 34px;
  padding-top: 24px;
  border-top: 1px solid var(--footer-line);
  color: var(--footer-muted);
  font-size: .88rem;
}

.footer-bottom p {
  margin: 0;
}

.footer-bottom nav {
  display: flex;
  flex-wrap: wrap;
  gap: 22px;
}

.footer-bottom a {
  font-weight: 800;
}

@media (max-width: 1100px) {
  .footer-grid {
    grid-template-columns: 1fr 1fr;
  }

  .footer-locations {
    grid-column: 1 / -1;
    padding-left: 0;
    border-left: 0;
  }
}

@media (max-width: 760px) {
  .ulab-footer {
    padding-bottom: 104px;
  }

  .footer-shell {
    width: min(100% - 28px, 1320px);
  }

  .footer-grid,
  .footer-location-list {
    grid-template-columns: 1fr;
  }

  .footer-contact,
  .footer-locations {
    padding-left: 0;
    border-left: 0;
  }

  .footer-bottom {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>

<style lang="scss">
.ulab-footer-v2 {
  --footer-line: rgba(255, 255, 255, .18);
  --footer-text: #c7d7ee;
  --footer-muted: #9fb1ca;
  --footer-panel: rgba(255, 255, 255, .055);

  padding: clamp(42px, 5.4vw, 64px) 0 26px;
  color: var(--footer-text);
  background:
    radial-gradient(circle at 20% 0%, rgba(13, 121, 176, .24), transparent 32%),
    linear-gradient(135deg, rgba(1, 20, 52, .99), rgba(3, 54, 105, .97));
  border-top: 1px solid rgba(255, 255, 255, .12);
}

.ulab-footer-v2 .footer-shell {
  width: min(1320px, calc(100% - 48px));
  margin: 0 auto;
}

.ulab-footer-v2 .footer-grid {
  display: grid;
  grid-template-columns: minmax(260px, .92fr) minmax(230px, .62fr) minmax(430px, 1.18fr);
  gap: clamp(28px, 5vw, 58px);
  align-items: start;
}

.ulab-footer-v2 .footer-logo {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  color: #fff !important;
  text-decoration: none !important;
}

.ulab-footer-v2 .footer-logo-mark {
  width: 58px;
  height: 58px;
  display: grid;
  place-items: center;
  overflow: hidden;
  border-radius: 14px;
}

.ulab-footer-v2 .footer-logo-mark img {
  width: 58px;
  height: 58px;
  display: block;
  object-fit: contain;
  transform: scale(1.75);
  transform-origin: 50% 32%;
}

.ulab-footer-v2 .footer-logo-copy {
  display: flex;
  flex-direction: column;
  line-height: 1.12;
}

.ulab-footer-v2 .footer-logo-copy strong {
  color: #fff;
  font-size: 1rem;
  font-weight: 950;
}

.ulab-footer-v2 .footer-logo-copy small {
  margin-top: 4px;
  color: #c9dcf6;
  font-size: .68rem;
  font-weight: 800;
}

.ulab-footer-v2 .footer-brand p {
  max-width: 430px;
  margin: 20px 0 0;
  color: var(--footer-text);
  font-size: .96rem;
  line-height: 1.7;
}

.ulab-footer-v2 .footer-credit {
  display: grid;
  gap: 5px;
  margin-top: 18px;
  color: var(--footer-muted);
  font-size: .84rem;
  font-weight: 750;
  line-height: 1.45;
}

.ulab-footer-v2 .footer-credit i {
  color: #f4b4bc;
}

.ulab-footer-v2 .footer-social {
  display: flex;
  gap: 10px;
  margin-top: 18px;
}

.ulab-footer-v2 .footer-social a {
  width: 34px;
  height: 34px;
  display: inline-grid;
  place-items: center;
  border-radius: 999px;
  color: #0b2d60 !important;
  background: #fff;
  text-decoration: none !important;
  transition: transform .16s ease, background .16s ease;
}

.ulab-footer-v2 .footer-social a:hover {
  transform: translateY(-2px);
  background: #dff3ff;
}

.ulab-footer-v2 h2 {
  margin: 0 0 18px;
  color: #fff;
  font-size: .92rem;
  font-weight: 950;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.ulab-footer-v2 .footer-contact,
.ulab-footer-v2 .footer-locations {
  padding-left: clamp(0px, 3vw, 36px);
  border-left: 1px solid var(--footer-line);
}

.ulab-footer-v2 .footer-contact ul {
  display: grid;
  gap: 14px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.ulab-footer-v2 .footer-contact li {
  display: grid;
  grid-template-columns: 24px minmax(0, 1fr);
  gap: 12px;
  align-items: start;
  color: #dceaff;
  font-size: .95rem;
  line-height: 1.45;
}

.ulab-footer-v2 .footer-contact i {
  margin-top: 2px;
  color: #b9d9ff;
}

.ulab-footer-v2 .footer-contact .fa-whatsapp {
  color: #25d366;
}

.ulab-footer-v2 .footer-contact .fa-envelope {
  color: #ff746b;
}

.ulab-footer-v2 .footer-contact a,
.ulab-footer-v2 .footer-bottom a {
  color: #dceaff !important;
  text-decoration: none !important;
}

.ulab-footer-v2 .footer-contact a:hover,
.ulab-footer-v2 .footer-bottom a:hover {
  color: #74d6ff !important;
}

.ulab-footer-v2 .footer-location-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.ulab-footer-v2 .footer-location-card {
  min-width: 0;
}

.ulab-footer-v2 .footer-location-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 8px;
}

.ulab-footer-v2 .footer-location-head span {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 9px;
  color: #fff;
  background: #075eea;
  font-size: .75rem;
  font-weight: 950;
  text-transform: uppercase;
  white-space: nowrap;
}

.ulab-footer-v2 .footer-location-head a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #9ee7ff !important;
  font-size: .72rem;
  font-weight: 850;
  text-decoration: none !important;
  white-space: nowrap;
}

.ulab-footer-v2 .footer-location-head a:hover {
  color: #fff !important;
}

.ulab-footer-v2 .footer-map-frame {
  height: 122px;
  overflow: hidden;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, .18);
  background: var(--footer-panel);
}

.ulab-footer-v2 .footer-map-frame iframe {
  width: 100%;
  height: 100%;
  display: block;
  border: 0;
  filter: invert(90%) hue-rotate(180deg) saturate(.72) contrast(.98);
}

.ulab-footer-v2 .footer-location-card p {
  margin: 10px 0 0;
  color: #d1def0;
  font-size: .86rem;
  line-height: 1.55;
}

.ulab-footer-v2 .footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-top: 34px;
  padding-top: 24px;
  border-top: 1px solid var(--footer-line);
  color: var(--footer-muted);
  font-size: .88rem;
}

.ulab-footer-v2 .footer-bottom p {
  margin: 0;
}

.ulab-footer-v2 .footer-bottom nav {
  display: flex;
  flex-wrap: wrap;
  gap: 22px;
}

.ulab-footer-v2 .footer-bottom a {
  font-weight: 800;
}

@media (max-width: 1100px) {
  .ulab-footer-v2 .footer-grid {
    grid-template-columns: 1fr 1fr;
  }

  .ulab-footer-v2 .footer-locations {
    grid-column: 1 / -1;
    padding-left: 0;
    border-left: 0;
  }
}

@media (max-width: 760px) {
  .ulab-footer-v2 {
    padding-bottom: 104px;
  }

  .ulab-footer-v2 .footer-shell {
    width: min(100% - 28px, 1320px);
  }

  .ulab-footer-v2 .footer-grid,
  .ulab-footer-v2 .footer-location-list {
    grid-template-columns: 1fr;
  }

  .ulab-footer-v2 .footer-contact,
  .ulab-footer-v2 .footer-locations {
    padding-left: 0;
    border-left: 0;
  }

  .ulab-footer-v2 .footer-bottom {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
