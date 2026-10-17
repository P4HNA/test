{{--
    The "Change password" and "Suspend account" dialogs, shared by the Students
    list and a student's own page. Include it inside an element that has
    x-data="studentsPage(reopen, passwordUrl, suspendUrl)".

    Both dialogs move keyboard focus inside when they open, keep Tab inside
    while open, and hand focus back to the button that opened them on close.
--}}
        {{-- ================= Change password dialog ================= --}}
        <div class="ad-modal-back" x-show="open" x-cloak x-transition.opacity
            @click.self="close()" @keydown.escape.window="close()">
            <div class="ad-modal" role="dialog" aria-modal="true" aria-labelledby="pw-title" @keydown.tab="trap($event)">
                <form method="POST" :action="action" @submit="busy = true" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="user_id" :value="user.id">

                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:20px 22px 0">
                        <div style="min-width:0">
                            <h2 id="pw-title" class="ad-h2" style="font-size:17px">Change password</h2>
                            <p class="ad-muted ad-sm" style="margin:4px 0 0; overflow-wrap:anywhere">
                                For <strong x-text="user.name"></strong> (<span x-text="user.email"></span>)
                            </p>
                        </div>
                        <button type="button" class="ad-btn ad-icon-btn" style="border:0" @click="close()" aria-label="Close">
                            <x-admin.icon name="x" class="ad-i18" />
                        </button>
                    </div>

                    <div style="display:grid; gap:16px; padding:20px 22px">
                        <div>
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px">
                                <label for="pw-new" class="ad-label" style="margin:0">New password</label>
                                <button type="button" class="ad-sm" style="border:0; background:none; color:var(--accent); font-weight:600; cursor:pointer" @click="generate()">
                                    Generate a strong one
                                </button>
                            </div>
                            <div style="position:relative">
                                <input id="pw-new" x-ref="pw" name="password" x-model="pw" :type="show ? 'text' : 'password'"
                                    class="ad-input" style="padding-right:84px" required minlength="8" autocomplete="new-password">
                                <div style="position:absolute; right:6px; top:4px; display:flex; gap:2px">
                                    <button type="button" class="ad-btn ad-btn-sm" style="border:0; background:transparent" @click="show = !show" x-text="show ? 'Hide' : 'Show'"></button>
                                </div>
                            </div>
                            <div class="ad-bar" style="margin-top:10px"><span :style="'width:' + (pw ? Math.max(12, score * 25) : 0) + '%'"></span></div>
                            <p class="ad-hint" x-text="pw ? strength : 'At least 8 characters with letters and numbers.'"></p>
                            <template x-if="copied === false && show && pw">
                                <button type="button" class="ad-sm" style="margin-top:6px; border:0; background:none; color:var(--accent); font-weight:600; cursor:pointer" @click="copy()">Copy password to share it</button>
                            </template>
                            <p class="ad-hint" style="color:var(--green)" x-show="copied">Copied. Send it to the student privately.</p>
                            @error('password')
                            <p class="ad-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="pw-confirm" class="ad-label">Repeat new password</label>
                            <input id="pw-confirm" name="password_confirmation" x-model="pw2" :type="show ? 'text' : 'password'"
                                class="ad-input" required autocomplete="new-password">
                            <p class="ad-error" x-show="pw2 !== '' && pw !== pw2">The two passwords do not match.</p>
                        </div>

                        <div style="padding-top:16px; border-top:1px solid var(--line)">
                            <label for="pw-admin" class="ad-label">Your own password</label>
                            <input id="pw-admin" type="password" name="current_password" class="ad-input" required autocomplete="current-password">
                            <p class="ad-hint">We ask for this to make sure it is really you making the change.</p>
                            @error('current_password')
                            <p class="ad-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="ad-muted ad-sm" style="margin:0">
                            They will be signed out of every device and must log in with the new password.
                        </p>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; padding:0 22px 22px">
                        <button type="button" class="ad-btn" @click="close()">Cancel</button>
                        <button type="submit" class="ad-btn ad-btn-primary" :disabled="!ready">Save new password</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================= Suspend dialog ================= --}}
        <div class="ad-modal-back" x-show="banOpen" x-cloak x-transition.opacity
            @click.self="closeBan()" @keydown.escape.window="closeBan()">
            <div class="ad-modal" role="dialog" aria-modal="true" aria-labelledby="ban-title" @keydown.tab="trap($event)">
                <form method="POST" :action="banAction" @submit="banBusy = true">
                    @csrf
                    @method('PATCH')

                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:20px 22px 0">
                        <div style="min-width:0">
                            <h2 id="ban-title" class="ad-h2" style="font-size:17px">Suspend account</h2>
                            <p class="ad-muted ad-sm" style="margin:4px 0 0; overflow-wrap:anywhere">
                                <strong x-text="banUser.name"></strong> won't be able to sign in, by password or Google, until you unsuspend them. Their history is kept.
                            </p>
                        </div>
                        <button type="button" class="ad-btn ad-icon-btn" style="border:0" @click="closeBan()" aria-label="Close">
                            <x-admin.icon name="x" class="ad-i18" />
                        </button>
                    </div>

                    <div style="display:grid; gap:16px; padding:20px 22px">
                        <div>
                            <label for="ban-reason" class="ad-label">Reason (optional, only admins see this)</label>
                            <input id="ban-reason" x-ref="banReason" name="reason" x-model="banReason" class="ad-input" maxlength="255" placeholder="e.g. Repeated chargebacks">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; padding:0 22px 22px">
                        <button type="button" class="ad-btn" @click="closeBan()">Cancel</button>
                        <button type="submit" class="ad-btn ad-btn-danger" :disabled="banBusy">Suspend</button>
                    </div>
                </form>
            </div>
        </div>

    @push('scripts')
    <script>
        function makePassword() {
            var letters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
            var digits = '23456789';
            var all = letters + digits;
            var symbols = '!#$%*?-_';
            var a = new Uint32Array(16);
            crypto.getRandomValues(a);

            var chars = [letters[a[0] % letters.length], digits[a[1] % digits.length]];
            for (var i = 2; i < 15; i++) chars.push(all[a[i] % all.length]);
            chars.push(symbols[a[15] % symbols.length]);

            var r = new Uint32Array(chars.length);
            crypto.getRandomValues(r);
            for (var j = chars.length - 1; j > 0; j--) {
                var k = r[j] % (j + 1);
                var t = chars[j]; chars[j] = chars[k]; chars[k] = t;
            }
            return chars.join('');
        }

        function studentsPage(reopen, urlTemplate, banUrlTemplate) {
            return {
                open: !!reopen,
                user: reopen || { id: null, name: '', email: '' },
                pw: '',
                pw2: '',
                show: false,
                copied: false,
                busy: false,

                banOpen: false,
                banUser: { id: null, name: '' },
                banReason: '',
                banBusy: false,

                get action() { return urlTemplate.replace('__ID__', this.user.id); },
                get banAction() { return banUrlTemplate.replace('__ID__', this.banUser.id); },

                opener: null,

                /* Keep Tab and Shift+Tab inside the open dialog. */
                trap(e) {
                    var items = [].slice.call(e.currentTarget.querySelectorAll('button:not([disabled]), input:not([type=hidden]), a[href]'))
                        .filter(function(el) { return el.offsetParent !== null; });
                    if (!items.length) return;
                    var first = items[0], last = items[items.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                },
                /* Hand focus back to whatever opened the dialog. */
                restore() {
                    var el = this.opener;
                    this.opener = null;
                    if (el && document.contains(el)) this.$nextTick(function() { el.focus(); });
                },

                openBan(id, name) {
                    this.opener = document.activeElement;
                    this.banUser = { id: id, name: name };
                    this.banReason = '';
                    this.banBusy = false;
                    this.banOpen = true;
                    var self = this;
                    this.$nextTick(function() { if (self.$refs.banReason) self.$refs.banReason.focus(); });
                },
                closeBan() {
                    if (!this.banOpen) return;
                    this.banOpen = false;
                    this.restore();
                },
                get score() {
                    var p = this.pw, s = 0;
                    if (p.length >= 8) s++;
                    if (p.length >= 12) s++;
                    if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
                    if (/\d/.test(p) && /[^A-Za-z0-9]/.test(p)) s++;
                    return s;
                },
                get strength() { return ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'][this.score]; },
                get ready() { return this.pw.length >= 8 && this.pw === this.pw2 && !this.busy; },

                openPw(id, name, email) {
                    this.opener = document.activeElement;
                    this.user = { id: id, name: name, email: email };
                    this.pw = ''; this.pw2 = ''; this.show = false; this.copied = false; this.busy = false;
                    this.open = true;
                    var self = this;
                    this.$nextTick(function() { if (self.$refs.pw) self.$refs.pw.focus(); });
                },
                close() {
                    if (!this.open) return;
                    this.open = false;
                    this.restore();
                },
                generate() {
                    this.pw = this.pw2 = makePassword();
                    this.show = true;
                    this.copied = false;
                },
                copy() {
                    var self = this;
                    navigator.clipboard.writeText(this.pw).then(function() { self.copied = true; });
                }
            };
        }
    </script>
    @endpush
