<!DOCTYPE html>
<html>
<head>
    <title>New Career Form Submission</title>
</head>
<body>
    <h1>Career Application Details</h1>
    <p><strong>Name:</strong> {{ $careerData['name'] }}</p>
    <p><strong>Email:</strong> {{ $careerData['email'] }}</p>
    <p><strong>Phone:</strong> {{ $careerData['phone'] }}</p>
    <p><strong>LinkedIn Profile:</strong> <a href="{{ $careerData['linkedin_profile'] }}">{{ $careerData['linkedin_profile'] }}</a></p>
    <p><strong>About Yourself:</strong> {{ $careerData['about_yourself'] }}</p>
    <p><strong>CV:</strong> <a href="{{ asset('images/Cv/' . $careerData['cv']) }}">View CV</a></p>
</body>
</html>