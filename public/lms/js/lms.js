function notify_it(
    status,
    message,
    redirectUrl = null,
    type = "alert",
    options = {},
) {
    const defaultTitle = status.charAt(0).toUpperCase() + status.slice(1);
    if (type === "alert") {
        return Swal.fire({
            icon: status,
            title: options.title || defaultTitle,
            html: message,
            // timer: options.timer || 2000,
            showConfirmButton: true, // Show button
            confirmButtonText: options.confirmButtonText || "Close", // Button label
            showCloseButton: options.showCloseButton ?? false, // Optional X button
            allowOutsideClick: false, // Prevent closing by clicking outside
            allowEscapeKey: false, // Prevent closing with Esc key
        }).then((result) => {
            if (redirectUrl && result.isConfirmed) {
                window.location.href = redirectUrl;
            }
        });
    }

    // Toast notification
    if (type === "toast") {
        if (typeof flasher[status] === "function") {
            flasher[status](message);
            if (redirectUrl) {
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, options.redirectDelay || 2000);
            }
        } else {
            console.error(`Invalid toast status: ${status}`);
        }
        return;
    }

    // Confirmation flow
    if (type === "confirm-upload") {
        const {
            confirmOptions = {},
            loadingOptions = {},
            ajaxOptions = {},
            onSuccess = () => {},
            onError = () => {},
        } = options;

        Swal.fire({
            icon: "warning",
            title: confirmOptions.title || "Are you sure?",
            text: confirmOptions.text || "Do you really want to proceed?",
            showCancelButton: true,
            confirmButtonText: confirmOptions.confirmText || "Yes, do it!",
            cancelButtonText: confirmOptions.cancelText || "Cancel",
            allowOutsideClick: false,
        }).then((result) => {
            if (result.isConfirmed) {
                let startTime = Date.now();

                Swal.fire({
                    title: loadingOptions.title || "Processing...",
                    html: `${loadingOptions.html || "Please wait..."}<br><strong><span id="swal-timer">00:00</span></strong>`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                        Swal._timerInterval = setInterval(() => {
                            let elapsed = Math.floor(
                                (Date.now() - startTime) / 1000,
                            );
                            let minutes = String(
                                Math.floor(elapsed / 60),
                            ).padStart(2, "0");
                            let seconds = String(elapsed % 60).padStart(2, "0");
                            document.getElementById("swal-timer").textContent =
                                `${minutes}:${seconds}`;
                        }, 1000);
                    },
                    willClose: () => clearInterval(Swal._timerInterval),
                });

                $.ajax({
                    type: ajaxOptions.type || "POST",
                    url: ajaxOptions.url,
                    data: ajaxOptions.data,
                    headers: {
                        "X-CSRF-TOKEN": $('input[name="_token"]').val(),
                    },
                    success: function (response) {
                        Swal.close();
                        if (response.success) {
                        notify_it("success", response.message, redirectUrl);
                        } else {
                            notify_it("error", response.message, null, "alert");
                        }
                    },
                    error: function (xhr) {
                        Swal.close();
                        console.log("error");

                        console.log(xhr.responseJSON);
                        let msg = "Something went wrong.";
                        if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }
                        notify_it("error", msg, "alert");
                        onError(xhr);
                    },
                });
            }
        });
    }
}
$(document).ready(function () {
    $(document).on("submit", ".ajaxForm", function (e) {
        e.preventDefault();
        var $form = $(this);

        if (!$form.valid()) return; // jQuery validation

        var $submitBtn = $form.find('[type="submit"]');
        $submitBtn.prop("disabled", true); // disable submit

        var formData = new FormData(this);

        $.ajax({
            url: $form.attr("action"),
            method: $form.attr("method") || "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $submitBtn.prop("disabled", false); // enable submit
                if (response.success) {
                    notify_it("success", response.message);

                    // Reset form if it's a create form
                    if (!$form.find('[name$="_id"]').val()) {
                        $form[0].reset();
                        if ($form.find(".summernote").length) {
                            $form.find(".summernote").each(function () {
                                $(this).summernote("code", "");
                            });
                        }
                    }

                    // Redirect if needed
                    if (response.redirect_url) {
                        setTimeout(function () {
                            window.location.href = response.redirect_url;
                        }, 2000);
                    }
                } else {
                    notify_it("error", response.message);
                }
            },
            error: function (xhr) {
                $submitBtn.prop("disabled", false); // enable submit
                let messages = [];
                if (xhr.status === 422 && xhr.responseJSON) {
                    const response = xhr.responseJSON;
                    if (response.errors) {
                        for (let field in response.errors) {
                            response.errors[field].forEach((msg) =>
                                messages.push(msg),
                            );
                        }
                    }
                    if (messages.length === 0 && response.message) {
                        messages.push(response.message);
                    }
                } else {
                    messages.push("An unexpected error occurred.");
                }
                notify_it("error", messages.join("<br>"));
            },
        });
    });
});

$(document).on("click", ".deleteRecord", function () {
    const $button = $(this);
    const actionUrl = $('#deleteUrl').val();
    const dataId = $button.data("id");

    Swal.fire({
        title: "Are you sure?",
        text: "Do you want to delete this course?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Delete",
        cancelButtonText: "Cancel",
        allowOutsideClick: false,
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: "POST",
                url: actionUrl,
                data: { id: dataId },
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content",
                    ),
                },
                success: function (response) {
                    if (response.status) {
                        notify_it("success", response.message);
                        $button.closest("tr").fadeOut(500, function () {
                            $(this).remove();
                        });
                    } else {
                        notify_it("error", response.message);
                    }
                },
                error: function (xhr) {
                    let messages = [];
                    if (xhr.status === 422 && xhr.responseJSON) {
                        const response = xhr.responseJSON;
                        if (response.errors) {
                            for (let field in response.errors) {
                                response.errors[field].forEach((msg) =>
                                    messages.push(msg),
                                );
                            }
                        }
                        if (messages.length === 0 && response.message) {
                            messages.push(response.message);
                        }
                    } else {
                        messages.push("An unexpected error occurred.");
                    }
                    notify_it("error", messages.join("<br>"));
                },
            });
        }
    });
});