/**
 * ============================================================================
 *  KARINCA TICARET - IDOR LAB (SEVIYE 2 / LEVEL 2)
 *  ANT TRADE - IDOR LAB (LEVEL 2)
 * ============================================================================
 *
 *  TR: Bu sunucu KASITLI OLARAK zafiyetli yazilmistir. Egitim / lab amaclidir.
 *      Gercek bir uretim ortaminda ASLA bu sekilde kod yazmayin.
 *
 *  EN: This server is INTENTIONALLY vulnerable. Built for educational /
 *      lab purposes only. NEVER write production code like this.
 *
 *  Zafiyetli noktalar asagida "[ZAFIYET / VULNERABILITY]" etiketiyle
 *  isaretlenmistir. Detayli anlatim icin README.md dosyasina bakin.
 *  Vulnerable spots are tagged with "[ZAFIYET / VULNERABILITY]" below.
 *  See README.md for the full write-up.
 * ============================================================================
 */

const express = require("express");
const session = require("express-session");
const fs = require("fs");
const path = require("path");
const { v4: uuidv4 } = require("uuid");

const app = express();
const PORT = process.env.PORT || 3000;

const UYELER_PATH = path.join(__dirname, "data", "uyeler.json");
const URUNLER_PATH = path.join(__dirname, "data", "urunler.json");

function uyeleriOku() {
  return JSON.parse(fs.readFileSync(UYELER_PATH, "utf-8"));
}
function uyeleriYaz(uyeler) {
  fs.writeFileSync(UYELER_PATH, JSON.stringify(uyeler, null, 2));
}
function urunleriOku() {
  return JSON.parse(fs.readFileSync(URUNLER_PATH, "utf-8"));
}

app.use(express.json());
app.use(express.static(path.join(__dirname, "public")));
app.use(
  session({
    secret: "karinca-kolonisi-super-gizli-anahtar", // sadece lab icin / lab only
    resave: false,
    saveUninitialized: false,
    cookie: { httpOnly: true, maxAge: 1000 * 60 * 60 * 4 },
  })
);

// -------------------------------------------------------------------------
// Yardimci middleware / Helper middleware
// -------------------------------------------------------------------------
function girisGerekli(req, res, next) {
  if (!req.session || !req.session.uyeId) {
    return res.status(401).json({ hata: "Once giris yapmalisin. / You must log in first." });
  }
  next();
}

// -------------------------------------------------------------------------
// KAYIT / GIRIS / CIKIS  --  REGISTER / LOGIN / LOGOUT
// -------------------------------------------------------------------------
app.post("/api/kayit", (req, res) => {
  const { kullaniciAdi, sifre, email } = req.body;
  if (!kullaniciAdi || !sifre || !email) {
    return res.status(400).json({ hata: "Tum alanlar zorunlu. / All fields are required." });
  }
  const uyeler = uyeleriOku();
  if (uyeler.some((u) => u.kullaniciAdi.toLowerCase() === kullaniciAdi.toLowerCase())) {
    return res.status(409).json({ hata: "Bu kullanici adi zaten alinmis. / Username already taken." });
  }
  const yeniUye = {
    id: uuidv4(),
    kullaniciAdi,
    sifre,
    email,
    rol: "isci", // yeni uyeler her zaman 'isci' (worker) olarak baslar / new members always start as 'isci' (worker)
    yuva: "Yuva D - Yeni Tunel",
    besinPuani: 100,
    aciklama: "Koloniye yeni katilan bir isci karinca. / A worker ant newly joined to the colony.",
  };
  uyeler.push(yeniUye);
  uyeleriYaz(uyeler);
  res.status(201).json({ mesaj: "Kayit basarili, simdi giris yapabilirsin. / Registration successful, you may now log in." });
});

app.post("/api/giris", (req, res) => {
  const { kullaniciAdi, sifre } = req.body;
  const uyeler = uyeleriOku();
  const uye = uyeler.find(
    (u) => u.kullaniciAdi.toLowerCase() === (kullaniciAdi || "").toLowerCase() && u.sifre === sifre
  );
  if (!uye) {
    return res.status(401).json({ hata: "Kullanici adi veya sifre hatali. / Invalid username or password." });
  }
  req.session.uyeId = uye.id;
  req.session.rol = uye.rol;
  req.session.kullaniciAdi = uye.kullaniciAdi;
  res.json({ mesaj: "Giris basarili. / Login successful.", id: uye.id, rol: uye.rol });
});

app.post("/api/cikis", (req, res) => {
  req.session.destroy(() => res.json({ mesaj: "Cikis yapildi. / Logged out." }));
});

app.get("/api/oturum", (req, res) => {
  if (!req.session || !req.session.uyeId) {
    return res.json({ girisYapildi: false });
  }
  res.json({
    girisYapildi: true,
    id: req.session.uyeId,
    kullaniciAdi: req.session.kullaniciAdi,
    rol: req.session.rol,
  });
});

// -------------------------------------------------------------------------
// PAZAR (MARKET)
// -------------------------------------------------------------------------
app.get("/api/pazar/urunler", (req, res) => {
  res.json(urunleriOku());
});

app.post("/api/pazar/satin-al", girisGerekli, (req, res) => {
  const { urunId } = req.body;
  const urunler = urunleriOku();
  const urun = urunler.find((u) => u.id === urunId);
  if (!urun) return res.status(404).json({ hata: "Urun bulunamadi. / Product not found." });

  const uyeler = uyeleriOku();
  const uye = uyeler.find((u) => u.id === req.session.uyeId);
  if (!uye) return res.status(404).json({ hata: "Uye bulunamadi. / Member not found." });

  if (uye.besinPuani < urun.fiyat) {
    return res.status(400).json({ hata: "Yetersiz besin puani. / Insufficient nutrition points." });
  }
  uye.besinPuani -= urun.fiyat;
  uyeleriYaz(uyeler);
  res.json({ mesaj: `${urun.ad} satin alindi. / ${urun.adEn} purchased.`, kalanPuan: uye.besinPuani });
});

// -------------------------------------------------------------------------
// KOLONI UYELERI (colony member directory)
//
// [ZAFIYET 1 / VULNERABILITY 1 - Asiri Veri Ifsasi / Excessive Data Exposure]
// TR: Bu endpoint herkese acik "uye rehberi" ozelligi icin var; ancak her
//     uyenin gercek veritabani ID'sini (uuid) de disariya sizdiriyor.
//     Normalde arayuz sadece isim/rol gosterirken, API tüm "id" alanlarini
//     da dondurur. Bu ID'ler bir sonraki adimda (asagidaki /api/uye/:id ve
//     DELETE /api/uye/:id) kullanilabilir hale gelir.
// EN: This endpoint exists for a public "member directory" feature, but it
//     also leaks every member's real database ID (uuid). The UI only shows
//     name/role, yet the API returns the raw "id" field for everyone. Those
//     IDs can then be reused in the next step (see /api/uye/:id and
//     DELETE /api/uye/:id below).
// -------------------------------------------------------------------------
app.get("/api/koloni/uyeler", girisGerekli, (req, res) => {
  const uyeler = uyeleriOku().map((u) => ({
    id: u.id, // <-- sizdirilan ID / the leaked ID
    kullaniciAdi: u.kullaniciAdi,
    rol: u.rol,
    yuva: u.yuva,
  }));
  res.json(uyeler);
});

// -------------------------------------------------------------------------
// TEKIL UYE PROFILI (single member profile)
//
// [ZAFIYET 2 / VULNERABILITY 2 - IDOR: Nesne Sahipligi Kontrolu Yok]
// [VULNERABILITY 2 - IDOR: Missing Object Ownership Check]
// TR: Endpoint sadece "giris yapilmis mi?" diye bakiyor. Istekte gecen :id
//     parametresinin, istegi yapan kullaniciya ait olup olmadigini HICBIR
//     ZAMAN dogrulamiyor. Yani A kullanicisi, B (hatta kralice) kullanicisinin
//     tam profilini (email, yuva, besin puani, aciklama) ID'sini bilerek
//     cekebiliyor.
// EN: The endpoint only checks "are you logged in?". It NEVER verifies that
//     the :id in the request actually belongs to the requester. So user A
//     can fetch user B's (or even the queen's) full profile - email, nest,
//     nutrition points, bio - simply by knowing their ID.
// -------------------------------------------------------------------------
app.get("/api/uye/:id", girisGerekli, (req, res) => {
  const uyeler = uyeleriOku();
  const uye = uyeler.find((u) => u.id === req.params.id);
  if (!uye) return res.status(404).json({ hata: "Uye bulunamadi. / Member not found." });

  // sifre asla donmez, ama diger her sey doner / password is never returned, but everything else is
  const { sifre, ...guvenliUye } = uye;
  res.json(guvenliUye);
});

app.get("/api/uye/me/bilgi", girisGerekli, (req, res) => {
  const uyeler = uyeleriOku();
  const uye = uyeler.find((u) => u.id === req.session.uyeId);
  if (!uye) return res.status(404).json({ hata: "Uye bulunamadi. / Member not found." });
  const { sifre, ...guvenliUye } = uye;
  res.json(guvenliUye);
});

// -------------------------------------------------------------------------
// UYE SILME (delete member)
//
// [ZAFIYET 3 / VULNERABILITY 3 - Kirik Islev Seviyesi Yetkilendirme]
// [VULNERABILITY 3 - Broken Function Level Authorization]
// TR: Bu islem sadece Kralice'nin (admin) yonetim panelinden cagirilmasi
//     gereken bir islevdir. Ancak sunucu tarafinda SADECE oturum acik mi
//     diye bakiliyor; req.session.rol === 'kralice' kontrolu YOK. Bu yuzden
//     sikayetci ID'yi (yukaridaki iki zafiyetle bulunan) bilen HERHANGI BIR
//     giris yapmis uye, baska bir uyeyi -hatta kralicenin kendisini- silebilir.
//     Bu, IDOR'un "veri okuma" halinden "yetkisiz islem yapma" haline evrilmis
//     klasik bir ornegidir.
// EN: This action is meant to be called only from the Queen's (admin) panel.
//     But the server only checks whether a session exists - there is NO
//     req.session.rol === 'kralice' check. So ANY logged-in member who knows
//     a target ID (discovered via the two vulnerabilities above) can delete
//     another member - even the Queen herself. This is the classic evolution
//     of an IDOR from "unauthorized read" into "unauthorized write/delete".
// -------------------------------------------------------------------------
app.delete("/api/uye/:id", girisGerekli, (req, res) => {
  let uyeler = uyeleriOku();
  const hedef = uyeler.find((u) => u.id === req.params.id);
  if (!hedef) return res.status(404).json({ hata: "Uye bulunamadi. / Member not found." });

  uyeler = uyeler.filter((u) => u.id !== req.params.id);
  uyeleriYaz(uyeler);
  res.json({ mesaj: `${hedef.kullaniciAdi} koloniden silindi. / ${hedef.kullaniciAdi} was removed from the colony.` });
});

// -------------------------------------------------------------------------
// YONETIM PANELI ICIN DOGRU ORNEK  (kontrast icin - the CORRECT pattern, for contrast)
// TR: Bu endpoint referans amaclidir: rolu dogru sekilde kontrol eder.
//     Ogrenciler bu iki yaklasimi karsilastirarak dogru duzeltmeyi anlayabilir.
// EN: This endpoint is a reference: it checks the role correctly. Students
//     can compare the two approaches to understand the proper fix.
// -------------------------------------------------------------------------
app.get("/api/yonetim/uyeler", girisGerekli, (req, res) => {
  if (req.session.rol !== "kralice") {
    return res.status(403).json({ hata: "Bu alana sadece kralice erisebilir. / Only the queen can access this area." });
  }
  const uyeler = uyeleriOku().map(({ sifre, ...rest }) => rest);
  res.json(uyeler);
});

app.listen(PORT, () => {
  console.log(`\n🐜  Karinca Ticaret (IDOR Lab - Seviye 2) http://localhost:${PORT} adresinde calisiyor.`);
  console.log(`🐜  Ant Trade (IDOR Lab - Level 2) running at http://localhost:${PORT}\n`);
});
