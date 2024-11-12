const surveyJson = `{!! json_encode($formContent) !!}`;
const surveyResponse = JSON.parse(`{!! json_encode($collection->submission) !!}`);
var currentTheme = localStorage.getItem('phoenixTheme') ?? 'light';

const survey = new Survey.Model(surveyJson);
survey.data = surveyResponse;

document.addEventListener("DOMContentLoaded", function() {
    survey.render(document.getElementById("surveyContainer"));
    if(currentTheme === 'light'){
        survey.applyTheme(SurveyTheme.SolidLightPanelless);
    }else{
        survey.applyTheme(SurveyTheme.SolidDarkPanelless);
    }
});

document.getElementById('reviewInput').addEventListener('change', function(e) {
    const review = this.value;

    $.ajax({
        url: `@route('collections.review', $collection->id)`,
        method: 'POST',
        data: {
            _token: `{{ csrf_token() }}`,
            review: review
        },
        success: function(response) {
            if(response.status){
                return toast.success({message: response.message});
            }

            return toast.error({message: response.message ?? 'An error occurred'});
        },
        error: function(error) {
            return toast.error({message: error.responseJSON.message ?? 'An error occurred'});
        }
    });
});

(function() {
    const originalSetItem = localStorage.setItem;
    localStorage.setItem = function (key, value) {
      const event = new Event('phoenixThemeChanged');
      document.dispatchEvent(event);
      originalSetItem.apply(this, arguments);
    };
})();
  
  // Listen for the custom event
document.addEventListener('phoenixThemeChanged', () => {
    const newTheme = localStorage.getItem('phoenixTheme');
    currentTheme = newTheme;
    if(currentTheme === 'dark'){
        survey.applyTheme(SurveyTheme.SolidLightPanelless);
    }
    else{
        survey.applyTheme(SurveyTheme.SolidDarkPanelless);
    }
});