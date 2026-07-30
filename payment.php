<?php
session_start();

define('MOOLRE_API_USER', 'kofiloner');
define('MOOLRE_API_KEY', 'eb829f13-6f15-4b92-b143-ad3a519b88a2');
define('MOOLRE_API_PUBKEY', 'YOUR_PUBLIC_API_KEY');     // X-API-PUBKEY (public)
define('MOOLRE_ACCOUNT_NO', '10933306072910');
define('MOOLRE_BUSINESS_EMAIL', 'mesickisicki@gmail.com');
define('MOOLRE_SANDBOX', true);                      // set false in production

define(
    'MOOLRE_BASE',
    MOOLRE_SANDBOX
    ? 'https://sandbox.moolre.com'
    : 'https://api.moolre.com'
);

define('SITE_URL', 'https://seekerafric.online/payment_success.php');

function moolre_post(string $endpoint, array $payload, string $key_header = 'X-API-KEY'): array
{
    $url = MOOLRE_BASE . $endpoint;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-USER: ' . MOOLRE_API_USER,
            $key_header . ': ' . (
                $key_header === 'X-API-PUBKEY'
                ? MOOLRE_API_PUBKEY
                : MOOLRE_API_KEY
            ),
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($res, true) ?? [];
    $data['_http'] = $code;
    return $data;
}

function unique_ref(): string
{
    return 'SA-' . strtoupper(bin2hex(random_bytes(6))) . '-' . time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    if ($_POST['ajax_action'] === 'pay_momo') {
        $phone = preg_replace('/\D/', '', trim($_POST['phone'] ?? ''));
        $amount = trim($_POST['amount'] ?? '');
        $network = trim($_POST['network'] ?? '13'); // 13=MTN, 6=Telecel, 7=AT
        $ref = unique_ref();

        if (!$phone || strlen($phone) < 9) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number.']);
            exit;
        }
        if (!$amount || !is_numeric($amount) || (float) $amount < 1) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid amount.']);
            exit;
        }

        // Normalise Ghana number: 0XXXXXXXXX → 233XXXXXXXXX
        if (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '233' . substr($phone, 1);
        }

        $res = moolre_post('/open/transact/payment', [
            'type' => 1,
            'channel' => $network,
            'currency' => 'GHS',
            'payer' => $phone,
            'amount' => $amount,
            'externalref' => $ref,
            'accountnumber' => MOOLRE_ACCOUNT_NO,
        ]);

        $_SESSION['moolre_ref'] = $ref;
        $_SESSION['moolre_amt'] = $amount;

        if (($res['status'] ?? 0) == 1) {
            // Code TR099 
            // Code TP14  
            $needs_otp = ($res['code'] ?? '') === 'TP14';
            echo json_encode([
                'success' => true,
                'needs_otp' => $needs_otp,
                'ref' => $ref,
                'tx_id' => $res['data'] ?? null,
                'message' => $needs_otp
                    ? 'An OTP has been sent to your phone. Enter it below to confirm.'
                    : 'A payment prompt has been sent to your phone. Approve it to complete payment.',
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $res['message'] ?? 'Payment initiation failed. Please try again.',
            ]);
        }
        exit;
    }

    if ($_POST['ajax_action'] === 'submit_otp') {
        $otp = trim($_POST['otp'] ?? '');
        $phone = preg_replace('/\D/', '', trim($_POST['phone'] ?? ''));
        $amount = $_SESSION['moolre_amt'] ?? '0';
        $ref = $_SESSION['moolre_ref'] ?? unique_ref();
        $network = trim($_POST['network'] ?? '13');

        if (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '233' . substr($phone, 1);
        }

        $res = moolre_post('/open/transact/payment', [
            'type' => 1,
            'channel' => $network,
            'currency' => 'GHS',
            'payer' => $phone,
            'amount' => $amount,
            'externalref' => $ref,
            'otpcode' => $otp,
            'accountnumber' => MOOLRE_ACCOUNT_NO,
        ]);

        if (($res['status'] ?? 0) == 1) {
            echo json_encode([
                'success' => true,
                'ref' => $ref,
                'tx_id' => $res['data'] ?? null,
                'message' => 'OTP verified. A payment prompt has been sent to your phone. Approve it.',
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $res['message'] ?? 'Invalid OTP. Please try again.',
            ]);
        }
        exit;
    }

    if ($_POST['ajax_action'] === 'check_status') {
        $ref = $_POST['ref'] ?? $_SESSION['moolre_ref'] ?? '';

        if (!$ref) {
            echo json_encode(['success' => false, 'message' => 'No reference found.']);
            exit;
        }

        $res = moolre_post('/open/transact/status', [
            'type' => 1,
            'idtype' => '1',
            'id' => $ref,
            'accountnumber' => MOOLRE_ACCOUNT_NO,
        ], 'X-API-PUBKEY');

        $tx_status = $res['data']['txstatus'] ?? -1;

        if (($res['status'] ?? 0) == 1 && $tx_status == 1) {
            echo json_encode([
                'success' => true,
                'status' => 'paid',
                'message' => 'Payment confirmed! Thank you.',
                'amount' => $res['data']['amount'] ?? '',
                'tx_id' => $res['data']['transactionid'] ?? '',
            ]);
        } elseif ($tx_status == 0) {
            echo json_encode(['success' => true, 'status' => 'pending', 'message' => 'Payment is pending. Please approve on your phone.']);
        } else {
            echo json_encode(['success' => true, 'status' => 'failed', 'message' => 'Payment failed or was declined.']);
        }
        exit;
    }

    if ($_POST['ajax_action'] === 'generate_link') {
        $amount = trim($_POST['amount'] ?? '');
        $ref = unique_ref();

        if (!$amount || !is_numeric($amount) || (float) $amount < 1) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid amount.']);
            exit;
        }

        $res = moolre_post('/embed/link', [
            'type' => 1,
            'amount' => $amount,
            'currency' => 'GHS',
            'email' => MOOLRE_BUSINESS_EMAIL,
            'externalref' => $ref,
            'reusable' => '0',
            'expiration_time' => 30,
            'accountnumber' => MOOLRE_ACCOUNT_NO,
            'callback' => SITE_URL . '/payment_callback.php',
            'redirect' => SITE_URL . '/payment_success.php',
        ], 'X-API-PUBKEY');

        if (($res['status'] ?? 0) == 1 && !empty($res['data']['authorization_url'])) {
            $_SESSION['moolre_ref'] = $ref;
            echo json_encode([
                'success' => true,
                'url' => $res['data']['authorization_url'],
                'ref' => $ref,
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $res['message'] ?? 'Failed to generate payment link.',
            ]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pay — SeekerAfric</title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg+xml" href="static/seekerafric.svg">
    <link rel="icon" type="image/x-icon" href="static/seekerafric.ico">
</head>

<body>

    <div class="pay-wrap">
        <div class="pay-box">
            <div class="pay-header">
                <div class="pay-brand">Seeker<span>Afric</span></div>
                <p>Secure Payment — Powered by Moolre</p>
            </div>
            <div class="pay-tabs">
                <!--    <button alig class="pay-tab active" onclick="switchTab('momo')">
                    Mobile Money
                </button>
                <button class="pay-tab" onclick="switchTab('card')">
                    Card / Bank
                </button>-->
            </div>

            <div class="pay-body">
                <div class="pay-panel active" id="panel-momo">
                    <div class="step active" id="momo-step-1">
                        <div class="amount-display">
                            <div class="amount-label">Amount to Pay</div>
                            <div class="amount-value"><span>GH₵</span> <span id="momo-amt-display">100.00</span></div>
                        </div>

                        <div class="form-group">
                            <label>Amount (GH₵) <span class="req">*</span></label>
                            <input type="number" id="momo-amount" placeholder="e.g. 50.00" min="50" step="50.00"
                                oninput="document.getElementById('momo-amt-display').textContent = parseFloat(this.value||0).toFixed(2)">
                        </div>

                        <div class="form-group">
                            <label>Select Network <span class="req">*</span></label>
                            <div class="network-grid">
                                <button type="button" class="network-btn selected" onclick="selectNetwork(this,'13')">
                                    <span class="net-icon"></span>
                                    <span class="net-name">MTN MoMo</span>
                                </button>
                                <button type="button" class="network-btn" onclick="selectNetwork(this,'6')">
                                    <span class="net-icon"></span>
                                    <span class="net-name">Telecel</span>
                                </button>
                                <button type="button" class="network-btn" onclick="selectNetwork(this,'7')">
                                    <span class="net-icon"></span>
                                    <span class="net-name">AirtelTigo</span>
                                </button>
                            </div>
                            <input type="hidden" id="selected-network" value="13">
                        </div>

                        <div class="form-group">
                            <label>Mobile Money Number <span class="req">*</span></label>
                            <input type="tel" id="momo-phone" placeholder="e.g. 0241234567" maxlength="13">
                        </div>

                        <div id="momo-error" class="alert alert-error" style="display:none;"></div>

                        <button class="btn btn-primary btn-block" onclick="initiateMomo()" id="momo-pay-btn">
                            Pay Now
                        </button>

                        <div style="margin-top:20px;text-align:center;">
                            <p style="font-size:0.8rem;color:var(--gray-4);margin-bottom:10px;">Or pay directly via USSD
                            </p>
                            <div class="ussd-box">
                                <div style="font-size:0.8rem;color:var(--gray-3);">Dial on any phone</div>
                                <div class="ussd-code" id="ussd-code">*170#</div>
                                <div class="ussd-sub">Select "Transfer Money" → "To Merchant" → Enter your amount</div>
                            </div>
                        </div>
                    </div>

                    <div class="step" id="momo-step-otp">
                        <div class="status-box">
                            <div class="status-icon" style="font-size:2.5rem;">📩</div>
                            <div class="status-title">OTP Required</div>
                            <div class="status-msg" id="otp-msg">An OTP has been sent to your phone. Enter it below.
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Enter OTP Code <span class="req">*</span></label>
                            <input type="number" id="otp-input" placeholder="e.g. 123456" maxlength="8">
                        </div>
                        <div id="otp-error" class="alert alert-error" style="display:none;"></div>
                        <button class="btn btn-primary btn-block" onclick="submitOtp()">Verify OTP →</button>
                        <span class="back-link" onclick="goStep('momo','1')">Back</span>
                    </div>

                    <div class="step" id="momo-step-poll">
                        <div class="status-box">
                            <div style="font-size:3rem;margin-bottom:12px;">📲</div>
                            <div class="status-title">Waiting for Approval</div>
                            <div class="status-msg">
                                A payment prompt has been sent to your phone.<br>
                                Please approve it to complete payment.
                            </div>
                            <div class="polling-dots">
                                <span></span><span></span><span></span>
                            </div>
                            <p style="font-size:0.78rem;color:var(--gray-4);margin-top:16px;" id="poll-counter"></p>
                        </div>
                        <button class="btn btn-ghost btn-block btn-sm" onclick="checkStatus(true)">Check Status
                            Manually</button>
                        <span class="back-link" onclick="cancelPoll()">Cancel</span>
                    </div>

                    <div class="step" id="momo-step-result">
                        <div class="status-box" id="momo-result-box">
                            <div class="status-icon"></div>
                            <div class="status-title" id="momo-result-title"></div>
                            <div class="status-msg" id="momo-result-msg"></div>
                        </div>
                        <a href="index.php" class="btn btn-dark btn-block">Back to SeekerAfric</a>
                    </div>

                </div>

                <div class="pay-panel" id="panel-card">

                    <div class="step active" id="card-step-1">
                        <div class="amount-display">
                            <div class="amount-label">Amount to Pay</div>
                            <div class="amount-value"><span>GH₵</span> <span id="card-amt-display">100.00</span></div>
                        </div>

                        <div class="form-group">
                            <label>Amount (GH₵) <span class="req">*</span></label>
                            <input type="number" id="card-amount" placeholder="e.g. 50.00" min="50" step="50.00"
                                oninput="document.getElementById('card-amt-display').textContent = parseFloat(this.value||0).toFixed(2)">
                        </div>

                        <div class="alert alert-info" style="font-size:0.84rem;">
                            💳 You will be redirected to a secure Moolre payment page where you can pay by card or bank
                            transfer.
                        </div>

                        <div id="card-error" class="alert alert-error" style="display:none;margin-top:12px;"></div>

                        <button class="btn btn-primary btn-block" onclick="generateLink()" id="card-pay-btn"
                            style="margin-top:16px;">
                            Proceed to Payment
                        </button>
                    </div>

                    <div class="step" id="card-step-redirect">
                        <div class="status-box">
                            <div style="font-size:3rem;margin-bottom:12px;">🔗</div>
                            <div class="status-title">Redirecting...</div>
                            <div class="status-msg">Opening secure payment page. If nothing happens, click the button
                                below.</div>
                        </div>
                        <a href="#" class="btn btn-primary btn-block" id="card-link-btn" target="_blank">
                            Open Payment Page →
                        </a>
                        <span class="back-link" onclick="goStep('card','1')">Back</span>
                    </div>
                </div>
            </div>

            <div class="pay-footer">
                Secured by <strong>Moolre</strong> · SSL Encrypted · Ghana 🇬🇭
            </div>

        </div>
    </div>

    <script>
        let selectedNetwork = '13';
        let momoRef = null;
        let pollInterval = null;
        let pollCount = 0;
        const MAX_POLLS = 24; // 2 minutes at 5s intervals

        function switchTab(tab) {
            document.querySelectorAll('.pay-tab').forEach((t, i) => {
                t.classList.toggle('active', (i === 0 && tab === 'momo') || (i === 1 && tab === 'card'));
            });
            document.getElementById('panel-momo').classList.toggle('active', tab === 'momo');
            document.getElementById('panel-card').classList.toggle('active', tab === 'card');
        }

        function goStep(panel, step) {
            const prefix = panel + '-step-';
            document.querySelectorAll(`[id^="${prefix}"]`).forEach(el => el.classList.remove('active'));
            const target = document.getElementById(prefix + step);
            if (target) target.classList.add('active');
        }

        function selectNetwork(btn, code) {
            document.querySelectorAll('.network-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedNetwork = code;
            document.getElementById('selected-network').value = code;
            // Update USSD code
            const codes = { '13': '*170#', '6': '*134#', '7': '*110#' };
            document.getElementById('ussd-code').textContent = codes[code] || '*170#';
        }

        async function apiPost(data) {
            const body = new URLSearchParams(data);
            const res = await fetch('payment.php', { method: 'POST', body });
            return res.json();
        }

        async function initiateMomo() {
            const phone = document.getElementById('momo-phone').value.trim();
            const amount = document.getElementById('momo-amount').value.trim();
            const errEl = document.getElementById('momo-error');
            const btn = document.getElementById('momo-pay-btn');

            errEl.style.display = 'none';

            if (!amount || parseFloat(amount) < 1) {
                errEl.textContent = 'Please enter an amount of at least GH₵ 1.';
                errEl.style.display = 'block';
                return;
            }
            if (!phone || phone.replace(/\D/, '').length < 9) {
                errEl.textContent = 'Please enter a valid mobile money number.';
                errEl.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Processing...';

            try {
                const res = await apiPost({
                    ajax_action: 'pay_momo',
                    phone, amount,
                    network: selectedNetwork,
                });

                if (res.success) {
                    momoRef = res.ref;
                    if (res.needs_otp) {
                        document.getElementById('otp-msg').textContent = res.message;
                        goStep('momo', 'otp');
                    } else {
                        goStep('momo', 'poll');
                        startPolling();
                    }
                } else {
                    errEl.textContent = res.message || 'Payment failed. Please try again.';
                    errEl.style.display = 'block';
                }
            } catch (e) {
                errEl.textContent = 'Connection error. Please check your internet and try again.';
                errEl.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Pay Now →';
            }
        }

        async function submitOtp() {
            const otp = document.getElementById('otp-input').value.trim();
            const phone = document.getElementById('momo-phone').value.trim();
            const errEl = document.getElementById('otp-error');

            errEl.style.display = 'none';

            if (!otp) {
                errEl.textContent = 'Please enter the OTP sent to your phone.';
                errEl.style.display = 'block';
                return;
            }

            const res = await apiPost({
                ajax_action: 'submit_otp',
                otp, phone,
                network: selectedNetwork,
            });

            if (res.success) {
                momoRef = res.ref;
                goStep('momo', 'poll');
                startPolling();
            } else {
                errEl.textContent = res.message || 'OTP verification failed.';
                errEl.style.display = 'block';
            }
        }

        function startPolling() {
            pollCount = 0;
            clearInterval(pollInterval);
            pollInterval = setInterval(() => checkStatus(false), 5000);
        }

        async function checkStatus(manual = false) {
            if (!momoRef) return;
            pollCount++;

            document.getElementById('poll-counter').textContent =
                'Checking... (' + pollCount + '/' + MAX_POLLS + ')';

            const res = await apiPost({ ajax_action: 'check_status', ref: momoRef });

            if (res.status === 'paid') {
                stopPolling();
                showMomoResult('success', 'Payment Successful!', res.message);
            } else if (res.status === 'failed') {
                stopPolling();
                showMomoResult('failed', 'Payment Failed', res.message);
            } else if (pollCount >= MAX_POLLS) {
                stopPolling();
                showMomoResult('pending', '⏳', 'Timed Out',
                    'We could not confirm your payment. If money was deducted, contact support with reference: ' + momoRef);
            }
        }

        function stopPolling() {
            clearInterval(pollInterval);
            pollInterval = null;
        }

        function cancelPoll() {
            stopPolling();
            goStep('momo', '1');
        }

        function showMomoResult(type, icon, title, msg) {
            goStep('momo', 'result');
            const box = document.getElementById('momo-result-box');
            box.className = 'status-box ' + type;
            document.querySelector('#momo-result-box .status-icon').textContent = icon;
            document.getElementById('momo-result-title').textContent = title;
            document.getElementById('momo-result-msg').textContent = msg;
        }

        async function generateLink() {
            const amount = document.getElementById('card-amount').value.trim();
            const errEl = document.getElementById('card-error');
            const btn = document.getElementById('card-pay-btn');

            errEl.style.display = 'none';

            if (!amount || parseFloat(amount) < 1) {
                errEl.textContent = 'Please enter an amount of at least GH₵ 1.';
                errEl.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Generating link...';

            try {
                const res = await apiPost({ ajax_action: 'generate_link', amount });

                if (res.success && res.url) {
                    document.getElementById('card-link-btn').href = res.url;
                    goStep('card', 'redirect');
                    window.open(res.url, '_blank');
                } else {
                    errEl.textContent = res.message || 'Failed to generate payment link.';
                    errEl.style.display = 'block';
                }
            } catch (e) {
                errEl.textContent = 'Connection error. Please try again.';
                errEl.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Proceed to Payment →';
            }
        }
    </script>
</body>

</html>