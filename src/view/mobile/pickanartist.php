<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include (__DIR__ . '/../../ticket_db/connectdb.php');

$user_id = $_SESSION['user_id'] ?? 1;

$error_msg = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_artists'])) {
    

    if (!isset($_POST['artists']) || count($_POST['artists']) < 3) {
        $error_msg = "Please select at least 3 artists before proceeding.";
    } else {

        $stmt_del = mysqli_prepare($conn, "DELETE FROM user_artist_likes WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $user_id);
        mysqli_stmt_execute($stmt_del);


        $stmt_ins = mysqli_prepare($conn, "INSERT INTO user_artist_likes (user_id, artist_id) VALUES (?, ?)");
        
        foreach ($_POST['artists'] as $artist_id) {
            $aid = (int)$artist_id;
            mysqli_stmt_bind_param($stmt_ins, "ii", $user_id, $aid);
            mysqli_stmt_execute($stmt_ins);
        }

        header("Location: ?page=pickanarena");
        exit();
    }
}


$artists = [];
$res = mysqli_query($conn, "SELECT artist_id, name, image_url FROM artists ORDER BY name ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $artists[] = $row;
    }
}
?>

<div class="flex flex-col w-full relative min-h-screen">

    <form method="POST" action="?page=pickanarena" class="w-full flex flex-col min-h-screen">
        
        <div class="h-[15vh] w-full fixed top-0 flex flex-col items-center justify-center bg-black z-20">
            <p class="font-ballmer text-2xl p-4 sticky text-white text-center">pick three or more artist that you listen to</p>

            <?php if(!empty($error_msg)): ?>
                <p class="text-primary font-bold animate-pulse text-sm -mt-2 pb-2"><?= htmlspecialchars($error_msg) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-3 gap-4 p-4 z-10 mt-32 mb-40">
            <?php if(empty($artists)): ?>
                <p class="col-span-3 text-center text-zinc-500 mt-10">No artists available yet. Please add some via the Staff Portal.</p>
            <?php endif; ?>

            <?php foreach($artists as $artist): ?>
                <label class="relative border border-white/10 rounded-xl aspect-square overflow-hidden cursor-pointer select-none bg-white/5 block hover:border-primary/50 transition-colors">

                    <?php if(!empty($artist['image_url'])): ?>
                        <img src="<?= htmlspecialchars($artist['image_url']) ?>" alt="<?= htmlspecialchars($artist['name']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex flex-col items-center justify-center p-2 bg-zinc-900">
                            <svg class="w-8 h-8 text-zinc-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                        </div>
                    <?php endif; ?>

                    <div class="absolute bottom-0 w-full bg-gradient-to-t from-black/90 to-transparent p-2 text-center">
                        <span class="text-white text-xs font-bold truncate block drop-shadow-md"><?= htmlspecialchars($artist['name']) ?></span>
                    </div>

                    <input type="checkbox" name="artists[]" value="<?= $artist['artist_id'] ?>" class="sr-only peer" <?php echo (isset($_POST['artists']) && in_array($artist['artist_id'], $_POST['artists'])) ? 'checked' : ''; ?>>
                    
                    <div class="absolute inset-0 bg-primary opacity-0 peer-checked:opacity-50 transition-opacity duration-200"></div>
                    
                    <div class="absolute inset-0 opacity-0 peer-checked:opacity-100 flex items-center justify-center transition-opacity duration-200">
                        <div class="w-14 h-14 border-4 border-white rounded-full flex items-center justify-center z-10 shadow-lg">
                            <svg class="w-8 h-8 text-white drop-shadow-md" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="p-10 fixed bottom-0 bg-gradient-to-t from-black via-black/80 to-transparent h-[25%] w-full flex flex-col justify-end items-center z-20 pointer-events-none">
            <button type="submit" name="submit_artists" class="bg-primary max-w-2xl max-h-13 text-white p-2 rounded-full w-1/2 pointer-events-auto hover:scale-105 active:scale-95 transition">
                <p class="font-ballmer text-2xl translate-y-1">next</p>
            </button>
        </div>
    </form>
</div>