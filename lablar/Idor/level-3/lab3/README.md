
## 🎯 Görev / Mission

**TR:**  işçi karıncasın (`iscikarinca_toprak`). Amacın, **Kraliçe'ye özel yönetim yetkisi olan "üye silme" işlemini, kraliçenin şifresini bilmeden, sadece IDOR zafiyetini kullanarak kendi hesabınla gerçekleştirmek.**

**EN:** You are an ordinary worker ant (`iscikarinca_toprak`). Your goal is to **trigger the Queen-only "remove member" action from your own regular account, using only the IDOR flaw — without ever knowing the Queen's password.**

## ⚙️ Kurulum / Setup

```bash
cd lab3
npm install
npm start
```

Sunucu / Server: **http://localhost:3000**

## 🔑 Test Hesapları / Test Accounts

| `iscikarinca_toprak` | `yaprak123` | İşçi / Worker — **saldırgan hesabı olarak kullan / use as attacker account** |

> **TR:** Görevi `kralice_nefertari` olarak giriş yaparak "çözmek" bu labın amacını boşa çıkarır — amaç kraliçenin şifresini bilmeden onun yetkisini taklit etmektir.
> **EN:** "Solving" the mission by logging in as `kralice_nefertari` defeats the purpose — the goal is to replicate her authority *without* knowing her password.

