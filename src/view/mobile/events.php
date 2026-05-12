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
    #tierSelector option {
        background-color: #121212;
        color: white;
        padding: 12px;
        font-family: inherit;
    }
    #tierSelector:focus {
        border-color: #FF6699;
        box-shadow: 0 0 15px rgba(255, 102, 153, 0.2);
    }
</style>

<?php
// Fetch event details
$sql = "SELECT 
            e.event_name, 
            e.event_start_datetime, 
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

// Fetch seating tiers (sections), prices, and images for this event
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
    </div>

    <!-- Arena Layout Section -->
    <div class="flex-1 relative flex flex-col items-center justify-center p-4 pt-24 overflow-hidden">
        
        <!-- Seating Tier Selector (New) -->
        

        <div class="w-full flex-col max-w-md aspect-square flex justify-center items-center relative group">
            <?php if ($is_phil_arena || $is_moa_arena || $is_araneta): ?>
                <div class="absolute inset-0 rounded-full blur-[80px]"></div>
                
                <!-- Base Layout -->
                <img src="<?= $base_image ?>" alt="Arena Layout" class="w-[80%] h-[80%] object-contain drop-shadow-[0_0_20px_rgba(0,0,0,0.5)] transition-transform duration-700">
                
                <!-- Dynamic Section Overlay -->
                <img id="mobileSectionOverlay" src="<?= htmlspecialchars($seating_tiers[0]['section_img'] ?? '') ?>" 
                     class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[80%] h-[80%] object-contain transition-opacity duration-300 <?= empty($seating_tiers[0]['section_img']) ? 'opacity-0' : 'opacity-100' ?> pointer-events-none">

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
        
    </div>


    <!-- Event Details Panel (Floating Glassmorphism) -->
    <div class="px-6 bg-gradient-to-t from-black via-black/90 to-transparent pb-6 pt-0">
        <!-- Legend (Placed here for visibility) -->
        <div class="mb-3 flex gap-4 justify-center">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-primary shadow-[0_0_8px_rgba(255,102,153,0.8)]"></div>
                <span class="text-[10px] uppercase tracking-tighter text-white/60 font-bold">Selected Section</span>
            </div>
        </div>

        <div class="w-full mb-4 z-20">
            <div class="relative custom-dropdown group" id="mobileTierDropdown">
                <!-- Dropdown Trigger -->
                <div id="mobileDropdownTrigger" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 flex items-center justify-between cursor-pointer active:scale-[0.98] transition-all backdrop-blur-3xl hover:border-primary/50">
                    <span id="mobileSelectedTierText" class="font-bold text-white">
                        <?= !empty($seating_tiers) ? htmlspecialchars($seating_tiers[0]['section_name']) . ' - ' . ($seating_tiers[0]['price'] ? '₱' . number_format($seating_tiers[0]['price']) : 'Price TBA') : 'No tiers available' ?>
                    </span>
                    <svg class="w-5 h-5 text-primary transition-transform duration-300 group-[.open]:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>

                <!-- Dropdown Menu -->
                <div id="mobileDropdownMenu" class="absolute left-0 right-0 top-full mt-3 bg-zinc-900/90 backdrop-blur-3xl border border-white/10 rounded-2xl overflow-hidden opacity-0 pointer-events-none scale-95 origin-top transition-all duration-300 z-[100] shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                        <?php if (empty($seating_tiers)): ?>
                            <div class="px-6 py-4 text-white/40 italic">No tiers available</div>
                        <?php else: ?>
                            <?php foreach ($seating_tiers as $index => $tier): ?>
                                <div class="mobile-tier-option px-6 py-4 hover:bg-primary/20 cursor-pointer transition-colors backdrop-blur-3xl border-b border-white/5 last:border-0 flex items-center justify-between group/opt"
                                     data-value="<?= $tier['section_id'] ?>" 
                                     data-img="<?= htmlspecialchars($tier['section_img'] ?? '') ?>"
                                     data-text="<?= htmlspecialchars($tier['section_name']) ?> - <?= $tier['price'] ? '₱' . number_format($tier['price']) : 'Price TBA' ?>">
                                    <span class="text-white group-hover/opt:text-primary transition-colors font-semibold">
                                        <?= htmlspecialchars($tier['section_name']) ?>
                                    </span>
                                    <span class="text-white/40 text-xs font-bold group-hover/opt:text-white/80 transition-colors">
                                        <?= $tier['price'] ? '₱' . number_format($tier['price']) : 'Price TBA' ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tierDropdown = document.getElementById('mobileTierDropdown');
                const trigger = document.getElementById('mobileDropdownTrigger');
                const menu = document.getElementById('mobileDropdownMenu');
                const text = document.getElementById('mobileSelectedTierText');
                const options = document.querySelectorAll('.mobile-tier-option');
                const sectionOverlay = document.getElementById('mobileSectionOverlay');
                let selectedSectionId = "<?= !empty($seating_tiers) ? $seating_tiers[0]['section_id'] : '' ?>";

                const reserveBtn = document.getElementById('reserveBtn');
                if (reserveBtn) {
                    reserveBtn.addEventListener('click', () => {
                        if (selectedSectionId) {
                            window.location.href = `?page=transactions&event_id=<?= $event_id ?>&section_id=${selectedSectionId}`;
                        } else {
                            alert('Please select a seating tier first.');
                        }
                    });
                }

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
                            const val = this.getAttribute('data-value');
                            const imgUrl = this.getAttribute('data-img');
                            const display = this.getAttribute('data-text');
                        selectedSectionId = val;

                            // Update text
                            text.textContent = display;

                            // Update overlay
                            if (sectionOverlay) {
                                if (imgUrl && imgUrl.trim() !== '') {
                                    sectionOverlay.src = imgUrl;
                                    sectionOverlay.classList.remove('opacity-0');
                                    sectionOverlay.classList.add('opacity-100');
                                } else {
                                    sectionOverlay.classList.remove('opacity-100');
                                    sectionOverlay.classList.add('opacity-0');
                                }
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
                            <p class="text-primary text-xl font-bold font-aubette tracking-wide"><?= $formatted_time ?></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                        <p class="text-white/40 text-[10px] font-bold uppercase tracking-widest mb-1">Date</p>
                        <p class="text-white text-sm font-bold tracking-wide"><?= $formatted_date ?></p>
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                        <p class="text-white/40 text-[10px] font-bold uppercase tracking-widest mb-1">Location</p>
                        <p class="text-white text-sm font-bold truncate"><?= htmlspecialchars($event['venue_name']) ?></p>
                    </div>
                </div>

                <button id="reserveBtn" class="w-full py-5 bg-primary hover:bg-primary-dark text-white rounded-2xl font-bold text-lg shadow-xl shadow-primary/20 transition-all active:scale-[0.98] flex items-center justify-center gap-3">
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
