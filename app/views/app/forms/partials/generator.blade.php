<script>
    const initialData = 'Form Title';
    const placeholder = 'Descript or paste your form description here...';
</script>

@style('app.forms.styles.generator')
<div class="offcanvas min-w-90 offcanvas-end" tabindex="-1" id="formGeneratorCanvas" aria-labelledby="formGeneratorCanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title ps-5" id="formGeneratorCanvasLabel">AI Form Generator</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-0 px-5">
        <form action="@route('forms.generate')" method="POST" onsubmit="submitForm(event)">
            @csrf
            <div class="position-relative mb-3">
                <div id="editor" class="pb-1"></div>
                <div class="position-absolute" style="bottom:-1.4rem;">
                    <a href="javascript:void(0)" class="fs-9 text-info" onclick="generateSampleDescription(1)">sample 1</a> | 
                    <a href="javascript:void(0)" class="fs-9 text-info" onclick="generateSampleDescription(2)">sample 2</a>
                </div>
                <button type="button" id="autoFormGeneratorBtn" 
                    class="btn btn-primary position-absolute"
                    style="bottom:-1.5rem; right:0.4rem; z-index:1">Generate
                </button>
            </div>
        </form>
    </div>
</div>

<script>

    function generateSampleDescription(type) {

        // simple form description
        let sample1 = `
        # Customer Feedback Form
        # A simple customer feedback form that allows to collect feedback from your customers, it should collect the following information
            - Customer Name
            - Email Address
            - Phone Number
            - Satisfaction Level
            - Likelihood to recommend if satisfaction level is medium to high
            - Feedback
        `;

        // full form description
        let sample2 = `
        # Customer Feedback Form

        # Page 1:
            1. What is your name? required
            2. Would you like us to contact you?
            3. What is your email address? if yes to question 2
            4. What is your phone number? if they would like to be contacted

        # Page 2:
            5. How satisfied are you with our service? required
            6. How likely are you to recommend us to a friend? if satisfaction level is medium to high
            7. Please provide any feedback or suggestions
        `;

        // update the editor with the description
        let description = type == 1 ? sample1 : sample2;
        description = description.split('\n').map(line => line.trim()).join('\n');
        editor.setData(description);
    }

    document.getElementById('autoFormGeneratorBtn').addEventListener('click', function() {
        buttonState('#autoFormGeneratorBtn', 'loading');
        let title = $('.ck-content h1').first().text();
        let description = editor.getData();

        if (title.trim() == '' || description.trim() == '') {
            toast.error({message: 'Please provide a title and description for the form'});
            buttonState('#autoFormGeneratorBtn', 'reset', 'Generate');
            return;
        }

        // remove first 2 lines of the description
        description = description.split('\n').slice(2).join('\n');

        try{
            $.ajax({
                url: "@route('forms.generate')",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    title: title,
                    description: description
                },
                success: function(response) {
                    if (response.status) {
                        window.location.href = response.redirect;
                    } else {
                        toast.error({message: response.message || 'An error occurred while generating the form'});
                        buttonState('#autoFormGeneratorBtn', 'reset', 'Generate');
                    }
                },
                error: function(xhr, status, error) {
                    toast.error({message: 'An error occurred while generating the form'});
                    buttonState('#autoFormGeneratorBtn', 'reset', 'Generate');
                }
            });
        }

        catch(e) {
            toast.error({message: 'An error occurred while generating the form'});
        }
    });
    
</script>