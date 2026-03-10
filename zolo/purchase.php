<script>
    function openPurchaseModal(serviceId, serviceName, price) {
        document.getElementById('modalServiceName').textContent = serviceName;
        document.getElementById('modalPrice').textContent = '$' + price.toFixed(2);
        document.getElementById('purchaseModal').classList.add('active');
        document.getElementById('serviceId').value = serviceId;
    }

    function closePurchaseModal() {
        document.getElementById('purchaseModal').classList.remove('active');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('purchaseModal');
        if (event.target == modal) {
            closePurchaseModal();
        }
    }

    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.querySelector('#paymentForm .purchase-btn');
        const originalText = btn.textContent;
        btn.textContent = "Processing...";
        btn.disabled = true;
        
        const formData = {
            service_id: document.getElementById('serviceId').value,
            card_name: document.getElementById('card_name').value,
            card_number: document.getElementById('card_number').value,
            expiry: document.getElementById('expiry').value,
            cvv: document.getElementById('cvv').value,
            duration: document.getElementById('purchase_duration').value
        };

        fetch('purchase_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ Purchase Successful! Service activated.');
                closePurchaseModal();
                window.location.href = 'my-purchases.php';
            } else {
                alert('❌ ' + data.message);
                btn.textContent = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Something went wrong!');
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });
</script>
    <!-- Purchase Modal -->
    <div id="purchaseModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closePurchaseModal()">&times;</span>
            <h2>Complete Your Purchase</h2>
            <p id="modalServiceName"></p>
            <div class="price-tag" id="modalPrice"></div>
            
            <form id="paymentForm" class="payment-form">
                <input type="hidden" id="serviceId" name="service_id">
                
                <div class="form-group">
                    <label for="card_name">Cardholder Name</label>
                    <input type="text" id="card_name" required placeholder="John Doe">
                </div>
                
                <div class="form-group">
                    <label for="card_number">Card Number</label>
                    <input type="text" id="card_number" required placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="expiry">Expiry Date</label>
                        <input type="text" id="expiry" required placeholder="MM/YY" maxlength="5">
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" required placeholder="123" maxlength="3">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="purchase_duration">Subscription Duration</label>
                    <select id="purchase_duration" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                        <option value="30">1 Month</option>
                        <option value="90">3 Months</option>
                        <option value="180">6 Months</option>
                        <option value="365">1 Year</option>
                    </select>
                </div>
                
                <button type="submit" class="purchase-btn" style="width: 100%;">Complete Purchase</button>
            </form>
        </div>
    </div>