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
  .box  { background:#f4f6fb; border-radius:10px; padding:20px; margin:20px 0; }
  .box p { margin:6px 0; }
  .label { font-weight:700; color:#1A2B5F; }
  .warn  { background:#fff3cd; border-radius:8px; padding:14px; margin:20px 0; color:#856404; font-size:14px; }
  .btn   { display:inline-block; background:#1A2B5F; color:#fff; text-decoration:none;
            padding:12px 28px; border-radius:8px; font-weight:600; margin:6px 4px; font-size:14px; }
  .links { text-align:center; margin:24px 0; }
  .ftr   { text-align:center; padding:16px 32px 28px; color:#8A93B2; font-size:12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr"><h1>HD Accountancy</h1></div>
  <div class="bod">
    <p>Hi {{ $name }},</p>
    <p>Welcome to <strong>HD Accountancy</strong>! Here are your app login details:</p>

    <div class="box">
      <p><span class="label">Email:</span> {{ $email }}</p>
      <p><span class="label">Password:</span> {{ $password }}</p>
    </div>

    <div class="warn">
      <strong>Important:</strong> For your security, please change your password after your first login. You can do this from <strong>Account &gt; Change Password</strong> in the app.
    </div>

    <p>Download the app to get started:</p>
    <div class="links">
      <a href="https://play.google.com/store/apps/details?id=uk.mentosoftware.hdaccountancy" class="btn">Google Play</a>
      <a href="https://apps.apple.com/app/hd-accountancy/id6748746543" class="btn">App Store</a>
    </div>

    <p>If you have any questions, just reply to this email or contact your accountant directly.</p>
  </div>
  <div class="ftr">&copy; {{ date('Y') }} HD Accountancy. All rights reserved.</div>
</div>
</body>
</html>
