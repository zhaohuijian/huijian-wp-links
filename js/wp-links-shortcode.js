(function ($) {
  jQuery(document).ready(function ($) {
    $("#wplf-sb #cat").attr("multiple", "multiple");

    //radios
    $("#wplf-sb .radio-i label, #wplf-sb .radio-no-i label").on("click", function () {
      $(this).css("background-color", "#008ec2");
      $(this).css("color", "#ffffff");
      $(this).siblings("label").css("background-color", "#e5e5e5");
      $(this).siblings("label").css("color", "inherit");
    });

    //checkboxes
    $("#wplf-sb .checks label").on("click", function () {
      if ($(this).children("input").is(":checked")) {
        $(this).css("background-color", "#008ec2");
        $(this).css("color", "#ffffff");
      } else {
        $(this).css("background-color", "#e5e5e5");
        $(this).css("color", "inherit");
      }
    });

    //Display Type
    $("#wplf-sb #tabs-1 input").on("change", function () {
      if ($(this).val() == "grid") {
        $(".grid").show();
      } else {
        $(".grid").hide();
      }

      if ($(this).val() == "carousel") {
        $(".carousel").show();
        $(".not-carousel").hide();
      } else {
        $(".carousel").hide();
        $(".not-carousel").show();
      }
    });

    $("input[name=wplf-num]").on("change", function () {
      var value = $(this).val();
      if ($.isNumeric(value) || value == "" || value == "all") {
        return;
      } else {
        alert("You must enter a number.");
        $(this).val("");
      }
    });

    $("input[name=wplf-title-size]").on("change", function () {
      var value = $(this).val();
      if ($.isNumeric(value) || value == "") {
        return;
      } else {
        alert("You must enter a number.");
        $(this).val("");
      }
    });

    $("input[name=wplf-desc-size]").on("change", function () {
      var value = $(this).val();
      if ($.isNumeric(value) || value == "") {
        return;
      } else {
        alert("You must enter a number.");
        $(this).val("");
      }
    });

    $("#wplf-sb input, #wplf-sb select").on("change", function () {
      var sc = "[wp_links_page";

      if (typeof $("input[name=wplf-display]:checked").val() != "undefined") {
        sc += ' display="' + $("input[name=wplf-display]:checked").val() + '"';
      }

      if (typeof $("input[name=wplf-columns]:checked").val() != "undefined") {
        sc += ' cols="' + $("input[name=wplf-columns]:checked").val() + '"';
      }

      if (typeof $("input[name=wplf-order]:checked").val() != "undefined") {
        sc += ' orderby="' + $("input[name=wplf-order]:checked").val() + '"';
      }

      if (typeof $("input[name=wplf-orderby]:checked").val() != "undefined") {
        sc += ' order="' + $("input[name=wplf-orderby]:checked").val() + '"';
      }

      if (typeof $("input[name=wplf-image]:checked").val() != "undefined") {
        sc += ' image="' + $("input[name=wplf-image]:checked").val() + '"';
      }

      if (
        typeof $("input[name=wplf-image-size]:checked").val() != "undefined"
      ) {
        sc +=
          ' img_size="' + $("input[name=wplf-image-size]:checked").val() + '"';
      }

      var is = "";
      if (
        typeof $("input[name=wplf-image-style]:checked").val() != "undefined"
      ) {
        $("input[name=wplf-image-style]:checked").each(function () {
          if ($(this).val() == "border") {
            is += "border: 3px solid black; ";
          }
          if ($(this).val() == "shadow") {
            is +=
              "box-shadow: 0 5px 10px 0 rgba(0,0,0,0.2),0 5px 15px 0 rgba(0,0,0,0.19);";
          }
        });
      }

      if (is != "") {
        sc += ' img_style="' + is + '"';
      }

      var ts = "";
      if (
        typeof $("input[name=wplf-title-style]:checked").val() != "undefined"
      ) {
        $("input[name=wplf-title-style]:checked").each(function () {
          if ($(this).val() == "bold") {
            ts += "font-weight: bold; ";
          }
          if ($(this).val() == "italic") {
            ts += "font-style: italic; ";
          }
          if ($(this).val() == "underline") {
            ts += "text-decoration: underline; ";
          }
        });
      }
      if ($("input[name=wplf-title-size]").val() != "") {
        ts += "font-size: " + $("input[name=wplf-title-size]").val() + "px; ";
      }
      if (
        typeof $("input[name=wplf-title-align]:checked").val() != "undefined"
      ) {
        ts += "text-align: " + $("input[name=wplf-title-align]:checked").val();
      }

      if (ts != "") {
        sc += ' title_style="' + ts + '"';
      }

      if (typeof $("input[name=wplf-desc]:checked").val() != "undefined") {
        sc += ' desc="' + $("input[name=wplf-desc]:checked").val() + '"';
      }

      if (
        typeof $("input[name=wplf-description_link]:checked").val() !=
        "undefined"
      ) {
        sc +=
          ' description_link="' +
          $("input[name=wplf-description_link]:checked").val() +
          '"';
      }

      var ds = "";
      if (
        typeof $("input[name=wplf-desc-style]:checked").val() != "undefined"
      ) {
        $("input[name=wplf-desc-style]:checked").each(function () {
          if ($(this).val() == "bold") {
            ds += "font-weight: bold; ";
          }
          if ($(this).val() == "italic") {
            ds += "font-style: italic; ";
          }
          if ($(this).val() == "underline") {
            ds += "text-decoration: underline; ";
          }
        });
      }
      if ($("input[name=wplf-desc-size]").val() != "") {
        ds += "font-size: " + $("input[name=wplf-desc-size]").val() + "px; ";
      }
      if (
        typeof $("input[name=wplf-desc-align]:checked").val() != "undefined"
      ) {
        ds += "text-align: " + $("input[name=wplf-desc-align]:checked").val();
      }

      if (ds != "") {
        sc += ' desc_style="' + ds + '"';
      }

      sc += "]";
      $("#final-shortcode").val(sc);
    });
  });
})(jQuery);
