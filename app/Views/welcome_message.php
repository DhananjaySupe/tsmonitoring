<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Page</title>
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
        }

        .welcome-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
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
            margin-bottom: 20px;
            color: #2575fc;
        }

        h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .subtitle {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .welcome-message {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            border-left: 5px solid #2575fc;
            text-align: left;
        }

        .welcome-message p {
            color: #444;
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .features {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin: 30px 0;
        }

        .feature {
            background: #eef5ff;
            padding: 15px;
            border-radius: 10px;
            flex: 1;
            min-width: 150px;
            transition: transform 0.3s;
        }

        .feature:hover {
            transform: translateY(-5px);
        }

        .feature h3 {
            color: #2575fc;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .cta-button {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            border: none;
            padding: 16px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(106, 17, 203, 0.4);
        }

        .footer {
            margin-top: 30px;
            color: #777;
            font-size: 0.9rem;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        @media (max-width: 600px) {
            .welcome-container {
                padding: 25px;
            }

            h1 {
                font-size: 2rem;
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
            <div class="welcome-icon">🚀</div>
            <h1>Welcome Aboard!</h1>
            <p class="subtitle">We're thrilled to have you here. Let's get started!</p>
        </div>

        <div class="welcome-message">
            <p>Welcome to our platform! Whether you're here to explore, learn, or create something amazing, you're in the right place.</p>
            <p>Our goal is to provide you with the best experience possible. Take your time to look around and discover everything we have to offer.</p>
        </div>

        <div class="features">
            <div class="feature">
                <h3>📱 Easy to Use</h3>
                <p>Intuitive interface designed for everyone</p>
            </div>
            <div class="feature">
                <h3>⚡ Fast & Secure</h3>
                <p>Lightning speed with top-notch security</p>
            </div>
            <div class="feature">
                <h3>🌟 Premium Features</h3>
                <p>Access to exclusive tools and resources</p>
            </div>
        </div>

        <button class="cta-button" onclick="startJourney()">Get Started →</button>

        <div class="footer">
            <p>Need help? <a href="#" style="color: #2575fc; text-decoration: none;">Contact our support team</a></p>
            <p style="margin-top: 10px;">© 2024 Your Company. All rights reserved.</p>
        </div>
    </div>

    <script>
        function startJourney() {
            alert("Welcome! Let's begin your journey. 🎉");
            // In a real application, you would redirect or show more content here
            // window.location.href = "dashboard.html";
        }

        // Add a simple animation on load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.welcome-container').style.opacity = '0';
            document.querySelector('.welcome-container').style.transform = 'translateY(20px)';

            setTimeout(() => {
                document.querySelector('.welcome-container').style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                document.querySelector('.welcome-container').style.opacity = '1';
                document.querySelector('.welcome-container').style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>