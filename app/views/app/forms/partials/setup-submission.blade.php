<div class="card fo-settings-card" id="submissionSettings">
    <div class="card-header py-3">
        <h5 class="card-title mb-0">Submission</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('forms.setup.update', 'submission', $form->id) }}" method="POST" id="submissionSetupForm" onsubmit="submitForm(event)">
            @csrf

            <div class="row">
                <div class="col-12 mb-3">
                    <label for="flagGroup" class="form-label d-block">Flag group</label>
                    <small class="text-muted fs-10 ms-3 d-block">
                        Adds a flag column to the submissions table.
                        <a href="@route('flags.list')" target="_blank">Manage flags</a>
                    </small>
                    <select class="form-selector" id="flagGroup" name="flag_group_id" data-placeholder="Select a flag group">
                        <option value="">None (no flag column)</option>
                        @foreach ($flagGroups as $group)
                            <option value="{{ $group->id }}" {{ (int) $form->flag_group_id === (int) $group->id ? 'selected' : '' }}>
                                {{ $group->name }} ({{ implode(', ', array_column($group->flags ?? [], 'label')) }})
                            </option>
                        @endforeach
                    </select>
                    @if($form->flag_group_id)
                        <small class="text-warning fs-10 ms-3 d-block mt-1">Changing the group clears the flags already set on this form's submissions.</small>
                    @endif
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