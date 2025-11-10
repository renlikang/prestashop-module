document.addEventListener('DOMContentLoaded', function () {
    try {
        var substrings = window.hcoStatusSubstrings || [];
        if (!Array.isArray(substrings) || substrings.length === 0) return;

        // Attempt to find the order history table rows and remove rows matching cancelled statuses.
        // Adapt to common PrestaShop themes: look for table rows under .order-list, .orders, or #content div.
        var selectors = [
            '.order-list tbody tr',
            '.js-order-row',
            '.history tbody tr',
            '#order-list tbody tr',
            'table.orders tbody tr',
            'table.table tbody tr'
        ];

        var rows = [];
        selectors.forEach(function (sel) {
            var found = document.querySelectorAll(sel);
            if (found && found.length) {
                found.forEach(function (r) { rows.push(r); });
            }
        });

        if (rows.length === 0) return;

        rows.forEach(function (row) {
            var text = row.innerText || '';
            var match = substrings.some(function (s) {
                if (!s) return false;
                return text.toLowerCase().indexOf(s.toLowerCase()) !== -1;
            });
            if (match) {
                row.remove();
            }
        });
    } catch (e) {
        // silent fail
        console && console.debug && console.debug('HideCancelledOrders error', e);
    }
});