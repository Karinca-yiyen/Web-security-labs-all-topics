document.addEventListener("DOMContentLoaded", async () => {
  const oturum = await ustBariOlustur("yonetim");
  if (!oturum || !oturum.girisYapildi) {
    window.location.href = "/index.html";
    return;
  }
  if (oturum.rol !== "kralice") {
    document.querySelector(".ana-icerik").innerHTML =
      '<div class="uyari-kutu"><strong>Erişim reddedildi.</strong> Bu sayfa sadece kraliçeye özeldir. / <em>Access denied.</em> This page is queen-only.</div>';
    return;
  }

  const liste = document.getElementById("uye-listesi");

  async function yenile() {
    const uyeler = await apiCagir("/api/yonetim/uyeler");
    liste.innerHTML = uyeler
      .map(
        (u) => `
      <div class="uye-satiri">
        <img class="avatar" src="${rolAvatari(u.rol)}" alt="${u.kullaniciAdi}" />
        <div class="ad-blok">
          <strong>${u.kullaniciAdi}</strong>
          <div class="kod">${u.id}</div>
        </div>
        <span class="${rozetSinifi(u.rol)}">${rolAdi(u.rol)}</span>
        <div class="eylemler">
          ${u.rol !== "kralice" ? `<button class="tehlike" data-id="${u.id}" data-ad="${u.kullaniciAdi}">Çıkar / Remove</button>` : ""}
        </div>
      </div>`
      )
      .join("");

    liste.querySelectorAll("button[data-id]").forEach((btn) => {
      btn.addEventListener("click", async () => {
        if (!confirm(`${btn.dataset.ad} koloniden çıkarılsın mı? / Remove ${btn.dataset.ad}?`)) return;
        await apiCagir(`/api/uye/${btn.dataset.id}`, { method: "DELETE" });
        yenile();
      });
    });
  }

  yenile();
});
