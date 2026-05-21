<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, sans-serif; background:#f4f6fb; margin:0; padding:0; }
  .wrap { max-width:480px; margin:40px auto; background:#fff; border-radius:12px; overflow:hidden; }
  .hdr  { background:#1A2B5F; padding:28px 32px; }
  .hdr h1 { color:#fff; margin:0; font-size:18px; letter-spacing:.5px; }
  .bod  { padding:32px; color:#11183C; font-size:15px; line-height:1.6; }
  .otp  { background:#f4f6fb; border-radius:10px; text-align:center;
          padding:24px; margin:24px 0; letter-spacing:14px;
          font-size:38px; font-weight:700; color:#1A2B5F; }
  .ftr  { text-align:center; padding:16px 32px 28px; color:#8A93B2; font-size:12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr"><h1>HD Accountancy</h1></div>
  <div class="bod">
    <p>Hi {{ $name }},</p>
    <p>You requested a password reset. Enter the code below in the app. It expires in <strong>15 minutes</strong>.</p>
    <div class="otp">{{ $otp }}</div>
    <p>If you did not request this, you can safely ignore this email. Your password will not change.</p>
  </div>
  <div class="ftr">&copy; {{ date('Y') }} HD Accountancy. All rights reserved.</div>
</div>
</body>
</html>