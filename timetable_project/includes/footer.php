<!-- Authenticated layout footer shared by protected application pages. -->
</div> <!-- Close container -->

<footer class="bg-emerald-900 border-t border-emerald-800 text-emerald-100 text-center py-4 mt-8 text-sm shadow-inner">

    <p class="font-medium">
        Academic Scheduling & Seating Suite © <?= date('Y') ?> • University Administration System
    </p>

    <p class="mt-1 text-emerald-200">
        Developed for Efficient Scheduling and Seating Management
    </p>

    <div class="mt-3 border-t border-emerald-700 pt-3">

        <p class="text-emerald-200">
            Supervised By
        </p>

        <p class="font-semibold text-white text-base">
            Dr. Qamar Nawaz
        </p>

        <p class="mt-3 text-emerald-200">
            Developed By
        </p>

        <p class="font-semibold text-white text-base">
            Marriam Shafiq
        </p>

        <p class="mt-3 text-emerald-400 text-xs">
            All Rights Reserved
        </p>

    </div>

</footer>

<script>
    if (window.jQuery && window.appCsrfToken) {
        jQuery.ajaxSetup({
            beforeSend: function (xhr, settings) {
                if ((settings.type || settings.method || 'GET').toUpperCase() === 'POST') {
                    xhr.setRequestHeader('X-CSRF-Token', window.appCsrfToken);
                }
            }
        });
    }
</script>

</body>
</html>