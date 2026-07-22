    <!-- Payment Modal (for Paid courses) -->
    <div id="pay-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#111; border:1px solid #333; border-radius:16px; padding:35px 30px; max-width:480px; width:90%; text-align:center; position:relative;">
            <button onclick="closePayModal()" style="position:absolute;top:15px;right:18px;background:none;border:none;color:#aaa;font-size:1.4rem;cursor:pointer;">&#x2715;</button>
            <i class="fas fa-lock" style="color:#ffc400;font-size:2.5rem;margin-bottom:15px;"></i>
            <h2 id="pay-course-title" style="color:#fff;margin-bottom:5px;"></h2>
            <div id="pay-amount" style="font-size:2rem;font-weight:700;color:#ffc400;margin:15px 0;"></div>
            <p style="color:#aaa;margin-bottom:20px;">Scan the QR code or pay to the UPI ID below.</p>
            <div style="background:#fff;display:inline-block;padding:10px;border-radius:8px;margin-bottom:12px;">
                <img id="pay-qr" src="" alt="UPI QR" style="width:200px;height:200px;display:block;">
            </div>
            <p style="color:#ccc;margin:8px 0 4px;">UPI ID:</p>
            <div style="font-size:1.1rem;color:#ffc400;font-weight:700;letter-spacing:1px;margin-bottom:20px;">tushpendrakum@slc</div>
            <a id="pay-upi-btn" href="#" style="display:block;background:#007bff;color:#fff;padding:12px;border-radius:8px;font-weight:bold;text-decoration:none;margin-bottom:12px;">Pay via UPI App (Mobile)</a>
            <button id="pay-done-btn" onclick="confirmPayment()" style="width:100%;background:#28a745;color:#fff;padding:13px;border:none;border-radius:8px;font-size:1rem;font-weight:bold;cursor:pointer;">I Have Completed Payment</button>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="success-toast" style="display:none;position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:#111;border:1px solid #28a745;color:#fff;padding:18px 28px;border-radius:12px;z-index:99999;text-align:center;box-shadow:0 8px 30px rgba(0,0,0,0.5);min-width:300px;">
        <div style="font-size:1.8rem;color:#28a745;margin-bottom:6px;">&#10003;</div>
        <div style="font-weight:700;font-size:1.1rem;margin-bottom:4px;">Payment Successful!</div>
        <div style="color:#aaa;font-size:0.95rem;">Thank you for purchasing this course. Your download has started.</div>
    </div>

    <script>
        var _payingCourseId = null;

        function initPayment(courseId, title, price) {
            // If not logged in, redirect to login
            <?php if (!Auth::check()): ?>
            window.location.href = '/auth/login.php?redirect=' + encodeURIComponent(window.location.pathname);
            return;
            <?php endif; ?>

            _payingCourseId = courseId;
            document.getElementById('pay-course-title').textContent = title;
            document.getElementById('pay-amount').textContent = '\u20B9' + parseFloat(price).toFixed(2);

            var upiId  = 'tushpendrakum@slc';
            var upiLink = 'upi://pay?pa=' + upiId + '&pn=CodeByTushu&am=' + price + '&cu=INR';
            var qrUrl   = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(upiLink);

            document.getElementById('pay-qr').src      = qrUrl;
            document.getElementById('pay-upi-btn').href = upiLink;

            var modal = document.getElementById('pay-modal');
            modal.style.display = 'flex';
        }

        function closePayModal() {
            document.getElementById('pay-modal').style.display = 'none';
            _payingCourseId = null;
        }

        async function confirmPayment() {
            if (!_payingCourseId) return;
            var btn = document.getElementById('pay-done-btn');
            btn.disabled = true;
            btn.textContent = 'Processing...';

            try {
                var res  = await fetch('/api/checkout/submit_single.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'course_id=' + _payingCourseId
                });
                var data = await res.json();

                if (data.success) {
                    closePayModal();
                    // Auto-trigger download
                    var a = document.createElement('a');
                    a.href = '/api/courses/download.php?id=' + _payingCourseId;
                    a.download = '';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    // Show success toast
                    var toast = document.getElementById('success-toast');
                    toast.style.display = 'block';
                    setTimeout(function() { toast.style.display = 'none'; location.reload(); }, 4000);
                } else {
                    alert('Error: ' + (data.error || 'Something went wrong.'));
                }
            } catch(e) {
                alert('Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'I Have Completed Payment';
            }
        }
    </script>
