// assets/js/invoice.js
$(document).ready(function() {
    // Calculate row total
    $(document).on('keyup change', '.qty, .price, .tax', function() {
        let row = $(this).closest('tr');
        let qty = parseFloat(row.find('.qty').val()) || 0;
        let price = parseFloat(row.find('.price').val()) || 0;
        let taxRate = parseFloat(row.find('.tax').val()) || 0;
        
        // Calculate item amount without tax
        let amount = qty * price;
        // Calculate item tax
        let itemTax = amount * (taxRate / 100);
        let total = amount + itemTax;

        row.find('.total').val(total.toFixed(2));
        
        // Store raw numbers in data attributes for summary calculations
        row.data('amount', amount);
        row.data('tax', itemTax);
        
        calculateGrandTotal();
    });

    // Calculate grand total for invoice
    function calculateGrandTotal() {
        let subTotal = 0;
        let totalTax = 0;
        let grandTotal = 0;

        $('#invoiceItems tr').each(function() {
            let tr = $(this);
            if(tr.data('amount')) {
                subTotal += parseFloat(tr.data('amount')) || 0;
            }
            if(tr.data('tax')) {
                totalTax += parseFloat(tr.data('tax')) || 0;
            }
        });

        // Discount
        let discount = parseFloat($('#discount').val()) || 0;

        grandTotal = subTotal + totalTax - discount;

        $('#subtotal_view').text(subTotal.toFixed(2));
        $('#tax_view').text(totalTax.toFixed(2));
        $('#grand_total_view').text(grandTotal.toFixed(2));

        // Hidden inputs
        $('#subtotal').val(subTotal.toFixed(2));
        $('#tax_total').val(totalTax.toFixed(2));
        $('#total').val(grandTotal.toFixed(2));
    }

    // Trigger on discount change
    $('#discount').on('keyup change', function() {
        calculateGrandTotal();
    });

    // Add new row
    $('#addRow').off('click').on('click', function() {
        let rowCount = $('#invoiceItems tr').length;
        let html = `
            <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
                <td class="p-4">
                    <select name="product_id[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 product-select transition-colors outline-none" required>
                        <option value="">Select Product...</option>
                        <!-- Need to fetch products via AJAX or inject them initially -->
                        ${window.productOptions || ''}
                    </select>
                </td>
                <td class="p-4"><input type="number" step="0.01" name="price[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 price transition-colors outline-none" required></td>
                <td class="p-4"><input type="number" step="1" name="quantity[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 qty transition-colors outline-none" value="1" required></td>
                <td class="p-4"><input type="number" step="0.01" name="item_tax[]" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block p-2.5 tax transition-colors outline-none" value="0"></td>
                <td class="p-4"><input type="text" name="item_total[]" class="w-full bg-gray-100 border border-gray-200 text-gray-900 text-sm rounded-xl block p-2.5 total outline-none" readonly></td>
                <td class="p-4"><button type="button" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors removeRow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></button></td>
            </tr>
        `;
        $('#invoiceItems').append(html);
        
        // Re-initialize premium dropdowns for the newly added select element
        if (typeof window.initPremiumDropdowns === 'function') {
            window.initPremiumDropdowns();
        }
    });

    // Remove row
    $(document).on('click', '.removeRow', function() {
        $(this).closest('tr').remove();
        calculateGrandTotal();
    });

    // Fetch product details when selected
    $(document).on('change', '.product-select', function() {
        let row = $(this).closest('tr');
        let productId = $(this).val();
        
        if (productId) {
            $.ajax({
                url: BASE_URL + '/ajax/search_product.php',
                type: 'GET',
                data: {id: productId},
                dataType: 'json',
                success: function(res) {
                    if(res && res.success) {
                        row.find('.price').val(res.data.price);
                        row.find('.tax').val(res.data.tax);
                        // Trigger calculation
                        row.find('.qty').trigger('change');
                    }
                }
            });
        } else {
            row.find('.price').val('');
            row.find('.tax').val('');
            row.find('.total').val('');
            row.find('.qty').trigger('change');
        }
    });
});
