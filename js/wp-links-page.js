(function ($) {
  jQuery(document).ready(function ($) {
    function getBase64Image(img) {
      var canvas = document.createElement("canvas");
      canvas.width = img.width;
      canvas.height = img.height;
      var ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0);
      var dataURL = canvas.toDataURL("image/png");
      return dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
    }

    $("#titlediv #title:not(.ss), #title-prompt-text:not(.ss)").remove();

    if ($("#wplp_featured_image").val() == "") {
      $("#publish[value=Publish]").prop("disabled", true);
    }

    $("input[name=wplf_image_type]").change(function () {
      if ($("input[name=wplf_image_type]:checked").val() == "screenshot") {
        $(".screenshot").show();
        var value = $("input[name=wplp_screenshot_refresh]").attr(
          "data-current"
        );
        $("input[name=wplp_screenshot_refresh][value=" + value + "]").prop(
          "checked",
          true
        );
      } else {
        $(".screenshot").hide();
        $("input[name=wplp_screenshot_refresh][value=never]").prop(
          "checked",
          true
        );
      }
    });

    function get_hostname(url) {
      var m = url.match(/^http:\/\/[^/]+/);
      return m ? m[0] : null;
    }

    var progressbar = $("#progressbar"),
      progressLabel = $(".progress-label");

    progressbar.progressbar({
      value: false,
      change: function () {
        progressLabel.text(progressbar.progressbar("value") + "%");
      },
      complete: function () {
        progressLabel.text("Complete!");
      },
    });

    var progressbar1 = $("#progressbar1"),
      progressLabel1 = $(".progress-label1");

    progressbar1.progressbar({
      value: false,
      change: function () {
        progressLabel1.text(progressbar1.progressbar("value") + "%");
      },
      complete: function () {
        progressLabel1.text("Complete!");
      },
    });

    $("#update-screenshots").on("click", function () {
      var ids = $(this).data("total").toString();
      if (ids.indexOf(",") >= 0) {
        var id_arr = ids.split(",");
      } else id_arr = [ids];
      var total = id_arr.length;
      var done = 0;
      $(".update-message").show();
      $("#progressbar").show();

      var val = progressbar.progressbar("value") || 0;

      $.each(id_arr, function (key, id) {
        $.post(ajax_object.ajax_url, {
          action: "wplf_ajax_update_screenshots",
          nonce: ajax_object.nonce,
          id: id,
        })
          .always(function () {
            done = done + 1;
            val = (done / total) * 100;
            val = Math.round(val);
            progressbar.progressbar("value", val);
          })
          .done(function () {
            $(document).ajaxStop(function () {
              $("#update-screenshots").prop("disabled", false);
              location.reload(true);
              window.location = self.location;
            });
          });
      });
      $("#update-screenshots").prop("disabled", true);
      $("#update-screenshots").html("Please Wait...");
    });

    /**
     * 下载特色图片
     */
    function download_featured_image() {
      var formData = new FormData(document.getElementById("post"));
      formData.append("action", "wplf_quick_link");
      formData.append("nonce", ajax_object.nonce);
      $.ajax({
        url: ajax_object.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          $(".wplp_featured").attr("src", response.data.attachment_url);
          $(".wplp_loading").hide();
          $(".wplp_featured").show();
          $("#wplp_featured_image").val(response.data.attachment_url);
          $("#wplp_media_image").val("true");

          $(".set-featured-screenshot").html("Generate New Screenshot");

          /**
           * 设置特色图片
           */
          $("#postimagediv .hide-if-no-js").remove();
          $("#postimagediv .inside").append(
            '<p class="hide-if-no-js"><img src="' +
              response.data.attachment_url +
              '" alt="" style="max-width:100%;"/></p><p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail">移除特色图片</a></p>'
          );
          $("#_thumbnail_id").val(response.data.attachment_id);

          $(".wplp_error").hide();
        },
        error: function (xhr, status, error) {
          $(".wplp_loading").hide();
          $(".set-featured-screenshot").html("Generate New Screenshot");
          $("#titlediv").append(
            '<div class="wplp_notice notice notice-error" ><p>' +
              xhr.responseJSON.data.message +
              "</p></div>"
          );
        },
      });
    }

    /**
     * 下载图片
     * @param {*} event 
     * @returns 
     */

    function handleTitleAndScreenshotEvents(event) {
      event.preventDefault(); // 防止默认行为（针对 click 事件）
      var url = $("#title").val();
      $("#titlediv").find(".wplp_notice").remove();
      if (!url) {
        $("#titlediv").append(
          '<div class="wplp_notice notice notice-error" ><p>Link Url is empty</p></div>'
        );
        return;
      }

      // 获取触发的事件类型
      if (event.type === "change") {
        $("#Publish").prop("disabled", true);
        $(".wplp_loading").show();
        $(".wplp_featured").hide();
      } else if (event.type === "click") {
        $(".set-featured-screenshot").prop("disabled", true);
        $(".set-featured-screenshot").html("Please Wait...");
      }

      if (!$("input#wplp_display").val()) {
        $("input#wplp_display").val(url);
      }
      var url_check = url;
      // var done = "";

      if ($("#wplp_screenshot_url").val()) {
        url = $("#wplp_screenshot_url").val();
      }

      if (url.indexOf("http") < 0) {
        url_check = "https://" + url;
        url = "https%3A%2F%2F" + url;
      }

      $("#title").val(url_check);

      $.post(ajax_object.ajax_url, {
        url: url_check,
        post_action:
          window.location.href.indexOf("post-new.php") !== -1 ? "new" : "edit",
        action: "wplf_get_url_meta",
        nonce: ajax_object.nonce,
      })
        .done(function (response) {
          $("input#wplp_display").val(response.data.title);
          tinymce.activeEditor.setContent(response.data.description);

          var first = "https://s0.wp.com/mshots/v1/" + url + "?w=1200";
          $("#wplp_featured_image").val(first);
          $("#wplp_media_image").val("false");
          download_featured_image();
        })
        .fail(function (response) {
          $(".wplp_loading").hide();
          $("#titlediv").append(
            '<div class="wplp_notice notice notice-error" ><p>' +
              response.responseJSON.data.message +
              "</p></div>"
          );
          $(".set-featured-screenshot").html("Generate New Screenshot");
        });
    }

    $("#title").on("change", handleTitleAndScreenshotEvents);

    $(".set-featured-screenshot").on("click", handleTitleAndScreenshotEvents);

    /**
     * 选择图片
     */
    var custom_uploader1;
    $(".set-featured-thumbnail").on("click", function (e) {
      e.preventDefault();
      var set = false;

      if (set == false) {
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader1) {
          custom_uploader1.open();
          return;
        }

        //Extend the wp.media object
        custom_uploader1 = wp.media.frames.file_frame = wp.media({
          title: "Replace Screenshot",
          button: {
            text: "Set Screenshot",
          },
          multiple: false,
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader1.on("select", function () {
          attachment = custom_uploader1
            .state()
            .get("selection")
            .first()
            .toJSON();
          $(".wplp_featured").attr("src", attachment.url);
          $(".wplp_featured").show();
          $("#wplp_featured_image").val(attachment.id);
          $("#wplp_media_image").val("true");

          /**
           * 设置特色图片
           */
          $("#postimagediv .hide-if-no-js").remove();
          $("#postimagediv .inside").append(
            '<p class="hide-if-no-js"><img src="' +
              attachment.url +
              '" alt="" style="max-width:100%;"/></p><p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail">移除特色图片</a></p>'
          );
          $("#_thumbnail_id").val(attachment.id);
        });

        //Open the uploader dialog
        custom_uploader1.open();
      }
    });

    /**
     * 批量更新图片
     */
    $("#update_wplf").on("click", function (e) {
      e.preventDefault();
      var ids = $(this).data("total");
      var id_arr = ids.split(",");
      var total = id_arr.length;
      var done = 0;
      $("#progressbar").show();
      var val = progressbar.progressbar("value") || 0;
      $(this).html("Please Wait...");
      var success = 0;
      var error = 0;
      var error_ids = "";
      $.each(id_arr, function (key, id) {
        $.post(ajax_object.ajax_url, {
          action: "wplf_update_from_previous",
          nonce: ajax_object.nonce,
          id: id,
        })
          .always(function () {
            done = done + 1;
            val = (done / total) * 100;
            val = Math.round(val);
            progressbar.progressbar("value", val);
          })
          .done(function () {
            $(document).ajaxStop(function () {
              $("#progressbar").hide();
              if (error_ids != "") {
                $("#update_wplf").html("Update Links");
                $("p.error.update")
                  .html(
                    "There were " +
                      error +
                      " error(s) while importing. You will need to manually add these links. The link ids that failed are " +
                      error_ids +
                      "."
                  )
                  .show();
              } else if (error_ids == "") {
                $("p.success")
                  .html("All Links were imported successfully.")
                  .show();
                $("#update_wplf").html("Update Links");
              }
            });
          })
          .fail(function () {
            error++;
            if (error_ids === "") {
              error_ids = id;
            } else {
              error_ids = error_ids + ", " + id;
            }
          });
      });
    });
  });
})(jQuery);
