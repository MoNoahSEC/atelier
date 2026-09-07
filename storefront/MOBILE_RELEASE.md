# ATELIER mobile release checklist

The Android and iOS shells are Capacitor applications with ID `com.atelier.storefront`. They load the secured production store at `https://atelier404.store`; do not build store releases until the domain has HTTPS and the API is live.

## Before every release

1. Copy `.env.production.example` to `.env.production` and confirm the public URL.
2. Run `npm ci` and `npm run build`. `npm run lint` must also be clean before a store submission; it currently reports legacy TypeScript/React lint debt that should be resolved separately rather than suppressed.
3. Run `npm run mobile:sync` to update the native projects.
4. Increase version number and build number in Android and iOS before uploading a new release.

## Android / Google Play

1. Open `android` in Android Studio after `npm run mobile:android`.
2. Set a unique signing key in Android Studio; never commit the `.jks` file or passwords.
3. Set `versionCode` higher than the previous release and update `versionName` in `android/app/build.gradle`.
4. Build **Generate Signed Bundle / APK → Android App Bundle**. Upload the `.aab` to Google Play Console.
5. Complete Data safety, Privacy Policy URL, screenshots, contact email, and content rating in Play Console.
6. For verified product links, replace the fingerprint placeholder in `../public/.well-known/assetlinks.json.example`, rename it to `assetlinks.json`, then redeploy the Laravel site.

## iOS / App Store

1. On a Mac with Xcode, run `npm run mobile:ios`, then open `ios/App/App.xcworkspace`.
2. Sign in with the Apple Developer account, set Team and a unique Bundle Identifier, then increment Version and Build.
3. Archive and upload through Xcode Organizer to App Store Connect.
4. Complete App Privacy, support URL, Privacy Policy URL, age rating, screenshots, and reviewer account instructions if the admin area needs review access.
5. Replace the Apple Team ID placeholder in `../public/.well-known/apple-app-site-association.example`, rename it without `.example`, then redeploy. This enables opening product links inside the app.

## Important store rules

- Payment cards are entered only in Paymob's hosted, certified page—never inside the app's own fields.
- The app needs a clear customer purpose beyond an empty web wrapper: catalog, order tracking, saved addresses, and push notifications are the current customer features. Add native push notifications before a public launch for a stronger store submission.
- Test login, cart, color selection, checkout, webhooks, and deep links on real devices using the final HTTPS domain.
