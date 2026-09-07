# تفعيل دفع Visa / Mastercard بأمان

الموقع لا يحتفظ برقم البطاقة أو CVV. عند اختيار الدفع الإلكتروني ينشئ طلبًا ثم ينقل العميل إلى صفحة Paymob الآمنة لإدخال بيانات البطاقة وإتمام 3D Secure.

## الإعداد

1. افتح حساب Merchant على Paymob وفعّل **Card payments** في مصر.
2. من لوحة Paymob أنشئ Card integration، ثم انسخ Integration ID وPublic Key وSecret Key وHMAC secret.
3. أضف القيم التالية إلى ملف `.env` على الخادم فقط، ولا تضعها في صفحة الإدارة أو في Git:

```env
PAYMENT_ENABLED_METHODS=paymob,cod
PAYMOB_SECRET_KEY=ضع_المفتاح_السري_هنا
PAYMOB_PUBLIC_KEY=ضع_المفتاح_العام_هنا
PAYMOB_HMAC_SECRET=ضع_مفتاح_HMAC_هنا
PAYMOB_INTEGRATION_IDS=123456
PAYMOB_SANDBOX=false
```

4. في Paymob أضف Webhook URL التالي لاستلام نتيجة الدفع:

```text
https://YOUR-DOMAIN.com/api/v1/webhooks/paymob
```

5. شغّل `php artisan config:clear` بعد تعديل ملف البيئة، ثم نفّذ عملية دفع تجريبية قبل تفعيل الوضع الحقيقي.

إذا كانت المفاتيح ناقصة، سيظهر للعميل تنبيه واضح ويظل الدفع عند الاستلام متاحًا؛ لا يُنشأ طلب دفع إلكتروني غير قابل للإتمام.
