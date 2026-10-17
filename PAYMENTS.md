# ABA PayWay KHQR payments — setup & checklist

The site takes card-free KHQR payments through **ABA PayWay**. PayWay tells the
server about a payment by calling a webhook, so there is **no daily request
limit** — the reason the old Bakong integration had to go (a basic Bakong token
allows 100 checks per *day*, and polling burned through it before lunch). That
integration has since been removed entirely; PayWay is the only gateway.

---

## 1. .env

```
APP_TIMEZONE=Asia/Phnom_Penh

# Sandbox while testing, live when you go live
PAYWAY_BASE_URL=https://checkout-sandbox.payway.com.kh
PAYWAY_MERCHANT_ID=your_merchant_id
PAYWAY_API_KEY=your_api_key
# Must be a PUBLIC https URL — see section 3
PAYWAY_CALLBACK_URL=https://your-tunnel.trycloudflare.com/payway/callback
PAYWAY_CURRENCY=USD
PAYWAY_QR_LIFETIME=10
PAYWAY_TIMEOUT=15
```

| Key | Where it comes from |
|---|---|
| `PAYWAY_MERCHANT_ID` | ABA merchant portal |
| `PAYWAY_API_KEY` | ABA merchant portal — they call it the **Public Key** |
| `PAYWAY_BASE_URL` | `https://checkout-sandbox.payway.com.kh` (test) / `https://checkout.payway.com.kh` (live) |
| `PAYWAY_CALLBACK_URL` | Your own public URL ending in `/payway/callback` |

Watch out for:

- **No trailing spaces or newlines in `PAYWAY_API_KEY`.** It is the HMAC secret;
  one stray space makes every request fail with PayWay error code `1`.
- **ABA must whitelist your server's public IP**, or every request fails with
  code `6` / `102`. Ask them to add it. This also applies to your tunnel's exit
  IP while testing.
- **KHQR must be enabled on your merchant profile** (code `23` if not).

After *every* `.env` change:

```
php artisan config:clear
php artisan payway:test
```

Everything must show `[OK]` before customers can pay.

---

## 2. Run the migration once

```
php artisan migrate
```

This adds `qr_image` and `deeplink` to `payments` and makes the old Bakong-only
`md5` column nullable. Existing payment rows are untouched.

---

## 3. Testing the callback locally (XAMPP)

**PayWay cannot reach `localhost` or `127.0.0.1`.** Its servers are on the
internet and yours is not, so without a tunnel the webhook never arrives. (The
payment still works — the page falls back to asking PayWay directly at most once
every 20 seconds — but you would not be testing the thing you actually shipped.)

### Option A — cloudflared (free, no account)

```
# install once (Windows):
winget install --id Cloudflare.cloudflared

# then, with XAMPP/Apache already serving the site:
cloudflared tunnel --url http://localhost:8000
```

It prints a URL like `https://random-words-here.trycloudflare.com`. Put it in
`.env`:

```
PAYWAY_CALLBACK_URL=https://random-words-here.trycloudflare.com/payway/callback
```

Then `php artisan config:clear`.

### Option B — ngrok (needs a free account)

```
winget install --id ngrok.ngrok
ngrok config add-authtoken YOUR_TOKEN
ngrok http 8000
```

Same idea: copy the `https://....ngrok-free.app` URL into
`PAYWAY_CALLBACK_URL` with `/payway/callback` on the end, then
`php artisan config:clear`.

### Confirm the tunnel actually reaches you

```
curl -i https://your-tunnel-url/payway/callback
```

A **405 Method Not Allowed** is the *correct* answer — the route exists and only
accepts POST. A 404 means the URL is wrong; a timeout means the tunnel is down.

### Things that will catch you out

- **`PAYWAY_CALLBACK_URL` is YOUR address, not PayWay's.** It is where PayWay
  should notify *you*, so it must end in `/payway/callback` on your own domain or
  tunnel. Pasting one of PayWay's own API endpoints here (for example
  `https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase`)
  breaks payments *silently*: QRs still generate, so everything looks fine, but
  PayWay posts the confirmation to itself and this app is never told. Customers
  then only get unlocked while they sit on the payment page, and anyone who
  closes it stays locked out after paying. `payway:test` now refuses this
  outright, and checkout refuses to sell rather than take money it cannot
  confirm.
- The free cloudflared/ngrok URL **changes every time you restart the tunnel**.
  Update `.env` and run `config:clear` each time, or the callback goes to a dead
  address.
- Your `APP_URL` can stay `http://127.0.0.1:8000`. Only
  `PAYWAY_CALLBACK_URL` needs to be public.
- Ask ABA to whitelist the tunnel's exit IP if PayWay refuses your requests.
- Watch it arrive: `php artisan pail` in a second terminal, or tail
  `storage/logs/laravel.log` for `PayWay callback received`.

---

## 4. How a payment works

1. Customer clicks Pay → the server reads the price **from the database**, asks
   PayWay for a KHQR, and stores a `pending` payment row.
2. The page shows the QR, the amount, a countdown, and on a phone an
   **Open ABA Mobile** button (PayWay's deeplink).
3. Customer pays in ABA Mobile or any KHQR bank app.
4. **PayWay POSTs to `/payway/callback`.** That callback is *not trusted on its
   own*: the server reads only the transaction id from it and then asks PayWay's
   `check-transaction-2` endpoint what really happened.
5. Only if that answers `payment_status = APPROVED` **and the amount matches**
   does the payment become `paid` and the enrollment get created — inside one
   database transaction with a row lock, so a repeated callback cannot enrol
   twice.
6. Meanwhile the page polls **our own** status endpoint every 3 seconds. That
   reads the database only; it falls back to asking PayWay at most once per 20
   seconds per order, as a safety net for a callback that never arrived.
7. When paid: success tick, then straight into the course.

An amount that does not match is held as `review` and the course stays locked —
settle those by hand (section 6). **Every admin is emailed the moment that
happens**, with the two figures side by side, because the customer is told to
contact you and their money has most likely already moved. That email goes out
exactly once per payment.

---

## 5. Useful commands

| Command | What it does |
|---|---|
| `php artisan payway:test` | Checks settings and generates a real sandbox QR. Nothing is charged. **Run after every .env change.** |
| `php artisan payments:confirm PD...` | Asks PayWay about one invoice and unlocks the course. Add `--force` to confirm manually *after* you have seen the money arrive. |
| `php artisan payway:recheck` | The safety net. Asks PayWay about pending QRs whose callback never arrived and unlocks what was paid. Also re-sends any receipt or review alert that was owed and never went out (an SMTP outage, say) — those cost no PayWay calls. **Runs automatically every 10 minutes** — see below. `--pretend` to look without changing anything. |
| `php artisan payments:receipt PD...` | Emails the receipt for one paid payment. For the customer who says they never got it, and for payments made before receipts existed. `--force` to send one that has already gone out. |
| `php artisan payments:refund PD...` | Records a refund and removes the course access it paid for. Asks PayWay first and refuses unless PayWay agrees it was refunded; `--force` skips that check. Refund the money in the ABA portal — this command does not move money. |

### The scheduler

`payway:recheck` is on a 10-minute timer, so the server needs a cron entry:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Without it the sweeper never fires and a payment whose callback was lost stays
locked until the customer comes back to the site. Check it with
`php artisan schedule:list`.

### Pre-PayWay payments

The `payments` table still holds rows from before the switch, tagged with a
Bakong gateway and an `md5`. They stay in the books and read normally, but
nothing can verify them any more — `payments:confirm` refuses them rather than
stamping them as PayWay payments, which would make the books lie about where
the money came from. To give such a student their course, use
**Admin → Students → the student → Give course access**.

---

## 6. When something goes wrong

Customers **never** see PayWay's technical wording — it names `.env` keys, the
API host, hash failures and merchant ids. They always get a plain message
("Online payment is temporarily unavailable…") in their own language, regardless
of `APP_DEBUG`.

The real reason goes to `storage/logs/laravel.log`. To read it:

```
php artisan payway:test     # the full diagnosis, for you
```

Or search the log for:

| Log line | Meaning |
|---|---|
| `ABA PayWay is not configured` | A `.env` key is missing — the entry lists which |
| `PayWay callback received` | The raw webhook payload, exactly as PayWay sent it |
| `PayWay course payment confirmed` | Money in, course unlocked |
| `PayWay payment amount does not match the order` | Held for `review`, course **not** unlocked — the admins are emailed |
| `A payment is held for review but there is no admin to tell` | Nobody has `role = admin`, so the alert could not be sent. It retries on the next sweep |
| `Could not email the review alert` | The mail server refused. The payment is still correctly held, and the next sweep tries again |
| `PayWay callback could not be confirmed` | We could not reach PayWay; it will retry |

### PayWay error codes you are likely to hit

| Code | Fix |
|---|---|
| `1` | Wrong `PAYWAY_API_KEY` — check for a trailing space or newline |
| `3` / `403` | Duplicate `tran_id` (should not happen; invoice numbers are unique) |
| `6` / `102` | Your server's public IP is not whitelisted by ABA |
| `11` | Server clock is wrong — `req_time` is sent in UTC |
| `23` | KHQR not enabled on the merchant profile |
| `96` | Wrong `PAYWAY_MERCHANT_ID` |

### "cURL error 60: SSL certificate problem"

Windows PHP often ships without a CA bundle. `AppServiceProvider` already looks
for XAMPP's (`C:\xampp\apache\bin\curl-ca-bundle.crt`) and Laragon's. If yours is
elsewhere, set `HTTP_CA_BUNDLE="C:\path\to\cacert.pem"` in `.env`.

---

## 7. Before going live

- Swap `PAYWAY_BASE_URL` to `https://checkout.payway.com.kh`.
- Set `PAYWAY_CALLBACK_URL` to your **real domain**, `https://`, not a tunnel.
- Make sure ABA has whitelisted the production server's IP.
- `php artisan config:clear && php artisan payway:test`.
- Make one small real payment (set a course to $0.01 temporarily) and confirm the
  log shows `PayWay callback received` followed by
  `PayWay course payment confirmed`.
- `APP_ENV=production` and `APP_DEBUG=false`. Payment messages are safe either
  way (section 6), but `APP_DEBUG=true` still exposes Laravel's own stack traces
  on any *other* unhandled error.
- Change the admin password.

---

## 8. Tests

```
php artisan test --filter=PayWay
```

Covers QR creation and its HMAC field order, an approved callback, a callback
that is not approved, an amount mismatch, a duplicate callback enrolling only
once, the forged-callback case, and the status endpoint's rate limiting. PayWay
itself is faked — no network, no money.
