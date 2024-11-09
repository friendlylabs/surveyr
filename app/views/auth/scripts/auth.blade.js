function resetRequestResponse(response) {
    if (response.status) {
        toast.success({ message: response.message });
        $('.alert').removeClass('d-none');
    }else{
        toast.error({ message: response.message });
    }
}

