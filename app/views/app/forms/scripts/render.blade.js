const defaultJson = `{!! json_encode($form->content) !!}`;
var currentTheme = localStorage.getItem('phoenixTheme') ?? 'light';

var formTheme = "{{ $form->theme }}";
const SurveyThemeDark = SurveyTheme[formTheme + 'DarkPanelless'];
const SurveyThemeLight = SurveyTheme[formTheme + 'LightPanelless'];

const survey = new Survey.Model(defaultJson)
document.addEventListener("DOMContentLoaded", function() {
    survey.render(document.getElementById("surveyContainer"));
    if(currentTheme === 'light'){
        survey.applyTheme(SurveyThemeLight);
    }else{
        survey.applyTheme(SurveyThemeDark);
    }
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
        survey.applyTheme(SurveyThemeLight);
    }
    else{
        survey.applyTheme(SurveyThemeDark);
    }
});