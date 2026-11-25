{{-- Where the verified middleware holds an unconfirmed account (routes/web.php): resend the link, or sign out --}}
<x-layout-auth :title="__('Confirm your email')">
    <section class="pc-auth-card items-center text-center" aria-labelledby="title">
        <span class="pc-auth-badge">
            <i class="ph ph-envelope-simple text-[22px]" aria-hidden="true"></i>
        </span>
        <div>
            <h1 id="title" class="pc-auth-title">{{ __('Confirm your email') }}</h1>
            <p class="pc-auth-sub">
                {{ __('We sent a confirmation link to') }}<br>
                <strong class="font-semibold break-all text-pc-text">{{ $email }}</strong>
            </p>
            <p class="pc-auth-sub">{{ __('Open it to finish setting up your account. The link expires in :minutes minutes.', ['minutes' => $minutes]) }}</p>
        </div>

        <form id="resend" class="w-full" method="POST" action="{{ route('auth.verify-email.send') }}">
            @csrf
            <button type="submit" id="resendBtn" class="pc-btn pc-btn-secondary pc-btn-lg pc-btn-block">{{ __('Resend email') }}</button>
        </form>

        <form method="POST" action="{{ route('auth.logout') }}" onsubmit="submitForm(event)" class="pc-auth-foot">
            {{ __('Wrong address?') }} <button type="submit" class="pc-auth-link border-0 bg-transparent p-0">{{ __('Sign out') }}</button> {{ __('and use another.') }}
        </form>
    </section>

    @push('body')
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                // Resend is locked for COOLDOWN seconds after each send (the first went out when the account was made or the email changed).
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
                    if (data.redirect) return (window.location.href = data.redirect); // confirmed in another tab
                    toast.success({ message: data.message });
                    setTimeout(cooldown); // after submitForm resets the button
                }));
            });
        </script>
    @endpush
</x-layout-auth>
