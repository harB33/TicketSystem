<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    echo "Event not found.";
    exit();
}

// Fetch event details
$sql = "SELECT 
            e.event_name, 
            e.event_start_datetime, 
            v.name AS venue_name, 
            GROUP_CONCAT(a.name ORDER BY el.is_headliner DESC SEPARATOR ', ') AS artist_lineup,
            GROUP_CONCAT(COALESCE(a.image_url, '') ORDER BY el.is_headliner DESC SEPARATOR '|') AS artist_images
        FROM events e
        JOIN venues v ON e.venue_id = v.venue_id
        LEFT JOIN event_lineup el ON e.event_id = el.event_id
        LEFT JOIN artists a ON el.artist_id = a.artist_id
        WHERE e.event_id = ?
        GROUP BY e.event_id";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

if (!$event) {
    echo "Event not found.";
    exit();
}

$is_phil_arena = (stripos($event['venue_name'], 'Philippine') !== false);
$date = new DateTime($event['event_start_datetime']);
$formatted_date = $date->format('F d, Y');
$formatted_time = $date->format('h:i A');

// Fetch seating tiers (sections) and prices for this event
// We use a LEFT JOIN on tickets so that sections show up even if no tickets are generated yet
$sections_sql = "SELECT ss.section_id, ss.section_name, MIN(t.price) as price
                 FROM seating_sections ss
                 JOIN events e ON ss.venue_id = e.venue_id
                 LEFT JOIN tickets t ON (ss.section_id = t.section_id AND t.event_id = e.event_id)
                 WHERE e.event_id = ?
                 GROUP BY ss.section_id
                 ORDER BY price DESC, ss.section_name ASC";
$stmt_sec = mysqli_prepare($conn, $sections_sql);
mysqli_stmt_bind_param($stmt_sec, "i", $event_id);
mysqli_stmt_execute($stmt_sec);
$sections_res = mysqli_stmt_get_result($stmt_sec);
$seating_tiers = [];
while ($row = mysqli_fetch_assoc($sections_res)) {
    $seating_tiers[] = $row;
}
?>

<div class="flex flex-col w-full h-full bg-black text-white overflow-hidden relative">
    <!-- Headliner Background Blur -->
    <?php if (!empty($event['artist_images'])): 
        $imgs = explode('|', $event['artist_images']);
        $headliner_img = $imgs[0];
    ?>
        <div class="absolute inset-0 z-0 opacity-20 blur-[100px] pointer-events-none">
            <img src="<?= htmlspecialchars($headliner_img) ?>" class="w-full h-full object-cover">
        </div>
    <?php endif; ?>

    <!-- Header with Back Button -->
    <div class="absolute top-0 left-0 w-full p-6 flex items-center justify-between z-50 pointer-events-none">
        <a href="?page=featured" class="p-3 bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 pointer-events-auto active:scale-95 transition-transform">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div class="p-3 bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 pointer-events-auto">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
            </svg>
        </div>
    </div>

    <!-- Arena Layout Section -->
    <div class="flex-1 relative flex flex-col items-center justify-center p-4 pt-24 overflow-y-auto">
        
        <!-- Seating Tier Selector (New) -->
        <div class="w-full max-w-xs mb-8 z-20">
            <div class="relative">
                <select class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 appearance-none font-bold text-white focus:outline-none focus:border-primary transition-all backdrop-blur-xl">
                    <?php if (empty($seating_tiers)): ?>
                        <option value="" disabled selected>No tiers available</option>
                    <?php else: ?>
                        <?php foreach ($seating_tiers as $index => $tier): ?>
                            <option value="<?= $tier['section_id'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tier['section_name']) ?> - 
                                <?= $tier['price'] ? '₱' . number_format($tier['price']) : 'Price TBA' ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="w-full max-w-md aspect-square relative group">
            <?php if ($is_phil_arena): ?>
                <div class="absolute inset-0 bg-primary/5 rounded-full blur-[80px] opacity-30"></div>
                
                <!-- Base Layout (Corrected to single image) -->
                <img src="https://i.imgur.com/Wxh5ymv.png" alt="Philippine Arena Layout" class="w-full h-full object-contain drop-shadow-[0_0_20px_rgba(0,0,0,0.5)] transition-transform duration-700 group-hover:scale-105">
            <?php else: ?>
                <div class="w-full h-full border-2 border-dashed border-white/10 rounded-[3rem] flex flex-col items-center justify-center bg-zinc-900/30 backdrop-blur-sm">
                    <svg class="w-20 h-20 text-zinc-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <p class="text-zinc-500 font-aubette text-lg uppercase tracking-widest">Venue Layout TBA</p>
                    <p class="text-zinc-600 text-xs mt-2 px-12 text-center"><?= htmlspecialchars($event['venue_name']) ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Legend / Selection Indicator -->
        <div class="mt-8 flex gap-4">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-primary shadow-[0_0_8px_rgba(255,102,153,0.8)]"></div>
                <span class="text-[10px] uppercase tracking-tighter text-white/60 font-bold">VIP Section</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-zinc-800"></div>
                <span class="text-[10px] uppercase tracking-tighter text-white/60 font-bold">Reserved</span>
            </div>
        </div>
    </div>

    <!-- Event Details Panel (Floating Glassmorphism) -->
    <div class="p-6 pb-12 bg-gradient-to-t from-black via-black/90 to-transparent pt-20">
        <div class="bg-white/5 backdrop-blur-2xl border border-white/10 rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden">
            <!-- Decorative Accent -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-primary/10 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-3xl font-bold font-aubette tracking-tight leading-none mb-2">
                            <?= htmlspecialchars($event['event_name']) ?>
                        </h1>
                        <p class="text-primary text-xs font-bold uppercase tracking-[0.2em]">
                            <?= htmlspecialchars($event['artist_lineup']) ?>
                        </p>
                    </div>
                    <div class="flex flex-col items-end">
                        <div class="px-4 py-2 bg-primary/20 border border-primary/30 rounded-2xl">
                            <p class="text-primary text-xl font-bold font-aubette"><?= $formatted_time ?></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                        <p class="text-white/40 text-[10px] font-bold uppercase tracking-widest mb-1">Date</p>
                        <p class="text-white text-sm font-bold"><?= $formatted_date ?></p>
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                        <p class="text-white/40 text-[10px] font-bold uppercase tracking-widest mb-1">Location</p>
                        <p class="text-white text-sm font-bold truncate"><?= htmlspecialchars($event['venue_name']) ?></p>
                    </div>
                </div>

                <button class="w-full py-5 bg-primary hover:bg-primary-dark text-white rounded-2xl font-bold text-lg shadow-xl shadow-primary/20 transition-all active:scale-[0.98] flex items-center justify-center gap-3">
                    Select Seats
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@font-face {
    font-family: 'Aubette';
    src: url('./asset/font/Aubette-Bold.otf') format('opentype');
    font-weight: bold;
}

.font-aubette {
    font-family: 'Aubette', sans-serif;
}

:root {
    --primary: #ff6699;
}

.bg-primary {
    background-color: var(--primary);
}

.text-primary {
    color: var(--primary);
}

.border-primary {
    border-color: var(--primary);
}
</style>
