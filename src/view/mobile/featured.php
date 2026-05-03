<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include (__DIR__ . '/../../ticket_db/connectdb.php');

$user_id = $_SESSION['user_id'] ?? 1;

// 1. Fetch Recommended Events (based on liked artists)
$recommended_sql = "SELECT 
            e.event_id, 
            e.event_name, 
            e.event_start_datetime, 
            v.name AS venue_name, 
            a.name AS headliner_name, 
            a.image_url AS headliner_image
        FROM events e
        JOIN venues v ON e.venue_id = v.venue_id
        JOIN event_lineup el ON e.event_id = el.event_id
        JOIN user_artist_likes ual ON el.artist_id = ual.artist_id
        JOIN artists a ON el.artist_id = a.artist_id
        WHERE e.event_status = 'Published' AND ual.user_id = ?
        GROUP BY e.event_id
        ORDER BY e.event_start_datetime ASC";

$stmt_rec = mysqli_prepare($conn, $recommended_sql);
mysqli_stmt_bind_param($stmt_rec, "i", $user_id);
mysqli_stmt_execute($stmt_rec);
$recommended_res = mysqli_stmt_get_result($stmt_rec);
$recommended_events = [];
while ($row = mysqli_fetch_assoc($recommended_res)) {
    $recommended_events[] = $row;
}

// 2. Fetch All Events grouped by Venue
$all_sql = "SELECT 
            e.event_id, 
            e.event_name, 
            e.event_start_datetime, 
            v.name AS venue_name, 
            a.name AS headliner_name, 
            a.image_url AS headliner_image
        FROM events e
        JOIN venues v ON e.venue_id = v.venue_id
        LEFT JOIN event_lineup el ON e.event_id = el.event_id AND el.is_headliner = 1
        LEFT JOIN artists a ON el.artist_id = a.artist_id
        WHERE e.event_status = 'Published'
        ORDER BY v.name, e.event_start_datetime ASC";

$res = mysqli_query($conn, $all_sql);
$grouped_events = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $grouped_events[$row['venue_name']][] = $row;
    }
}
?>

<div class="flex flex-col w-full relative min-h-screen bg-black overflow-y-auto custom-scrollbar-v">
    <div class="h-[15vh] w-full shrink-0 flex flex-col items-center justify-center bg-black z-20">
        <img src="./asset/logo/featured.png" alt="" class="w-2/3">
    </div>
    
    <div class="shrink-0">
        <p id="live-timestamp" class="text-white text-5xl font-aubette text-center"><?php echo date("m d y"); ?></p>
    </div>
    
    <div class="pb-32 flex flex-col gap-8 mt-4">
        
        <?php if (!empty($recommended_events)): ?>
            <div class="flex flex-col gap-4">
                <div class="px-4 flex items-center gap-2">
                    <div class="w-2 h-8 bg-primary rounded-full"></div>
                    <p class="font-tschichold text-white text-3xl font-bold lowercase">recommended for you</p>
                </div>
                <div class="flex gap-4 px-4 z-10 w-full overflow-x-auto pb-4 custom-scrollbar-h">
                    <?php foreach ($recommended_events as $event): ?>
                        <?php 
                            $date = new DateTime($event['event_start_datetime']);
                            $formatted_date = $date->format('m d y');
                            $formatted_time = $date->format('H:i');
                        ?>
                        <a href="?page=event&id=<?= $event['event_id'] ?>" class="bg-zinc-900 rounded-[2.5rem] h-56 w-[80%] shrink-0 relative overflow-hidden border border-primary/20 block">
                            <div class="absolute inset-0 p-6 flex items-end justify-between z-10 bg-liniear-to-t from-black via-black/40 to-transparent">
                                <div>
                                    <p class="text-primary text-sm font-bold uppercase tracking-widest mb-1">Your Favorite</p>
                                    <p class="text-white text-2xl font-bold font-aubette leading-tight"><?= $formatted_date ?></p>
                                    <p class="text-white text-xl font-aubette opacity-80 truncate max-w-50"><?= htmlspecialchars($event['headliner_name'] ?? 'TBA') ?></p>
                                </div>
                                <div class="p-4 bg-primary rounded-3xl shadow-xl shadow-primary/30">
                                    <p class="text-white text-3xl font-bold font-aubette"><?= $formatted_time ?></p>
                                </div>
                            </div>
                            <?php if (!empty($event['headliner_image'])): ?>
                                <img src="<?= htmlspecialchars($event['headliner_image']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-zinc-800 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($grouped_events)): ?>
            <div class="p-10 text-center text-zinc-500">
                <p class="font-bold text-xl">No events found.</p>
                <p class="text-sm">Please check back later.</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_events as $venue_name => $events): ?>
                <div class="flex flex-col gap-3">
                    <div class="px-4">
                        <p class="font-tschichold text-white text-2xl font-bold lowercase opacity-60"><?= htmlspecialchars($venue_name) ?></p>
                    </div>
                    <div class="flex gap-4 px-4 z-10 w-full overflow-x-auto pb-4 custom-scrollbar-h">
                        <?php foreach ($events as $event): ?>
                            <?php 
                                $date = new DateTime($event['event_start_datetime']);
                                $formatted_date = $date->format('m d y');
                                $formatted_time = $date->format('H:i');
                            ?>
                                <a href="?page=event&id=<?= $event['event_id'] ?>" class="bg-zinc-900 rounded-3xl h-40 w-[70%] shrink-0 relative overflow-hidden border border-white/5 block">                                <div class="absolute inset-0 p-4 flex items-end justify-between z-10 bg-linear-to-t from-black/90 via-black/20 to-transparent">
                                    <div>
                                        <p class="text-white text-lg font-bold font-aubette"><?= $formatted_date ?></p>
                                        <p class="text-white text-lg font-aubette opacity-70 truncate max-w-35"><?= htmlspecialchars($event['headliner_name'] ?? 'TBA') ?></p>
                                    </div>
                                    <div class="p-2.5 bg-zinc-800 border border-white/10 rounded-2xl">
                                        <p class="text-white text-xl font-bold font-aubette"><?= $formatted_time ?></p>
                                    </div>
                                </a>
                                <?php if (!empty($event['headliner_image'])): ?>
                                    <img src="<?= htmlspecialchars($event['headliner_image']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-zinc-800 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.custom-scrollbar-h::-webkit-scrollbar { height: 4px; }
.custom-scrollbar-h::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar-h::-webkit-scrollbar-thumb { background: rgba(255, 102, 153, 0.1); border-radius: 10px; }
.custom-scrollbar-v::-webkit-scrollbar { width: 0; }
</style>