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
            a.name AS headliner_name
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
    <div class="max-w-3xl w-full flex flex-col h-full">
        <!-- Header -->
        <div class="h-[15vh] w-full shrink-0 flex flex-col items-center justify-end pb-4 bg-black z-20 sticky top-0">
            <div class="px-4 flex items-center gap-2">
                <div class="w-2 h-8 bg-primary rounded-full"></div>
                <p class="font-aubette text-white text-3xl font-bold">MY TICKETS</p>
            </div>
        </div>
    
    <div class="flex flex-col gap-6 px-6 mt-6 z-10">
        <?php if (empty($tickets)): ?>
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center mt-20 gap-4 opacity-70">
                <div class="w-24 h-24 rounded-full bg-zinc-900 border-2 border-dashed border-zinc-700 flex items-center justify-center">
                    <svg class="w-12 h-12 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                </div>
                <p class="text-white font-aubette text-2xl mt-4">no tickets yet</p>
                <p class="text-zinc-500 text-center max-w-[250px]">Looks like you haven't secured your spot for any events. Let's change that!</p>
                <a href="?page=featured" class="mt-4 px-8 py-4 bg-primary text-white rounded-full font-bold hover:scale-105 active:scale-95 transition-transform">
                    <p class="font-ballmer text-xl translate-y-1">browse events</p>
                </a>
            </div>
        <?php else: ?>
            <!-- Ticket List -->
            <?php foreach ($tickets as $ticket): ?>
                <?php 
                    $start_date = new DateTime($ticket['event_start_datetime']);
                    $end_date = !empty($ticket['event_end_datetime']) ? new DateTime($ticket['event_end_datetime']) : null;
                    
                    $formatted_date = $start_date->format('M d, Y');
                    $formatted_time = $start_date->format('H:i');
                    $display_name = !empty($ticket['event_name']) ? $ticket['event_name'] : ($ticket['headliner_name'] ?? 'Upcoming Event');
                    
                    $full_location = $ticket['venue_name'];
                    if (!empty($ticket['venue_city'])) {
                        $full_location .= ', ' . $ticket['venue_city'];
                    }
                    
                    $price_display = !empty($ticket['unit_price']) ? '₱' . number_format($ticket['unit_price'], 2) : 'FREE';
                ?>
                <div class="relative bg-zinc-900 rounded-[2.5rem] overflow-hidden border border-white/10 flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.5)] max-w-lg mx-auto w-full">
                    
                    <!-- Decorative cutouts -->
                    <div class="absolute left-[-15px] top-[220px] w-8 h-8 bg-black rounded-full z-20"></div>
                    <div class="absolute right-[-15px] top-[220px] w-8 h-8 bg-black rounded-full z-20"></div>
                    <div class="absolute left-4 right-4 top-[236px] border-b-2 border-dashed border-zinc-700 z-10"></div>

                    <!-- Top half: Event info (No Images) -->
                    <div class="h-[236px] w-full relative bg-linear-to-br from-primary/30 via-zinc-800 to-zinc-900 p-6 flex flex-col justify-between">
                        
                        <!-- Watermark -->
                        <div class="absolute inset-0 overflow-hidden opacity-5 pointer-events-none z-0 flex flex-wrap content-start">
                            <?php for($i=0; $i<50; $i++): ?>
                                <span class="font-black text-2xl uppercase tracking-tighter mr-2 leading-none font-sans text-white">TICKET</span>
                            <?php endfor; ?>
                        </div>

                        <div class="relative z-10 flex justify-between items-start">
                            <div class="bg-primary/20 text-primary border border-primary/30 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest">
                                Valid Ticket
                            </div>
                            <div class="text-right">
                                <p class="text-white/50 text-[10px] uppercase tracking-widest font-bold">Order Ref</p>
                                <p class="text-white/80 font-mono text-xs"><?= htmlspecialchars($ticket['transaction_ref'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                        
                        <div class="relative z-10 mt-auto">
                            <p class="text-white text-3xl font-bold font-aubette leading-tight truncate mb-2"><?= htmlspecialchars($display_name) ?></p>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-white/50 text-[10px] uppercase tracking-widest font-bold mb-1">Location</p>
                                    <p class="text-zinc-300 text-sm font-medium truncate"><?= htmlspecialchars($full_location) ?></p>
                                </div>
                                <div>
                                    <p class="text-white/50 text-[10px] uppercase tracking-widest font-bold mb-1">Purchased For</p>
                                    <p class="text-zinc-300 text-sm font-medium"><?= $price_display ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bottom half: Details & Barcode -->
                    <div class="p-6 pt-8 flex flex-col gap-4 bg-zinc-900">
                        <!-- Datetime -->
                        <div class="flex justify-between items-center bg-black/30 p-4 rounded-2xl border border-white/5">
                            <div>
                                <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold">Date</p>
                                <p class="text-white font-aubette text-xl"><?= $formatted_date ?></p>
                            </div>
                            <div class="w-px h-8 bg-zinc-700"></div>
                            <div class="text-right">
                                <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold">Time</p>
                                <p class="text-white font-aubette text-xl"><?= $formatted_time ?></p>
                            </div>
                        </div>

                        <!-- Seating -->
                        <div class="flex justify-between px-2 mt-2">
                            <div>
                                <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Section</p>
                                <p class="text-primary font-bold text-lg"><?= htmlspecialchars($ticket['section_name']) ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Row</p>
                                <p class="text-white font-bold text-lg"><?= htmlspecialchars($ticket['row_number'] ?? 'N/A') ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Seat</p>
                                <p class="text-white font-bold text-lg"><?= htmlspecialchars($ticket['seat_number'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                        
                        <div class="flex justify-between px-2 mt-1">
                            <?php if (!empty($ticket['attendee_first_name'])): ?>
                                <div>
                                    <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Ticket Holder</p>
                                    <p class="text-white/80 font-medium"><?= htmlspecialchars($ticket['attendee_first_name'] . ' ' . $ticket['attendee_last_name']) ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="text-right">
                                <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Ticket ID</p>
                                <p class="text-white/80 font-mono text-sm">#<?= str_pad($ticket['order_item_id'], 6, '0', STR_PAD_LEFT) ?></p>
                            </div>
                        </div>

                        <!-- Barcode Area -->
                        <div class="mt-4 flex flex-col items-center justify-center bg-white rounded-2xl p-4">
                            <!-- Visual Barcode Representation -->
                            <div class="flex h-12 w-full max-w-[200px] justify-between items-end gap-[2px] opacity-90">
                                <?php 
                                    srand(crc32($ticket['barcode_string'])); 
                                    for ($i = 0; $i < 35; $i++) {
                                        $width = rand(1, 4);
                                        echo "<div class='bg-black h-full' style='width: {$width}px;'></div>";
                                    }
                                ?>
                            </div>
                            <p class="text-black font-mono text-xs tracking-[0.4em] mt-2 font-bold uppercase">
                                <?= htmlspecialchars($ticket['barcode_string']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    </div>
</div>

<style>
.custom-scrollbar-v::-webkit-scrollbar { width: 0; }
</style>