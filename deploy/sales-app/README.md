# MSK Devs Sales: salesman aur owner ki app

Salesman kahin bhi order leta hai, internet na ho tab bhi. Order phone mein mehfooz rehta hai aur internet aate hi owner tak pohanch jata hai.
Owner app mein (ya computer par **Orders from salesmen**) order dekhta hai, manzoor karta hai, aur aik click se sale bana deta hai.

| File | Kis kaam ki |
|---|---|
| `MSK-Devs-Sales-1.0.apk` | Seedha phone par install (WhatsApp se salesmen ko bhej dein) |
| `MSK-Devs-Sales-1.0.aab` | Google Play Store par upload ke liye |

---

## 1. Pehle server update (zaroori)
`deploy/update-2026-11-11/` se:
1. `update-files.zip` → products.assanzameen.com ke folder mein Extract (overwrite).
2. Database: SSH ho to `php artisan migrate --force`.
   Warna phpMyAdmin → Import, **is tartib se, har aik sirf aik dafa**:
   - `database-update.sql` (11 Nov: vendors/employees). Agar pehle chala chuke hain to dobara nahi.
   - `database-update-2026-11-12.sql` (orders aur app logins).
3. `.env` mein ye line add karein:
   ```
   SALES_APP_ANDROID_SHA256=EF:9A:74:00:69:57:E2:A5:84:6A:AB:04:94:B2:CF:A5:D6:B7:2E:3F:D3:40:91:0C:42:03:EC:88:A2:A2:14:F3
   ```
4. `bootstrap/cache/` mein `config.php` / `routes-v7.php` ho to delete karein.
5. Check karein: https://products.assanzameen.com/sales khule, aur https://products.assanzameen.com/.well-known/assetlinks.json mein upar wala SHA nazar aaye.

## 2. Salesman ko login dein
Computer par: **Vendors and staff → Employees and salesmen** → salesman kholein → **Give app login** → login (phone) aur password → Save.
Wahi login aur password salesman ko de dein. Owner apne normal login se app mein aata hai.
Login band karna ho to wahin **Switch off login**: us ka phone foran sign out ho jata hai.

## 3. App phone par
- **Abhi foran:** `MSK-Devs-Sales-1.0.apk` WhatsApp par bhejein → phone par kholein → "Install unknown apps" ki ijazat → Install.
- **Ya bina APK:** phone ke Chrome mein https://products.assanzameen.com/sales → menu → **Add to Home screen**.
- Pehli dafa login ke liye internet chahiye. Us ke baad products aur customers phone mein rehte hain.

## 4. Google Play Store par lagana
1. https://play.google.com/console par developer account banayein (aik dafa ki fees, $25).
2. **Create app** → naam `MSK Devs Sales`, Free, App.
3. **Release → Production → Create new release** → `MSK-Devs-Sales-1.0.aab` upload.
4. Store listing:
   - Icon: `public/app-icons/icon-512.png`
   - Kam az kam 2 phone screenshots
   - Chhoti aur lambi tafseel
5. App content: privacy policy ka link (website par aik "Privacy" page bana kar uska link), content rating, Data safety.
   Data safety mein likhein: naam/phone (customers ke), location (order ki jagah, optional), sab business ke apne server par.
6. Upload ke baad: **Setup → App integrity → App signing**. Wahan **"App signing key certificate" ka SHA-256** milega.
   Isay `.env` ki usi line mein comma laga kar jor dein:
   ```
   SALES_APP_ANDROID_SHA256=EF:9A:...:F3,XX:XX:...(Play wala)
   ```
   Is ke baghair Play Store wali app mein upar browser ki patti nazar aayegi.

## 5. ⚠️ Signing key: bahut zaroori
`android/keystore/` folder (`msk-sales-upload.jks` aur `keystore.properties`) ka **backup** USB/Google Drive par rakhein, aur kisi ko na dein.
Ye kho gaya to Play Store par app ka update nahi ho sakega. Ye folder kabhi server par upload na karein.

## Naya version banana (baad mein)
`android/app/build.gradle.kts` mein `versionCode` 1 barha dein (2, 3…), phir:
```
cd android
gradlew bundleRelease assembleRelease
```
(Is computer par Android Studio ki Java ke saath; files `android/app/build/outputs/` mein bante hain.)
App ka zyada tar kaam website par hai: screen ya features badalne ke liye nayi APK ki zaroorat **nahi**. Server update karte hi sab phones par naya version aa jata hai.
