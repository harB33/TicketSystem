<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: ?page=login");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ?page=login");
    exit();
}

$email = "User";

$sql = "SELECT email FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $email = $row['email'];
}
?>

<div class="flex flex-col w-full relative min-h-screen bg-black overflow-y-auto custom-scrollbar-v pb-32">
    <!-- Header -->
    <div class="h-[15vh] w-full shrink-0 flex flex-col items-center justify-end pb-4 bg-black z-20 sticky top-0">
        <div class="px-4 flex items-center gap-2">
            <div class="w-2 h-8 bg-primary rounded-full"></div>
            <p class="font-aubette text-white text-3xl font-bold">MY PROFILE</p>
        </div>
    </div>
    
    <!-- Profile Info -->
    <div class="flex flex-col items-center mt-8 gap-4 px-6">
        <div class="w-32 h-32 rounded-full bg-zinc-900 border-4 border-primary/20 flex items-center justify-center shadow-[0_0_30px_rgba(255,102,153,0.15)] relative">
            <svg class="w-16 h-16 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <button class="absolute bottom-0 right-0 p-2 bg-primary rounded-full text-white hover:scale-105 transition shadow-lg border-2 border-black">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
            </button>
        </div>
        <div class="text-center">
            <p class="text-white text-2xl font-bold font-aubette truncate max-w-[280px]"><?= htmlspecialchars($email) ?></p>
            <p class="text-zinc-500 text-sm mt-1">TicketSystem Member</p>
        </div>
    </div>

    <!-- Menu Options -->
    <div class="flex flex-col mt-10 px-6 gap-4">
        <a href="?page=payment_methods" class="flex items-center justify-between p-5 bg-zinc-900/60 rounded-3xl border border-white/5 hover:border-primary/30 transition-colors">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-zinc-800 rounded-xl text-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <p class="text-white font-bold text-lg font-aubette">Payment Methods</p>
            </div>
            <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>

        <a href="?page=pickanartist" class="flex items-center justify-between p-5 bg-zinc-900/60 rounded-3xl border border-white/5 hover:border-primary/30 transition-colors">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-zinc-800 rounded-xl text-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                </div>
                <p class="text-white font-bold text-lg font-aubette">Edit Preferences</p>
            </div>
            <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>

        <a href="#" class="flex items-center justify-between p-5 bg-zinc-900/60 rounded-3xl border border-white/5 hover:border-primary/30 transition-colors">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-zinc-800 rounded-xl text-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <p class="text-white font-bold text-lg font-aubette">Settings</p>
            </div>
            <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>

        <a href="?page=profile&action=logout" class="flex items-center justify-center p-5 mt-4 bg-red-500/10 rounded-3xl border border-red-500/20 hover:bg-red-500/20 transition-colors">
            <p class="text-red-400 font-bold text-lg font-aubette tracking-widest uppercase">Log Out</p>
        </a>
    </div>
</div>

<style>
.custom-scrollbar-v::-webkit-scrollbar { width: 0; }
</style>
