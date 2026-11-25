<x-layout-auth :title="__('Check your email')">
    <section class="pc-auth-card items-center text-center" aria-labelledby="title">
        <span class="pc-auth-badge">
            <i class="ph ph-envelope-simple text-[22px]" aria-hidden="true"></i>
        </span>
        <div>
            <h1 id="title" class="pc-auth-title">Check your email</h1>
            <p class="pc-auth-sub">
                We sent {{ $isLink ? 'a one-time sign-in link' : 'a password reset link' }} to<br>
                <strong id="sentTo" class="font-semibold break-all text-pc-text">{{ $email ?: 'your email' }}</strong>
            </p>
            <p class="pc-auth-sub">The link expires in {{ $minutes }} minutes{{ $isLink ? ' and works once' : '' }}.</p>
        </div>

        {{-- Resend posts the same email back to the endpoint that sent it --}}
        <form id="resend" class="w-full" method="POST" action="{{ route($isLink ? 'auth.send-email-link' : 'auth.send-reset-link') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" id="resendBtn" class="pc-btn pc-btn-secondary pc-btn-lg pc-btn-block">Resend email</button>
        </form>

        <p class="pc-auth-foot">
            Wrong address? <a href="{{ route($isLink ? 'auth.email-link' : 'auth.forgot-password', array_filter(['email' => $email])) }}" class="pc-auth-link">Use a different email</a>
        </p>
    </section>

    <a href="{{ route('auth.login') }}" class="pc-auth-link inline-flex items-center gap-1.5 text-[13px]">
        <i class="ph ph-arrow-left text-sm" aria-hidden="true"></i>Back to sign in
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
                        resend.textContent = left > 0 ? `Resend email in ${left}s` : "Resend email";
                        if (left-- <= 0) clearInterval(timer);
                    };
                    tick();
                    timer = setInterval(tick, 1000);
                }
                cooldown();

                form.addEventListener("submit", (e) => submitForm(e, (data, response) => {
                    if (!(response.ok && data.success)) return toast.error({ message: data.message });
                    toast.success({ message: "Email sent again" + (form.email.value ? " to " + form.email.value : "") + "." });
                    setTimeout(cooldown); // after submitForm resets the button
                }));
            });
        </script>
    @endpush
</x-layout-auth>
