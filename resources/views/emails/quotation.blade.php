<!DOCTYPE html>
<html>
<head>
    <title>New Quotation Submitted</title>
</head>
<body>
    <h1>Quotation Details</h1>
    <p><strong>Name:</strong> {{ $quotation['name'] }}</p>
    <p><strong>Email:</strong> {{ $quotation['email'] }}</p>
    <p><strong>Company Name:</strong> {{ $quotation['company_name'] }}</p>
    <p><strong>Phone:</strong> {{ $quotation['phone'] }}</p>
    <p><strong>Business Type:</strong> {{ $quotation['business_type'] }}</p>
    <p><strong>Turnover:</strong> {{ $quotation['turnover'] }}</p>
    <p><strong>VAT Returns:</strong> {{ $quotation['vat_returns'] }}</p>
    <p><strong>Payroll:</strong> {{ $quotation['payroll'] }}</p>
    <p><strong>Bookkeeping:</strong> {{ $quotation['bookkeeping'] }}</p>
    <p><strong>Bookkeeping Software:</strong> {{ $quotation['bookkeeping_software'] }}</p>
    <p><strong>Management Account:</strong> {{ $quotation['management_account'] }}</p>
    <p><strong>Bank Accounts:</strong> {{ $quotation['bank_accounts'] }}</p>
</body>
</html>