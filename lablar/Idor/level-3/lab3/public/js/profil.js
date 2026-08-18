document.addEventListener("DOMContentLoaded", async () => {
  const oturum = await ustBariOlustur("profil");
  if (!oturum || !oturum.girisYapildi) {
    window.location.href = "/index.html";
    return;
  }

  const kendi = await apiCagir("/api/uye/me/bilgi");
  document.getElementById("kendi-profil").innerHTML = profilKartiHtml(kendi, false);

  document.getElementById("goruntule-btn").addEventListener("click", async () => {
    const id = document.getElementById("hedef-id").value.trim();
    const hedefAlan = document.getElementById("hedef-profil");
    hedefAlan.innerHTML = "";
    if (!id) return;
    try {
      // TR: Bu istek /api/uye/:id ucuna gider. Sunucu SADECE oturumun
      //     acik olup olmadigina bakar; ID'nin sana ait olup olmadigina
      //     BAKMAZ. Yani kraliceye ait ID'yi buraya yapistirirsan onun
      //     tam profilini gorebilirsin.
      // EN: This request hits /api/uye/:id. The server ONLY checks
      //     whether you are logged in - it does NOT check whether the
      //     ID belongs to you. Paste the queen's ID here and you'll see
      //     her full profile.
      const hedef = await apiCagir(`/api/uye/${encodeURIComponent(id)}`);
      hedefAlan.innerHTML = profilKartiHtml(hedef, oturum.rol === "kralice");
      const silBtn = document.getElementById("uye-sil-btn");
      if (silBtn) {
        silBtn.addEventListener("click", async () => {
          if (!confirm(`${hedef.kullaniciAdi} koloniden silinsin mi? / Remove ${hedef.kullaniciAdi} from the colony?`)) return;
          try {
            const sonuc = await apiCagir(`/api/uye/${encodeURIComponent(id)}`, { method: "DELETE" });
            mesajGoster("mesaj", sonuc.mesaj, "basari");
            hedefAlan.innerHTML = "";
          } catch (err) {
            mesajGoster("mesaj", err.message, "hata");
          }
        });
      }
    } catch (err) {
      mesajGoster("mesaj", err.message, "hata");
    }
  });
});

function profilKartiHtml(u, silButonuGoster) {
  return `
    <img class="buyuk-avatar" src="${rolAvatari(u.rol)}" alt="${u.kullaniciAdi}" />
    <div>
      <h2 style="margin:0 0 4px;color:var(--amber-parlak)">${u.kullaniciAdi}</h2>
      <span class="${rozetSinifi(u.rol)}">${rolAdi(u.rol)}</span>
      <div class="profil-satir"><span class="etiket">Yuva / Nest</span><span>${u.yuva}</span></div>
      <div class="profil-satir"><span class="etiket">E-posta / Email</span><span>${u.email}</span></div>
      <div class="profil-satir"><span class="etiket">Besin Puanı / Nutrition Points</span><span>${u.besinPuani}</span></div>
      <div class="profil-satir" style="border-bottom:none;"><span class="etiket">Hakkında / About</span><span style="max-width:60%;text-align:right;">${u.aciklama}</span></div>
      ${silButonuGoster ? `<div style="margin-top:14px;text-align:right;"><button class="tehlike" id="uye-sil-btn">Bu Üyeyi Koloniden Çıkar / Remove Member</button></div>` : ""}
    </div>`;
}
