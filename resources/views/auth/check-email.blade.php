<x-layout-auth :title="__('Check your email')">
    <section class="pc-auth-card items-center text-center" aria-labelledby="title">
        <span class="pc-auth-badge">
            <i class="ph ph-envelope-simple text-[22px]" aria-hidden="true"></i>
        </span>
        <div>
            <h1 id="title" class="pc-auth-title">{{ __('Check your email') }}</h1>
            <p class="pc-auth-sub">
                {{ $isLink ? __('We sent a one-time sign-in link to') : __('We sent a password reset link to') }}<br>
                <strong id="sentTo" class="font-semibold break-all text-pc-text">{{ $email ?: __('your email') }}</strong>
            </p>
            <p class="pc-auth-sub">{{ $isLink ? __('The link expires in :minutes minutes and works once.', ['minutes' => $minutes]) : __('The link expires in :minutes minutes.', ['minutes' => $minutes]) }}</p>
        </div>

        {{-- Resend posts the same email back to the endpoint that sent it --}}
        <form id="resend" class="w-full" method="POST" action="{{ route($isLink ? 'auth.send-email-link' : 'auth.send-reset-link') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" id="resendBtn" class="pc-btn pc-btn-secondary pc-btn-lg pc-btn-block">{{ __('Resend email') }}</button>
        </form>

        <p class="pc-auth-foot">
            {{ __('Wrong address?') }} <a href="{{ route($isLink ? 'auth.email-link' : 'auth.forgot-password', array_filter(['email' => $email])) }}" class="pc-auth-link">{{ __('Use a different email') }}</a>
        </p>
    </section>

    <a href="{{ route('auth.login') }}" class="pc-auth-link inline-flex items-center gap-1.5 text-[13px]">
        <i class="ph ph-arrow-left text-sm rtl:-scale-x-100" aria-hidden="true"></i>{{ __('Back to sign in') }}
    </a>

    @push('body')
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                // Resend is locked for COOLDOWN seconds after each send (the first send happened on the previous page).
                const COOLDOWN = 30;
                const form = document.getElementById("resend");
                const resend = document.getElementById("resendBtn");
                let timer;

                function cooldown() {
                    let left = COOLDOWN;
                    clearInterval(timer);
                    const tick = () => {
                        resend.disabled = left > 0;
                        resend.textContent = left > 0 ? pc.t("Resend email in :time", { time: left + "s" }) : pc.t("Resend email");
                        if (left-- <= 0) clearInterval(timer);
                    };
                    tick();
                    timer = setInterval(tick, 1000);
                }
                cooldown();

                form.addEventListener("submit", (e) => submitForm(e, (data, response) => {
                    if (!(response.ok && data.success)) return toast.error({ message: data.message });
                    toast.success({ message: form.email.value ? pc.t("Email sent again to :email.", { email: form.email.value }) : pc.t("Email sent again.") });
                    setTimeout(cooldown); // after submitForm resets the button
                }));
            });
        </script>
    @endpush
</x-layout-auth>
