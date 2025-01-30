<table class="table table-hover">
    <tbody>
        @foreach ($apiKeys as $key)
            <tr>
                <td class="px-2 rounded-start">
                    <div class="w-75 overflow-hidden">
                        <p class="m-0 fw-bold">{{ $key->name }}</p>
                        <p class="m-0">sk_{{ substring($key->token, 70) }}</p>
                    </div>
                </td>
                <td class="text-end px-2 rounded-end">
                    <button class="btn btn-sm btn-outline-primary" data-clipboard="{{ $key->token }}"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Copy to clipboard"
                        onclick="copyTheKey(this)">
                        <i class="fa-solid fa-clipboard"></i>
                    </button>

                    <div class="dropdown table-dropdown d-inline">
                        <button class="btn btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li>
                                <a class="dropdown-item" href="@route('keys.show', $key->id)">
                                    <i class="fa-solid fa-caret-right fs-8 pt-2 me-2"></i>
                                    View
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" onclick="confirmDelete(event)" href="@route('keys.revoke', $key->id)"
                                    data-delete-message="Are you sure you want to revoke this key? All integrations using this key will stop working.">
                                    <i class="fa-solid fa-trash me-2"></i>
                                    Revoke
                                </a>        
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<script>
    function copyTheKey(element) {
        const textToCopy = element.getAttribute('data-clipboard');
        
        // Create a temporary textarea element to use the clipboard API
        const textarea = document.createElement('textarea');
        textarea.value = textToCopy;
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            toast.info({message:'Key copied to clipboard'});
        } catch (err) {
            console.error('Failed to copy text:', err);
        }

        document.body.removeChild(textarea);
    }

</script>