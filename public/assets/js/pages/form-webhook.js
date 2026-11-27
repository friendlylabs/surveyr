/**
 * A form's webhook settings (app/forms/partials/settings/submission): Send test says how the test went, delivered or
 * not, in a toast.
 */

/** submitForm's handler for Send test. */
function webhookAnswered(data, response) {
  if (response.ok && data.success) toast.success({ message: data.message });
  else toast.error({ message: data.message || pc.t("An unknown error occurred.") });
}
