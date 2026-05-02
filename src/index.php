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
    <div id="loading-screen">
        <div id="loader-pop-container" class="loader-pop-circle">
            <div id="loader-shape" class="loader-morph-and-rotate"></div>
        </div>
    </div>
    <div class="absolute inset-0 bg-black/75 z-5"></div>
    <?php include './view/view.php'; ?>

    <script>
        // Check for pageTransition in sessionStorage immediately
        const isTransition = sessionStorage.getItem('pageTransition') === 'true';
        if (isTransition) {
            sessionStorage.removeItem('pageTransition');
            // Ensure #loading-screen starts at translateY(0) without any initial slide-in delay
            const style = document.createElement('style');
            style.id = 'transition-loader-override';
            style.innerHTML = `
                #loading-screen {
                    animation: none !important;
                    transform: translateY(0) !important;
                }
            `;
            document.head.appendChild(style);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const loader = document.getElementById('loading-screen');
            if (loader) {
                const morphTime = isTransition ? 2500 : 6000;
                const popOutTime = isTransition ? 3000 : 6500;
                const slideTime = isTransition ? 3500 : 7000;

                // Morph the square back into a circle
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
                }, morphTime);

                // Pop out that circle at the reverse of popIn
                setTimeout(() => {
                    const container = document.getElementById('loader-pop-container');
                    if (container) {
                        container.style.animation = 'popOut 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
                    }
                }, popOutTime);

                // Then slide down the loading screen
                setTimeout(() => {
                    if (loader && loader.parentNode) {
                        const styleOverride = document.getElementById('transition-loader-override');
                        if (styleOverride) styleOverride.remove();
                        
                        loader.style.animation = 'none';
                        loader.offsetHeight; // Force reflow
                        loader.style.transition = 'transform 0.8s ease-in-out';
                        loader.style.transform = 'translateY(100%)';
                        // Keep loader in the DOM so it can be reused on navigation
                    }
                }, slideTime);
            }

            // Function to show loader before navigating or submitting
            const showLoaderAndThen = (callback) => {
                sessionStorage.setItem('pageTransition', 'true');
                let currentLoader = document.getElementById('loading-screen');
                if (!currentLoader) {
                    const newLoader = document.createElement('div');
                    newLoader.id = 'loading-screen';
                    newLoader.style.animation = 'none';
                    newLoader.style.transform = 'translateY(100%)';
                    newLoader.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    
                    const newPopContainer = document.createElement('div');
                    newPopContainer.id = 'loader-pop-container';
                    newPopContainer.className = 'loader-pop-circle';
                    
                    const newShape = document.createElement('div');
                    newShape.id = 'loader-shape';
                    newShape.className = 'loader-morph-and-rotate';
                    
                    newPopContainer.appendChild(newShape);
                    newLoader.appendChild(newPopContainer);
                    document.body.appendChild(newLoader);
                    
                    currentLoader = newLoader;
                    
                    setTimeout(() => {
                        currentLoader.style.transform = 'translateY(0)';
                    }, 20);
                } else {
                    currentLoader.style.animation = 'none';
                    currentLoader.style.transition = 'none';
                    currentLoader.style.transform = 'translateY(-100%)';
                    currentLoader.offsetHeight; // force reflow
                    
                    currentLoader.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    currentLoader.style.transform = 'translateY(0)';
                }

                // Wait for the loader animation to finish completely before invoking the callback
                setTimeout(callback, 600);
            };

            // Intercept navigation links to trigger the loading screen before navigating
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && link.href && (link.href.includes('?page=') || link.getAttribute('href').startsWith('?page='))) {
                    e.preventDefault();
                    showLoaderAndThen(() => {
                        window.location.href = link.href;
                    });
                }
            });

            // Intercept form submissions to trigger the loading screen before navigating
            let formSubmitted = false;
            document.addEventListener('submit', (e) => {
                if (formSubmitted) return;
                e.preventDefault();
                const form = e.target;
                
                showLoaderAndThen(() => {
                    formSubmitted = true;
                    form.submit();
                });
            });
        });
    </script>
</body>
</html>