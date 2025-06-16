/**
 * Admin JavaScript for Order Departments plugin
 */

jQuery(document).ready(function ($) {
  /**
   * Initialize Select2 fields
   */
  function initializeSelect2() {
    $(".wc-enhanced-select").each(function () {
      if ($(this).data("select2")) {
        $(this).select2("destroy");
      }

      $(this).select2({
        placeholder: runthingsOrderDepartments.selectPlaceholder,
        allowClear: true,
        width: "100%",
      });
    });
  }

  // Initialize Select2 on page load
  initializeSelect2();

  /**
   * Handle AJAX form clearing after successful term creation
   */
  $(document).ajaxSuccess(function (_, xhr, settings) {
    // Check if this was a successful add-tag request
    if (settings.data && settings.data.indexOf("action=add-tag") !== -1) {
      // Check for term_id in the XML response - most reliable success indicator
      if (
        xhr.responseText &&
        xhr.responseText.indexOf("<term_id><![CDATA[") !== -1
      ) {
        var termIdStart = xhr.responseText.indexOf("<term_id><![CDATA[") + 18;
        var termIdEnd = xhr.responseText.indexOf("]]></term_id>");

        if (termIdEnd > termIdStart) {
          var termId = xhr.responseText.substring(termIdStart, termIdEnd);

          // If we got a numeric term ID, the term was successfully created
          if (termId && !isNaN(termId) && parseInt(termId) > 0) {
            // Clear our custom Select2 fields
            $(
              "#" +
                runthingsOrderDepartments.metaPrefix +
                "department_categories"
            )
              .val(null)
              .trigger("change");
            $("#" + runthingsOrderDepartments.metaPrefix + "selected_products")
              .val(null)
              .trigger("change");
          }
        }
      }
    }
  });
});
