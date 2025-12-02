<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HD Accountancy - Grow More, Keep More. Save Tax, Save Time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <style>
        :root {
            --primary-navy: #1e3a5f;
            --accent-yellow: #F9D783;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #1a1a1a;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 0;
        }

        .navbar-nav {
            gap: 10px;
        }

        .nav-link {
            font-size: 20px;
            color: #1a1a1a;
            font-weight: 500;
            padding: 10px 15px;
        }

        .btn-phone {
            background: var(--accent-yellow);
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .btn-phone:hover {
            background: var(--accent-yellow) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Hero Section */
        .hero-section {
            text-align: center;
            padding: 80px 0 0px;
            background: white;
        }

        .hero-title {
            font-size: 53px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 18px;
            color: #000000;
            max-width: 650px;
            margin: 0 auto 40px;
            line-height: 1.6;
        }

        .hero-image {
            max-width: 900px;
            width: 100%;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            margin: 0px auto;
            display: block;
        }

        /* Partner Logos */
        .partner-section {
            text-align: center;
            padding: 10px 0;
            background: white;
        }

        .partner-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        /* Discount Section */
        .discount-section {
            background: var(--primary-navy);
            color: white;
            padding: 60px 0;
            text-align: center;
        }

        .discount-title {
            font-size: 34px;
            margin-bottom: 5px;
            font-weight: semibold;
            opacity: 0.95;
        }

        .discount-subtitle {
            color: #F9D783;
            font-size: 43px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .discount-description {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        /* Form */
        .contact-form {
            max-width: 900px;
            margin: 0 auto;
        }

        .form-label {
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
            text-align: left;
        }

        .form-control {
            background: white;
            border: none;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(255, 201, 60, 0.3);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn-submit {
            background: var(--accent-yellow);
            border: none;
            padding: 12px 60px;
            border-radius: 5px;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 1.1rem;
        }

        .btn-submit:hover {
            background: var(--accent-yellow);
            color: #1a1a1a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Stats Section */
        .stats-section {
            background: var(--light-bg);
            padding: 0;
            text-align: center;
        }

        .stats-section-1 {
            padding: 30px 0;
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .stats-title {
            font-size: 43px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .stats-description {
            font-size: 1.1rem;
            color: #000000;
            max-width: 800px;
            margin: 20px;
            line-height: 1.6;
        }

        .stat-box {
            padding: 20px;
        }

        .stat-number {
            font-family: Impact, Charcoal, sans-serif;
            font-size: 53px;
            color: var(--primary-navy);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            color: #000000;
        }

        /* Pricing Section */
        .pricing-section {
            padding: 80px 0;
            background: white;
        }



        .pricing-title {
            font-size: 43px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 15px;
        }

        .pricing-subtitle {
            font-size: 18px;
            color: #000000;
            text-align: center;
            margin-bottom: 50px;
        }

        .pricing-card {

            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 0;
            min-height: 700px;
            display: flex;
            flex-direction: column;
            background: white;
            transition: all 0.3s;
            overflow: hidden;
            text-align: left;
            height: 100%;
            flex: 1;
        }

        .discount-section img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 20px auto 0; /* optional: center & add spacing */
        }

        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .pricing-card.growth {
            border: 3px solid var(--primary-navy);
            background: #EFF3FF;
        }

        .pricing-card.featured {
            background: var(--primary-navy);
            color: white;
            border-color: var(--primary-navy);
        }

        /* Pricing Badge Styles - Rounded Pill Shape */
        .pricing-badge {
            max-width: 50%;
            background: #F5F5F5;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 700;
            text-align: center;
            margin: 20px;
            color: #000;
            border-radius: 5px;
            display: inline-block;
            width: auto;
        }

        .pricing-badge-growth {
            background: #C8D5F0;
            max-width: 50%;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 700;
            text-align: center;
            margin: 20px;
            color: var(--primary-navy);
            border-radius: 5px;
            display: inline-block;
            width: auto;
        }

        .pricing-badge-featured {
            background: white;
            max-width: 70%;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 700;
            text-align: center;
            margin: 20px;
            color: var(--primary-navy);
            border-radius: 5px;
            display: inline-block;
            width: auto;
        }

        .pricing-turnover {
            font-size: 0.9rem;
            padding: 0 25px 15px 25px;
            text-align: left;
        }

        .pricing-card.growth .pricing-turnover {
            color: #000;
        }

        /* Divider Lines */
        .pricing-divider {
            border: none;
            border-top: 1px solid #000000;
            margin: 15px 25px;
        }

        .pricing-divider-growth {
            border: none;
            border-top: 1px solid var(--primary-navy);
            margin: 15px 25px;
        }

        .pricing-divider-featured {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            margin: 15px 25px;
        }

        .pricing-from {
            font-size: 0.9rem;
            font-weight: 600;
            text-align: left;
            padding: 0 25px;
            margin-bottom: 10px;
        }

        .pricing-price {
            font-size: 3.5rem;
            font-weight: 700;
            text-align: left;
            padding: 0 25px;
            margin-bottom: 5px;
        }

        .pricing-period {
            font-size: 0.9rem;
            text-align: left;
            padding: 0 25px;
            margin-bottom: 15px;
        }

        .pricing-features {
            list-style: none;
            padding: 0 25px;
            margin-bottom: 25px;
            text-align: left;
            flex-grow: 1;
        }

        @media (max-width: 991.98px) {
            .col-lg-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        .pricing-features li {
            padding: 10px 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.95rem;
        }

        .pricing-features .iconify {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .pricing-card:not(.featured) .pricing-features .iconify {
            color: #000;
        }

        .pricing-card.featured .pricing-features .iconify {
            color: white;
        }

        .btn-pricing {
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: 600;
            border: 2px solid;
            width: calc(100% - 50px);
            margin: 0 25px 25px 25px;
            font-size: 1rem;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .btn-pricing-outline {
            background: var(--primary-navy);
            border-color: var(--primary-navy);
            color: white;
        }

        .btn-pricing-outline:hover {
            color: white;
            background: #152d4a;
            border-color: #152d4a;
        }

        .btn-pricing-outline-growth {
            background: var(--primary-navy);
            border-color: var(--primary-navy);
            color: white;
        }

        .btn-pricing-outline-growth:hover {
            color: white;
            background: #152d4a;
            border-color: #152d4a;
        }

        .btn-pricing-featured {
            background: var(--accent-yellow);
            border-color: var(--accent-yellow);
            color: #1a1a1a;
        }

        .btn-pricing-featured:hover {
            color: #1a1a1a;
            background: #f5cd6b;
            border-color: #f5cd6b;
        }

        /* Contact Section */
        .contact-section {
            background: white;
            padding: 60px 0 60px 0;
        }

        .contact-title {
            color: var(--primary-navy);
            font-size: 43px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 15px;
        }

        .contact-subtitle {
            font-size: 1.1rem;
            color: #000000;
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-info {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
        }

        .contact-icon {
            background: var(--primary-navy);
            color: white;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .contact-item h5 {
            font-weight: 700;
            margin-bottom: 8px;
            color: #233969;
        }

        .contact-item p {
            margin: 0;
            color: #233969;
            line-height: 1.6;
        }

        .office-image {
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            height: 100%;
            object-fit: cover;
        }

        /* Footer */
        footer {
            background: white;
            color: #1a1a1a;
            padding: 40px 0;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }

        footer p {
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #6D6A6A;
        }

        .btn-learn {
            background: var(--primary-navy);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            font-weight: 600;
        }

        .btn-learn:hover {
            background: var(--primary-navy);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .pricing-card.featured {
                transform: scale(1);
            }

            .pricing-card {
                min-height: auto;
            }

            .pricing-price {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light sticky-top">
        <div class="container">
            <div class="w-100">
                <ul class="navbar-nav flex-row justify-content-end align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('frontend.index') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="tel:01484409951" class="btn btn-phone">📞 01484409951</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <h1 class="hero-title">Grow More, Keep More<br>Save Tax, Save Time</h1>
            <p class="hero-subtitle">We handle your accounting and tax affairs so you can focus on what matters
                most—running your business. Our strategic approach ensures maximum savings and minimum stress.</p>

            <img src="{{ asset('banner.jpg') }}" alt="Professional Team" class="hero-image">
        </div>
    </section>

    <!-- Partner Logos -->
    <section class="partner-section">
        <div class="container">
            <div class="partner-logos">
                <img src="Xero and goolgle review.png" width="400" height="100">
            </div>
        </div>
    </section>

    <!-- Discount Section -->
    <section class="discount-section" id="discount">
        <div class="container">
            <h2 class="discount-title">Its About Time....</h2>
            <h3 class="discount-subtitle">to claim your 10% discount</h3>
            <p class="discount-description">Fill this form and we will get back to you straight away</p>
            <img src="{{ asset('offer.png') }}" alt="" width="436" height="64">

            <div class="contact-form mt-5">

                @if (session('success'))
                    <div class="alert alert-success mt-3 text-center">
                        {{ session('success') }}
                    </div>
                @endif
                <form id="contactForm" action="{{ route('offer.contact') }}" method="POST">
                    @csrf
                    <input type="hidden" name="submission_type" value="ltd_offer">
                    
                    <div class="row text-start">
                        <div class="col-md-6 mb-2">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" required value="{{ old('name') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mt-2">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" required value="{{ old('email') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mt-2">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" required value="{{ old('phone') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mt-2">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" class="form-control @error('company') is-invalid @enderror" name="company" value="{{ old('company') }}">
                                    @error('company')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Message </label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" rows="10" name="message">{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                <label class="form-label" id="captchaQuestion"></label>
                                <input type="number" class="form-control"
                                    id="captchaAnswer" style="width: 120px;" placeholder="Answer" required>
                                    <span id="captchaError" class="text-warning d-none">Incorrect</span>
                                    </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-submit mt-3" id="submitBtn">Submit</button>
                    
                    <div id="loadingText" class="alert alert-info d-none mt-3 text-center">
                        Sending your message...
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate simple math captcha
            let num1 = Math.floor(Math.random() * 10) + 1;
            let num2 = Math.floor(Math.random() * 10) + 1;
            let correctAnswer = num1 + num2;
            
            document.getElementById('captchaQuestion').textContent = `What is ${num1} + ${num2}? *`;
            
            // Form submission handler
            document.getElementById('contactForm').addEventListener('submit', function(e) {
                let userAnswer = parseInt(document.getElementById('captchaAnswer').value);
                
                if (userAnswer !== correctAnswer) {
                    e.preventDefault();
                    document.getElementById('captchaError').classList.remove('d-none');
                } else {
                    document.getElementById('captchaError').classList.add('d-none');
                    // Show loading and disable button
                    document.getElementById('loadingText').classList.remove('d-none');
                    document.getElementById('submitBtn').disabled = true;
                    document.getElementById('submitBtn').textContent = 'Sending...';
                }
            });
        });
    </script>

    <!-- Stats Section -->
    <section class="stats-section-1">
        <div class="container">
            <h2 class="stats-title">Your business needs more than an accountant</h2>
            <p class="stats-description">It deserves a partner you can trust to deliver accuracy, transparency, and
                financial excellence every step of the way</p>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-box">
                        <div class="stat-number">1230+</div>
                        <div class="stat-label">Client Serviced</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Client Satisfaction Rate</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <div class="stat-number">2100+</div>
                        <div class="stat-label">Tax Submission Yearly</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section">
        <div class="container">
            <h2 class="pricing-title">Best Value for money</h2>
            <p class="pricing-subtitle">Choose the plan that fits for your turnover</p>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <div class="pricing-badge">Startup Plan</div>
                        <div class="pricing-turnover">For Turnover up to £50000</div>

                        <hr class="pricing-divider">

                        <div class="pricing-from">Starting From</div>
                        <div class="pricing-price">£45</div>
                        <div class="pricing-period">Per Month + VAT</div>

                        <hr class="pricing-divider">

                        <ul class="pricing-features">
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Dedicated Accountant</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Year End Account</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Corporation Tax Return</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> 1 Director Self-Assessment
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> 1 Director/Employee Payroll
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Confirmation Statement</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Register Office Service
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> HMRC Tax Agent Service</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Annual Tax Planning</li>
                        </ul>

                        <a href="#discount" class="btn btn-pricing btn-pricing-outline">Start Now</a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="pricing-card growth">
                        <div class="pricing-badge-growth">Growth Plan</div>
                        <div class="pricing-turnover">For Turnover up to £51000 - £90000</div>

                        <hr class="pricing-divider-growth">

                        <div class="pricing-from">Starting From</div>
                        <div class="pricing-price">£65</div>
                        <div class="pricing-period">Per Month + VAT</div>

                        <hr class="pricing-divider-growth">

                        <ul class="pricing-features">
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Dedicated Accountant</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Year End Account</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Corporation Tax Return</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> 2 Director Self-Assessment
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> 2 Director/Employee Payroll
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Confirmation Statement</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Register Office Service
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> HMRC Tax Agent Service</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Annual Tax Planning</li>
                        </ul>

                        <a href="#discount" class="btn btn-pricing btn-pricing-outline-growth">Start Now</a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="pricing-card featured">
                        <div class="pricing-badge-featured">VAT Enterprise Plan</div>
                        <div class="pricing-turnover">For Turnover up to £91000 - £150000</div>

                        <hr class="pricing-divider-featured">

                        <div class="pricing-from">Starting From</div>
                        <div class="pricing-price">£99</div>
                        <div class="pricing-period">Per Month + VAT</div>

                        <hr class="pricing-divider-featured">

                        <ul class="pricing-features">
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Dedicated Accountant</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Quarterly VAT Submission
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Monthly Bookkeeping by
                                QuickBooks/Xero</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Year End Account</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Corporation Tax Return</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> 2 Director Self-Assessment
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> 2 Director/Employee Payroll
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Confirmation Statement</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Register Office Service
                            </li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> HMRC Tax Agent Service</li>
                            <li><span class="iconify" data-icon="mdi:check-circle"></span> Annual Tax Planning</li>
                        </ul>

                        <a href="#discount" class="btn btn-pricing btn-pricing-featured">Start Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" style="background: var(--light-bg);">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <h2 class="contact-title">Visit us anytime</h2>
                        <p class="contact-subtitle">If you want to visit us, you are always welcome to our office</p>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <span class="iconify" data-icon="mdi:phone"></span>
                            </div>
                            <div>
                                <h5>Phone</h5>
                                    <p><a href="tel:01484508951" style="text-decoration: none; color: inherit;">01484 508951 (Huddersfield)</a></p>
                                    <p><a href="tel:01612416746" style="text-decoration: none; color: inherit;">01612 416746 (Manchester)</a></p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <span class="iconify" data-icon="mdi:email"></span>
                            </div>
                            <div>
                                <h5>Email</h5>
                                    <p><a href="mailto:info@hd-accountancy.co.uk" style="text-decoration: none; color: inherit;">info@hd-accountancy.co.uk</a></p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <span class="iconify" data-icon="mdi:map-marker"></span>
                            </div>
                            <div>
                                <h5>Huddersfield Address</h5>
                                    <p>
                                        <a href="https://www.google.com/maps/place/Hd+Accountancy+Services+Ltd/@53.6486275,-1.7825057,17z/data=!3m1!4b1!4m6!3m5!1s0x487bdc05ae205df9:0x769caa7d7cdbcd0d!8m2!3d53.6486275!4d-1.7799308!16s%2Fg%2F11j8lm4sd5!17m2!4m1!1e3!18m1!1e1?entry=ttu&g_ep=EgoyMDI1MTEyMy4xIKXMDSoASAFQAw%3D%3D" 
                                        target="_blank" 
                                        style="text-decoration: none; color: inherit;">
                                            St. Peters Chambers, 2-4 Primitive Street<br>
                                            Huddersfield, HD1 1AG
                                        </a>
                                    </p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <span class="iconify" data-icon="mdi:map-marker"></span>
                            </div>
                            <div>
                                <h5>Manchester Address</h5>
                                <p>Suite no: 320, Peter House, Oxford Street,<br>Manchester, M1 5AN</p>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-lg-6">
                    <img src="{{ asset('Office pic.jpg') }}" alt="Office Building" class="office-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>2025 | HD Accountancy Services LTD | Registered in England and Wales | Company No-05600401 | VAT No-437899234</p>
            <a href="{{ route('frontend.index') }}" class="btn btn-learn">Learn more</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
