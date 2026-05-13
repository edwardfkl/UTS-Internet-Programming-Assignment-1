<script>
(function () {
    document.querySelectorAll('form[data-admin-live-search]').forEach(function (form) {
        var input = form.querySelector('input[name="q"]');
        if (!input) {
            return;
        }
        var debounceMs = 350;
        var timer = null;
        function submitNow() {
            if (timer !== null) {
                clearTimeout(timer);
                timer = null;
            }
            form.submit();
        }
        input.addEventListener('input', function () {
            if (timer !== null) {
                clearTimeout(timer);
            }
            timer = setTimeout(submitNow, debounceMs);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitNow();
            }
        });
    });
})();
</script>
