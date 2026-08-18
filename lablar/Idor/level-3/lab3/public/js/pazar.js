document.addEventListener("DOMContentLoaded", async () => {
  const oturum = await ustBariOlustur("pazar");
  if (!oturum || !oturum.girisYapildi) {
    window.location.href = "/index.html";
    return;
  }

  const izgara = document.getElementById("urun-izgara");
  const urunler = await apiCagir("/api/pazar/urunler");

  izgara.innerHTML = urunler
    .map(
      (u) => `
      <div class="urun-karti">
        <img src="/img/${u.ikon}" alt="${u.ad}" />
        <h3>${u.ad}</h3>
        <div class="en-ad">${u.adEn}</div>
        <p class="aciklama">${u.aciklama.split(" / ")[0]}</p>
        <div class="fiyat">${u.fiyat} ${u.birim}</div>
        <button data-id="${u.id}" class="satin-al-btn">Satın Al / Buy</button>
      </div>`
    )
    .join("");

  izgara.querySelectorAll(".satin-al-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      try {
        const sonuc = await apiCagir("/api/pazar/satin-al", {
          method: "POST",
          body: JSON.stringify({ urunId: Number(btn.dataset.id) }),
        });
        mesajGoster("mesaj", `${sonuc.mesaj} (Kalan puan / Remaining: ${sonuc.kalanPuan})`, "basari");
      } catch (err) {
        mesajGoster("mesaj", err.message, "hata");
      }
    });
  });
});
