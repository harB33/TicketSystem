<!DOCTYPE html>
<html lang="en" class="min-h-screen" data-theme="mytheme" >
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./style/output.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="w-full h-screen">
    <video autoplay muted loop playsinline class="absolute inset-0 w-full h-screen object-cover z-0">
        <source src="./Video%20Project%204.mp4" type="video/mp4">
    </video>
    <div class="absolute inset-0 bg-black/75 z-5"></div>
    <?php include './view/view.php'; ?>
</body>
</html>