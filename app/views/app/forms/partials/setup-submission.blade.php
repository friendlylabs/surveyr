<div class="card d-none setup-card" id="submissionSettings">
    <div class="card-header py-3">
        <h5 class="card-title mb-0">Submission Settings</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('forms.setup.update', 'submission', $form->id) }}" method="POST" id="submissionSetupForm" onsubmit="submitForm(event)">
            @csrf

            <div class="row">
                <div class="col-12 mb-3">
                    <label for="reviewFormat" class="form-label d-block">Review Format</label>
                    <small class="text-muted fs-10 ms-3 d-block">Select the review format for this form</small>
                    <select class="form-selector" id="reviewFormat" name="reviews" data-placeholder="Select Review Format">
                        @foreach ($reviewTypes as $review)
                            <option
                                value="{!! json_encode($review->properties) !!}"
                                {{ array_diff($review->properties, $form->reviews)  ? '' : 'selected' }}>
                                {{ $review->name }} ({{ implode(', ', $review->properties) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 mb-3">
                    <label for="redirectionUrl" class="form-label d-block">Redirection URL</label>
                    <small class="text-muted fs-10 ms-3 d-block">Redirect users after submission</small>
                    <input type="text" class="form-control" placeholder="Redirection url" id="redirectionUrl" name="redirection_url" value="{{ $form->content['navigateToUrl'] ?? null }}">
                </div>
                <div class="col-12 mb-3">
                    <label for="WebhookUrl" class="form-label d-block">Webhook URL</label>
                    <small class="text-danger fs-10 ms-3 d-block">Make sure you have this site whitelisted in your app to avoid <b>cors</b> issues</small>
                    <input type="text" class="form-control" placeholder="Webhook url" id="WebhookUrl" name="webhook_url" value="{{ $form->webhook_url }}">
                </div>

                <div class="col-12 mt-5 mx-auto">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Update Form Details</button>
                </div>
            </div>
        </form>
    </div>
</div>