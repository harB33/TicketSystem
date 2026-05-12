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
?>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 102, 153, 0.3); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 102, 153, 0.5); }
</style>

<?php
// Fetch event details
$sql = "SELECT 
            e.event_name, 
            e.event_start_datetime, 
            e.event_description,
            v.name AS venue_name, 
            v.venue_img, 
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

$venue_name = $event['venue_name'];
$is_phil_arena = (stripos($venue_name, 'phil.arena') !== false || stripos($venue_name, 'Philippine') !== false);
$is_moa_arena = (stripos($venue_name, 'moa.arena') !== false || stripos($venue_name, 'Mall of Asia') !== false);
$is_araneta = (stripos($venue_name, 'araneta') !== false);

$base_image = !empty($event['venue_img']) ? $event['venue_img'] : "./asset/image/venue_placeholder.png";

$date = new DateTime($event['event_start_datetime']);
$formatted_date = $date->format('F d, Y');
$formatted_time = $date->format('h:i A');

// Fetch seating tiers (sections) and prices from event_section_prices
$sections_sql = "SELECT ss.section_id, ss.section_name, ss.section_img, esp.price
                 FROM seating_sections ss
                 JOIN events e ON ss.venue_id = e.venue_id
                 LEFT JOIN event_section_prices esp ON (ss.section_id = esp.section_id AND esp.event_id = e.event_id)
                 WHERE e.event_id = ?
                 ORDER BY esp.price DESC, ss.section_name ASC";
$stmt_sec = mysqli_prepare($conn, $sections_sql);
mysqli_stmt_bind_param($stmt_sec, "i", $event_id);
mysqli_stmt_execute($stmt_sec);
$sections_res = mysqli_stmt_get_result($stmt_sec);
$seating_tiers = [];
while ($row = mysqli_fetch_assoc($sections_res)) {
    $seating_tiers[] = $row;
}
?>

<div class="flex w-full h-screen bg-black text-white overflow-hidden relative">
    
    <!-- Immersive Background Headliner -->
    <?php if (!empty($event['artist_images'])): 
        $imgs = explode('|', $event['artist_images']);
        $headliner_img = $imgs[0];
    ?>
        <div class="absolute inset-0 z-0 opacity-10 blur-[120px] scale-110 pointer-events-none">
            <img src="<?= htmlspecialchars($headliner_img) ?>" class="w-full h-full object-cover">
        </div>
    <?php endif; ?>

    <!-- Header Navigation -->
    <div class="absolute top-0 left-0 w-full p-8 flex items-center justify-between z-50">
        <a href="?page=featured" class="flex items-center gap-4 group">
            <div class="p-4 bg-white/5 backdrop-blur-2xl rounded-2xl border border-white/10 group-hover:bg-white/10 transition-all group-hover:scale-110">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </div>
            <span class="text-white/40 group-hover:text-white transition-colors font-bold uppercase tracking-[0.3em] text-xs">Back to Featured</span>
        </a>
        <div class="flex gap-4">
            <div class="p-4 bg-white/5 backdrop-blur-2xl rounded-2xl border border-white/10 hover:bg-white/10 transition-all cursor-pointer">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-row items-stretch pt-24 z-10 px-12 pb-12 gap-12">
        
        <!-- Left Column: Arena Map (Fixed) -->
        <div class="w-7/12 relative flex flex-col items-center justify-center">
            <div class="w-full max-w-2xl aspect-[4/3] flex justify-center items-center relative group">
                <?php if ($is_phil_arena || $is_moa_arena || $is_araneta): ?>
                    <div class="absolute inset-0 bg-primary/10 rounded-full blur-[150px] opacity-20"></div>
                    
                    <!-- Base Layout -->
                    <img src="<?= $base_image ?>" alt="Arena Layout" class="w-[85%] h-[85%] object-contain drop-shadow-[0_0_60px_rgba(0,0,0,1)] transition-transform duration-1000 group-hover:scale-[1.02]">
                    
                    <!-- Dynamic Section Overlay -->
                    <img id="desktopSectionOverlay" src="<?= htmlspecialchars($seating_tiers[0]['section_img'] ?? '') ?>" 
                         class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[85%] h-[85%] object-contain transition-opacity duration-500 <?= empty($seating_tiers[0]['section_img']) ? 'opacity-0' : 'opacity-100' ?> pointer-events-none">
                <?php else: ?>
                    <div class="w-full h-full border-2 border-dashed border-white/5 rounded-[4rem] flex flex-col items-center justify-center bg-zinc-900/20 backdrop-blur-sm">
                        <svg class="w-24 h-24 text-zinc-800 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <p class="text-zinc-600 font-bold uppercase tracking-widest text-xl">Venue Layout TBA</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-12 flex gap-8 p-6 bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-primary shadow-[0_0_12px_rgba(255,102,153,1)]"></div>
                    <span class="text-xs uppercase tracking-widest text-white/80 font-bold">Selected Section</span>
                </div>
            </div>
        </div>

        <!-- Right Column: Details & Selection -->
        <div class="w-5/12 flex flex-col justify-center">
            <div class="bg-white/5 backdrop-blur-3xl border border-white/10 rounded-[3.5rem] p-12 shadow-[0_40px_120px_rgba(0,0,0,0.8)] relative flex flex-col gap-10">
                <!-- Decorative Blur -->
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-primary/10 rounded-full blur-[100px]"></div>

                <!-- Event Info -->
                <div class="relative z-10">
                    <div class="flex flex-col gap-3 mb-6">
                        <span class="text-primary font-bold uppercase tracking-[0.4em] text-xs">Now Live • Booking Available</span>
                        <h1 class="text-6xl font-bold font-aubette leading-[0.9] tracking-tighter"><?= htmlspecialchars($event['event_name']) ?></h1>
                    </div>
                    
                    <div class="flex flex-wrap gap-8 items-center text-white/60">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-white/5 rounded-xl border border-white/5">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.244a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <span class="font-bold text-sm"><?= htmlspecialchars($venue_name) ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-white/5 rounded-xl border border-white/5">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <span class="font-bold text-sm"><?= $formatted_date ?> • <?= $formatted_time ?></span>
                        </div>
                    </div>
                </div>

                <!-- Custom Dropdown Component -->
                <div class="relative z-20">
                    <label class="block text-white/40 uppercase tracking-[0.3em] text-[10px] font-bold mb-4 ml-4">Choose Seating Tier</label>
                    <div class="relative custom-dropdown group w-full" id="desktopTierDropdown">
                        <div id="desktopDropdownTrigger" class="w-full bg-white/5 border border-white/10 rounded-[2rem] px-10 py-7 flex items-center justify-between cursor-pointer active:scale-[0.98] transition-all backdrop-blur-xl hover:border-primary/50 hover:bg-white/10">
                            <span id="desktopSelectedTierText" class="font-bold text-2xl text-white">
                                <?= !empty($seating_tiers) ? htmlspecialchars($seating_tiers[0]['section_name']) . ' - ' . ($seating_tiers[0]['price'] ? '₱' . number_format($seating_tiers[0]['price']) : 'Price TBA') : 'No tiers available' ?>
                            </span>
                            <div class="p-2 bg-primary/20 rounded-full group-[.open]:rotate-180 transition-transform duration-300">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <div id="desktopDropdownMenu" class="absolute left-0 right-0 top-full mt-4 bg-[#0a0a0a]/95 backdrop-blur-3xl border border-white/10 rounded-[2rem] overflow-hidden opacity-0 pointer-events-none scale-95 origin-top transition-all duration-300 z-[100] shadow-[0_30px_100px_rgba(0,0,0,0.8)]">
                            <div class="max-h-[400px] overflow-y-auto custom-scrollbar">
                                <?php if (empty($seating_tiers)): ?>
                                    <div class="px-10 py-6 text-white/40 italic">No tiers available</div>
                                <?php else: ?>
                                    <?php foreach ($seating_tiers as $tier): ?>
                                        <div class="desktop-tier-option px-10 py-6 hover:bg-primary/20 cursor-pointer transition-all border-b border-white/5 last:border-0 flex items-center justify-between group/opt"
                                             data-value="<?= $tier['section_id'] ?>" 
                                             data-img="<?= htmlspecialchars($tier['section_img'] ?? '') ?>"
                                             data-text="<?= htmlspecialchars($tier['section_name']) ?> - <?= $tier['price'] ? '₱' . number_format($tier['price']) : 'Price TBA' ?>">
                                            <div class="flex flex-col">
                                                <span class="text-white group-hover/opt:text-primary transition-colors text-xl font-bold">
                                                    <?= htmlspecialchars($tier['section_name']) ?>
                                                </span>
                                                <span class="text-white/30 text-xs font-bold uppercase tracking-widest mt-1">Limited Availability</span>
                                            </div>
                                            <span class="text-white font-aubette text-2xl group-hover/opt:scale-110 transition-transform">
                                                <?= $tier['price'] ? '₱' . number_format($tier['price']) : 'TBA' ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 pt-4">
                    <a id="reserveBtn" href="?page=transactions&event_id=<?= $event_id ?>&section_id=<?= !empty($seating_tiers) ? $seating_tiers[0]['section_id'] : '' ?>" class="block text-center w-full py-8 bg-primary rounded-[2rem] text-white font-bold text-2xl uppercase tracking-widest shadow-[0_20px_50px_rgba(255,102,153,0.3)] hover:scale-[1.02] active:scale-[0.98] transition-all">
                        Reserve Seats Now
                    </a>
                    <p class="text-center text-white/20 text-[10px] uppercase tracking-[0.3em] mt-6 font-bold">Secure checkout with instant ticket delivery</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tierDropdown = document.getElementById('desktopTierDropdown');
        const trigger = document.getElementById('desktopDropdownTrigger');
        const menu = document.getElementById('desktopDropdownMenu');
        const text = document.getElementById('desktopSelectedTierText');
        const options = document.querySelectorAll('.desktop-tier-option');
        const sectionOverlay = document.getElementById('desktopSectionOverlay');
        let selectedSectionId = "<?= !empty($seating_tiers) ? $seating_tiers[0]['section_id'] : '' ?>";
        const reserveBtn = document.getElementById('reserveBtn');

        if (trigger && menu) {
            // Toggle dropdown
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = tierDropdown.classList.contains('open');
                
                if (isOpen) {
                    tierDropdown.classList.remove('open');
                    menu.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
                    menu.classList.remove('opacity-100', 'pointer-events-auto', 'scale-100');
                } else {
                    tierDropdown.classList.add('open');
                    menu.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
                    menu.classList.add('opacity-100', 'pointer-events-auto', 'scale-100');
                }
            });

            // Option selection
            options.forEach(option => {
                option.addEventListener('click', function() {
                    const imgUrl = this.getAttribute('data-img');
                    const display = this.getAttribute('data-text');
                    selectedSectionId = this.getAttribute('data-value');

                    if (reserveBtn) {
                        reserveBtn.href = `?page=transactions&event_id=<?= $event_id ?>&section_id=${selectedSectionId}`;
                    }

                    // Update text
                    text.textContent = display;

                    // Update overlay with smooth cross-fade
                    if (sectionOverlay) {
                        sectionOverlay.classList.add('opacity-0');
                        setTimeout(() => {
                            if (imgUrl && imgUrl.trim() !== '') {
                                sectionOverlay.src = imgUrl;
                                sectionOverlay.classList.remove('opacity-0');
                            }
                        }, 250);
                    }

                    // Close menu
                    tierDropdown.classList.remove('open');
                    menu.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
                    menu.classList.remove('opacity-100', 'pointer-events-auto', 'scale-100');
                });
            });

            // Close on outside click
            document.addEventListener('click', () => {
                if (tierDropdown) {
                    tierDropdown.classList.remove('open');
                    menu.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
                    menu.classList.remove('opacity-100', 'pointer-events-auto', 'scale-100');
                }
            });
        }
    });
</script>

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
