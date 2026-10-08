<?php
session_start();

/* =====================================================
   DATABASE CONNECTION
===================================================== */

$server   = "localhost";
$username = "root";
$password = "";
$database = "studentregistration";

$conn = mysqli_connect($server, $username, $password, $database);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
)
");


/* =====================================================
   LOGIN DETAILS
===================================================== */

$valid_username = "admin";
$valid_password = "admin123";

$login_error = "";
$signup_error = "";


/* =====================================================
   LOGOUT
===================================================== */

if (isset($_GET['logout'])) {

    session_unset();
    session_destroy();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


/* =====================================================
   LOGIN PROCESS
===================================================== */

if (isset($_POST['login'])) {

    $login_username = trim($_POST['username']);
    $login_password = trim($_POST['password']);

    if (
        $login_username === $valid_username &&
        $login_password === $valid_password
    ) {

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = "Administrator";

        /*
         * First login shows welcome page
         */
        $_SESSION['show_welcome'] = true;

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;

    } else {

        $login_username = mysqli_real_escape_string(
            $conn,
            $login_username
        );

        $user_query = mysqli_query(
            $conn,
            "SELECT * FROM users
             WHERE username = '$login_username'"
        );

        if (
            $user_query &&
            mysqli_num_rows($user_query) > 0
        ) {

            $user_data = mysqli_fetch_assoc($user_query);

            if (
                password_verify(
                    $login_password,
                    $user_data['password_hash']
                )
            ) {

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_name'] = $user_data['full_name'];
                $_SESSION['show_welcome'] = true;

                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        }

        $login_error = "Invalid username or password!";
    }
}


if (isset($_POST['signup'])) {

    $signup_name = trim($_POST['signup_name']);
    $signup_email = trim($_POST['signup_email']);
    $signup_username = trim($_POST['signup_username']);
    $signup_password = trim($_POST['signup_password']);
    $signup_confirm = trim($_POST['signup_confirm']);

    if (
        $signup_name === "" ||
        $signup_email === "" ||
        $signup_username === "" ||
        $signup_password === ""
    ) {

        $signup_error = "Please fill in all sign-in fields.";

    } elseif ($signup_password !== $signup_confirm) {

        $signup_error = "Passwords do not match.";

    } else {

        $signup_name = mysqli_real_escape_string($conn, $signup_name);
        $signup_email = mysqli_real_escape_string($conn, $signup_email);
        $signup_username = mysqli_real_escape_string($conn, $signup_username);

        $existing_user = mysqli_query(
            $conn,
            "SELECT * FROM users
             WHERE username = '$signup_username'"
        );

        if ($existing_user && mysqli_num_rows($existing_user) > 0) {

            $signup_error = "This username is already taken.";

        } else {

            $password_hash = password_hash($signup_password, PASSWORD_DEFAULT);

            $signup_query = mysqli_query(
                $conn,
                "INSERT INTO users
                (full_name, email, username, password_hash)
                VALUES
                ('$signup_name', '$signup_email', '$signup_username', '$password_hash')"
            );

            if ($signup_query) {

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_name'] = $signup_name;
                $_SESSION['show_welcome'] = true;

                header("Location: " . $_SERVER['PHP_SELF']);
                exit;

            } else {

                $signup_error = "Unable to create account. Please try again.";
            }
        }
    }
}


/* =====================================================
   CREATE TABLES
===================================================== */

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    gender VARCHAR(20),
    mobile VARCHAR(15),
    email VARCHAR(100),
    address TEXT
)
");


mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_duration VARCHAR(50),
    course_fee DECIMAL(10,2)
)
");


mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(100),
    course_id INT,
    start_date DATE,
    end_date DATE,
    start_time TIME,
    end_time TIME
)
");


mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS payment_modes (
    payment_mode_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_mode VARCHAR(50)
)
");


mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS payment_terms (
    payment_term_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_term VARCHAR(50)
)
");


mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    batch_id INT,
    payment_mode_id INT,
    payment_term_id INT,
    paid_amount DECIMAL(10,2),
    balance_amount DECIMAL(10,2),
    transaction_date DATE
)
");


mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS faculty (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_name VARCHAR(100) NOT NULL,
    faculty_role VARCHAR(100),
    faculty_specialty VARCHAR(100),
    faculty_experience VARCHAR(50),
    faculty_bio TEXT
)
");


/* =====================================================
   DEFAULT COURSES
===================================================== */

$check_course = mysqli_query(
    $conn,
    "SELECT * FROM courses"
);

if (mysqli_num_rows($check_course) == 0) {

    mysqli_query($conn, "
    INSERT INTO courses
    (course_name, course_duration, course_fee)
    VALUES
    ('Python Full Stack', '6 Months', 30000),
    ('Java Full Stack', '6 Months', 35000),
    ('Web Development', '4 Months', 20000),
    ('Data Science', '6 Months', 40000)
    ");
}


$check_faculty = mysqli_query(
    $conn,
    "SELECT * FROM faculty"
);

if (mysqli_num_rows($check_faculty) == 0) {

    mysqli_query($conn, "
    INSERT INTO faculty
    (faculty_name, faculty_role, faculty_specialty, faculty_experience, faculty_bio)
    VALUES
    ('Emma Johnson', 'Head of Digital Learning', 'Python, Web Development', '8 Years', 'Emma Johnson leads the digital learning team and helps students grow in coding, web development, and applied problem-solving with a modern, student-centered approach.'),
    ('Sophia Martinez', 'UI/UX Mentor', 'UI/UX Design, Front-End', '6 Years', 'Sophia Martinez specializes in responsive design, interface thinking, and creative user experience strategies that help students build visually strong and practical web products.'),
    ('James Carter', 'Software Engineering Mentor', 'Java Full Stack', '7 Years', 'James Carter helps students master Java full stack development and enterprise coding practices through project-based learning and hands-on technical guidance.'),
    ('Daniel Brooks', 'Data Science Instructor', 'Data Analytics & Machine Learning', '9 Years', 'Daniel Brooks guides students through data analysis, ML workflows, visualization, and real-world analytics challenges with a focus on career readiness.' )
    ");
} else {

    $faculty_updates = array(
        1 => array(
            'Emma Johnson',
            'Head of Digital Learning',
            'Python, Web Development',
            '8 Years',
            'Emma Johnson leads the digital learning team and helps students grow in coding, web development, and applied problem-solving with a modern, student-centered approach.'
        ),
        2 => array(
            'Sophia Martinez',
            'UI/UX Mentor',
            'UI/UX Design, Front-End',
            '6 Years',
            'Sophia Martinez specializes in responsive design, interface thinking, and creative user experience strategies that help students build visually strong and practical web products.'
        ),
        3 => array(
            'James Carter',
            'Software Engineering Mentor',
            'Java Full Stack',
            '7 Years',
            'James Carter helps students master Java full stack development and enterprise coding practices through project-based learning and hands-on technical guidance.'
        ),
        4 => array(
            'Daniel Brooks',
            'Data Science Instructor',
            'Data Analytics & Machine Learning',
            '9 Years',
            'Daniel Brooks guides students through data analysis, ML workflows, visualization, and real-world analytics challenges with a focus on career readiness.'
        )
    );

    foreach ($faculty_updates as $faculty_id => $faculty_data) {

        mysqli_query(
            $conn,
            "UPDATE faculty
             SET faculty_name = '" . mysqli_real_escape_string($conn, $faculty_data[0]) . "',
                 faculty_role = '" . mysqli_real_escape_string($conn, $faculty_data[1]) . "',
                 faculty_specialty = '" . mysqli_real_escape_string($conn, $faculty_data[2]) . "',
                 faculty_experience = '" . mysqli_real_escape_string($conn, $faculty_data[3]) . "',
                 faculty_bio = '" . mysqli_real_escape_string($conn, $faculty_data[4]) . "'
             WHERE faculty_id = " . intval($faculty_id)
        );

    }

}


/* =====================================================
   DEFAULT BATCHES
===================================================== */

$check_batch = mysqli_query(
    $conn,
    "SELECT * FROM batches"
);

if (mysqli_num_rows($check_batch) == 0) {

    mysqli_query($conn, "
    INSERT INTO batches
    (
        batch_name,
        course_id,
        start_date,
        end_date,
        start_time,
        end_time
    )
    VALUES
    ('Morning Batch', 1, '2026-09-01', '2027-03-01', '09:00:00', '11:00:00'),
    ('Afternoon Batch', 2, '2026-09-01', '2027-03-01', '14:00:00', '16:00:00'),
    ('Evening Batch', 3, '2026-09-01', '2027-01-01', '17:00:00', '19:00:00'),
    ('Weekend Batch', 4, '2026-09-01', '2027-03-01', '10:00:00', '13:00:00')
    ");
}


/* =====================================================
   DEFAULT PAYMENT MODES
===================================================== */

$check_payment_mode = mysqli_query(
    $conn,
    "SELECT * FROM payment_modes"
);

if (mysqli_num_rows($check_payment_mode) == 0) {

    mysqli_query($conn, "
    INSERT INTO payment_modes (payment_mode)
    VALUES
    ('Cash'),
    ('UPI'),
    ('Cheque'),
    ('Credit Card'),
    ('Debit Card')
    ");
}


/* =====================================================
   DEFAULT PAYMENT TERMS
===================================================== */

$check_payment_term = mysqli_query(
    $conn,
    "SELECT * FROM payment_terms"
);

if (mysqli_num_rows($check_payment_term) == 0) {

    mysqli_query($conn, "
    INSERT INTO payment_terms (payment_term)
    VALUES
    ('1st Installment'),
    ('2nd Installment'),
    ('3rd Installment'),
    ('Full Payment')
    ");
}


/* =====================================================
   IF NOT LOGGED IN
   SHOW LOGIN PAGE
===================================================== */

if (!isset($_SESSION['admin_logged_in'])) {
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>EduSphere - Admin Login</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #2b0a1a,
                    #4a102a,
                    #7b1e3a
                );

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
            animation: pageGlow 10s ease-in-out infinite alternate;
        }

        @keyframes pageGlow {
            0% {
                filter: saturate(1);
            }
            100% {
                filter: saturate(1.15);
            }
        }


        /* Background circles */

        .circle {
            position: fixed;
            border-radius: 50%;
            filter: blur(2px);
            opacity: .25;
        }

        .circle-one {
            width: 350px;
            height: 350px;
            background: rgba(255, 196, 205, 0.38);
            top: -120px;
            left: -100px;
            animation: floatOrb 14s ease-in-out infinite alternate;
        }

        .circle-two {
            width: 400px;
            height: 400px;
            background: rgba(228, 120, 157, 0.32);
            bottom: -180px;
            right: -120px;
            animation: floatOrb 18s ease-in-out infinite alternate-reverse;
        }

        @keyframes floatOrb {
            0% {
                transform: translateY(0px) scale(1);
            }
            100% {
                transform: translateY(24px) scale(1.08);
            }
        }


        /* Login wrapper */

        .login-wrapper {
            width: 100%;
            max-width: 1150px;
            min-height: calc(100vh - 40px);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        /* Login card */

        .login-card {
            width: 100%;
            min-height: calc(100vh - 40px);
            background: rgba(255,255,255,.96);
            border-radius: 28px;

            overflow: hidden;

            box-shadow:
                0 30px 80px rgba(0,0,0,.30);
        }


        /* Left */

        .login-left {
            min-height: calc(100vh - 40px);
            height: 100%;

            padding: 50px;

            color: white;

            background:
                linear-gradient(
                    145deg,
                    #2d081d,
                    #5d1238,
                    #8d2a4a,
                    #a33557
                );

            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.08), transparent 55%);
            pointer-events: none;
        }

        .logo {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1;
            letter-spacing: -0.5px;
            display: inline-block;
        }

        .logo span {
            color: #f7d3dd;
        }

        .login-left h1 {
            font-size: 42px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 18px;
        }

        .login-left p {
            color: #dbeafe;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 24px;
            max-width: 420px;
        }

        .feature {
            margin-top: 10px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 15px;

            color: #eff6ff;
            font-size: 13px;
        }

        .feature-icon {
            width: 35px;
            height: 35px;

            border-radius: 10px;

            background: rgba(255,255,255,.15);

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mini-metrics {
            margin-top: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,.18);
        }

        .metric-box {
            flex: 1;
            min-width: 110px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
            padding: 12px 10px;
            text-align: center;
        }

        .metric-box strong {
            display: block;
            font-size: 20px;
            font-weight: 700;
            color: white;
        }

        .metric-box span {
            font-size: 11px;
            color: #dbeafe;
            letter-spacing: .2px;
        }


        /* Right */

        .login-right {
            min-height: calc(100vh - 40px);

            padding: 55px 45px;

            display: flex;
            align-items: center;
        }

        .login-form {
            width: 100%;
        }

        .login-form h2 {
            font-size: 30px;
            font-weight: 700;
            color: #4d132d;
        }

        .login-form .subtitle {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 20px;
        }

        .auth-toggle {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            padding: 6px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fbe9ef, #f4e6ec);
            border: 1px solid #f0d8e2;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
        }

        .auth-option {
            flex: 1;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            background: transparent;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            transition: .2s ease;
            cursor: pointer;
        }

        .auth-option.active {
            background: linear-gradient(135deg, #3a0d24, #6e163f, #a33557);
            color: white;
            box-shadow: 0 10px 22px rgba(123,30,58,.28);
        }


        /* Form */

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .form-control {
            height: 50px;

            border-radius: 10px;

            border: 1px solid #cbd5e1;

            font-size: 14px;
        }

        .form-control:focus {
            border-color: #a33557;

            box-shadow:
                0 0 0 4px rgba(163,53,87,.10);
        }


        /* Login button */

        .login-btn {
            width: 100%;

            border: none;

            height: 52px;

            border-radius: 11px;

            background:
                linear-gradient(
                    90deg,
                    #7b1e3a,
                    #a33557
                );

            color: white;

            font-weight: 600;

            transition: .25s;
        }

        .login-btn:hover {
            transform: translateY(-3px) scale(1.02);

            box-shadow:
                0 12px 28px rgba(123,30,58,.34);
        }


        .demo-box {
            margin-top: 25px;

            padding: 15px;

            background: #fff0f4;

            border: 1px solid #f6d5df;

            border-radius: 10px;

            font-size: 12px;

            color: #7b1e3a;
        }


        .error-box {
            background: #fee2e2;
            color: #991b1b;

            padding: 12px 15px;

            border-radius: 10px;

            font-size: 13px;

            margin-bottom: 20px;
        }


        @media(max-width: 768px) {

            .login-left {
                display: none;
            }

            .login-right {
                min-height: auto;
                padding: 40px 25px;
            }

            .login-card {
                border-radius: 20px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }

    </style>

</head>


<body>

    <div class="circle circle-one"></div>
    <div class="circle circle-two"></div>


    <div class="login-wrapper">

        <div class="login-card">

            <div class="row g-0">


                <!-- LEFT SIDE -->

                <div class="col-lg-6">

                    <div class="login-left">

                        <div class="logo">
                            Edu<span>Sphere</span>
                        </div>

                        <p>
                            EduSphere is a modern institute
                            management platform that helps
                            organizations manage students,
                            courses, and academic operations
                            with ease.
                        </p>

                        <div class="feature">

                            <div class="feature-item">
                                <div class="feature-icon">✅</div>
                                Student Management
                            </div>

                            <div class="feature-item">
                                <div class="feature-icon">📚</div>
                                Course & Batch Tracking
                            </div>

                            <div class="feature-item">
                                <div class="feature-icon">💳</div>
                                Payment & Finance Overview
                            </div>

                        </div>

                        <div class="mini-metrics">
                            <div class="metric-box">
                                <strong>500+</strong>
                                <span>Students</span>
                            </div>

                            <div class="metric-box">
                                <strong>24/7</strong>
                                <span>Access</span>
                            </div>

                            <div class="metric-box">
                                <strong>100%</strong>
                                <span>Secure</span>
                            </div>
                        </div>

                    </div>

                </div>


                <!-- RIGHT SIDE -->

                <div class="col-lg-6">

                    <div class="login-right">

                        <div class="login-form">

                            <h2 id="auth-title">
                                Admin Login
                            </h2>

                            <p class="subtitle" id="auth-subtitle">
                                Sign in to access EduSphere
                            </p>

                            <div class="auth-toggle">
                                <button type="button" class="auth-option" data-mode="signin" aria-pressed="false">
                                    Sign In
                                </button>
                                <button type="button" class="auth-option active" data-mode="login" aria-pressed="true">
                                    Login
                                </button>
                            </div>

                            <?php

                            if ($login_error != "") {

                                echo "
                                <div class='error-box'>
                                    ⚠️ " .
                                    htmlspecialchars($login_error)
                                    . "
                                </div>
                                ";
                            }

                            if ($signup_error != "") {

                                echo "
                                <div class='error-box'>
                                    ⚠️ " .
                                    htmlspecialchars($signup_error)
                                    . "
                                </div>
                                ";
                            }

                            ?>

                            <div id="signin-panel" style="display:none;">
                                <form method="POST" autocomplete="on">

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Full Name
                                        </label>
                                        <input
                                            type="text"
                                            name="signup_name"
                                            class="form-control"
                                            placeholder="Enter your full name"
                                            autocomplete="name"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Email
                                        </label>
                                        <input
                                            type="email"
                                            name="signup_email"
                                            class="form-control"
                                            placeholder="Enter your email"
                                            autocomplete="email"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Username
                                        </label>
                                        <input
                                            type="text"
                                            name="signup_username"
                                            class="form-control"
                                            placeholder="Choose a username"
                                            autocomplete="username"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Password
                                        </label>
                                        <input
                                            type="password"
                                            name="signup_password"
                                            class="form-control"
                                            placeholder="Create a password"
                                            autocomplete="new-password"
                                            required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">
                                            Confirm Password
                                        </label>
                                        <input
                                            type="password"
                                            name="signup_confirm"
                                            class="form-control"
                                            placeholder="Confirm your password"
                                            autocomplete="new-password"
                                            required>
                                    </div>

                                    <button
                                        type="submit"
                                        name="signup"
                                        class="login-btn">

                                        ✨ Sign In to Dashboard

                                    </button>

                                </form>
                            </div>

                            <div id="login-form-panel">
                                <form method="POST" autocomplete="off">


                                    <div class="mb-3">

                                        <label class="form-label">
                                            Username
                                        </label>

                                        <input
                                            type="text"
                                            name="username"
                                            class="form-control"
                                            placeholder="Enter username"
                                            autocomplete="off"
                                            required>

                                    </div>


                                    <div class="mb-4">

                                        <label class="form-label">
                                            Password
                                        </label>

                                        <input
                                            type="password"
                                            name="password"
                                            class="form-control"
                                            placeholder="Enter password"
                                            autocomplete="off"
                                            required>

                                    </div>


                                    <button
                                        type="submit"
                                        name="login"
                                        id="auth-submit-btn"
                                        class="login-btn">

                                        🔐 Login to Dashboard

                                    </button>

                                </form>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const authOptions = document.querySelectorAll('.auth-option');
            const authTitle = document.getElementById('auth-title');
            const authSubtitle = document.getElementById('auth-subtitle');
            const authSubmitBtn = document.getElementById('auth-submit-btn');
            const loginFormPanel = document.getElementById('login-form-panel');
            const signinPanel = document.getElementById('signin-panel');

            const setAuthMode = function (mode) {
                authOptions.forEach(function (option) {
                    const isActive = option.dataset.mode === mode;
                    option.classList.toggle('active', isActive);
                    option.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                if (mode === 'signin') {
                    authTitle.textContent = 'Sign In';
                    authSubtitle.textContent = 'Create or access your EduSphere account';
                    authSubmitBtn.style.display = 'none';
                    loginFormPanel.style.display = 'none';
                    signinPanel.style.display = 'block';
                } else {
                    authTitle.textContent = 'Admin Login';
                    authSubtitle.textContent = 'Sign in to access EduSphere';
                    authSubmitBtn.style.display = 'block';
                    loginFormPanel.style.display = 'block';
                    signinPanel.style.display = 'none';
                }
            };

            authOptions.forEach(function (option) {
                option.addEventListener('click', function () {
                    setAuthMode(this.dataset.mode);
                });
            });

            setAuthMode('login');
        });
    </script>

</body>

</html>

<?php
exit;
}


/* =====================================================
   WELCOME PAGE
===================================================== */

if (
    isset($_SESSION['show_welcome']) &&
    $_SESSION['show_welcome'] === true
) {

    $_SESSION['show_welcome'] = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Welcome - EduSphere</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">


    <style>

        * {
            box-sizing: border-box;
        }

        body {

            min-height: 100vh;

            margin: 0;

            font-family: 'Poppins', sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #2b0a1a,
                    #5b1230,
                    #7f1d3a,
                    #a33557
                );

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;
        }


        .welcome-card {

            width: 90%;

            max-width: 850px;

            padding: 65px 45px;

            text-align: center;

            border-radius: 30px;

            background:
                rgba(255,255,255,.10);

            border:
                1px solid rgba(255,255,255,.20);

            backdrop-filter: blur(15px);

            box-shadow:
                0 30px 80px rgba(0,0,0,.25);

            animation:
                welcomeIn .8s ease;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: "";
            position: absolute;
            inset: -30% auto auto -10%;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            filter: blur(10px);
        }


        @keyframes welcomeIn {

            from {
                opacity: 0;
                transform: translateY(30px) scale(.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

        }


        .welcome-icon {

            width: 85px;

            height: 85px;

            margin: auto;

            border-radius: 50%;

            background:
                rgba(255,255,255,.15);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 42px;

            margin-bottom: 25px;
        }


        .welcome-card h1 {

            font-size: 45px;

            font-weight: 700;

            margin-bottom: 12px;
        }


        .welcome-card h1 span {

            color: #f7d3dd;
        }


        .welcome-card p {

            color: #dbeafe;

            font-size: 15px;

            line-height: 1.8;

            max-width: 650px;

            margin:
                0 auto 30px;
        }


        .continue-btn {

            display: inline-block;

            padding: 13px 30px;

            border-radius: 11px;

            background: linear-gradient(90deg, #fff0f4, #ffffff);

            color: #7b1e3a;

            text-decoration: none;

            font-weight: 600;

            transition: .25s;
            box-shadow: 0 10px 25px rgba(0,0,0,.12);
        }


        .continue-btn:hover {

            transform: translateY(-3px);

            box-shadow:
                0 15px 30px rgba(0,0,0,.2);

            color: #7b1e3a;
        }


        .welcome-small {

            margin-top: 25px;

            color: #bfdbfe;

            font-size: 12px;
        }


        @media(max-width:600px) {

            .welcome-card {
                padding: 45px 25px;
            }

            .welcome-card h1 {
                font-size: 32px;
            }

        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }

    </style>

</head>


<body>


    <div class="welcome-card">

        <div class="welcome-icon">
            🎓
        </div>


        <h1>
            Welcome,
            <span>
                <?php
                echo htmlspecialchars(
                    $_SESSION['admin_name']
                );
                ?>
            </span>
        </h1>


        <p>

            You have successfully logged in to
            <b>EduSphere Institute Management System.</b>

            <br>

            Manage students, courses, batches,
            admissions and payments from one
            centralized dashboard.

        </p>


        <a
            href="<?php echo $_SERVER['PHP_SELF']; ?>?dashboard=true"
            class="continue-btn">

            🚀 Go to Dashboard

        </a>


        <div class="welcome-small">

            Secure Admin Session • EduSphere

        </div>

    </div>


</body>

</html>

<?php
exit;
}


/* =====================================================
   DASHBOARD VARIABLES
===================================================== */

$message = "";
$message_type = "";


/* =====================================================
   DELETE STUDENT
===================================================== */

if (isset($_GET['delete_student'])) {

    $delete_id = intval($_GET['delete_student']);

    mysqli_query(
        $conn,
        "DELETE FROM transactions
         WHERE student_id = $delete_id"
    );

    if (
        mysqli_query(
            $conn,
            "DELETE FROM students
             WHERE student_id = $delete_id"
        )
    ) {

        $message = "Student deleted successfully!";
        $message_type = "success";

    } else {

        $message = "Unable to delete student.";
        $message_type = "error";
    }
}


/* =====================================================
   UPDATE STUDENT
===================================================== */

if (isset($_POST['update_student'])) {

    $student_id = intval($_POST['student_id']);

    $student_name = mysqli_real_escape_string(
        $conn,
        $_POST['student_name']
    );

    $gender = mysqli_real_escape_string(
        $conn,
        $_POST['gender']
    );

    $mobile = mysqli_real_escape_string(
        $conn,
        $_POST['mobile']
    );

    $email = mysqli_real_escape_string(
        $conn,
        $_POST['email']
    );

    $address = mysqli_real_escape_string(
        $conn,
        $_POST['address']
    );


    $update_query = "
    UPDATE students SET

        student_name = '$student_name',
        gender = '$gender',
        mobile = '$mobile',
        email = '$email',
        address = '$address'

    WHERE student_id = $student_id
    ";


    if (mysqli_query($conn, $update_query)) {

        $message =
            "Student details updated successfully!";

        $message_type = "success";

    } else {

        $message =
            "Update Error: " .
            mysqli_error($conn);

        $message_type = "error";
    }
}


/* =====================================================
   STUDENT REGISTRATION
===================================================== */

if (isset($_POST['submit'])) {

    $student_name = mysqli_real_escape_string(
        $conn,
        $_POST['student_name']
    );

    $gender = mysqli_real_escape_string(
        $conn,
        $_POST['gender']
    );

    $mobile = mysqli_real_escape_string(
        $conn,
        $_POST['mobile']
    );

    $email = mysqli_real_escape_string(
        $conn,
        $_POST['email']
    );

    $address = mysqli_real_escape_string(
        $conn,
        $_POST['address']
    );

    $batch_id =
        intval($_POST['batch_id']);

    $payment_mode_id =
        intval($_POST['payment_mode_id']);

    $payment_term_id =
        intval($_POST['payment_term_id']);

    $paid_amount =
        floatval($_POST['paid_amount']);

    $balance_amount =
        floatval($_POST['balance_amount']);

    $transaction_date =
        $_POST['transaction_date'];


    $student_query = "
    INSERT INTO students
    (
        student_name,
        gender,
        mobile,
        email,
        address
    )

    VALUES
    (
        '$student_name',
        '$gender',
        '$mobile',
        '$email',
        '$address'
    )
    ";


    if (mysqli_query($conn, $student_query)) {

        $student_id =
            mysqli_insert_id($conn);


        $transaction_query = "
        INSERT INTO transactions
        (
            student_id,
            batch_id,
            payment_mode_id,
            payment_term_id,
            paid_amount,
            balance_amount,
            transaction_date
        )

        VALUES
        (
            '$student_id',
            '$batch_id',
            '$payment_mode_id',
            '$payment_term_id',
            '$paid_amount',
            '$balance_amount',
            '$transaction_date'
        )
        ";


        if (
            mysqli_query(
                $conn,
                $transaction_query
            )
        ) {

            $message =
                "Student Registered Successfully!";

            $message_type =
                "success";

        } else {

            $message =
                "Transaction Error: " .
                mysqli_error($conn);

            $message_type =
                "error";
        }

    } else {

        $message =
            "Student Registration Error: " .
            mysqli_error($conn);

        $message_type =
            "error";
    }
}


/* =====================================================
   DASHBOARD COUNTS
===================================================== */

$total_students_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM students"
);

$total_students =
    mysqli_fetch_assoc(
        $total_students_query
    )['total'];


$total_courses_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM courses"
);

$total_courses =
    mysqli_fetch_assoc(
        $total_courses_query
    )['total'];


$total_batches_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM batches"
);

$total_batches =
    mysqli_fetch_assoc(
        $total_batches_query
    )['total'];


$total_payment_query = mysqli_query(
    $conn,
    "SELECT SUM(paid_amount) AS total
     FROM transactions"
);

$total_payment_data =
    mysqli_fetch_assoc(
        $total_payment_query
    );

$total_payments =
    isset($total_payment_data['total'])
    ? $total_payment_data['total']
    : 0;


$total_balance_query = mysqli_query(
    $conn,
    "SELECT SUM(balance_amount) AS total
     FROM transactions"
);

$total_balance_data =
    mysqli_fetch_assoc(
        $total_balance_query
    );

$total_balance =
    isset($total_balance_data['total'])
    ? $total_balance_data['total']
    : 0;


$total_pending_students_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM (
         SELECT students.student_id
         FROM students
         LEFT JOIN transactions
         ON transactions.student_id = students.student_id
         GROUP BY students.student_id
         HAVING COALESCE(SUM(transactions.balance_amount), 0) > 0
     ) AS pending_students"
);

$total_pending_students_data = mysqli_fetch_assoc(
    $total_pending_students_query
);

$total_pending_students =
    isset($total_pending_students_data['total'])
    ? $total_pending_students_data['total']
    : 0;


$selected_course_id =
    isset($_GET['course_id'])
    ? intval($_GET['course_id'])
    : 0;


$selected_faculty_id =
    isset($_GET['faculty_id'])
    ? intval($_GET['faculty_id'])
    : 0;


$course_images = array(
    1 => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80',
    2 => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80',
    3 => 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=900&q=80',
    4 => 'https://images.unsplash.com/photo-1555949963-aa79dcee981c?auto=format&fit=crop&w=900&q=80'
);


$course_descriptions = array(
    1 => 'Build real-world web applications with Python, databases, APIs, front-end integration, and deployment workflows designed for modern careers.',
    2 => 'Learn full-stack Java development with enterprise coding practices, REST APIs, database handling, and job-ready project development.',
    3 => 'Create responsive, user-friendly websites using modern web technologies, layout design, and interactive front-end development techniques.',
    4 => 'Explore data analysis, machine learning concepts, visualization, and problem-solving strategies for decision-making and analytics roles.'
);


$selected_course = null;

if ($selected_course_id > 0) {

    $selected_course_result = mysqli_query(
        $conn,
        "SELECT *
         FROM courses
         WHERE course_id = $selected_course_id"
    );

    if (
        $selected_course_result &&
        mysqli_num_rows($selected_course_result) > 0
    ) {

        $selected_course =
            mysqli_fetch_assoc(
                $selected_course_result
            );
    }
}


$faculty_images = array(
    1 => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80',
    2 => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=900&q=80',
    3 => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=900&q=80',
    4 => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=900&q=80'
);


$faculty_descriptions = array(
    1 => 'Emma Johnson leads the digital learning team and helps students grow in coding, web development, and applied problem-solving with a modern, student-centered approach.',
    2 => 'Sophia Martinez specializes in responsive design, interface thinking, and creative user experience strategies that help students build visually strong and practical web products.',
    3 => 'James Carter helps students master Java full stack development and enterprise coding practices through project-based learning and hands-on technical guidance.',
    4 => 'Daniel Brooks guides students through data analysis, ML workflows, visualization, and real-world analytics challenges with a focus on career readiness.'
);


$selected_faculty = null;

if ($selected_faculty_id > 0) {

    $selected_faculty_result = mysqli_query(
        $conn,
        "SELECT *
         FROM faculty
         WHERE faculty_id = $selected_faculty_id"
    );

    if (
        $selected_faculty_result &&
        mysqli_num_rows($selected_faculty_result) > 0
    ) {

        $selected_faculty =
            mysqli_fetch_assoc(
                $selected_faculty_result
            );
    }
}


/* =====================================================
   EDIT STUDENT DATA
===================================================== */

$editing_student = null;

if (isset($_GET['edit_student'])) {

    $edit_student_id = intval($_GET['edit_student']);

    $edit_student_result = mysqli_query(
        $conn,
        "SELECT *
         FROM students
         WHERE student_id = $edit_student_id"
    );

    if (
        $edit_student_result &&
        mysqli_num_rows($edit_student_result) > 0
    ) {

        $editing_student =
            mysqli_fetch_assoc(
                $edit_student_result
            );
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>
    EduSphere - Institute Management System
</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">


<!-- Fonts -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet">


<style>

/* =====================================================
   GLOBAL
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    font-family: 'Poppins', sans-serif;

    background:
        linear-gradient(
            135deg,
            #fff3f6,
            #f8eef2
        );

    color: #1e293b;
    animation: dashboardBgFade 12s ease-in-out infinite alternate;
}

@keyframes dashboardBgFade {
    0% {
        background-position: 0% 50%;
    }
    100% {
        background-position: 100% 50%;
    }
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    width: 270px;

    height: 100vh;

    position: fixed;

    left: 0;

    top: 0;

    background:
        linear-gradient(
            180deg,
            #2d0b1a 0%,
            #4d132d 50%,
            #7b1e3a 100%
        );

    color: white;

    padding: 28px 18px;

    box-shadow:
        5px 0 25px rgba(0,0,0,.15);

    z-index: 1000;
}


.brand {

    font-size: 28px;

    font-weight: 700;
}

.brand span {
    color: #f7d3dd;
}


.tagline {

    font-size: 12px;

    color: #cbd5e1;

    line-height: 1.7;

    margin-top: 7px;

    margin-bottom: 30px;
}


.nav-title {

    font-size: 10px;

    letter-spacing: 2px;

    color: #f7d3dd;

    margin:
        20px 12px 10px;
}


.nav a {

    display: block;

    color: #dbeafe;

    text-decoration: none;

    padding: 13px 14px;

    margin: 6px 0;

    border-radius: 11px;

    font-size: 14px;

    transition: transform .25s ease, background .25s ease, box-shadow .25s ease, margin-left .25s ease;
    transform: translateX(0);
}


.nav a:hover,
.nav a.active {

    background:
        linear-gradient(90deg, rgba(255,255,255,.16), rgba(255,255,255,.08));

    color: white;

    transform:
        translateX(6px) scale(1.01);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
}


.institute-card {

    position: absolute;

    bottom: 25px;

    left: 18px;

    right: 18px;

    background:
        rgba(255,255,255,.10);

    border:
        1px solid rgba(255,255,255,.1);

    padding: 15px;

    border-radius: 14px;

    font-size: 11px;

    line-height: 1.7;

    color: #dbeafe;
}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 270px;

    padding: 30px;

    min-height: 100vh;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}


.topbar h1 {

    font-size: 28px;

    font-weight: 700;

    color: #0f172a;
}


.topbar p {

    font-size: 13px;

    color: #64748b;

    margin-top: 5px;
}


.admin {

    background: white;

    padding: 11px 17px;

    border-radius: 12px;

    box-shadow:
        0 5px 20px
        rgba(15,23,42,.07);

    font-size: 13px;
}


.admin a {

    color: #7b1e3a;

    text-decoration: none;
}


/* =====================================================
   PAGE SWITCHING
===================================================== */

.page-section {

    display: none;

    opacity: 0;
}


.page-section.active-section {
    display: block;
    animation: sectionReveal .55s cubic-bezier(.2, .8, .2, 1) forwards;
}


@keyframes sectionReveal {

    0% {

        opacity: 0;
        transform: translateY(26px) scale(.97);
        filter: blur(4px);
    }

    60% {

        opacity: 1;
        transform: translateY(-4px) scale(1.005);
        filter: blur(0);
    }

    100% {

        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}


@keyframes floatCard {

    0% {
        opacity: 0;
        transform: translateY(22px) scale(.98);
    }

    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}


@keyframes glowPulse {

    0% {
        box-shadow: 0 0 0 rgba(123, 30, 58, 0);
    }

    50% {
        box-shadow: 0 0 32px rgba(123, 30, 58, 0.18), 0 0 18px rgba(227, 134, 167, 0.12);
    }

    100% {
        box-shadow: 0 0 0 rgba(123, 30, 58, 0);
    }
}


@keyframes shimmer {

    0% {
        background-position: -200% 0;
    }

    100% {
        background-position: 200% 0;
    }
}


/* =====================================================
   DASHBOARD HERO
===================================================== */

.dashboard-hero {

    background:
        linear-gradient(
            135deg,
            #4d132d,
            #7b1e3a,
            #a33557
        );

    color: white;

    border-radius: 20px;

    padding: 30px;

    margin-bottom: 25px;

    box-shadow:
        0 12px 30px
        rgba(123,30,58,.25);
    position: relative;
    overflow: hidden;
}

.dashboard-hero::before {
    content: "";
    position: absolute;
    inset: -30% auto auto -10%;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    filter: blur(12px);
    animation: shimmer 8s linear infinite;
}


.dashboard-hero h2 {

    font-size: 25px;

    margin-bottom: 8px;
}


.dashboard-hero p {

    color: #dbeafe;

    font-size: 13px;

    margin-bottom: 0;
}


/* =====================================================
   STAT CARDS
===================================================== */

.stat {

    background: white;

    padding: 22px;

    border-radius: 17px;

    box-shadow:
        0 8px 25px
        rgba(30,58,138,.08);

    transition: .25s;
    animation: floatCard .7s ease both;
}


.stat:hover {

    transform:
        translateY(-6px) rotateX(4deg) rotateY(-3deg);

    box-shadow:
        0 14px 30px
        rgba(30,58,138,.13);

    animation: glowPulse 1.4s ease-in-out infinite;
}


.stat-icon {

    width: 45px;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: #fce8ef;

    font-size: 21px;

    margin-bottom: 13px;
}


.stat b {

    display: block;

    font-size: 26px;

    color: #4d132d;

    margin-bottom: 3px;
}


.stat span {

    color: #64748b;

    font-size: 12px;
}


/* =====================================================
   CARDS
===================================================== */

.dashboard-card,
.info-card,
.table-container,
.form-container {

    background: white;

    border-radius: 17px;

    box-shadow:
        0 8px 25px
        rgba(30,58,138,.07);

    animation: floatCard .8s ease both;
    transition: transform .25s ease, box-shadow .25s ease;
}


.dashboard-card {
    padding: 24px;
}

.dashboard-card:hover,
.info-card:hover,
.table-container:hover,
.form-container:hover {
    transform: translateY(-6px) rotateX(2deg) rotateY(-2deg);
    box-shadow: 0 18px 36px rgba(123,30,58,.10);
}


.info-card {

    padding: 22px;

    transition: .25s;
}


.info-card:hover {

    transform:
        translateY(-6px) rotateX(3deg) rotateY(-2deg);

    box-shadow:
        0 16px 32px
        rgba(123,30,58,.14);
}


.info-card h3 {

    color: #7b1e3a;

    font-size: 17px;

    margin-bottom: 10px;
}


.info-card p {

    color: #64748b;

    font-size: 13px;

    line-height: 1.7;
}


/* =====================================================
   COURSE DETAIL
===================================================== */

.course-detail-panel {
    background: linear-gradient(135deg, #fff8fb, #ffffff);
    border: 1px solid #f0d5df;
    border-radius: 22px;
    box-shadow: 0 18px 36px rgba(123,30,58,.10);
    padding: 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.course-detail-panel::before {
    content: "";
    position: absolute;
    inset: 0 auto auto -10%;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: rgba(123,30,58,.05);
    filter: blur(10px);
}

.course-detail-panel .course-image {
    width: 100%;
    height: 100%;
    min-height: 260px;
    object-fit: cover;
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(123,30,58,.14);
}

.course-detail-content {
    position: relative;
    z-index: 1;
}

.course-detail-content h3 {
    color: #4d132d;
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 12px;
}

.course-detail-content p {
    color: #475569;
    line-height: 1.8;
    margin-bottom: 18px;
}

.course-meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}

.course-meta-item {
    background: #fff0f4;
    color: #7b1e3a;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
}

.course-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.course-tag {
    display: inline-block;
    padding: 7px 10px;
    border-radius: 999px;
    background: #fce8ef;
    color: #7b1e3a;
    font-size: 12px;
    font-weight: 600;
}

.course-card-link {
    color: inherit;
    text-decoration: none;
    height: 100%;
    display: block;
    transform: translateY(0) scale(1);
    transition: transform 0.45s cubic-bezier(.22,1,.36,1),
                box-shadow 0.45s cubic-bezier(.22,1,.36,1),
                border-color 0.45s ease,
                filter 0.45s ease;
    will-change: transform, box-shadow;
}

.course-card-link:hover {
    color: inherit;
    text-decoration: none;
    transform: translateY(-8px) scale(1.01);
    filter: saturate(1.08);
}

.course-card-link.selected {
    border: 1px solid #a33557;
    box-shadow: 0 18px 34px rgba(123,30,58,.14);
}

.course-card-link .info-card {
    height: 100%;
    transition: transform 0.45s cubic-bezier(.22,1,.36,1),
                box-shadow 0.45s cubic-bezier(.22,1,.36,1),
                border-color 0.45s ease;
}

.course-card-link:hover .info-card {
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 18px 36px rgba(123,30,58,.10);
}


/* =====================================================
   SECTION
===================================================== */

.section {

    border:
        1px solid #e2e8f0;

    padding: 22px;

    border-radius: 14px;

    margin-bottom: 20px;

    background: #fcfdff;
}


.section h2 {

    font-size: 16px;

    color: #7b1e3a;

    padding-bottom: 12px;

    margin-bottom: 18px;

    border-bottom:
        1px solid #e2e8f0;
}


/* =====================================================
   FORM
===================================================== */

.input-box {

    display: flex;

    flex-direction: column;
}


.input-box label {

    margin-bottom: 7px;

    font-size: 13px;

    font-weight: 600;

    color: #475569;
}


input,
select,
textarea {

    padding: 12px 13px;

    border:
        1px solid #cbd5e1;

    border-radius: 9px;

    font-family: 'Inter', sans-serif;

    font-size: 14px;

    outline: none;

    transition: .2s;
}


input:focus,
select:focus,
textarea:focus {

    border-color: #a33557;

    box-shadow:
        0 0 0 3px
        rgba(163,53,87,.12);
}


textarea {

    height: 90px;

    resize: vertical;
}


/* =====================================================
   BUTTONS
===================================================== */

.btn-main {

    border: none;

    border-radius: 10px;

    padding: 12px 20px;

    background:
        linear-gradient(
            90deg,
            #7b1e3a,
            #a33557
        );

    color: white;

    font-weight: 600;

    transition: .25s;
}


.btn-main:hover {

    transform:
        translateY(-3px) scale(1.02);

    box-shadow:
        0 10px 24px
        rgba(123,30,58,.30);

    color: white;
}


.btn-edit {

    background: #fce8ef;

    color: #7b1e3a;

    border: none;

    padding: 7px 12px;

    border-radius: 7px;

    font-size: 12px;

    text-decoration: none;
}


.btn-delete {

    background: #fef2f2;

    color: #dc2626;

    border: none;

    padding: 7px 12px;

    border-radius: 7px;

    font-size: 12px;

    text-decoration: none;
}


/* =====================================================
   MESSAGE
===================================================== */

.message {

    padding: 14px 17px;

    border-radius: 11px;

    margin-bottom: 20px;

    font-size: 13px;

    font-weight: 600;
}


.success {

    background: #dcfce7;

    color: #166534;
}


.error {

    background: #fee2e2;

    color: #991b1b;
}


/* =====================================================
   TABLE
===================================================== */

.table-container {

    padding: 24px;

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;
}


table th {

    background: #4d132d;

    color: white;

    padding: 13px;

    text-align: left;

    font-size: 12px;

    white-space: nowrap;
}


table td {

    padding: 13px;

    border-bottom:
        1px solid #e2e8f0;

    font-size: 13px;

    color: #475569;
}


table tr:hover {

    background: #f8fafc;
}


.student-name {

    font-weight: 600;

    color: #4d132d;
}


/* =====================================================
   SEARCH
===================================================== */

.search-box {

    max-width: 350px;

    margin-bottom: 18px;
}


.search-box input {

    width: 100%;
}


/* =====================================================
   BADGE
===================================================== */

.badge-soft {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    background: #fce8ef;

    color: #7b1e3a;

    font-size: 11px;

    font-weight: 600;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1000px) {

    .sidebar {
        width: 220px;
    }

    .main {
        margin-left: 220px;
    }
}


@media(max-width:700px) {

    .sidebar {
        display: none;
    }

    .main {

        margin-left: 0;

        padding: 18px;
    }

    .topbar {

        align-items:
            flex-start;
    }

    .admin {
        display: none;
    }

    .dashboard-hero {
        padding: 22px;
    }

    .dashboard-hero h2 {
        font-size: 21px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
    }
}

</style>

</head>


<body>


<div class="app">


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="brand">

        Edu<span>Sphere</span>

    </div>


    <div class="tagline">

        A modern institute management system
        for admissions, courses, faculty
        and student success.

    </div>


    <div class="nav-title">

        MAIN MENU

    </div>


    <nav class="nav">


        <a
            href="#"
            class="menu-link active"
            data-section="dashboard"
            onclick="showSection('dashboard', this, event)">

            📊 &nbsp; Dashboard

        </a>


        <a
            href="#"
            class="menu-link"
            data-section="registration"
            onclick="showSection('registration', this, event)">

            ✦ &nbsp; Student Registration

        </a>


        <a
            href="#"
            class="menu-link"
            data-section="students"
            onclick="showSection('students', this, event)">

            👨‍🎓 &nbsp; Students

        </a>


        <a
            href="#"
            class="menu-link"
            data-section="courses-page"
            onclick="showSection('courses-page', this, event)">

            📚 &nbsp; Courses & Batches

        </a>


        <a
            href="#"
            class="menu-link"
            data-section="faculty"
            onclick="showSection('faculty', this, event)">

            👨‍🏫 &nbsp; Faculty

        </a>


        <a
            href="#"
            class="menu-link"
            data-section="payments"
            onclick="showSection('payments', this, event)">

            💳 &nbsp; Payments

        </a>


        <a
            href="#"
            class="menu-link"
            data-section="reports"
            onclick="showSection('reports', this, event)">

            📈 &nbsp; Reports

        </a>


    </nav>


    <div class="institute-card">

        <b>🏫 About Institute</b>

        <br><br>

        Quality education, expert faculty,
        practical learning and career-focused
        programs under one platform.

    </div>


</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


<!-- =====================================================
     DASHBOARD
===================================================== -->

<section
    id="dashboard"
    class="page-section active-section">


    <div class="topbar">


        <div>

            <h1>
                Institute Dashboard
            </h1>

            <p>
                Manage students, admissions
                and payments efficiently.
            </p>

        </div>


        <div class="admin">

            👤

            <?php
            echo htmlspecialchars(
                $_SESSION['admin_name']
            );
            ?>

            &nbsp; | &nbsp;

            <a href="?logout=true">

                Logout

            </a>

        </div>


    </div>


    <div class="dashboard-hero">

        <h2>
            Welcome to EduSphere 🎓
        </h2>

        <p>

            Your centralized institute
            management dashboard.
            Manage admissions, students,
            courses and payments from one place.

        </p>

    </div>


    <!-- STATISTICS -->

    <div class="row g-3 mb-4">


        <div class="col-sm-6 col-xl-3">

            <div class="stat">

                <div class="stat-icon">
                    👨‍🎓
                </div>

                <b>
                    <?php
                    echo $total_students;
                    ?>
                </b>

                <span>
                    Total Students
                </span>

            </div>

        </div>


        <div class="col-sm-6 col-xl-3">

            <div class="stat">

                <div class="stat-icon">
                    📚
                </div>

                <b>
                    <?php
                    echo $total_courses;
                    ?>
                </b>

                <span>
                    Available Courses
                </span>

            </div>

        </div>


        <div class="col-sm-6 col-xl-3">

            <div class="stat">

                <div class="stat-icon">
                    🗓️
                </div>

                <b>
                    <?php
                    echo $total_batches;
                    ?>
                </b>

                <span>
                    Active Batches
                </span>

            </div>

        </div>


        <div class="col-sm-6 col-xl-3">

            <div class="stat">

                <div class="stat-icon">
                    💰
                </div>

                <b>
                    ₹<?php
                    echo number_format(
                        $total_payments,
                        2
                    );
                    ?>
                </b>

                <span>
                    Total Payments
                </span>

            </div>

        </div>

    </div>


    <div class="row g-4">


        <div class="col-lg-8">

            <div class="dashboard-card">

                <h4>
                    Institute Overview
                </h4>

                <p class="text-secondary small mb-4">

                    Quickly manage your
                    institute's major activities.

                </p>


                <div class="row g-3">


                    <div class="col-md-4">

                        <a href="#students" class="info-card text-decoration-none d-block" onclick="showSection('students', document.querySelector('[data-section=\'students\']')); return false;">

                            <h3>
                                🎓 Students
                            </h3>

                            <p>

                                Register and manage
                                complete student
                                admission information.

                            </p>

                        </a>

                    </div>


                    <div class="col-md-4">

                        <a href="#courses-page" class="info-card text-decoration-none d-block" onclick="showSection('courses-page', document.querySelector('[data-section=\'courses-page\']')); return false;">

                            <h3>
                                📚 Courses
                            </h3>

                            <p>

                                Manage courses,
                                duration, fees and
                                batch schedules.

                            </p>

                        </a>

                    </div>


                    <div class="col-md-4">

                        <a href="#payments" class="info-card text-decoration-none d-block" onclick="showSection('payments', document.querySelector('[data-section=\'payments\']')); return false;">

                            <h3>
                                💳 Payments
                            </h3>

                            <p>

                                Track paid amounts,
                                installments and
                                balances.

                            </p>

                        </a>

                    </div>


                </div>

            </div>

        </div>


        <div class="col-lg-4">

            <div class="dashboard-card">

                <h4 class="mb-3">
                    Financial Summary
                </h4>


                <div
                    class="p-3 rounded-3 mb-3"
                    style="background:#ecfdf5;">

                    <small class="text-secondary">
                        Collected
                    </small>

                    <h4 class="text-success mt-1">

                        ₹<?php
                        echo number_format(
                            $total_payments,
                            2
                        );
                        ?>

                    </h4>

                </div>


                <div
                    class="p-3 rounded-3"
                    style="background:#fff7ed;">

                    <small class="text-secondary">
                        Pending
                    </small>

                    <h4 class="text-warning mt-1">

                        ₹<?php
                        echo number_format(
                            $total_balance,
                            2
                        );
                        ?>

                    </h4>

                </div>

            </div>

        </div>

    </div>


</section>


<!-- =====================================================
     REGISTRATION
===================================================== -->

<section
    id="registration"
    class="page-section">


    <div class="topbar">

        <div>

            <h1>
                Student Registration
            </h1>

            <p>
                Register a new student and
                admission details.
            </p>

        </div>

    </div>


    <?php

    if ($message != "") {

        echo "
        <div class='message $message_type'>
            " .
            htmlspecialchars($message)
            . "
        </div>
        ";
    }

    ?>


    <div class="form-container p-4">


        <form method="POST">


            <!-- STUDENT DETAILS -->

            <div class="section">

                <h2>
                    👤 Student Details
                </h2>


                <div class="row g-3">


                    <div class="input-box col-md-6">

                        <label>
                            Student Name
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            name="student_name"
                            placeholder="Enter Student Name"
                            required>

                    </div>


                    <div class="input-box col-md-6">

                        <label>
                            Gender
                        </label>

                        <select
                            class="form-select"
                            name="gender"
                            required>

                            <option value="">
                                Select Gender
                            </option>

                            <option value="Male">
                                Male
                            </option>

                            <option value="Female">
                                Female
                            </option>

                            <option value="Other">
                                Other
                            </option>

                        </select>

                    </div>


                    <div class="input-box col-md-6">

                        <label>
                            Mobile Number
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            name="mobile"
                            placeholder="Enter Mobile Number"
                            required>

                    </div>


                    <div class="input-box col-md-6">

                        <label>
                            Email
                        </label>

                        <input
                            class="form-control"
                            type="email"
                            name="email"
                            placeholder="Enter Email">

                    </div>


                    <div class="input-box col-12">

                        <label>
                            Address
                        </label>

                        <textarea
                            class="form-control"
                            name="address"
                            placeholder="Enter Address"></textarea>

                    </div>


                </div>

            </div>


            <!-- COURSE -->

            <div class="section">

                <h2>
                    📚 Course & Batch Details
                </h2>


                <div class="row g-3">


                    <div class="input-box col-md-6">

                        <label>
                            Select Course
                        </label>

                        <select
                            class="form-select"
                            name="course_id"
                            required>

                            <option value="">
                                Select Course
                            </option>


                            <?php

                            $course_dropdown =
                                mysqli_query(
                                    $conn,
                                    "SELECT *
                                     FROM courses"
                                );


                            while (
                                $row =
                                mysqli_fetch_assoc(
                                    $course_dropdown
                                )
                            ) {

                            ?>

                            <option
                                value="<?php
                                echo $row['course_id'];
                                ?>">

                                <?php
                                echo htmlspecialchars(
                                    $row['course_name']
                                );
                                ?>

                            </option>

                            <?php } ?>

                        </select>

                    </div>


                    <div class="input-box col-md-6">

                        <label>
                            Select Batch
                        </label>

                        <select
                            class="form-select"
                            name="batch_id"
                            required>

                            <option value="">
                                Select Batch
                            </option>


                            <?php

                            $batch_dropdown =
                                mysqli_query(
                                    $conn,
                                    "SELECT *
                                     FROM batches"
                                );


                            while (
                                $row =
                                mysqli_fetch_assoc(
                                    $batch_dropdown
                                )
                            ) {

                            ?>

                            <option
                                value="<?php
                                echo $row['batch_id'];
                                ?>">

                                <?php
                                echo htmlspecialchars(
                                    $row['batch_name']
                                );
                                ?>

                            </option>

                            <?php } ?>

                        </select>

                    </div>


                </div>

            </div>


            <!-- PAYMENT -->

            <div class="section">

                <h2>
                    💳 Payment Details
                </h2>


                <div class="row g-3">


                    <div class="input-box col-md-6">

                        <label>
                            Payment Mode
                        </label>

                        <select
                            class="form-select"
                            name="payment_mode_id"
                            required>

                            <option value="">
                                Select Payment Mode
                            </option>


                            <?php

                            $mode_dropdown =
                                mysqli_query(
                                    $conn,
                                    "SELECT *
                                     FROM payment_modes"
                                );


                            while (
                                $row =
                                mysqli_fetch_assoc(
                                    $mode_dropdown
                                )
                            ) {

                            ?>

                            <option
                                value="<?php
                                echo $row[
                                    'payment_mode_id'
                                ];
                                ?>">

                                <?php
                                echo htmlspecialchars(
                                    $row['payment_mode']
                                );
                                ?>

                            </option>

                            <?php } ?>

                        </select>

                    </div>


                    <div class="input-box col-md-6">

                        <label>
                            Payment Term
                        </label>

                        <select
                            class="form-select"
                            name="payment_term_id"
                            required>

                            <option value="">
                                Select Payment Term
                            </option>


                            <?php

                            $term_dropdown =
                                mysqli_query(
                                    $conn,
                                    "SELECT *
                                     FROM payment_terms"
                                );


                            while (
                                $row =
                                mysqli_fetch_assoc(
                                    $term_dropdown
                                )
                            ) {

                            ?>

                            <option
                                value="<?php
                                echo $row[
                                    'payment_term_id'
                                ];
                                ?>">

                                <?php
                                echo htmlspecialchars(
                                    $row['payment_term']
                                );
                                ?>

                            </option>

                            <?php } ?>

                        </select>

                    </div>


                    <div class="input-box col-md-6">

                        <label>
                            Paid Amount
                        </label>

                        <input
                            class="form-control"
                            type="number"
                            name="paid_amount"
                            min="0"
                            placeholder="Enter Paid Amount"
                            required>

                    </div>


                    <div class="input-box col-md-6">

                        <label>
                            Balance Amount
                        </label>

                        <input
                            class="form-control"
                            type="number"
                            name="balance_amount"
                            min="0"
                            placeholder="Enter Balance Amount"
                            required>

                    </div>


                    <div class="input-box col-md-6">

                        <label>
                            Transaction Date
                        </label>

                        <input
                            class="form-control"
                            type="date"
                            name="transaction_date"
                            value="<?php
                            echo date('Y-m-d');
                            ?>"
                            required>

                    </div>


                </div>

            </div>


            <button
                class="btn-main w-100"
                type="submit"
                name="submit">

                ✨ Register Student

            </button>


        </form>

    </div>


</section>


<!-- =====================================================
     STUDENTS
===================================================== -->

<section
    id="students"
    class="page-section">


    <div class="topbar">

        <div>

            <h1>
                Students
            </h1>

            <p>
                View, edit and delete registered
                students.
            </p>

        </div>

    </div>


    <?php

    if ($editing_student) {

    ?>

    <div class="form-container p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">

            <div>

                <h4 class="mb-1">
                    Edit Student Details
                </h4>

                <small class="text-secondary">
                    Update the selected student information.
                </small>

            </div>

            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary btn-sm">
                Cancel
            </a>

        </div>

        <form method="POST">

            <input type="hidden" name="student_id" value="<?php echo $editing_student['student_id']; ?>">

            <div class="row g-3">

                <div class="input-box col-md-6">

                    <label>
                        Student Name
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        name="student_name"
                        value="<?php echo htmlspecialchars($editing_student['student_name']); ?>"
                        required>

                </div>

                <div class="input-box col-md-6">

                    <label>
                        Gender
                    </label>

                    <select class="form-select" name="gender" required>

                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($editing_student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($editing_student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($editing_student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>

                    </select>

                </div>

                <div class="input-box col-md-6">

                    <label>
                        Mobile Number
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        name="mobile"
                        value="<?php echo htmlspecialchars($editing_student['mobile']); ?>"
                        required>

                </div>

                <div class="input-box col-md-6">

                    <label>
                        Email
                    </label>

                    <input
                        class="form-control"
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($editing_student['email']); ?>">

                </div>

                <div class="input-box col-12">

                    <label>
                        Address
                    </label>

                    <textarea
                        class="form-control"
                        name="address"><?php echo htmlspecialchars($editing_student['address']); ?></textarea>

                </div>

                <div class="col-12 d-flex gap-2 flex-wrap">

                    <button class="btn-main" type="submit" name="update_student">
                        Save Changes
                    </button>

                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">
                        Discard
                    </a>

                </div>

            </div>

        </form>

    </div>

    <?php

    }

    ?>

    <div class="table-container">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">

            <div>

                <h4 class="mb-1">
                    Registered Students
                </h4>

                <small class="text-secondary">

                    Total:
                    <?php
                    echo $total_students;
                    ?>
                    students

                </small>

            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">

                <div class="search-box mb-0">

                    <input
                        type="text"
                        id="studentSearch"
                        placeholder="🔍 Search student..."
                        onkeyup="searchStudents()">

                </div>

                <a href="#registration" class="btn btn-sm btn-main" onclick="showSection('registration', document.querySelectorAll('.menu-link')[1]); return false;">
                    + Add Student
                </a>

            </div>

        </div>


        <table
            class="table table-hover align-middle"
            id="studentTable">


            <thead>

                <tr>

                    <th>ID</th>

                    <th>Name</th>

                    <th>Gender</th>

                    <th>Mobile</th>

                    <th>Email</th>

                    <th>Address</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>


            <?php

            $student_list =
                mysqli_query(
                    $conn,
                    "SELECT *
                     FROM students
                     ORDER BY student_id DESC"
                );


            while (
                $student =
                mysqli_fetch_assoc(
                    $student_list
                )
            ) {

            ?>


            <tr>


                <td>

                    <span class="badge-soft">

                        #
                        <?php
                        echo $student['student_id'];
                        ?>

                    </span>

                </td>


                <td class="student-name">

                    <?php
                    echo htmlspecialchars(
                        $student['student_name']
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $student['gender']
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $student['mobile']
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $student['email']
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $student['address']
                    );
                    ?>

                </td>


                <td>

                    <a
                        href="?edit_student=<?php
                        echo $student['student_id'];
                        ?>#students"
                        class="btn-edit">

                        ✏ Edit

                    </a>


                    <a
                        href="?delete_student=<?php
                        echo $student['student_id'];
                        ?>"
                        class="btn-delete"
                        onclick="
                        return confirm(
                        'Are you sure you want to delete this student?'
                        );">

                        🗑 Delete

                    </a>

                </td>


            </tr>


            <?php } ?>


            </tbody>

        </table>

    </div>


</section>


<!-- =====================================================
     COURSES
===================================================== -->

<section
    id="courses-page"
    class="page-section">


    <div class="topbar">

        <div>

            <h1>
                Courses & Batches
            </h1>

            <p>
                Available programs and batch
                information.
            </p>

        </div>

    </div>


    <?php

    if ($selected_course) {

        $selected_course_image =
            isset(
                $course_images[
                    $selected_course['course_id']
                ]
            )
            ? $course_images[
                $selected_course['course_id']
            ]
            : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80';

        $selected_course_description =
            isset(
                $course_descriptions[
                    $selected_course['course_id']
                ]
            )
            ? $course_descriptions[
                $selected_course['course_id']
            ]
            : 'A complete learning program designed to help students gain practical skills, strong fundamentals, and career-ready knowledge.';

        $selected_course_batches = mysqli_query(
            $conn,
            "SELECT *
             FROM batches
             WHERE course_id = " . intval($selected_course['course_id']) . "
             ORDER BY start_date"
        );

    ?>

    <div class="course-detail-panel">

        <div class="row g-4 align-items-center">

            <div class="col-lg-5">

                <img
                    class="course-image"
                    src="<?php echo htmlspecialchars($selected_course_image); ?>"
                    alt="<?php echo htmlspecialchars($selected_course['course_name']); ?>">

            </div>

            <div class="col-lg-7 course-detail-content">

                <span class="badge-soft">
                    Selected Course
                </span>

                <h3>
                    <?php
                    echo htmlspecialchars(
                        $selected_course['course_name']
                    );
                    ?>
                </h3>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $selected_course_description
                    );
                    ?>
                </p>

                <div class="course-meta-row">

                    <div class="course-meta-item">
                        Duration: 
                        <?php
                        echo htmlspecialchars(
                            $selected_course['course_duration']
                        );
                        ?>
                    </div>

                    <div class="course-meta-item">
                        Course Fee: ₹<?php
                        echo number_format(
                            $selected_course['course_fee'],
                            2
                        );
                        ?>
                    </div>

                </div>

                <div class="course-tags">

                    <span class="course-tag">
                        Practical Learning
                    </span>

                    <span class="course-tag">
                        Industry Focused
                    </span>

                    <span class="course-tag">
                        Career Ready
                    </span>

                </div>

            </div>

        </div>

    </div>

    <?php

    }

    ?>


    <div class="row g-3">


    <?php

    $course_list =
        mysqli_query(
            $conn,
            "SELECT * FROM courses"
        );


    while (
        $course =
        mysqli_fetch_assoc(
            $course_list
        )
    ) {

        $is_selected =
            $selected_course &&
            $selected_course['course_id'] == $course['course_id'];

    ?>


        <div class="col-md-6 col-xl-3">


            <a
                href="?course_id=<?php echo $course['course_id']; ?>#courses-page"
                class="info-card course-card-link <?php echo $is_selected ? 'selected' : ''; ?>">


                <h3>

                    📚

                    <?php
                    echo htmlspecialchars(
                        $course['course_name']
                    );
                    ?>

                </h3>


                <p>

                    <b>Duration:</b>

                    <?php
                    echo htmlspecialchars(
                        $course['course_duration']
                    );
                    ?>

                </p>


                <p>

                    <b>Course Fee:</b>

                    ₹<?php
                    echo number_format(
                        $course['course_fee'],
                        2
                    );
                    ?>

                </p>

                <div class="text-primary fw-semibold mt-2">
                    View Details →
                </div>


            </a>


        </div>


    <?php } ?>


    </div>


    <div class="table-container mt-4">


        <h4 class="mb-3">
            Batch Details
        </h4>


        <table class="table table-hover">


            <thead>

                <tr>

                    <th>
                        Batch Name
                    </th>

                    <th>
                        Course
                    </th>

                    <th>
                        Start Date
                    </th>

                    <th>
                        End Date
                    </th>

                    <th>
                        Timing
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php

            $batch_list =
                mysqli_query(
                    $conn,
                    "
                    SELECT
                        batches.*,
                        courses.course_name

                    FROM batches

                    LEFT JOIN courses
                    ON batches.course_id =
                       courses.course_id

                    ORDER BY batches.batch_id
                    "
                );


            while (
                $batch =
                mysqli_fetch_assoc(
                    $batch_list
                )
            ) {

            ?>


                <tr>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $batch['batch_name']
                        );
                        ?>

                    </td>


                    <td>

                        <span class="badge-soft">

                            <?php
                            echo htmlspecialchars(
                                $batch['course_name']
                            );
                            ?>

                        </span>

                    </td>


                    <td>

                        <?php
                        echo $batch['start_date'];
                        ?>

                    </td>


                    <td>

                        <?php
                        echo $batch['end_date'];
                        ?>

                    </td>


                    <td>

                        <?php

                        echo date(
                            "h:i A",
                            strtotime(
                                $batch['start_time']
                            )
                        );

                        ?>

                        -

                        <?php

                        echo date(
                            "h:i A",
                            strtotime(
                                $batch['end_time']
                            )
                        );

                        ?>

                    </td>


                </tr>


            <?php } ?>


            </tbody>

        </table>

    </div>


</section>


<!-- =====================================================
     FACULTY
===================================================== -->

<section
    id="faculty"
    class="page-section">


    <div class="topbar">

        <div>

            <h1>
                Faculty Management
            </h1>

            <p>
                Manage institute faculty and
                trainers.
            </p>

        </div>

    </div>


    <?php

    if ($selected_faculty) {

        $selected_faculty_image =
            isset(
                $faculty_images[
                    $selected_faculty['faculty_id']
                ]
            )
            ? $faculty_images[
                $selected_faculty['faculty_id']
            ]
            : 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=900&q=80';

        $selected_faculty_description =
            isset(
                $faculty_descriptions[
                    $selected_faculty['faculty_id']
                ]
            )
            ? $faculty_descriptions[
                $selected_faculty['faculty_id']
            ]
            : 'A dedicated faculty member focused on guiding students with practical knowledge, mentorship, and strong academic support.';

    ?>

    <div class="course-detail-panel">

        <div class="row g-4 align-items-center">

            <div class="col-lg-5">

                <img
                    class="course-image"
                    src="<?php echo htmlspecialchars($selected_faculty_image); ?>"
                    alt="<?php echo htmlspecialchars($selected_faculty['faculty_name']); ?>">

            </div>

            <div class="col-lg-7 course-detail-content">

                <span class="badge-soft">
                    Selected Faculty
                </span>

                <h3>
                    <?php
                    echo htmlspecialchars(
                        $selected_faculty['faculty_name']
                    );
                    ?>
                </h3>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $selected_faculty_description
                    );
                    ?>
                </p>

                <div class="course-meta-row">

                    <div class="course-meta-item">
                        Role: 
                        <?php
                        echo htmlspecialchars(
                            $selected_faculty['faculty_role']
                        );
                        ?>
                    </div>

                    <div class="course-meta-item">
                        Specialty: 
                        <?php
                        echo htmlspecialchars(
                            $selected_faculty['faculty_specialty']
                        );
                        ?>
                    </div>

                    <div class="course-meta-item">
                        Experience: 
                        <?php
                        echo htmlspecialchars(
                            $selected_faculty['faculty_experience']
                        );
                        ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <?php

    }

    ?>


    <div class="row g-3">

    <?php

    $faculty_list = mysqli_query(
        $conn,
        "SELECT * FROM faculty ORDER BY faculty_id"
    );

    while (
        $faculty =
        mysqli_fetch_assoc(
            $faculty_list
        )
    ) {

        $is_selected =
            $selected_faculty &&
            $selected_faculty['faculty_id'] == $faculty['faculty_id'];

    ?>

        <div class="col-md-6 col-xl-3">

            <a
                href="?faculty_id=<?php echo $faculty['faculty_id']; ?>#faculty"
                class="info-card course-card-link <?php echo $is_selected ? 'selected' : ''; ?>">

                <h3>

                    👨‍🏫

                    <?php
                    echo htmlspecialchars(
                        $faculty['faculty_name']
                    );
                    ?>

                </h3>

                <p>

                    <b>Role:</b>

                    <?php
                    echo htmlspecialchars(
                        $faculty['faculty_role']
                    );
                    ?>

                </p>

                <p>

                    <b>Specialty:</b>

                    <?php
                    echo htmlspecialchars(
                        $faculty['faculty_specialty']
                    );
                    ?>

                </p>

                <div class="text-primary fw-semibold mt-2">
                    View Details →
                </div>


            </a>


        </div>


    <?php } ?>


    </div>


</section>


<!-- =====================================================
     PAYMENTS
===================================================== -->

<section
    id="payments"
    class="page-section">


    <div class="topbar">

        <div>

            <h1>
                Payment Management
            </h1>

            <p>
                Track student payments and
                pending balances.
            </p>

        </div>

    </div>


    <div class="row g-3 mb-4">


        <div class="col-md-4">

            <div class="stat">

                <div class="stat-icon">
                    💰
                </div>

                <b>

                    ₹<?php
                    echo number_format(
                        $total_payments,
                        2
                    );
                    ?>

                </b>

                <span>
                    Total Amount Collected
                </span>

            </div>

        </div>


        <div class="col-md-4">

            <div class="stat">

                <div class="stat-icon">
                    ⚠️
                </div>

                <b>

                    ₹<?php
                    echo number_format(
                        $total_balance,
                        2
                    );
                    ?>

                </b>

                <span>
                    Total Pending Balance
                </span>

            </div>

        </div>


        <div class="col-md-4">

            <div class="stat">

                <div class="stat-icon">
                    👥
                </div>

                <b>
                    <?php
                    echo $total_pending_students;
                    ?>
                </b>

                <span>
                    Students With Pending
                </span>

            </div>

        </div>


    </div>




    <div class="table-container">


        <h4 class="mb-3">
            Transaction History
        </h4>


        <table class="table table-hover">


            <thead>

                <tr>

                    <th>
                        Transaction ID
                    </th>

                    <th>
                        Student
                    </th>

                    <th>
                        Batch
                    </th>

                    <th>
                        Payment Mode
                    </th>

                    <th>
                        Paid Amount
                    </th>

                    <th>
                        Balance
                    </th>

                    <th>
                        Date
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php

            $payment_list =
                mysqli_query(
                    $conn,
                    "
                    SELECT

                        transactions.*,

                        students.student_name,

                        batches.batch_name,

                        payment_modes.payment_mode

                    FROM transactions

                    LEFT JOIN students
                    ON transactions.student_id =
                       students.student_id

                    LEFT JOIN batches
                    ON transactions.batch_id =
                       batches.batch_id

                    LEFT JOIN payment_modes
                    ON transactions.payment_mode_id =
                       payment_modes.payment_mode_id

                    ORDER BY
                        transactions.transaction_id DESC
                    "
                );


            while (
                $payment =
                mysqli_fetch_assoc(
                    $payment_list
                )
            ) {

            ?>


                <tr>


                    <td>

                        #
                        <?php
                        echo $payment[
                            'transaction_id'
                        ];
                        ?>

                    </td>


                    <td class="student-name">

                        <?php
                        echo htmlspecialchars(
                            $payment['student_name']
                        );
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $payment['batch_name']
                        );
                        ?>

                    </td>


                    <td>

                        <span class="badge-soft">

                            <?php
                            echo htmlspecialchars(
                                $payment['payment_mode']
                            );
                            ?>

                        </span>

                    </td>


                    <td>

                        ₹<?php
                        echo number_format(
                            $payment['paid_amount'],
                            2
                        );
                        ?>

                    </td>


                    <td>

                        ₹<?php
                        echo number_format(
                            $payment['balance_amount'],
                            2
                        );
                        ?>

                    </td>


                    <td>

                        <?php
                        echo $payment[
                            'transaction_date'
                        ];
                        ?>

                    </td>


                </tr>


            <?php } ?>


            </tbody>

        </table>

    </div>


</section>


<!-- =====================================================
     REPORTS
===================================================== -->

<section
    id="reports"
    class="page-section">


    <div class="topbar">

        <div>

            <h1>
                Reports
            </h1>

            <p>
                Institute overview and
                management reports.
            </p>

        </div>

    </div>


    <div class="row g-3">


        <div class="col-md-4">

            <a href="#students" class="info-card text-decoration-none d-block" onclick="showSection('students', document.querySelector('[data-section=\'students\']')); return false;">

                <h3>
                    🎓 Student Report
                </h3>

                <p>

                    Total Registered Students:

                    <b>
                        <?php
                        echo $total_students;
                        ?>
                    </b>

                </p>

            </a>

        </div>


        <div class="col-md-4">

            <a href="#courses-page" class="info-card text-decoration-none d-block" onclick="showSection('courses-page', document.querySelector('[data-section=\'courses-page\']')); return false;">

                <h3>
                    📚 Course Report
                </h3>

                <p>

                    Total Available Courses:

                    <b>
                        <?php
                        echo $total_courses;
                        ?>
                    </b>

                </p>

            </a>

        </div>


        <div class="col-md-4">

            <a href="#payments" class="info-card text-decoration-none d-block" onclick="showSection('payments', document.querySelector('[data-section=\'payments\']')); return false;">

                <h3>
                    💳 Payment Report
                </h3>

                <p>

                    Total Fee Collected:

                    <b>

                        ₹<?php
                        echo number_format(
                            $total_payments,
                            2
                        );
                        ?>

                    </b>

                </p>

            </a>

        </div>


        <div class="col-md-4">

            <a href="#payments" class="info-card text-decoration-none d-block" onclick="showSection('payments', document.querySelector('[data-section=\'payments\']')); return false;">

                <h3>
                    ⚠️ Pending Fees
                </h3>

                <p>

                    Total Pending Balance:

                    <b>

                        ₹<?php
                        echo number_format(
                            $total_balance,
                            2
                        );
                        ?>

                    </b>

                </p>

            </a>

        </div>


        <div class="col-md-4">

            <a href="#courses-page" class="info-card text-decoration-none d-block" onclick="showSection('courses-page', document.querySelector('[data-section=\'courses-page\']')); return false;">

                <h3>
                    🏫 Batch Report
                </h3>

                <p>

                    Total Active Batches:

                    <b>
                        <?php
                        echo $total_batches;
                        ?>
                    </b>

                </p>

            </a>

        </div>


        <div class="col-md-4">

            <div class="info-card">

                <h3>
                    📊 Institute Overview
                </h3>

                <p>

                    Centralized information about
                    students, courses, batches and
                    financial transactions.

                </p>

            </div>

        </div>


    </div>


</section>


</main>

</div>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>


/* =====================================================
   PAGE SWITCHING
===================================================== */

function showSection(sectionId, element, clickEvent) {
    if (clickEvent) {
        clickEvent.preventDefault();
    }


    const sections =
        document.querySelectorAll(
            '.page-section'
        );


    sections.forEach(
        function(section) {

            section.classList.remove(
                'active-section'
            );

        }
    );


    const selected =
        document.getElementById(
            sectionId
        );


    if (selected) {

        selected.classList.add(
            'active-section'
        );

    }


    const menuLinks =
        document.querySelectorAll(
            '.menu-link'
        );


    menuLinks.forEach(
        function(link) {

            link.classList.remove(
                'active'
            );

        }
    );


    if (element) {
        element.classList.add(
            'active'
        );
    }


    window.scrollTo({

        top: 0,

        behavior: 'smooth'

    });

}


/* =====================================================
   STUDENT SEARCH
===================================================== */

function searchStudents() {


    const input =
        document.getElementById(
            'studentSearch'
        );


    const filter =
        input.value.toLowerCase();


    const table =
        document.getElementById(
            'studentTable'
        );


    const rows =
        table.getElementsByTagName(
            'tr'
        );


    for (
        let i = 1;
        i < rows.length;
        i++
    ) {


        const text =
            rows[i].innerText.toLowerCase();


        if (
            text.includes(filter)
        ) {

            rows[i].style.display = '';

        } else {

            rows[i].style.display = 'none';

        }

    }

}



document.addEventListener('DOMContentLoaded', function () {

    const params = new URLSearchParams(window.location.search);

    if (params.has('course_id')) {

        const courseMenuLink = document.querySelector('.menu-link[data-section="courses-page"]');

        if (courseMenuLink) {
            showSection('courses-page', courseMenuLink);
        }
    }

    if (params.has('faculty_id')) {

        const facultyMenuLink = document.querySelector('.menu-link[data-section="faculty"]');

        if (facultyMenuLink) {
            showSection('faculty', facultyMenuLink);
        }
    }
});

</script>


</body>

</html>
