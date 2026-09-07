# تجهيز ATELIER للنطاق atelier404.store

هذا مشروع Laravel كامل (PHP + قاعدة بيانات + رفع صور + Webhooks)، لذلك لا يصلح نشره على Vercel أو Cloudflare Pages وحدهما. استخدم استضافة PHP/MySQL دائمة أو VPS؛ لا تستخدم خطة serverless مؤقتة للمتجر والطلبات.

## قبل الربط

1. فعّل الدومين من رسالة Hostinger أولًا.
2. اختر استضافة تدعم PHP 8.2+، MySQL، Cron jobs، وقرصًا دائمًا للصور. الاستضافة التقليدية من Hostinger أو VPS هما الاختياران الأنسب لهذا المشروع.
3. اجعل Document Root يشير إلى مجلد `public` فقط، وليس جذر المشروع.

## إعداد متغيرات الإنتاج

على الخادم، أنشئ ملف `.env` بالقيم التالية (لا ترفع هذا الملف إلى Git):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://atelier404.store
SESSION_DOMAIN=.atelier404.store
SESSION_SECURE_COOKIE=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=YOUR_DATABASE
DB_USERNAME=YOUR_USER
DB_PASSWORD=YOUR_PASSWORD
FILESYSTEM_DISK=public
```

ثم نفّذ:

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

## DNS في Hostinger

بعد أن تحصل على IP الاستضافة:

- أضف A record للاسم `@` باتجاه IP الاستضافة.
- أضف CNAME للاسم `www` باتجاه `atelier404.store` أو حسب ما تطلبه الاستضافة.
- فعّل SSL ثم اجعل الرابط الأساسي `https://atelier404.store`.

## مهام لا تُنسى

- فعّل Cron كل دقيقة: `php /path/to/artisan schedule:run`.
- اترك `storage/app/public` على قرص دائم؛ حذف الملفات عند إعادة التشغيل يزيل صور المنتجات.
- أضف Webhook Paymob بعد اكتمال الربط: `https://atelier404.store/api/v1/webhooks/paymob`.
- راجع [PAYMOB_SETUP.md](PAYMOB_SETUP.md) قبل تفعيل البطاقات.
