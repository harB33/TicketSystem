<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

// Fallback to user_id 1 for testing (matches featured.php) so the navbar doesn't redirect you to login
$user_id = $_SESSION['user_id'] ?? 1;

// Fetch user's purchased tickets
$sql = "SELECT 
            oi.order_item_id,
            oi.barcode_string,
            oi.attendee_first_name,
            oi.attendee_last_name,
            oi.unit_price,
            o.order_id,
            p.transaction_ref,
            e.event_name,
            e.event_start_datetime,
            e.event_end_datetime,
            v.name AS venue_name,
            v.city AS venue_city,
            ss.section_name,
            s.row_number,
            s.seat_number,
            a.name AS headliner_name,
            p.payment_method
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN tickets t ON oi.ticket_id = t.ticket_id
        JOIN events e ON t.event_id = e.event_id
        JOIN venues v ON e.venue_id = v.venue_id
        JOIN seating_sections ss ON t.section_id = ss.section_id
        LEFT JOIN seats s ON t.seat_id = s.seat_id
        LEFT JOIN event_lineup el ON e.event_id = el.event_id AND el.is_headliner = 1
        LEFT JOIN artists a ON el.artist_id = a.artist_id
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.user_id = ? 
        ORDER BY e.event_start_datetime ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tickets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}
?>

<div class="flex flex-col w-full items-center relative min-h-screen bg-black overflow-y-auto custom-scrollbar-v pb-40">
    <!-- Background Ambient Glow -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[500px] bg-primary/5 blur-[120px] pointer-events-none"></div>

    <div class="max-w-7xl w-full flex flex-col h-full relative z-10">
        <!-- Header -->
        <div class="h-[25vh] w-full shrink-0 flex flex-col items-start justify-end pb-12 px-8">
            <div class="flex items-center gap-6 mb-4">
                <div class="w-3 h-12 bg-primary rounded-full shadow-[0_0_20px_rgba(255,102,153,0.6)]"></div>
                <div>
                    <p class="font-aubette text-white text-6xl font-bold tracking-tight">MY TICKETS</p>
                    <p class="text-zinc-500 font-medium tracking-[0.2em] uppercase text-xs mt-2">Your collection of upcoming experiences</p>
                </div>
            </div>
            
            <?php if (!empty($tickets)): ?>
                <div class="flex items-center gap-4 mt-4">
                    <div class="px-4 py-2 bg-zinc-900/80 border border-white/10 rounded-2xl backdrop-blur-xl">
                        <span class="text-primary font-bold text-xl"><?= count($tickets) ?></span>
                        <span class="text-zinc-400 text-sm ml-2 uppercase font-bold tracking-widest">Active Passes</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    
        <div class="px-8 z-10">
            <?php if (empty($tickets)): ?>
                <!-- Empty State -->
                <div class="flex flex-col items-center justify-center py-32 gap-6 bg-zinc-900/30 rounded-[4rem] border border-white/5 backdrop-blur-sm">
                    <div class="w-32 h-32 rounded-full bg-zinc-900 border-2 border-dashed border-zinc-700 flex items-center justify-center mb-4">
                        <svg class="w-16 h-16 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-white font-aubette text-4xl mb-2">no tickets yet</p>
                        <p class="text-zinc-500 max-w-sm mx-auto text-lg">Your dashboard is empty. Discover the next big event and secure your spot today.</p>
                    </div>
                    <a href="?page=featured" class="mt-6 px-12 py-5 bg-primary text-white rounded-2xl font-bold hover:scale-105 hover:shadow-[0_0_30px_rgba(255,102,153,0.4)] active:scale-95 transition-all duration-300">
                        <p class="font-ballmer text-2xl translate-y-1">explore events</p>
                    </a>
                </div>
            <?php else: ?>
                <!-- Ticket Grid -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <?php foreach ($tickets as $ticket): ?>
                        <?php 
                            $start_date = new DateTime($ticket['event_start_datetime']);
                            $formatted_date = $start_date->format('M d, Y');
                            $formatted_time = $start_date->format('H:i');
                            $display_name = !empty($ticket['event_name']) ? $ticket['event_name'] : ($ticket['headliner_name'] ?? 'Upcoming Event');
                            
                            $full_location = $ticket['venue_name'];
                            if (!empty($ticket['venue_city'])) {
                                $full_location .= ', ' . $ticket['venue_city'];
                            }
                        ?>
                        <div class="relative bg-zinc-900/40 backdrop-blur-3xl rounded-[2.5rem] overflow-hidden border border-white/10 flex flex-row shadow-[0_20px_50px_rgba(0,0,0,0.5)] group hover:scale-[1.02] hover:border-primary/40 transition-all duration-500 h-72">
                            
                            <!-- Left: Event Info -->
                            <div class="flex-1 p-8 flex flex-col justify-between relative overflow-hidden">
                                <!-- Watermark Background -->
                                <div class="absolute inset-0 opacity-[0.03] pointer-events-none select-none flex flex-wrap content-start p-2 gap-2 transform -rotate-12 scale-150">
                                    <?php for($i=0; $i<20; $i++): ?>
                                        <span class="font-black text-2xl uppercase tracking-tighter text-white">ACCESS</span>
                                    <?php endfor; ?>
                                </div>

                                <div class="relative z-10 flex justify-between items-start">
                                    <div class="bg-primary/10 text-primary border border-primary/20 px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-[0.2em] backdrop-blur-md">
                                        Confirmed Admission
                                    </div>
                                    <div class="text-right">
                                        <p class="text-white/30 text-[9px] uppercase tracking-widest font-bold">Method / Transaction</p>
                                        <p class="text-white/60 font-mono text-[10px]">
                                            <span class="text-primary/80 font-bold"><?= htmlspecialchars($ticket['payment_method'] ?? 'Unknown') ?></span> • <?= htmlspecialchars($ticket['transaction_ref'] ?? 'REF-00000') ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="relative z-10">
                                    <p class="text-white text-3xl font-bold font-aubette leading-tight truncate mb-1 group-hover:text-primary transition-colors"><?= htmlspecialchars($display_name) ?></p>
                                    <div class="flex items-center gap-2 text-zinc-400">
                                        <svg class="w-4 h-4 text-primary/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <p class="text-sm font-medium truncate"><?= htmlspecialchars($full_location) ?></p>
                                    </div>
                                </div>

                                <div class="relative z-10 flex gap-8">
                                    <div>
                                        <p class="text-white/30 text-[9px] uppercase tracking-widest font-bold mb-1">Date</p>
                                        <p class="text-white font-aubette text-lg"><?= $formatted_date ?></p>
                                    </div>
                                    <div>
                                        <p class="text-white/30 text-[9px] uppercase tracking-widest font-bold mb-1">Doors Open</p>
                                        <p class="text-white font-aubette text-lg"><?= $formatted_time ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Decorative Perforation -->
                            <div class="relative flex flex-col items-center justify-between py-4">
                                <div class="absolute top-[-20px] w-10 h-10 bg-black rounded-full z-20 border border-white/5"></div>
                                <div class="w-px h-full border-r-2 border-dashed border-zinc-700/50 mx-2"></div>
                                <div class="absolute bottom-[-20px] w-10 h-10 bg-black rounded-full z-20 border border-white/5"></div>
                            </div>

                            <!-- Right: Seating & Barcode -->
                            <div class="w-72 p-8 flex flex-col justify-between bg-zinc-800/20 border-l border-white/5 backdrop-blur-sm">
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="text-center">
                                        <p class="text-[9px] text-zinc-500 uppercase tracking-widest font-bold mb-1">Section</p>
                                        <p class="text-primary font-bold text-base truncate"><?= htmlspecialchars($ticket['section_name']) ?></p>
                                    </div>
                                    <div class="text-center border-x border-white/5">
                                        <p class="text-[9px] text-zinc-500 uppercase tracking-widest font-bold mb-1">Row</p>
                                        <p class="text-white font-bold text-base"><?= htmlspecialchars($ticket['row_number'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[9px] text-zinc-500 uppercase tracking-widest font-bold mb-1">Seat</p>
                                        <p class="text-white font-bold text-base"><?= htmlspecialchars($ticket['seat_number'] ?? 'N/A') ?></p>
                                    </div>
                                </div>

                                <!-- Barcode Container -->
                                <div class="mt-4 flex flex-col items-center group/barcode">
                                    <div class="bg-white p-3 rounded-xl transition-all duration-500 group-hover/barcode:scale-105 group-hover/barcode:shadow-[0_0_20px_rgba(255,255,255,0.2)]">
                                        <!-- Dynamic Barcode Rendering -->
                                        <div class="flex h-10 w-full max-w-[160px] justify-between items-end gap-[1px]">
                                            <?php 
                                                srand(crc32($ticket['barcode_string'])); 
                                                for ($i = 0; $i < 30; $i++) {
                                                    $width = rand(1, 3);
                                                    echo "<div class='bg-black h-full' style='width: {$width}px;'></div>";
                                                }
                                            ?>
                                        </div>
                                        <p class="text-black font-mono text-[8px] tracking-[0.3em] mt-2 font-bold uppercase text-center">
                                            <?= htmlspecialchars($ticket['barcode_string']) ?>
                                        </p>
                                    </div>
                                    <p class="text-[9px] text-zinc-500 uppercase tracking-[0.2em] mt-3 font-bold opacity-0 group-hover:opacity-100 transition-opacity">Scan at Entrance</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.custom-scrollbar-v::-webkit-scrollbar { width: 0; }
</style>