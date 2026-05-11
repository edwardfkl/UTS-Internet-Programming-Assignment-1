{{--
    Wires up the bulk-action toolbar markup rendered by
    `admin/partials/bulk-toolbar.blade.php`.

    Assumes the surrounding <form> contains:
      - Optional <input type="checkbox" data-bulk-select-all>
      - Many <input type="checkbox" name="ids[]" data-bulk-id>
      - A toolbar block with data-bulk-toolbar / data-bulk-count / data-bulk-submit
--}}
<script>
    (function () {
        const forms = document.querySelectorAll('form[data-bulk-form]');
        forms.forEach((form) => {
            const selectAll = form.querySelector('[data-bulk-select-all]');
            const rowChecks = form.querySelectorAll('[data-bulk-id]');
            const counters = form.querySelectorAll('[data-bulk-count]');
            const submits = form.querySelectorAll('[data-bulk-submit]');

            const refresh = () => {
                let selected = 0;
                rowChecks.forEach((cb) => {
                    if (cb.checked) selected++;
                });
                counters.forEach((el) => {
                    el.textContent = String(selected);
                });
                submits.forEach((btn) => {
                    btn.disabled = selected === 0;
                });
                if (selectAll) {
                    selectAll.checked = selected > 0 && selected === rowChecks.length;
                    selectAll.indeterminate = selected > 0 && selected < rowChecks.length;
                }
            };

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    rowChecks.forEach((cb) => {
                        cb.checked = selectAll.checked;
                    });
                    refresh();
                });
            }
            rowChecks.forEach((cb) => cb.addEventListener('change', refresh));
            refresh();
        });
    })();
</script>
