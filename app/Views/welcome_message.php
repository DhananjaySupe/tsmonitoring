<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TS Monitoring – Sanitation & Township Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
        }

        .welcome-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 40px;
            max-width: 640px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-header {
            margin-bottom: 30px;
        }

        .welcome-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }

        h1 {
            color: #1a3a4a;
            font-size: 2.2rem;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .app-tagline {
            color: #2c5364;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .subtitle {
            color: #666;
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .welcome-message {
            background: #f0f7fa;
            padding: 24px;
            border-radius: 14px;
            margin: 24px 0;
            border-left: 5px solid #2c5364;
            text-align: left;
        }

        .welcome-message p {
            color: #444;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .welcome-message p:last-child {
            margin-bottom: 0;
        }

        .features {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 14px;
            margin: 28px 0;
        }

        .feature {
            background: #e8f4f8;
            padding: 16px;
            border-radius: 12px;
            flex: 1;
            min-width: 160px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .feature:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(44, 83, 100, 0.15);
        }

        .feature h3 {
            color: #1a3a4a;
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .feature p {
            color: #555;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .cta-button {
            background: linear-gradient(to right, #203a43, #2c5364);
            color: white;
            border: none;
            padding: 14px 36px;
            font-size: 1.05rem;
            border-radius: 50px;
            cursor: pointer;
            margin-top: 18px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(26, 58, 74, 0.35);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 58, 74, 0.45);
        }

        .footer {
            margin-top: 28px;
            color: #777;
            font-size: 0.88rem;
            border-top: 1px solid #eee;
            padding-top: 18px;
        }

        .footer a {
            color: #2c5364;
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .welcome-container {
                padding: 24px;
            }

            h1 {
                font-size: 1.85rem;
            }

            .features {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="welcome-header">
            <div class="welcome-icon">🗑️</div>
            <h1>TS Monitoring</h1>
            <p class="app-tagline">Sanitation & Township Management</p>
            <p class="subtitle">Monitor assets, inspections, vehicles, and operations in one place.</p>
        </div>

        <div class="welcome-message">
            <p><strong>TS Monitoring</strong> is a sanitation and township management platform. Use it to manage circles and sectors, sanitation assets and allocations, vehicle fleets, vendors, shifts, and inspection questions.</p>
            <p>Access the API for login, user management, inspections, and all master data. Use the mobile or web client to get started.</p>
        </div>

        <div class="features">
            <div class="feature">
                <h3>📦 Assets & Allocations</h3>
                <p>Track sanitation assets and assign them to circles and sectors</p>
            </div>
            <div class="feature">
                <h3>🚛 Vehicles & Vendors</h3>
                <p>Manage fleet and vendor information</p>
            </div>
            <div class="feature">
                <h3>✅ Inspections</h3>
                <p>Run and record inspections with configurable questions</p>
            </div>
        </div>

        <button class="cta-button" onclick="openApi()">API Documentation →</button>

        <div class="footer">
            <p>Use the <strong>api</strong> endpoints for integration. Need help? <a href="#">Contact support</a></p>
            <p style="margin-top: 10px;">© 2026 TS Monitoring. All rights reserved.</p>
        </div>
    </div>

    <script>
        function openApi() {
            // API base URL (same origin as this page)
            window.location.href = window.location.origin + (window.location.pathname.replace(/\/$/, '') || '') + '/';
        }

        document.addEventListener('DOMContentLoaded', function() {
            var el = document.querySelector('.welcome-container');
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(function() {
                el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
