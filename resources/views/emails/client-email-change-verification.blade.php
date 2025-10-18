<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Change Verification - EMOH Real Estate</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #000000;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.15);
            border: 2px solid #ff8c00;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #ff8c00;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .title {
            font-size: 26px;
            color: #000000;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .content {
            font-size: 16px;
            color: #000000;
            margin-bottom: 30px;
        }
        .email-change-info {
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border-left: 4px solid #ff8c00;
        }
        .email-change-info .label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .email-change-info .email-value {
            font-size: 16px;
            color: #000000;
            font-weight: 600;
            margin-bottom: 15px;
            word-break: break-all;
        }
        .email-change-info .old-email {
            text-decoration: line-through;
            color: #999;
        }
        .email-change-info .new-email {
            color: #ff8c00;
        }
        .arrow {
            text-align: center;
            font-size: 24px;
            color: #ff8c00;
            margin: 10px 0;
        }
        .otp-container {
            text-align: center;
            margin: 30px 0;
            padding: 30px;
            background: linear-gradient(135deg, #ff8c00 0%, #ff6600 100%);
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.3);
        }
        .otp-code {
            font-size: 42px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 10px;
            font-family: 'Courier New', monospace;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
            background-color: rgba(0, 0, 0, 0.1);
            padding: 15px 25px;
            border-radius: 10px;
            display: inline-block;
        }
        .otp-label {
            color: #ffffff;
            font-size: 16px;
            margin-top: 15px;
            font-weight: 500;
        }
        .warning {
            background-color: #fff3e0;
            border: 2px solid #ff8c00;
            color: #000000;
            padding: 18px;
            border-radius: 10px;
            margin: 25px 0;
            font-size: 14px;
            font-weight: 500;
        }
        .security-alert {
            background-color: #ffebee;
            border: 2px solid #f44336;
            color: #000000;
            padding: 18px;
            border-radius: 10px;
            margin: 25px 0;
            font-size: 14px;
            font-weight: 500;
        }
        .info-box {
            background-color: #f5f5f5;
            border: 2px solid #000000;
            color: #000000;
            padding: 18px;
            border-radius: 10px;
            margin: 25px 0;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #ff8c00;
            font-size: 14px;
            color: #000000;
            text-align: center;
        }
        .steps {
            background-color: #fff8f0;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border-left: 4px solid #ff8c00;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 10px;
            color: #000000;
            font-weight: 500;
        }
        .steps strong {
            color: #ff8c00;
            font-weight: 600;
        }
        .warning strong {
            color: #ff8c00;
        }
        .security-alert strong {
            color: #f44336;
        }
        .info-box strong {
            color: #ff8c00;
        }
        ul li {
            margin-bottom: 8px;
            color: #000000;
        }
        p {
            color: #000000;
        }
        .highlight {
            background-color: #fff3e0;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            color: #ff8c00;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔐 EMOH Real Estate</div>
            <h1 class="title">Email Change Verification</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $client->name }}</strong>,</p>
            
            <p>We received a request to change the email address associated with your EMOH Real Estate account.</p>
            
            <div class="email-change-info">
                <div class="label">📧 Current Email Address:</div>
                <div class="email-value old-email">{{ $oldEmail }}</div>
                
                <div class="arrow">⬇️</div>
                
                <div class="label">✨ New Email Address:</div>
                <div class="email-value new-email">{{ $newEmail }}</div>
            </div>
            
            <p>To confirm this change and verify your new email address, please use the following <span class="highlight">6-digit verification code</span>:</p>
            
            <div class="otp-container">
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-label">Your Email Change Verification Code</div>
            </div>
            
            <div class="warning">
                <strong>⏰ Time Sensitive:</strong> This verification code will expire in <strong>10 minutes</strong> for security reasons. Please complete the verification process as soon as possible.
            </div>
            
            <div class="steps">
                <strong>📋 How to complete your email change:</strong>
                <ol>
                    <li>Return to the EMOH Real Estate app or website</li>
                    <li>Enter the 6-digit code shown above in the verification field</li>
                    <li>Click <strong>"Verify & Update Email"</strong> to complete the process</li>
                    <li>Your new email address will be immediately active</li>
                </ol>
            </div>
            
            <div class="info-box">
                <strong>🔒 Security Information:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>You have <strong>5 attempts</strong> to enter the correct code</li>
                    <li>After 5 failed attempts, you'll need to request a new verification code</li>
                    <li>Your account remains secure with your current email until verification is complete</li>
                    <li>Once verified, your new email will be used for all future communications and login</li>
                </ul>
            </div>
            
            <div class="security-alert">
                <strong>⚠️ Important Security Notice:</strong> If you did <strong>NOT</strong> request this email change, please take immediate action:
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>Do not share</strong> this verification code with anyone</li>
                    <li><strong>Contact our support team immediately</strong> to secure your account</li>
                    <li><strong>Change your password</strong> as a precautionary measure</li>
                    <li>This may indicate unauthorized access to your account</li>
                </ul>
            </div>
            
            <p><strong>What happens after verification?</strong></p>
            <ul>
                <li>✅ Your email address will be updated to: <strong style="color: #ff8c00;">{{ $newEmail }}</strong></li>
                <li>✅ Your new email will be automatically verified</li>
                <li>✅ You'll use your new email for future logins</li>
                <li>✅ All future notifications will be sent to your new email</li>
                <li>✅ Your account data and preferences will remain unchanged</li>
            </ul>
            
            <p>Thank you for keeping your account information up to date with EMOH Real Estate!</p>
        </div>

        <div class="footer">
            <p><strong>Best regards,</strong><br>The EMOH Real Estate Team</p>
            <p style="margin-top: 20px; font-size: 13px; color: #666;">
                This is an automated security message. Please do not reply to this email.<br>
                If you need assistance, please contact our support team.<br>
                <strong>Request Time:</strong> {{ now()->format('M d, Y - h:i A') }}
            </p>
        </div>
    </div>
</body>
</html>
