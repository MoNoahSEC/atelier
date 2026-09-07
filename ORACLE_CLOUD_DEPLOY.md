# نشر ATELIER على Oracle Cloud Free Tier

هذه الوصفة تستخدم Ubuntu على Oracle Always Free مع Docker، وتُشغّل Laravel وMySQL وRedis وNginx وHTTPS تلقائيًا عبر Caddy، بالإضافة إلى Worker وScheduler. لا تحتاج Vercel أو استضافة مدفوعة.

## 1. إعداد Oracle

1. أنشئ Compute Instance من Ubuntu 24.04 (ARM Ampere مناسب للخطة المجانية).
2. في Security List أو Network Security Group افتح منافذ TCP: `22` و`80` و`443` فقط.
3. احتفظ بالمفتاح الخاص للـSSH خارج المشروع.
4. ثبّت Docker على الخادم وفق وثائق Docker الرسمية، ثم تأكد أن `docker compose version` يعمل.

## 2. DNS في Hostinger

أضف السجلات بعد حصولك على الـPublic IPv4 من Oracle:

| Type | Name | Value |
| --- | --- | --- |
| A | `@` | Oracle public IPv4 |
| A | `www` | Oracle public IPv4 |

لا تضف سجل AAAA ما لم تكن قد جهزت IPv6 في Oracle. انتظر حتى يبدأ `atelier404.store` في الإشارة للخادم؛ بعدها Caddy يصدر SSL تلقائيًا. لا تفتح MySQL (`3306`) أو Redis (`6379`) للعامة.

## 3. رفع وتشغيل المشروع

على الخادم:

```bash
git clone YOUR_PRIVATE_REPOSITORY atelier
cd atelier
cp .env.production.example .env.production
nano .env.production
docker compose -f docker-compose.production.yml --env-file .env.production up -d --build
docker compose -f docker-compose.production.yml --env-file .env.production ps
```

ضع قيمة `APP_KEY` عبر هذا الأمر قبل التشغيل الأول:

```bash
docker compose -f docker-compose.production.yml --env-file .env.production run --rm --entrypoint php app artisan key:generate --show
```

انسخ الناتج إلى `APP_KEY` في `.env.production` ثم شغّل الأمر السابق `up -d --build`.

## 4. بعد النشر

```bash
docker compose -f docker-compose.production.yml --env-file .env.production logs -f caddy
curl -I https://atelier404.store/up
```

اختبر: الصفحة الرئيسية، رفع صورة، السلة مع لون، Checkout، Telegram، وPaymob test mode. لا تفعّل `PAYMOB_SANDBOX=false` قبل نجاح الاختبارات.

## نسخ احتياطي وتحديث

- قاعدة البيانات موجودة في Docker volume `mysql-data`؛ صدّر نسخة بانتظام عبر `mysqldump` داخل حاوية `db`.
- الصور في `app-storage`. انسخها أو استخدم Object Storage قبل أي تغيير كبير.
- للتحديث: `git pull` ثم نفس أمر `docker compose ... up -d --build`.

## ملاحظة الأمان

لا ترفع `.env.production` أو مفاتيح Paymob أو مفاتيح Apple/Google أو مفاتيح SSH إلى Git أو Telegram. غيّر كلمات مرور قاعدة البيانات إلى قيم طويلة وفريدة قبل أول تشغيل.
