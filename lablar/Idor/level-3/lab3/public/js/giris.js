document.addEventListener("DOMContentLoaded", async () => {
  await ustBariOlustur("");

  // eger zaten giris yapilmissa pazara yonlendir / redirect to market if already logged in
  const oturum = await apiCagir("/api/oturum");
  if (oturum.girisYapildi) {
    window.location.href = "/pazar.html";
    return;
  }

  const sekmeGiris = document.getElementById("sekme-giris");
  const sekmeKayit = document.getElementById("sekme-kayit");
  const girisForm = document.getElementById("giris-form");
  const kayitForm = document.getElementById("kayit-form");

  sekmeGiris.addEventListener("click", () => {
    sekmeGiris.classList.add("aktif");
    sekmeKayit.classList.remove("aktif");
    girisForm.style.display = "block";
    kayitForm.style.display = "none";
  });
  sekmeKayit.addEventListener("click", () => {
    sekmeKayit.classList.add("aktif");
    sekmeGiris.classList.remove("aktif");
    kayitForm.style.display = "block";
    girisForm.style.display = "none";
  });

  girisForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const kullaniciAdi = document.getElementById("giris-kullaniciAdi").value;
    const sifre = document.getElementById("giris-sifre").value;
    try {
      await apiCagir("/api/giris", {
        method: "POST",
        body: JSON.stringify({ kullaniciAdi, sifre }),
      });
      window.location.href = "/pazar.html";
    } catch (err) {
      mesajGoster("mesaj", err.message, "hata");
    }
  });

  kayitForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const kullaniciAdi = document.getElementById("kayit-kullaniciAdi").value;
    const email = document.getElementById("kayit-email").value;
    const sifre = document.getElementById("kayit-sifre").value;
    try {
      const sonuc = await apiCagir("/api/kayit", {
        method: "POST",
        body: JSON.stringify({ kullaniciAdi, email, sifre }),
      });
      mesajGoster("mesaj", sonuc.mesaj, "basari");
      sekmeGiris.click();
    } catch (err) {
      mesajGoster("mesaj", err.message, "hata");
    }
  });
});
