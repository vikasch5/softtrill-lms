<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register - JobKart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('lms/css/lib/bootstrap.min.css') }}">
    <script src="{{ asset('lms/js/lib/jquery-3.7.1.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('vendor/flasher/sweetalert2.min.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <link rel="stylesheet" href="{{ asset('lms/css/remixicon.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('lms/images/favicon.png') }}" sizes="16x16">
    <!-- main css -->
    <link rel="stylesheet" href="{{ asset('lms/css/style.css') }}">

    <style>
        body {
            margin: 0;
            /* overflow: hidden; */
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
        }

        /* ===== Full Screen Wrapper ===== */
        .auth-wrapper {
            height: 100vh;
            display: flex;
        }

        /* ===== Left Section ===== */
        .auth-left {
            flex: 1;
            /* background: linear-gradient(135deg, #4e73df, #224abe); */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .auth-left img {
            /* max-width: 90%; */
            max-height: 100vh;
        }

        /* ===== Right Section ===== */
        .auth-right {
            flex: 1;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .auth-box {
            width: 100%;
            max-width: 420px;
        }

        .auth-box h4 {
            font-weight: 600;
        }

        .form-control {
            height: 40px;
            border-radius: 8px;
        }

        .btn-primary {
            height: 40px;
            border-radius: 8px;
        }

        .otp-btn {
            height: 40px;
        }

        /* .toggle-password {
            position: absolute;
            right: 15px;
            top: 14px;
            cursor: pointer;
            font-size: 14px;
            color: #777;
        } */

        .error {
            color: red;
            font-size: 13px;
        }

        @media (max-width: 992px) {
            .auth-left {
                display: none;
            }
        }
    </style>
</head>

<body>

    <section class="auth-wrapper">

        <!-- LEFT SIDE -->
        <div class="auth-left">
            <img src="{{ asset('lms/images/auth/auth-img.png') }}" alt="Register">
        </div>

        <!-- RIGHT SIDE -->
        <div class="auth-right">
            <div class="auth-box">

                <div>
                    <a href="index.html" class="mb-40 max-w-290-px">
                        <!-- <img src="{{ asset('lms/images/logo.png') }}" alt="Image"> -->
                        <!-- <h4>JobKart Task</h4> -->
                    </a>
                    <h6 class="mb-12">Create your Account</h6>
                    {{-- <p class="mb- text-secondary-light text-lg">Welcome back! please enter your detail</p> --}}
                </div>
                <form id="registerForm" method="POST" action="{{ route('register') }}">
                    @csrf
                    <!-- Name -->
                    <div class="mb-3">

                        <input type="text" name="name" class="form-control" placeholder="Full Name">
                    </div>
                    <!-- Email -->
                    <div class="mb-3">
                        <input type="email" name="email" id="email" class="form-control" placeholder="Email Address">
                    </div>
                    <!-- Phone -->
                    {{-- <div class="mb-3">
                        <input type="text" name="phone" class="form-control" placeholder="Phone Number">
                    </div> --}}

                    <!-- OTP -->
                    <div class="row mb-3">
                        <div class="col-7">
                            <input type="text" name="otp" class="form-control" placeholder="Enter OTP">
                        </div>
                        <div class="col-5">
                            <button type="button" id="sendOtp" class="btn btn-outline-primary w-100 otp-btn">
                                Send OTP
                            </button>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="position-relative mb-20">
                        <div class="">
                            {{-- <span class="icon top-50 translate-middle-y">
                                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                            </span> --}}

                            <input type="password" name="password" id="password"
                                class="form-control h-56-px bg-neutral-50 radius-12" placeholder="Password">
                        </div>

                        <small class="text-danger error-password"></small>

                        <span
                            class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light"
                            data-toggle="#password">
                        </span>
                    </div>



                    <div class="position-relative mb-20">
                        <div class="">
                            {{-- <span class="icon top-50 translate-middle-y">
                                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                            </span> --}}
                            <input type="password" name="password_confirmation" id="confirmPassword"
                                class="form-control h-56-px bg-neutral-50 radius-12" placeholder="Confirm Password">
                        </div>
                        <small class="text-danger error-password"></small>
                        <spanz
                            class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light"
                            data-toggle="#confirmPassword"></span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">
                        Sign Up
                    </button>

                    <div class="mt-32 text-center">
                        <p class="mb-0">Already have an account? <a href="{{ route('login') }}"
                                class="text-primary-600 fw-semibold">Login</a></p>
                    </div>


                </form>
            </div>
        </div>

    </section>
    {{--
    <script src="{{ asset('lms/js/lib/jquery-3.7.1.min.js') }}"></script> --}}
    <!-- Bootstrap js -->
    {{--
    <script src="{{ asset('lms/js/lib/bootstrap.bundle.min.js') }}"></script> --}}
    {{--
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script> --}}
    <!-- Apex Chart js -->
    <script src="{{ asset('lms/js/lib/jquery-ui.min.js') }}"></script>
    {{--
    <script src="{{ asset('lms/js/app.js') }}"></script> --}}
    <script src="{{ asset('lms/js/task.js') }}"></script>
    <script src="{{ asset('vendor/flasher/sweetalert2.min.js')}}"></script>
    <script src="{{ asset('vendor/flasher/sweetalert2.min.js')}}"></script>
    <script src="{{ asset('lms/js/lib/iconify-icon.min.js') }}"></script>
    <script src="{{ asset('vendor/flasher/flasher-sweetalert.min.js')}}"></script>

    <script>
        function initializePasswordToggle(toggleSelector) {
            $(toggleSelector).on('click', function () {
                console.log("Initializing password toggle for selector:", toggleSelector);

                $(this).toggleClass("ri-eye-off-line");
                var input = $($(this).attr("data-toggle"));
                if (input.attr("type") === "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }
            });
        }
        // Call the function
        initializePasswordToggle('.toggle-password');
        function startOtpCountdown(button) {

            // Store expiry time (60 sec from now)
            let expiryTime = Date.now() + 60000;
            localStorage.setItem("otp_expiry", expiryTime);

            runOtpTimer(button);
        }

        function runOtpTimer(button) {

            let interval = setInterval(function () {

                let expiry = localStorage.getItem("otp_expiry");

                if (!expiry) {
                    clearInterval(interval);
                    button.prop("disabled", false).text("Send OTP");
                    return;
                }

                let remaining = Math.floor((expiry - Date.now()) / 1000);

                if (remaining > 0) {
                    button.prop("disabled", true);
                    button.text("Resend in " + remaining + "s");
                } else {
                    clearInterval(interval);
                    localStorage.removeItem("otp_expiry");
                    button.prop("disabled", false).text("Resend OTP");
                }

            }, 1000);
        }
        /* ===== Password Toggle ===== */
        // $(".toggle-password").click(function () {
        //     let input = $($(this).attr("data-target"));
        //     if (input.attr("type") === "password") {
        //         input.attr("type", "text");
        //         $(this).text("Hide");
        //     } else {
        //         input.attr("type", "password");
        //         $(this).text("Show");
        //     }
        // });

        /* ===== jQuery Validation ===== */
        $("#registerForm").validate({
            rules: {
                name: "required",
                email: {required: true, email: true},
                phone: {required: true, digits: true, minlength: 10, maxlength: 10},
                otp: {required: true, digits: true},
                password: {required: true, minlength: 6},
                password_confirmation: {
                    required: true,
                    equalTo: "#password"
                }
            }
        });

        /* ===== Send OTP AJAX ===== */
        $(document).on("click", "#sendOtp", function () {

            let $btn = $(this);
            let email = $("#email").val().trim();

            // Basic validation
            if (email === "") {
                notify_it('error', "Please enter your email.");
                return;
            }

            // Email format check
            let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                notify_it('error', "Enter a valid email address.");
                return;
            }

            // Prevent multiple clicks
            $btn.prop("disabled", true).text("Sending...");

            $.ajax({
                url: "{{ route('send.otp') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    email: email
                },
                success: function (res) {

                    if (res.success) {
                        notify_it('success', res.message);
                        startOtpCountdown($btn);
                    } else {
                        notify_it('error', res.message);
                        $btn.prop("disabled", false).text("Send OTP");
                    }
                },
                error: function (xhr) {

                    let message = "Something went wrong.";

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    notify_it('error', message);
                    $btn.prop("disabled", false).text("Send OTP");
                }
            });

        });

        $(document).ready(function () {
            runOtpTimer($("#sendOtp"));
        });

        /* ===== AJAX Register Submit ===== */
        $(document).on("submit", "#registerForm", function (e) {

            e.preventDefault();

            let $form = $(this);
            let $btn = $form.find("button[type='submit']");

            // Prevent double submit
            $btn.prop("disabled", true).text("Please wait...");

            $.ajax({
                url: $form.attr("action"),
                type: "POST",
                data: $form.serialize(),
                dataType: "json",

                success: function (res) {

                    if (res.status) {
                        notify_it('success', res.message, res.redirect_url);
                    } else {
                        if (res.errors) {

                            $.each(res.errors, function (key, value) {
                                notify_it('error', value[0]);
                            });

                        } else {
                            notify_it('error', res.message);
                        }
                        $btn.prop("disabled", false).text("Sign Up");
                    }
                },

                error: function (xhr) {

                    let message = "Server error. Please try again.";

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    notify_it('error', message);
                    $btn.prop("disabled", false).text("Sign Up");
                }
            });

        });
    </script>

</body>

</html>