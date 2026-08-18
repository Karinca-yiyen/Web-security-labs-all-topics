// Ortak yardimci fonksiyonlar / Common helper functions

async function apiCagir(url, secenekler = {}) {
  const yanit = await fetch(url, {
    headers: { "Content-Type": "application/json" },
    ...secenekler,
  });
  const veri = await yanit.json().catch(() => ({}));
  if (!yanit.ok) {
    const hata = new Error(veri.hata || "Bilinmeyen hata / Unknown error");
    hata.veri = veri;
    throw hata;
  }
  return veri;
}

function rozetSinifi(rol) {
  if (rol === "kralice") return "rozet kralice";
  if (rol === "asker") return "rozet asker";
  return "rozet isci";
}

function rolAdi(rol) {
  return { kralice: "Kraliçe / Queen", asker: "Asker Karınca / Soldier Ant", isci: "İşçi Karınca / Worker Ant" }[rol] || rol;
}

function rolAvatari(rol) {
  if (rol === "kralice") return "/img/karinca-kralice.svg";
  if (rol === "asker") return "/img/karinca-asker.svg";
  return "/img/karinca-isci.svg";
}

async function ustBariOlustur(aktifSayfa) {
  const kapsayici = document.getElementById("ust-bar-alani");
  if (!kapsayici) return null;

  let oturum = { girisYapildi: false };
  try {
    oturum = await apiCagir("/api/oturum");
  } catch (e) {
    /* sessizce gec / silently continue */
  }

  const linkler = [
    { href: "/pazar.html", ad: "Pazar", en: "Market", key: "pazar" },
    { href: "/koloni.html", ad: "Koloni Üyeleri", en: "Members", key: "koloni" },
    { href: "/profil.html", ad: "Profilim", en: "My Profile", key: "profil" },
  ];
  if (oturum.girisYapildi && oturum.rol === "kralice") {
    linkler.push({ href: "/yonetim.html", ad: "Yönetim Paneli", en: "Admin Panel", key: "yonetim" });
  }

  const menuHtml = oturum.girisYapildi
    ? linkler
        .map(
          (l) =>
            `<a href="${l.href}" class="${l.key === aktifSayfa ? "aktif" : ""}">${l.ad} <span style="opacity:.55;font-size:.75em">/ ${l.en}</span></a>`
        )
        .join("")
    : "";

  const sagHtml = oturum.girisYapildi
    ? `<div class="oturum-bilgi">
         <span class="${rozetSinifi(oturum.rol)}">${rolAdi(oturum.rol)}</span>
         <strong>${oturum.kullaniciAdi}</strong>
         <button class="ikincil" id="cikis-btn">Çıkış / Logout</button>
       </div>`
    : `<div class="oturum-bilgi"><a class="buton" href="/index.html">Giriş / Login</a></div>`;

  kapsayici.innerHTML = `
    <header class="ust-bar">
      <a class="logo" href="/pazar.html">
        <img src="/img/logo.svg" alt="Karınca Ticaret" />
        <span>Karınca Ticaret <span class="alt-baslik">Ant Trade — Colony Marketplace</span></span>
      </a>
      <nav class="menu">${menuHtml}</nav>
      ${sagHtml}
    </header>`;

  const cikisBtn = document.getElementById("cikis-btn");
  if (cikisBtn) {
    cikisBtn.addEventListener("click", async () => {
      await apiCagir("/api/cikis", { method: "POST" });
      window.location.href = "/index.html";
    });
  }

  return oturum;
}

function mesajGoster(elId, metin, tip = "hata") {
  const el = document.getElementById(elId);
  el.textContent = metin;
  el.className = `mesaj-kutu gorunur ${tip}`;
}
