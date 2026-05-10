<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');


$user_id = $_SESSION['user_id'] ?? 1;

$error_msg = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_arenas'])) {
    
    if (!isset($_POST['arenas']) || count($_POST['arenas']) < 1) {
        $error_msg = "Please select at least one venue.";
    } else {
        
        $stmt_del = mysqli_prepare($conn, "DELETE FROM user_venue_likes WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $user_id);
        mysqli_stmt_execute($stmt_del);


        $stmt_ins = mysqli_prepare($conn, "INSERT INTO user_venue_likes (user_id, venue_id) VALUES (?, ?)");
        
        foreach ($_POST['arenas'] as $venue_id) {
            $vid = (int)$venue_id;
            mysqli_stmt_bind_param($stmt_ins, "ii", $user_id, $vid);
            mysqli_stmt_execute($stmt_ins);
        }
        
        header("Location: ?page=featured");
        exit();
    }
}


$venues = [];
$res = mysqli_query($conn, "SELECT venue_id, name FROM venues ORDER BY name ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $venues[] = $row;
    }
}


$colors = [
    ['from' => 'from-primary/5', 'border' => 'peer-checked:border-primary', 'bg' => 'peer-checked:bg-primary', 'text' => 'text-primary', 'hover' => 'hover:border-primary/50'],
    ['from' => 'from-[#77e652]/5', 'border' => 'peer-checked:border-[#77e652]', 'bg' => 'peer-checked:bg-[#77e652]', 'text' => 'text-[#77e652]', 'hover' => 'hover:border-[#77e652]/50'],
    ['from' => 'from-[#919191]/5', 'border' => 'peer-checked:border-[#919191]', 'bg' => 'peer-checked:bg-[#919191]', 'text' => 'text-[#919191]', 'hover' => 'hover:border-[#919191]/50'],
];
?>


<div class="flex flex-col justify-center items-center w-full relative min-h-screen">
    <form method="POST" action="?page=featured" class="flex flex-col min-h-screen w-full max-w-2xl justify-between">
        
        <div class="h-[15vh] w-full fixed top-0 left-0 flex flex-col items-center justify-center bg-black z-20">
            <p class="font-ballmer text-2xl p-4 sticky text-white text-center">pick the venues that you prefer</p>
            
            <?php if(!empty($error_msg)): ?>
                <p class="text-primary font-bold animate-pulse text-sm -mt-2 pb-2"><?= htmlspecialchars($error_msg) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="flex flex-col gap-4 p-4 z-10 mt-32 mb-40 w-full">
            <?php if(empty($venues)): ?>
                <p class="text-center text-zinc-500 mt-10">No venues available yet. Please add some via the database.</p>
            <?php endif; ?>


            <?php 
            foreach($venues as $index => $venue): 
                $c = $colors[$index % count($colors)];
                $isChecked = (isset($_POST['arenas']) && in_array($venue['venue_id'], $_POST['arenas'])) ? 'checked' : '';
            ?>
                <label class="relative block w-full rounded-3xl h-36 cursor-pointer select-none overflow-hidden border border-zinc-700/50 <?= $c['hover'] ?> bg-zinc-900/40 transition-colors duration-200">
                    <input type="checkbox" name="arenas[]" value="<?= $venue['venue_id'] ?>" class="sr-only peer" <?= $isChecked ?>>
                    
                    <div class="absolute inset-0 bg-linear-to-br <?= $c['from'] ?> to-zinc-900 opacity-0 peer-checked:opacity-100 peer-checked:border-2 <?= $c['border'] ?> rounded-3xl transition-all duration-300"></div>
                    
                    <div class="absolute top-6 right-6 w-8 h-8 rounded-full border-4 border-white bg-transparent <?= $c['bg'] ?> transition-colors duration-200 z-20"></div>
                    
                    <div class="relative flex flex-col justify-between h-full p-6 z-10">
                        <div class="flex items-center gap-2 <?= $c['text'] ?>">
                            <div class="w-1.5 h-6 bg-current"></div>
                            <svg class="w-11 h-6" viewBox="0 0 44 24" fill="currentColor">
                                <path d="M0 0H32A12 12 0 0 0 32 24H0Z" />
                            </svg>
                        </div>
                        <p class="font-ballmer text-4xl text-white tracking-tight leading-none lowercase"><?= htmlspecialchars($venue['name']) ?></p>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>


        <div class="p-10 fixed bottom-0  left-0 bg-linear-to-t from-black via-black/80 to-transparent h-[25%] w-full flex flex-col justify-end items-center z-20 pointer-events-none">
            <button type="submit" name="submit_arenas" class="bg-primary max-w-sm max-h-13 text-white p-2 rounded-full w-1/2 pointer-events-auto hover:scale-105 active:scale-95 transition">
                <p class="font-ballmer text-2xl translate-y-1">next</p>
            </button>
        </div>
    </form>
</div>
