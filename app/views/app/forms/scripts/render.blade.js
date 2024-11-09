const creatorOptions = {
    showLogicTab: true,
    isAutoSave: true
};

const defaultJson = `{!! json_encode($form->content) !!}`;

const survey = new Survey.Model(defaultJson)
document.addEventListener("DOMContentLoaded", function() {
    survey.render(document.getElementById("surveyContainer"));
});