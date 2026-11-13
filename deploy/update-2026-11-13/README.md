# Update 13 Nov 2026: khud-ba-khud SMS / WhatsApp, owner panel Messaging, apna SMS gateway

- **Owner panel → Messaging** (naya). Yahan chunein ke paigham kaise jayen:
  - **Apna SMS gateway**: aap ka Android phone + SIM. Is ke liye "MSK SMS Gateway" app hai.
  - **WhatsApp / SMS company API**: UltraMsg, GreenAPI, ya koi Pakistani SMS company. Namoone tayyar hain.
  - **WhatsApp Business API (Meta)**.
  - **Off**: sirf WhatsApp outbox, jaise pehle.
- **Plans**: naya feature **"Automatic SMS / WhatsApp"**. Jis plan mein ho, sirf usi school ya business ke reminders khud jayenge.
  Baqi schools ke reminders WhatsApp outbox mein intezar karte hain.
  Purane schools ke liye ye pehle **band** hai. Plans mein tick karke plan dobara lagayein, ya school ke page par feature on karein.
- **Settings → Automatic reminders** (har school / business). Ye har subah khud chalte hain:
  - fee ya qist se 7/5/3/2/1 din pehle;
  - usi din;
  - late par har N din baad.
- **Messages page**:
  - har message ke sath SMS ya WhatsApp likha hota hai;
  - "Not sent" tab mein wajah, aur dobara bhejne ka button;
  - "Send all by SMS" button.

## 1. Files
`update-files.zip` → products.assanzameen.com ke folder mein upload → **Extract** → overwrite **Yes**.
Is mein `.htaccess` bhi hai (naya folder `android-gateway` block kiya gaya hai).

## 2. Database (aik dafa)
- SSH: `php artisan migrate --force` (dobara chal jaye to bhi kuch nahi bigarta).
- Ya phpMyAdmin → Import → `database-update.sql`.

Phir `bootstrap/cache/config.php` aur `routes-v7.php` delete karein (ya `php artisan optimize:clear`).

## 3. Roz subah ke liye Cron (aik dafa)
hPanel → Advanced → **Cron Jobs** → har minute (`* * * * *`):
```
/usr/bin/php /home/USERNAME/domains/products.assanzameen.com/public_html/artisan schedule:run
```
- `USERNAME` aap ka Hostinger user hai.
- Reminders subah 9 baje jate hain. Waqt badalna ho to `.env` mein `SMS_REMINDER_TIME=08:30` likhein.

## 4. Apna SMS gateway phone
1. Owner panel → **Messaging** → **Your own SMS gateway** → Save.
2. Usi page par "Download the gateway app". APK phone mein daal kar install karein.
3. **Gateway phones** → naam likh kar **Add phone** → **Copy setup code**.
4. Ye code phone par bhejein (apne aap ko WhatsApp). Phone par copy karein, app mein **Setup code paste karein** → **Start karein**.
5. App mein "Phone ki tayyari" ke teeno nishan ✓ hon (SMS ijazat, notification, battery saver se azad).
6. Owner panel → **Send a test** se apne number par SMS check karein.

Phone ka server link: `https://products.assanzameen.com/sms-gateway`

## 5. Har school / business
- Settings → neeche **Automatic reminders** → On.
- Din chunein aur message likhein.
- **Test** se check karein → **Save**.

## Sales app (MSK Devs Sales) ab standard app jaisi
Isi zip mein hai. Database mein koi tabdeeli nahi. Phone ki app (APK) dobara install karne ki zaroorat nahi: wo khud nayi shakal mein khulegi.
- **Neeche ki tabs:** Home, Orders, beech mein bara **+**, Customers, More.
- **Naya order 3 qadam mein:** dukaan chunein → products (category, search, "ye dukaan aksar leti hai") → check karke bhejein.
- **Shop ka page:** Call, WhatsApp, raasta (Google Maps), udhaar, pichle orders, "Order".
- **Orders:** din ke hisaab se (Aaj, Kal...), order ka status (Taken → Sent → Accepted → Sale), aur "Order the same again".
- Phone ka **Back** button pichli screen par le jata hai (pehle app band ho jati thi). Neeche khenchne se update hota hai.

## Count bubbles (ginti ke laal nishan)
Isi zip mein hai. Database mein koi tabdeeli nahi.
- **Web panel:** menu mein har cheez ke sath laal ginti:
  - salesmen ke naye orders;
  - late qisten;
  - bhejne wale messages / "not sent";
  - payment proofs;
  - nayi admission inquiries;
  - website ke unread messages;
  - owner panel mein aaj naakaam SMS.
- Upar **ghanti** par kul ginti. Dabane se list khulti hai, har cheez ka link ke sath.
- Mobile par "Menu" button par laal dot. Browser tab ke naam mein bhi ginti, masalan "(11) Dashboard".
- Har minute khud update hoti hai.
- **Sales app:** upar ghanti aur Orders tab par ginti.
  - Owner ke liye: manzoor karne wale orders.
  - Salesman ke liye: na bheje gaye orders.
  - Phone ijazat de to app ke icon par bhi.
