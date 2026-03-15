<!DOCTYPE html>
<html lang="en" data-theme="light">
@include('lms.common.head')
<body>
    <div class="app-wrapper">
<div class="body-overlay"></div>

    @include('lms.common.sidebar')
<main class="dashboard-main">

@include('lms.common.header')
@yield('content')
</main>
  <!-- jQuery library js -->
  <script src="{{ asset('lms/js/lib/jquery-3.7.1.min.js')}}"></script>
  <!-- Bootstrap js -->
  <script src="{{ asset('lms/js/lib/bootstrap.bundle.min.js')}}"></script>
  <!-- Apex Chart js -->
  {{-- <script src="{{ asset('lms/js/lib/apexcharts.min.js')}}"></script> --}}
  <!-- Data Table js -->
  {{-- <script src="{{ asset('lms/js/lib/dataTables.min.js')}}"></script> --}}
  <!-- Iconify Font js -->
  <script src="{{ asset('lms/js/lib/iconify-icon.min.js')}}"></script>
  <!-- jQuery UI js -->
  <script src="{{ asset('lms/js/lib/jquery-ui.min.js')}}"></script>
  <!-- Vector Map js -->
  {{-- <script src="{{ asset('lms/js/lib/jquery-jvectormap-2.0.5.min.js')}}"></script>
  <script src="{{ asset('lms/js/lib/jquery-jvectormap-world-mill-en.js')}}"></script> --}}
  <!-- Popup js -->
  {{-- <script src="{{ asset('lms/js/lib/magnifc-popup.min.js')}}"></script> --}}
  <!-- Slick Slider js -->
  <script src="{{ asset('lms/js/lib/slick.min.js')}}"></script>
  <!-- prism js -->
  {{-- <script src="{{ asset('lms/js/lib/prism.js')}}"></script> --}}
  <!-- file upload js -->
  <script src="{{ asset('lms/js/lib/file-upload.js')}}"></script>
  <!-- audioplayer -->
  {{-- <script src="{{ asset('lms/js/lib/audioplayer.js')}}"></script> --}}
  
  <!-- main js -->
  <script src="{{ asset('lms/js/app.js')}}"></script>

{{-- <script src="{{ asset('lms/js/homeOneChart.js')}}"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.20.0/dist/jquery.validate.min.js"></script>
    <script src="{{ asset('lms/js/validation.js')}}"></script>
<script src="{{ asset('lms/js/lms.js')}}"></script>
{{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
   <script src="{{ asset('vendor/flasher/sweetalert2.min.js')}}"></script>
   <script src="{{ asset('vendor/flasher/flasher-sweetalert.min.js')}}"></script>
   @yield('scripts')
</div>
</body>
</html>