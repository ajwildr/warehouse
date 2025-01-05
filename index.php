<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loading...</title>
    <style>
        body {
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #000;
        }
        #container {
            text-align: center;
        }
        #skipButton {
            display: none;
            padding: 10px 20px;
            font-size: 16px;
            margin-top: 20px;
            cursor: pointer;
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div id="container">
        <iframe 
            id="player"
            width="560"
            height="315"
            src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&start=0"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>
        <br>
        <button id="skipButton">Skip</button>
    </div>

    <script>
        // Show skip button after 10 seconds
        setTimeout(() => {
            document.getElementById('skipButton').style.display = 'block';
        }, 15000);

        // Redirect after 15 seconds
        setTimeout(() => {
            window.location.href = 'home.php';
        }, 25000);

        // Skip button handler
        document.getElementById('skipButton').addEventListener('click', () => {
            window.location.href = 'home.php';
        });
    </script>
</body>
</html>