<?php
session_start();

if (isset($_SESSION["user_id"])) {
    if ($_SESSION["role"] == "admin") {
        header("Location: php/admin/dashboard.php");
    } else {
        header("Location: php/dashboard/dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Smart Money Management System</title>
    <link rel="stylesheet" href="php/includes/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body { padding: 0; margin: 0; }

        /* ---- Split screen layout: two equal halves side by side ---- */
        .split-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ---- Left panel: green gradient, headline, illustration ---- */
        .left-panel {
            flex: 1;
            background: linear-gradient(160deg, #2CAE68 0%, #1B7943 100%);
            color: #ffffff;
            padding: 60px 50px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .left-panel h1 {
            font-size: 40px;
            font-weight: bold;
            line-height: 1.25;
            margin-bottom: 30px;
        }

        .left-panel p {
            font-size: 16px;
            max-width: 380px;
            opacity: 0.92;
            margin: 25px auto 0 auto;
        }

        .left-panel a.learn-more {
            color: #ffffff;
            font-size: 14px;
            margin-top: 15px;
            display: inline-block;
        }

        /* Decorative outlined circles, echoing the reference image */
        .circle-deco {
            position: absolute;
            border: 2px solid rgba(255,255,255,0.25);
            border-radius: 50%;
        }
        .circle-deco.c1 { width: 90px; height: 90px; top: 15%; right: -30px; }
        .circle-deco.c2 { width: 60px; height: 60px; bottom: 20%; left: -20px; }
        .circle-deco.c3 { width: 40px; height: 40px; top: 8%; left: 10%; }

        /* ---- CSS-only "device mockup" illustration ---- */
        .mockup {
            position: relative;
            width: 340px;
            margin: 10px auto;
        }
        .mockup-laptop {
            background: #ffffff;
            border-radius: 10px;
            padding: 14px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
        }
        .mockup-pills { display: flex; gap: 6px; margin-bottom: 10px; }
        .mockup-pill { flex: 1; height: 18px; border-radius: 4px; }
        .pill-teal { background: #2CAE68; }
        .pill-purple { background: #8E44AD; }
        .pill-orange { background: #E9B949; }

        .mockup-body { display: flex; gap: 8px; }
        .mockup-chart {
            flex: 2;
            height: 70px;
            background: #F2FBF6;
            border-radius: 6px;
            position: relative;
            overflow: hidden;
        }
        .mockup-chart::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 60%;
            background: linear-gradient(180deg, rgba(44,174,104,0.35), rgba(44,174,104,0));
            clip-path: polygon(0% 80%, 15% 60%, 30% 70%, 45% 40%, 60% 55%, 75% 30%, 90% 45%, 100% 20%, 100% 100%, 0% 100%);
        }
        .mockup-donut {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mockup-donut-shape {
            width: 55px; height: 55px;
            border-radius: 50%;
            background: conic-gradient(#2CAE68 0deg 180deg, #E9B949 180deg 260deg, #8E44AD 260deg 360deg);
            position: relative;
        }
        .mockup-donut-shape::after {
            content: "";
            position: absolute;
            top: 10px; left: 10px;
            width: 35px; height: 35px;
            border-radius: 50%;
            background: #ffffff;
        }

        .mockup-phone {
            position: absolute;
            right: -25px;
            bottom: -25px;
            width: 90px;
            height: 160px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            padding: 8px;
        }
        .mockup-phone .bar { background: #2CAE68; height: 14px; border-radius: 3px; margin-bottom: 6px; }
        .mockup-phone .line { background: #EFEFEF; height: 8px; border-radius: 3px; margin-bottom: 5px; }

        /* ---- Right panel: white background, auth card ---- */
        .right-panel {
            flex: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .auth-card {
            width: 100%;
            max-width: 340px;
        }

        .auth-card h2 {
            color: #1B3A2B;
            font-size: 26px;
            margin-bottom: 8px;
            max-width: none;
            margin-left: 0;
            margin-right: 0;
        }

        .auth-card .subtext {
            color: #777777;
            font-size: 14px;
            margin-bottom: 30px;
            max-width: none;
        }

        .auth-card a { text-decoration: none; display: block; }

        .btn-primary {
            background-color: #219653;
            color: #ffffff;
            padding: 14px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            border: none;
            width: 100%;
            cursor: pointer;
            margin-bottom: 14px;
        }
        .btn-primary:hover { background-color: #1B7943; }

        .btn-secondary {
            background-color: transparent;
            color: #219653;
            padding: 14px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            border: 2px solid #219653;
            width: 100%;
            cursor: pointer;
        }
        .btn-secondary:hover { background-color: #F2FBF6; }

        .btn-admin {
            background-color: #C97B4A;
            color: #ffffff;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            border: none;
            width: 100%;
            cursor: pointer;
        }
        .btn-admin:hover { background-color: #a8623a; }

        .admin-wrapper { margin-top: 25px; padding-top: 20px; border-top: 1px solid #eeeeee; }

        /* ---- Info boxes section, kept below the split hero ---- */
        .info-section { max-width: 1000px; margin: 70px auto; padding: 0 20px; text-align: center; }
        .info-section h2 { color: #1B3A2B; margin-bottom: 40px; }
        .info-grid { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }

        .info-box {
            position: relative; width: 220px; height: 160px; border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;
        }
        .info-box .default-view {
            width: 100%; height: 100%; background-color: #ffffff;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;
        }
        .info-box .default-view .icon { font-size: 26px; color: #219653; }
        .info-box .default-view h3 { color: #1B3A2B; font-size: 16px; margin: 0; }
        .info-box .detail-view {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-color: #219653; color: #ffffff; padding: 20px; box-sizing: border-box;
            display: flex; align-items: center; font-size: 13px; line-height: 1.5; text-align: left;
            transform: translateY(100%); transition: transform 0.3s ease;
        }
        .info-box:hover .detail-view { transform: translateY(0); }

        /* ---- Responsive: stack panels on narrow screens ---- */
        @media (max-width: 800px) {
            .split-wrapper { flex-direction: column; }
            .left-panel { padding: 40px 25px; }
            .mockup-phone { display: none; }
        }
    </style>
</head>
<body>
    <div class="split-wrapper">

        <!-- LEFT: green gradient panel with headline + illustration -->
        <div class="left-panel">
            <div class="circle-deco c1"></div>
            <div class="circle-deco c2"></div>
            <div class="circle-deco c3"></div>

            <h1>Your Finances<br>in One Place</h1>

            <div class="mockup">
                <div class="mockup-laptop">
                    <div class="mockup-pills">
                        <div class="mockup-pill pill-teal"></div>
                        <div class="mockup-pill pill-purple"></div>
                        <div class="mockup-pill pill-orange"></div>
                    </div>
                    <div class="mockup-body">
                        <div class="mockup-chart"></div>
                        <div class="mockup-donut"><div class="mockup-donut-shape"></div></div>
                    </div>
                </div>
                <div class="mockup-phone">
                    <div class="bar"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line" style="width:60%;"></div>
                </div>
            </div>

            <p>Track income and expenses, build budgets, and watch your savings goals grow - all in one simple dashboard.</p>
            <a class="learn-more" href="#about">Learn more about how SMMS works</a>
        </div>

        <!-- RIGHT: white panel with login/register/admin actions -->
        <div class="right-panel">
            <div class="auth-card">
                <h2>Smart Money Management</h2>
                <p class="subtext">Sign in to track your finances, or create a free account to get started.</p>

                <a href="php/auth/login.php"><button class="btn-primary">Login</button></a>
                <a href="php/auth/register.php"><button class="btn-secondary">Create an Account</button></a>

                <div class="admin-wrapper">
                    <a href="php/admin/admin_login.php"><button class="btn-admin">Admin Login</button></a>
                </div>
            </div>
        </div>

    </div>

    <div class="info-section" id="about">
        <h2>What You Can Do</h2>
        <div class="info-grid">
            <div class="info-box">
                <div class="default-view"><div class="icon"><i class="bi bi-cash-coin"></i></div><h3>Income & Expenses</h3></div>
                <div class="detail-view">Log every transaction with a category, amount, and date to build a real history of your finances.</div>
            </div>
            <div class="info-box">
                <div class="default-view"><div class="icon"><i class="bi bi-pie-chart-fill"></i></div><h3>Budgets</h3></div>
                <div class="detail-view">Set a monthly spending limit per category and track how close you are to going over.</div>
            </div>
            <div class="info-box">
                <div class="default-view"><div class="icon"><i class="bi bi-piggy-bank-fill"></i></div><h3>Savings Goals</h3></div>
                <div class="detail-view">Set a target amount for what you're saving toward and watch your progress grow.</div>
            </div>
            <div class="info-box">
                <div class="default-view"><div class="icon"><i class="bi bi-lightbulb-fill"></i></div><h3>Insights</h3></div>
                <div class="detail-view">Get plain-English summaries of your spending patterns, budget status, and goal progress.</div>
            </div>
        </div>
    </div>
</body>
</html>