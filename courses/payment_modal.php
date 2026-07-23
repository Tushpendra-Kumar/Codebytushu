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

    <!-- Premium Success Popup -->
    <div id="success-popup" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); opacity:0; transition:opacity 0.4s ease;">
        <div style="background:#111; border:1px solid rgba(255,196,0,0.3); border-radius:16px; padding:40px 30px; max-width:450px; width:90%; text-align:center; box-shadow:0 10px 40px rgba(255,196,0,0.15); transform:scale(0.9); transition:transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <div style="width:70px; height:70px; background:rgba(40,167,69,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; border:2px solid #28a745;">
                <i class="fas fa-check" style="color:#28a745; font-size:2rem;"></i>
            </div>
            <h2 style="color:#ffc400; margin-bottom:15px; font-size:1.8rem;">Payment Successful</h2>
            <p style="color:#ddd; font-size:1.1rem; line-height:1.6; margin-bottom:10px;"><strong>Thank you for purchasing this course.</strong></p>
            <p style="color:#aaa; margin-bottom:15px;">Your payment has been verified successfully.</p>
            <p id="success-download-text" style="color:#28a745; font-weight:bold; margin-bottom:30px;"><i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>Your PDF download is starting automatically...</p>
            <button onclick="closeSuccessPopup()" style="background:#333; color:#fff; border:1px solid #555; padding:12px 25px; border-radius:8px; font-weight:bold; cursor:pointer; transition:background 0.3s;">Continue Browsing</button>
        </div>
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
                    // Show Premium Success Popup
                    var popup = document.getElementById('success-popup');
                    popup.style.display = 'flex';
                    // Trigger fade and scale animation
                    setTimeout(function() {
                        popup.style.opacity = '1';
                        popup.firstElementChild.style.transform = 'scale(1)';
                    }, 50);

                    // Auto-trigger download after 1.5 seconds for better UX
                    setTimeout(function() {
                        document.getElementById('success-download-text').innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i>Download started!';
                        var a = document.createElement('a');
                        a.href = '/api/courses/download.php?id=' + _payingCourseId;
                        a.download = '';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }, 1500);
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

        function closeSuccessPopup() {
            var popup = document.getElementById('success-popup');
            popup.style.opacity = '0';
            popup.firstElementChild.style.transform = 'scale(0.9)';
            setTimeout(function() {
                popup.style.display = 'none';
                location.reload();
            }, 400);
        }
    </script>
