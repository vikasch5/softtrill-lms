$(document).ready(function () {
    if (typeof $.validator === "undefined") {
        console.error("jQuery Validation plugin is missing!");
        return;
    }

    /* -------------------------------
       Custom Validation Methods
    --------------------------------*/
    $.validator.addMethod("alphanumeric", function (value) {
        return (
            (/[a-zA-Z]/.test(value) && /\d/.test(value)) || // letters & numbers
            /^[a-zA-Z ]+$/.test(value)                      // only letters/spaces
        );
    }, "Must contain letters (and optionally numbers).");

    $.validator.addMethod("validMobile", function (value, el) {
        return this.optional(el) || /^[6-9]\d{9}$/.test(value);
    }, "Enter a valid 10-digit mobile.");

    $.validator.addMethod("validAadhaar", function (value, el) {
        return this.optional(el) || /^\d{12}$/.test(value);
    }, "Enter a valid 12-digit Aadhaar.");

    $.validator.addMethod("validPAN", function (value, el) {
        return this.optional(el) || /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(value);
    }, "Enter a valid PAN number (ABCDE1234F).");

    $.validator.addMethod("validGST", function (value, el) {
        return this.optional(el) ||
            /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(value);
    }, "Enter a valid GST number.");

    $.validator.addMethod("strongPassword", function (value, el) {
        return this.optional(el) ||
            /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,12}$/.test(value);
    }, "Password: 8–12 chars, letters, numbers & symbol.");

    $.validator.addMethod("strictEmail", function (value, el) {
        return this.optional(el) || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);
    }, "Enter a valid email.");

    /* -------------------------------
       Attach Validation to All Forms
    --------------------------------*/
    $("form").each(function () {
        const form = $(this);
        const rules = {};
        const messages = {};

        form.find("input,select,textarea").each(function () {
            const field = $(this);
            const name = field.attr("name");
            if (!name) return;

            switch (name) {
                case "vendor_name":
                    rules[name] = { required: true, alphanumeric: true, minlength: 3, maxlength: 100 };
                    messages[name] = "Vendor name: 3–100 letters/numbers.";
                    break;

                case "email":
                    rules[name] = { required: true, strictEmail: true, maxlength: 100 };
                    messages[name] = "Please enter a valid email.";
                    break;

                case "password":
                    rules[name] = { required: false, strongPassword: false };
                    messages[name] = "This field is required.";
                    break;

                case "mobile":
                    rules[name] = { required: true, validMobile: true };
                    break;

                case "pancard":
                    rules[name] = { required: false, validPAN: true, maxlength: 10 };
                    break;

                case "aadhar_card":
                    rules[name] = { required: false, validAadhaar: true };
                    break;

                case "company_name":
                    rules[name] = { required: false, minlength: 3, maxlength: 100 };
                    break;

                case "gst_number":
                    rules[name] = { required: false, validGST: true, maxlength: 15 };
                    break;

                case "bank_id":
                    rules[name] = { required: true, maxlength: 50 };
                    break;

                case "bank_ifsc_code":
                    rules[name] = { required: true, maxlength: 30 };
                    break;

                case "bank_account_number":
                    rules[name] = { required: true, maxlength: 20 };
                    break;

                case "branch":
                    rules[name] = { required: false, maxlength: 100 };
                    break;

                case "amount_percentage":
                    rules[name] = { required: false, maxlength: 10 };
                    break;
            }
        });

        form.validate({
            rules: rules,
            messages: messages,
            errorClass: "text-danger",
            errorPlacement: function (err, el) {
                err.insertAfter(el);
            },
            highlight: function (el) {
                $(el).addClass("is-invalid");
            },
            unhighlight: function (el) {
                $(el).removeClass("is-invalid");
            }
        });
    });

    /* -------------------------------
       Input Restrictions
    --------------------------------*/
    $(document).on("input", 'input[name="mobile"]', function () {
        this.value = this.value.replace(/\D/g, "").slice(0, 10);
    });

    $(document).on("input", 'input[name="aadhar_card"]', function () {
        this.value = this.value.replace(/\D/g, "").slice(0, 12);
    });

    $(document).on("input", 'input[name="email"]', function () {
        this.value = this.value.slice(0, 100);
    });

    $(document).on("input", 'input[name="pancard"]', function () {
        this.value = this.value.slice(0, 10).toUpperCase();
    });

    $(document).on("input", 'input[name="gst_number"]', function () {
        this.value = this.value.slice(0, 15).toUpperCase();
    });

    $(document).on("input", 'input.price-input', function () {
        let value = this.value.replace(/[^0-9.]/g, "");
        if ((value.match(/\./g) || []).length > 1) {
            value = value.replace(/\.(?=.*\.)/, "");
        }
        if (value.includes(".")) {
            let parts = value.split(".");
            parts[0] = parts[0].slice(0, 8);
            parts[1] = parts[1].slice(0, 2);
            value = parts.join(".");
        } else {
            value = value.slice(0, 8);
        }
        this.value = value;
    });
});