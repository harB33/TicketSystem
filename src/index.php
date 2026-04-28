<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gyro Parallax Test</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background: #121212;
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* The container that clips the moving layers */
        #parallax-viewport {
            position: relative;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Layer Styles */
        .layer {
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
            will-change: transform;
            transition: transform 0.1s ease-out; /* Smooths the jitter */
        }

        /* Background Layer: Moves the least */
        .bg-layer {
            width: 120%;
            height: 120%;
            background: linear-gradient(45deg, #2c3e50, #000000);
            z-index: 1;
        }

        /* Text Layer: Moves the most (foreground) */
        .text-layer {
            z-index: 2;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            text-shadow: 0 10px 20px rgba(0,0,0,0.5);
        }

        /* UI Elements */
        #controls {
            position: absolute;
            z-index: 10;
            text-align: center;
        }

        button {
            padding: 15px 30px;
            font-size: 1rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div id="controls">
        <button id="start-btn">Enable Gyroscope</button>
        <p style="color: gray; margin-top: 10px;">(Tap to start parallax)</p>
    </div>

    <div id="parallax-viewport">
        <div class="layer bg-layer"></div>
        <div class="layer text-layer">PARALLAX</div>
    </div>

    <script>
        const btn = document.getElementById('start-btn');
        const bgLayer = document.querySelector('.bg-layer');
        const textLayer = document.querySelector('.text-layer');

        btn.addEventListener('click', async () => {
            // Request permission for iOS 13+
            if (typeof DeviceOrientationEvent.requestPermission === 'function') {
                try {
                    const response = await DeviceOrientationEvent.requestPermission();
                    if (response === 'granted') {
                        startTracking();
                    }
                } catch (e) {
                    alert("Permission denied");
                }
            } else {
                // Android or older browsers
                startTracking();
            }
        });

        function startTracking() {
            // Hide the button once activated
            document.getElementById('controls').style.display = 'none';

            window.addEventListener('deviceorientation', (event) => {
                // Gamma: Left/Right tilt (-90 to 90)
                // Beta: Front/Back tilt (-180 to 180)
                const x = event.gamma; 
                const y = event.beta;

                // Move BG slowly (Depth)
                bgLayer.style.transform = `translate(${x * 0.5}px, ${(y - 45) * 0.5}px)`;
                
                // Move Text faster (Foreground)
                textLayer.style.transform = `translate(${x * 1.5}px, ${(y - 45) * 1.5}px)`;
            });
        }
    </script>
</body>
</html>