# MSK Devs: products.assanzameen.com (Hostinger, root se)

Is build mein website **root se chalti hai**: `index.php` aur `.htaccess` products folder mein hain, `public/` mein sirf CSS, JS aur images hain.
`.env`, `vendor`, `storage`, `config` waghera bahar se nahi khulte (403).

| File | Kya hai |
|---|---|
| `msk-devs.zip` | Poora software, root se chalne wala |
| `database.sql` | Khaali database + MSK Devs website + aap ka owner account |
| `OWNER-LOGIN.txt` | Super admin login. Kisi ko na dein. Pehli dafa password badalna parega |
| `root.htaccess`, `root-index.php` | Wahi 2 files jo zip ke root mein hain (pehle se lagi site theek karne ke liye) |

---

## A. Pehle se upload ki hui site ko theek karna (sirf 4 kaam)

File Manager → `domains/assanzameen.com/public_html/products` → "Show hidden files" on.

1. `public/index.php` aur `public/.htaccess` **delete** karein.
2. Products folder mein `index.php` banayein, `root-index.php` ka poora text paste karein.
3. Products folder ki `.htaccess` ka saara text hata kar `root.htaccess` ka poora text paste karein.
4. `.env` mein `APP_URL` wali line ke neeche ye line add karein:
   ```
   ASSET_URL=https://products.assanzameen.com/public
   ```

Bas. https://products.assanzameen.com root se khulegi, aur purane `/public/...` links khud root par chale jayenge.

---

## B. Bilkul naye sire se lagana

1. **PHP**: hPanel → Websites → assanzameen.com → Advanced → PHP Configuration → **8.2 ya 8.3**.
2. **Database**: Databases → MySQL Databases → naya database + user banayein. phpMyAdmin → Import → `database.sql`.
3. **Files**: File Manager → `public_html/products` khaali karein → `msk-devs.zip` upload → isi folder mein Extract → zip delete.
4. **`.env`** edit karein, sirf ye 3 lines:
   ```
   DB_DATABASE=u123456789_msk
   DB_USERNAME=u123456789_msk
   DB_PASSWORD=aap-ka-password
   ```
   `APP_KEY` kabhi na badlein.
5. **SSL**: Security → SSL → products.assanzameen.com → Install.
6. https://products.assanzameen.com/login → `OWNER-LOGIN.txt` wala login → naya password. Owner panel → Plans mein Business plan ki price set karein.
7. **Cron** (Advanced → Cron Jobs, har minute):
   ```
   /usr/bin/php /home/u123456789/domains/assanzameen.com/public_html/products/artisan schedule:run
   ```

500 error aaye to aksar `.env` ka database ghalat hota hai. `storage/logs/` ki aaj ki file dekhein ya mujhe bhej dein.

---

## School ka apna domain (misal aischoolofsciences.com)

0. **Pehle** products folder ki `index.php` aur `.htaccess` ko `root-index.php` aur `root.htaccess` (4 Nov 16:23 wale) se badal dein. Purani files mein school domain par CSS/JS nahi chalti.
1. hPanel → Websites → assanzameen.com → Domains → **Parked domains** → domain add karein.
2. **DNS** (jahan domain khareeda, misal Namecheap → Domain List → Manage → Advanced DNS):
   - Purane A records (88.222.222.68 waghera) aur "URL Redirect" / "Parking" records hata dein.
   - `A` record, Host `@`, Value `156.67.75.172`
   - `CNAME` record, Host `www`, Value `aischoolofsciences.com.`
   (Agar hPanel parked domain ke liye koi aur IP bataye to wahi lagayein.) 10 minute se kuch ghante lag sakte hain.
3. **Bridge (aik dafa):** products subdomain ka folder assanzameen.com ke web folder se bahar hai, is liye:
   - `msk-bridge.zip` ko assanzameen.com ke web folder (`domains/assanzameen.com/public_html`, jahan Assan Zameen ki `index.php` hai) mein upload karke extract karein. `msk-bridge/index.php` ban jayegi.
   - Usi folder ki `.htaccess` mein `RewriteEngine On` ke **foran neeche** `assanzameen-htaccess-lines.txt` ki 6 lines paste karein (baqi rules se pehle).
   - Domain par "software folder was not found" likha aaye to `msk-bridge/index.php` mein `MSK_APP = ''` ke andar products folder ka poora path likhein.
4. DNS chal jaye to Security → SSL → us domain ke liye SSL install karein.
5. Owner panel → Domains → school ke saamne domain likh kar **Check**. School ki website **Published** honi chahiye.

## Update

Naye feature ke liye main update zip dunga (`.env` aur `storage` ke baghair). Upload + extract ke baad SSH se:
```
cd domains/assanzameen.com/public_html/products && php artisan migrate --force && php artisan optimize:clear
```

Note: fingerprint machine wala feature shared hosting par nahi chalta. Emails abhi nahi jaatin (SMTP baad mein set ho sakta hai).
