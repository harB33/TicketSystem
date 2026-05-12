<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$user_id = $_SESSION['user_id'] ?? 1;

// 1. Fetch Recommended Events (based on liked artists)
$recommended_sql = "SELECT 
            e.event_id, 
            e.event_name, 
            e.event_start_datetime, 
            v.name AS venue_name, 
            GROUP_CONCAT(a.name ORDER BY el.is_headliner DESC SEPARATOR ', ') AS artist_lineup,
            GROUP_CONCAT(COALESCE(a.image_url, '') ORDER BY el.is_headliner DESC SEPARATOR '|') AS artist_images,
            MAX(CASE WHEN el.is_headliner = 1 THEN a.image_url ELSE a.image_url END) AS headliner_image
        FROM events e
        JOIN venues v ON e.venue_id = v.venue_id
        JOIN event_lineup el ON e.event_id = el.event_id
        JOIN artists a ON el.artist_id = a.artist_id
        WHERE e.event_status = 'Published' 
          AND e.event_id IN (
              SELECT el2.event_id 
              FROM event_lineup el2 
              JOIN user_artist_likes ual ON el2.artist_id = ual.artist_id 
              WHERE ual.user_id = ?
          )
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
            GROUP_CONCAT(a.name ORDER BY el.is_headliner DESC SEPARATOR ', ') AS artist_lineup,
            GROUP_CONCAT(COALESCE(a.image_url, '') ORDER BY el.is_headliner DESC SEPARATOR '|') AS artist_images,
            MAX(CASE WHEN el.is_headliner = 1 THEN a.image_url ELSE a.image_url END) AS headliner_image
        FROM events e
        JOIN venues v ON e.venue_id = v.venue_id
        LEFT JOIN event_lineup el ON e.event_id = el.event_id
        LEFT JOIN artists a ON el.artist_id = a.artist_id
        WHERE e.event_status = 'Published'
        GROUP BY e.event_id
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
                <div class="px-6 flex items-center gap-3">
                    <div class="w-1.5 h-8 bg-primary rounded-full shadow-[0_0_15px_rgba(255,102,153,0.5)]"></div>
                    <p class="font-tschichold text-white text-3xl font-bold lowercase tracking-tight">recommended for you</p>
                </div>
                <div class="flex gap-5 px-6 z-10 w-full overflow-x-auto pb-6 custom-scrollbar-h">
                    <?php foreach ($recommended_events as $event): ?>
                        <?php 
                            $date = new DateTime($event['event_start_datetime']);
                            $formatted_date = $date->format('m d y');
                            $formatted_time = $date->format('H:i');
                        ?>
                        <a href="?page=event&id=<?= $event['event_id'] ?>" class="group bg-zinc-900 rounded-[2.5rem] h-64 w-[85%] shrink-0 relative overflow-hidden border border-white/10 block transition-transform active:scale-[0.98]">
                            <div class="absolute inset-0 p-8 flex flex-col justify-between z-10 bg-gradient-to-t from-black via-black/40 to-transparent">
                                <div class="flex justify-between items-start">
                                    <span class="px-3 py-1 bg-primary/20 border border-primary/30 rounded-full text-primary text-[10px] font-bold uppercase tracking-widest backdrop-blur-sm">Featured</span>
                                    <div class="flex flex-col items-end gap-2">
                                        <div class="p-3 bg-primary/90 rounded-2xl shadow-xl shadow-primary/20 backdrop-blur-md">
                                            <p class="text-white text-2xl font-bold font-aubette"><?= $formatted_time ?></p>
                                        </div>
                                        <?php if (!empty($event['artist_images'])): ?>
                                            <div class="flex -space-x-3">
                                                <?php 
                                                $imgs = explode('|', $event['artist_images']);
                                                foreach (array_slice($imgs, 0, 4) as $img): if (empty($img)) continue;
                                                ?>
                                                    <div class="w-10 h-10 rounded-full border-2 border-zinc-900 overflow-hidden bg-zinc-800 shadow-lg">
                                                        <img src="<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover">
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($imgs) > 4): ?>
                                                    <div class="w-10 h-10 rounded-full border-2 border-zinc-900 bg-zinc-800 flex items-center justify-center shadow-lg">
                                                        <span class="text-white text-[10px] font-bold">+<?= count($imgs) - 4 ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div data-artist-names="<?= htmlspecialchars($event['artist_lineup'] ?? '') ?>">
                                    <p class="text-white/40 text-[10px] font-bold uppercase tracking-[0.2em] mb-1"><?= htmlspecialchars($event['event_name'] ?? 'TBA') ?></p>
                                    <?php $artist_array = explode(', ', $event['artist_lineup'] ?? ''); ?>
                                    <p class="text-white text-2xl font-bold font-aubette leading-tight mb-1"><?= $formatted_date ?></p>
                                    <p class="text-white text-xl font-aubette opacity-80 truncate artist-name-display"><?= htmlspecialchars($artist_array[0] ?? 'TBA') ?></p>
                                </div>
                            </div>
                            <div class="absolute inset-0 z-0">
                                <?php 
                                $all_imgs = explode('|', $event['artist_images']);
                                foreach ($all_imgs as $index => $img): if (empty($img)) continue;
                                ?>
                                    <img src="<?= htmlspecialchars($img) ?>" 
                                         class="artist-slide absolute inset-0 w-full h-full object-cover transition-all duration-1000 group-hover:scale-110 <?= $index === 0 ? 'opacity-100' : 'opacity-0' ?>">
                                <?php endforeach; ?>
                                <?php if (empty($event['artist_images'])): ?>
                                    <div class="w-full h-full bg-zinc-800 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($grouped_events)): ?>
            <div class="flex flex-col items-center justify-center p-12 text-center">
                <div class="w-20 h-20 bg-zinc-900 rounded-full flex items-center justify-center mb-4 border border-white/5">
                    <svg class="w-10 h-10 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <p class="font-aubette text-white text-xl font-bold">No events found</p>
                <p class="text-zinc-500 text-sm mt-1 max-w-[200px]">We couldn't find any events scheduled at this time.</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_events as $venue_name => $events): ?>
                <div class="flex flex-col gap-4 mb-2">
                    <div class="px-6 flex items-center justify-between">
                        <p class="font-tschichold text-white/40 text-xl font-bold lowercase tracking-wide"><?= htmlspecialchars($venue_name) ?></p>
                        <div class="h-[1px] flex-1 bg-white/5 ml-4"></div>
                    </div>
                    <div class="flex gap-4 px-6 z-10 w-full overflow-x-auto pb-6 custom-scrollbar-h">
                        <?php foreach ($events as $event): ?>
                            <?php 
                                $date = new DateTime($event['event_start_datetime']);
                                $formatted_date = $date->format('m d y');
                                $formatted_time = $date->format('H:i');
                            ?>
                            <a href="?page=event&id=<?= $event['event_id'] ?>" class="group bg-zinc-900 rounded-3xl h-44 w-[75%] shrink-0 relative overflow-hidden border border-white/5 block transition-transform active:scale-[0.98]">
                                <div class="absolute inset-0 p-5 flex items-end justify-between z-10 bg-gradient-to-t from-black/90 via-black/40 to-transparent">
                                    <div>
                                        <p class="text-white drop-shadow-[0_10px_10px_rgba(0,0,0,.8)] text-[14px] text-shadow-lg/50 absolute top-5 left-5 font-bold uppercase tracking-widest mb-0.5"><?= htmlspecialchars($event['event_name'] ?? 'TBA') ?></p>
                                        <?php if (!empty($event['artist_images'])): ?>
                                            <div class="flex -space-x-2 mb-3">
                                                <?php 
                                                $imgs = explode('|', $event['artist_images']);
                                                foreach (array_slice($imgs, 0, 3) as $img): if (empty($img)) continue;
                                                ?>
                                                    <div class="w-8 h-8 rounded-full border-2 border-zinc-900 overflow-hidden bg-zinc-800 shadow-lg">
                                                        <img src="<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover">
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($imgs) > 3): ?>
                                                    <div class="w-8 h-8 rounded-full border-2 border-zinc-900 bg-zinc-800 flex items-center justify-center shadow-lg">
                                                        <span class="text-white text-[8px] font-bold">+<?= count($imgs) - 3 ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div data-artist-names="<?= htmlspecialchars($event['artist_lineup'] ?? '') ?>">
                                            <p class="text-white text-xl font-bold font-aubette mb-0.5"><?= $formatted_date ?></p>
                                            <?php $artist_array_all = explode(', ', $event['artist_lineup'] ?? ''); ?>
                                            <p class="text-white/80 text-base font-aubette truncate max-w-[140px] artist-name-display"><?= htmlspecialchars($artist_array_all[0] ?? 'TBA') ?></p>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-zinc-800/80 border border-white/10 rounded-2xl backdrop-blur-md self-end">
                                        <p class="text-white text-lg font-bold font-aubette"><?= $formatted_time ?></p>
                                    </div>
                                </div>
                                <div class="absolute inset-0 z-0">
                                    <?php 
                                    $all_imgs = explode('|', $event['artist_images']);
                                    foreach ($all_imgs as $index => $img): if (empty($img)) continue;
                                    ?>
                                        <img src="<?= htmlspecialchars($img) ?>" 
                                             class="artist-slide absolute inset-0 w-full h-full object-cover transition-all duration-1000 group-hover:scale-110 <?= $index === 0 ? 'opacity-100' : 'opacity-0' ?>">
                                    <?php endforeach; ?>
                                    <?php if (empty($event['artist_images'])): ?>
                                        <div class="w-full h-full bg-zinc-800 flex items-center justify-center">
                                            <svg class="w-10 h-10 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Background Image & Name Carousel for Event Cards
    const cards = document.querySelectorAll('.group');
    cards.forEach(card => {
        const slides = card.querySelectorAll('.artist-slide');
        const nameDisplay = card.querySelector('.artist-name-display');
        const nameData = card.querySelector('[data-artist-names]');
        
        if (slides.length > 1 && nameDisplay && nameData) {
            const names = nameData.getAttribute('data-artist-names').split(', ');
            let currentSlide = 0;
            
            // Cross-fade every 5 seconds for a premium feel
            setInterval(() => {
                // Fade out current image
                slides[currentSlide].classList.remove('opacity-100');
                slides[currentSlide].classList.add('opacity-0');
                
                // Transition the text (subtle fade)
                nameDisplay.style.opacity = '0';
                
                setTimeout(() => {
                    // Move to next
                    currentSlide = (currentSlide + 1) % slides.length;
                    
                    // Update text
                    nameDisplay.textContent = names[currentSlide] || 'TBA';
                    
                    // Fade in next image
                    slides[currentSlide].classList.remove('opacity-0');
                    slides[currentSlide].classList.add('opacity-100');
                    
                    // Fade in text
                    nameDisplay.style.transition = 'opacity 0.5s ease-in-out';
                    nameDisplay.style.opacity = '1';
                }, 500); // sync text change with image cross-fade midpoint
            }, 5000);
        }
    });
});
</script>