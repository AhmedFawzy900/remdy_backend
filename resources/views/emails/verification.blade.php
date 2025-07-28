<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $type === 'forget_password' ? 'Password Reset' : 'Email Verification' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .code {
            background-color: #fff;
            border: 2px solid #4CAF50;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            margin: 20px 0;
            letter-spacing: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $type === 'forget_password' ? 'Password Reset Request' : 'Email Verification' }}</h1>
    </div>
    
    <div class="content">
        <p>Hello {{ $name }},</p>
        
        @if($type === 'forget_password')
            <p>You have requested to reset your password. Please use the following verification code to complete the process:</p>
        @else
            <p>Thank you for registering! Please use the following verification code to complete your registration:</p>
        @endif
        
        <div class="code">
            {{ $code }}
        </div>
        
        <p>This code will expire in 24 hours.</p>
        
        @if($type === 'forget_password')
            <p>If you did not request a password reset, please ignore this email.</p>
        @else
            <p>If you did not create an account, please ignore this email.</p>
        @endif
        
        <p>Best regards,<br>Your App Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html> 