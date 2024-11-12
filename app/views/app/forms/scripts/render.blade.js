const defaultJson = `{!! json_encode($form->content) !!}`;
var currentTheme = localStorage.getItem('phoenixTheme') ?? 'light';

const survey = new Survey.Model(defaultJson)
document.addEventListener("DOMContentLoaded", function() {
    survey.render(document.getElementById("surveyContainer"));
    if(currentTheme === 'light'){
        survey.applyTheme(SurveyTheme.SolidLightPanelless);
    }else{
        survey.applyTheme(SurveyTheme.SolidDarkPanelless);
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
        survey.applyTheme(SurveyTheme.SolidLightPanelless);
    }
    else{
        survey.applyTheme(SurveyTheme.SolidDarkPanelless);
    }
});