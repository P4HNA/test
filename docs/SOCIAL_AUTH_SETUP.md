# Social Authentication Setup

This project uses Laravel Socialite to provide Google authentication.

## 1. Google OAuth Setup

This project uses a single, shared Google OAuth client for the whole team. Everyone
uses the **same** `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` in their own local
`.env` — teammates should **not** create their own OAuth client in Google Cloud
Console; ask whoever owns the project's Google Cloud project to share the existing
Client ID/Secret with you through a secure channel (password manager, etc.), never
via Git or chat history that gets committed anywhere.

1. (Project owner only, one-time) Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project or select an existing one.
3. Go to **APIs & Services > Credentials**.
4. Click **Create Credentials > OAuth client ID**.
5. Set Application type to **Web application**.
6. Under **Authorized redirect URIs**, add every callback URL the team actually uses. This
   project's local callback is `http://127.0.0.1:8000/auth/google/callback` — use that
   exact host (`127.0.0.1`, not `localhost`; Google matches redirect URIs as exact
   strings, and the two are different origins). Add:
   - `http://127.0.0.1:8000/auth/google/callback`
   - `https://yourdomain.com/auth/google/callback` (production)
   Under **Authorized JavaScript origins**, add `http://127.0.0.1:8000` for the same reason.
7. Copy the **Client ID** and **Client Secret**, and share them with the team through a
   secure channel.
8. Every teammate adds the shared values to their own local `.env` file (copy
   `.env.example` if you haven't already):
   ```env
   GOOGLE_CLIENT_ID=the-shared-client-id
   GOOGLE_CLIENT_SECRET=the-shared-client-secret
   GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
   ```
9. Always open the app at `http://127.0.0.1:8000` while developing (e.g. `php artisan
   serve` already binds there) — not `http://localhost:8000`. Visiting via `localhost`
   will make Google reject the callback with a `redirect_uri_mismatch` error, even
   though the app itself works fine either way.
10. **Consent screen in "Testing" mode:** while the OAuth consent screen is in
    Testing (the default until the app is verified/published), only Google accounts
    explicitly added as **Test users** in the consent screen configuration can
    complete sign-in — everyone else sees an "app not verified" / access-blocked
    screen. If a teammate can't complete Google Sign-In, first check whether their
    Google account has been added as a test user (project owner does this in
    **APIs & Services > OAuth consent screen > Test users**), rather than assuming
    the `.env` values are wrong.

## 2. Account Linkage & Security Notes

- If a user attempts to log in with Google and an existing account with that email already exists, the social account will be automatically linked to the existing user.
- New users created via OAuth will be assigned the `student` role by default.
- They are also assigned a random, highly secure password since the database requires it.
- **Admin Access:** OAuth alone does not grant admin rights. Admin roles must still be granted explicitly (e.g. via the database or a future role management UI).
