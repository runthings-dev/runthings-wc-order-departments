/**
 * Settings page JavaScript for Order Departments plugin
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    // Initialize conditional field visibility
    initConditionalFields();

    // Handle enable/disable checkbox
    $('input[name*="[enable_reply_to_override]"]').on("change", function () {
      toggleMultiDeptSection();
    });
  });

  /**
   * Initialize conditional field visibility on page load
   */
  function initConditionalFields() {
    toggleMultiDeptSection();
  }

  /**
   * Enable/disable the multi-department section based on the main checkbox
   */
  function toggleMultiDeptSection() {
    var isEnabled = $('input[name*="[enable_reply_to_override]"]').is(
      ":checked"
    );
    var $fieldset = $("#multi-dept-mode-fieldset");
    var $radioInputs = $fieldset.find('input[type="radio"]');
    var $note = $("#multi-dept-note");

    if (isEnabled) {
      $fieldset.prop("disabled", false);
      $radioInputs.prop("disabled", false);
      $fieldset.css("opacity", "1");
      $note.css("opacity", "1");
    } else {
      $fieldset.prop("disabled", true);
      $radioInputs.prop("disabled", true);
      $fieldset.css("opacity", "0.5");
      $note.css("opacity", "0.5");
    }
  }
})(jQuery);
