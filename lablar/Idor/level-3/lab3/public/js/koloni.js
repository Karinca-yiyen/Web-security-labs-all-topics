// TR: Bu sayfa arayuzde sadece isim/rol/yuva gosterir; ancak arka planda
//     cagirdigi /api/koloni/uyeler her uyenin gercek "id" alanini da
//     iceren tam JSON'u dondurur. Tarayici Gelistirici Araclari > Network
//     sekmesinden bu istegi incelersen her uyenin ID'sini gorebilirsin.
// EN: This page only displays name/role/nest in the UI; but the
//     /api/koloni/uyeler request it makes behind the scenes returns the
//     full JSON including every member's real "id" field. Inspect this
//     request in your browser's DevTools > Network tab to see each ID.

document.addEventListener("DOMContentLoaded", async () => {
  const oturum = await ustBariOlustur("koloni");
  if (!oturum || !oturum.girisYapildi) {
    window.location.href = "/index.html";
    return;
  }

  const liste = document.getElementById("uye-listesi");
  let uyeler = await apiCagir("/api/koloni/uyeler");

  function ciz(veri) {
    liste.innerHTML = veri
      .map(
        (u) => `
      <div class="uye-satiri">
        <img class="avatar" src="${rolAvatari(u.rol)}" alt="${u.kullaniciAdi}" />
        <div class="ad-blok">
          <strong>${u.kullaniciAdi}</strong>
          <div style="font-size:0.82rem;opacity:0.75;">${u.yuva}</div>
        </div>
        <span class="${rozetSinifi(u.rol)}">${rolAdi(u.rol)}</span>
      </div>`
      )
      .join("");
  }

  ciz(uyeler);

  document.getElementById("arama").addEventListener("input", (e) => {
    const terim = e.target.value.toLowerCase();
    ciz(uyeler.filter((u) => u.kullaniciAdi.toLowerCase().includes(terim)));
  });
});
