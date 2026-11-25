/**
 * Registers the SurveyJS licence key (config/surveyr.php `surveyjs.license_key`) before any SurveyJS component
 * renders. <x-surveyjs> puts it in <meta name="surveyjs-license">; without a key SurveyJS shows its own banner.
 */
import { setLicenseKey } from "survey-core";

const key = document.querySelector('meta[name="surveyjs-license"]')?.content;

if (key) {
  setLicenseKey(key);
}
