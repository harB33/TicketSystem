<!DOCTYPE html>
<html lang="en" class="min-h-screen" data-theme="mytheme" >
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./style/output.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        #loading-screen {
            position: fixed;
            inset: 0;
            background-color: black;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            pointer-events: all;
            cursor: pointer;
            animation: dropInScreen 0.8s ease-in-out forwards;
        }

        @keyframes dropInScreen {
            0% {
                transform: translateY(-100%);
            }
            100% {
                transform: translateY(0);
            }
        }

        .loader-pop-circle {
            opacity: 0;
            transform: scale(0);
            animation: popIn 0.6s ease-in-out 1s forwards;
        }

        @keyframes popIn {  
            0% {
                opacity: 0;
                transform: scale(0);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes popOut {
            0% {
                opacity: 1;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(0);
            }
        }

        .loader-morph-and-rotate {
            width: 4rem;
            height: 4rem;
            background-color: #ff6699; /* primary color */
            border-radius: 50%;
            animation: 
                morphToSquare 0.6s ease-in-out 1s forwards,
                steppedRotate 5s infinite ease-in-out 1.4s;
        }

        @keyframes morphToSquare {
            0% {
                border-radius: 50%;
            }
            100% {
                border-radius: 0%;
            }
        }

        @keyframes steppedRotate {
            0% {
                transform: rotate(0deg);
            }
            5% {
                transform: rotate(90deg);
            }
            25% {
                transform: rotate(90deg);
            }
            30% {
                transform: rotate(180deg);
            }
            50% {
                transform: rotate(180deg);
            }
            55% {
                transform: rotate(270deg);
            }
            75% {
                transform: rotate(270deg);
            }
            80% {
                transform: rotate(360deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body class="w-full h-screen">
    <div id="loading-screen" title="Click to dismiss">
        <div id="loader-pop-container" class="loader-pop-circle">
            <div id="loader-shape" class="loader-morph-and-rotate"></div>
        </div>
    </div>
    <div class="absolute inset-0 bg-black/75 z-5"></div>
    <?php include './view/view.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loader = document.getElementById('loading-screen');
            if (loader) {
                // Morph the square back into a circle at 6.0 seconds
                setTimeout(() => {
                    const shape = document.getElementById('loader-shape');
                    if (shape) {
                        shape.style.animation = 'none';
                        shape.style.borderRadius = '0%';
                        setTimeout(() => {
                            shape.style.transition = 'border-radius 0.5s ease-in-out';
                            shape.style.borderRadius = '50%';
                        }, 20);
                    }
                }, 6000);

                // Pop out that circle at 6.5 seconds (reverse of popIn)
                setTimeout(() => {
                    const container = document.getElementById('loader-pop-container');
                    if (container) {
                        container.style.animation = 'popOut 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
                    }
                }, 6500);

                // Then slide down the loading screen after 7 seconds
                setTimeout(() => {
                    if (loader && loader.parentNode) {
                        loader.style.animation = 'none';
                        loader.offsetHeight; // Force reflow
                        loader.style.transition = 'transform 0.8s ease-in-out';
                        loader.style.transform = 'translateY(100%)';
                        setTimeout(() => loader.remove(), 800);
                    }
                }, 7000);

                // Clicking anywhere on the loading screen will dismiss it immediately
                loader.addEventListener('click', () => {
                    const shape = document.getElementById('loader-shape');
                    const container = document.getElementById('loader-pop-container');
                    if (shape) {
                        shape.style.animation = 'none';
                        shape.style.borderRadius = '0%';
                        setTimeout(() => {
                            shape.style.transition = 'border-radius 0.25s ease-in-out';
                            shape.style.borderRadius = '50%';
                        }, 20);
                    }
                    setTimeout(() => {
                        if (container) {
                            container.style.animation = 'popOut 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
                        }
                    }, 250);
                    setTimeout(() => {
                        loader.style.animation = 'none';
                        loader.offsetHeight; // Force reflow
                        loader.style.transition = 'transform 0.5s ease-in-out';
                        loader.style.transform = 'translateY(100%)';
                        setTimeout(() => loader.remove(), 500);
                    }, 500);
                });
            }
        });
    </script>
</body>
</html>