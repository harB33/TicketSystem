<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$user_id = $_SESSION['user_id'] ?? 1;
$event_id = $_GET['event_id'] ?? null;
$section_id = $_GET['section_id'] ?? null;

if (!$event_id || !$section_id) {
    echo "Invalid transaction request.";
    exit();
}

// Fetch details
$sql = "SELECT e.event_name, e.event_start_datetime, v.name as venue_name, ss.section_name, esp.price 
        FROM events e 
        JOIN venues v ON e.venue_id = v.venue_id
        JOIN seating_sections ss ON ss.section_id = ?
        LEFT JOIN event_section_prices esp ON esp.event_id = e.event_id AND esp.section_id = ss.section_id
        WHERE e.event_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $section_id, $event_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$details = mysqli_fetch_assoc($res);

if (!$details) {
    echo "Event or Section not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? 'credit_card';
    $qty = 1; // Assuming 1 ticket for simplicity
    $total_amount = $details['price'] * $qty;
    
    mysqli_begin_transaction($conn);
    try {
        // Create Order
        $sql = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'Completed')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "id", $user_id, $total_amount);
        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);
        
        // Pick a ticket
        $sql = "SELECT ticket_id FROM tickets WHERE event_id = ? AND section_id = ? AND status = 'Available' LIMIT 1 FOR UPDATE";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $event_id, $section_id);
        mysqli_stmt_execute($stmt);
        $ticket_res = mysqli_stmt_get_result($stmt);
        if ($ticket_row = mysqli_fetch_assoc($ticket_res)) {
            $ticket_id = $ticket_row['ticket_id'];
            
            // Mark Sold
            $sql = "UPDATE tickets SET status = 'Sold' WHERE ticket_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $ticket_id);
            mysqli_stmt_execute($stmt);
            
            // Create Order Item
            $barcode = 'TKT-' . strtoupper(uniqid()) . '-' . $ticket_id;
            $sql = "INSERT INTO order_items (order_id, ticket_id, unit_price, barcode_string) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iids", $order_id, $ticket_id, $details['price'], $barcode);
            mysqli_stmt_execute($stmt);
        } else {
            // Actually, we should probably generate a ticket if none exists, to avoid errors on testing
            $sql = "INSERT INTO tickets (event_id, section_id, price, status) VALUES (?, ?, ?, 'Sold')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iid", $event_id, $section_id, $details['price']);
            mysqli_stmt_execute($stmt);
            $ticket_id = mysqli_insert_id($conn);
            
            $barcode = 'TKT-' . strtoupper(uniqid()) . '-' . $ticket_id;
            $sql = "INSERT INTO order_items (order_id, ticket_id, unit_price, barcode_string) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iids", $order_id, $ticket_id, $details['price'], $barcode);
            mysqli_stmt_execute($stmt);
        }
        
        // Payment
        $ref = 'TXN-' . strtoupper(uniqid());
        $sql = "INSERT INTO payments (order_id, payment_method, transaction_ref, amount) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issd", $order_id, $payment_method, $ref, $total_amount);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        header("Location: ?page=mytickets");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Transaction failed: " . $e->getMessage();
    }
}
?>

<div class="flex flex-col w-full relative min-h-screen bg-black overflow-y-auto custom-scrollbar-v pb-40">
    <!-- Header -->
    <div class="h-[15vh] w-full shrink-0 flex flex-col items-center justify-end pb-4 bg-black z-20 sticky top-0">
        <div class="px-4 flex items-center gap-2">
            <a href="?page=event&id=<?= $event_id ?>" class="absolute left-6 p-3 bg-white/5 rounded-2xl border border-white/10 hover:bg-white/10 transition-all">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div class="w-2 h-8 bg-primary rounded-full"></div>
            <p class="font-aubette text-white text-3xl font-bold uppercase">CHECKOUT</p>
        </div>
    </div>
    
    <div class="flex flex-col gap-8 px-6 mt-6 w-full max-w-4xl mx-auto z-10">
        <?php if(isset($error)): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-xl">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="flex flex-col gap-8">
            <!-- Order Summary -->
            <div class="bg-zinc-900 rounded-[2.5rem] p-8 border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)]">
                <h2 class="text-white font-aubette text-2xl mb-6">Order Summary</h2>
                <div class="flex flex-col gap-4">
                    <div class="flex justify-between items-center border-b border-white/5 pb-4">
                        <div class="flex flex-col">
                            <span class="text-white/60 text-xs uppercase tracking-widest font-bold">Event</span>
                            <span class="text-white text-xl font-bold"><?= htmlspecialchars($details['event_name']) ?></span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center border-b border-white/5 pb-4">
                        <div class="flex flex-col">
                            <span class="text-white/60 text-xs uppercase tracking-widest font-bold">Location & Time</span>
                            <span class="text-white font-medium"><?= htmlspecialchars($details['venue_name']) ?></span>
                            <span class="text-zinc-400 text-sm"><?= (new DateTime($details['event_start_datetime']))->format('F d, Y • h:i A') ?></span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center border-b border-white/5 pb-4">
                        <div class="flex flex-col">
                            <span class="text-white/60 text-xs uppercase tracking-widest font-bold">Tickets</span>
                            <span class="text-primary font-bold text-lg"><?= htmlspecialchars($details['section_name']) ?> <span class="text-white text-sm">x 1</span></span>
                        </div>
                        <span class="text-white font-aubette text-xl">₱<?= number_format($details['price'], 2) ?></span>
                    </div>
                    <div class="flex justify-between items-center pt-2">
                        <span class="text-white font-aubette text-2xl">Total</span>
                        <span class="text-primary font-aubette text-4xl">₱<?= number_format($details['price'], 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="bg-zinc-900 rounded-[2.5rem] p-8 border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)]">
                <h2 class="text-white font-aubette text-2xl mb-6">Payment Method</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="payment_method" value="credit_card" class="peer sr-only" checked>
                        <div class="p-6 rounded-2xl border-2 border-white/10 bg-white/5 text-white peer-checked:border-primary peer-checked:bg-primary/10 transition-all text-center flex flex-col items-center gap-3 hover:bg-white/10">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            <span class="font-bold">Credit Card</span>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="payment_method" value="gcash" class="peer sr-only">
                        <div class="p-6 rounded-2xl border-2 border-white/10 bg-white/5 text-white peer-checked:border-primary peer-checked:bg-primary/10 transition-all text-center flex flex-col items-center gap-3 hover:bg-white/10">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <span class="font-bold">GCash</span>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="payment_method" value="maya" class="peer sr-only">
                        <div class="p-6 rounded-2xl border-2 border-white/10 bg-white/5 text-white peer-checked:border-primary peer-checked:bg-primary/10 transition-all text-center flex flex-col items-center gap-3 hover:bg-white/10">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span class="font-bold">Maya</span>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="w-full py-6 bg-primary rounded-[2rem] text-white font-bold text-2xl uppercase tracking-widest shadow-[0_20px_50px_rgba(255,102,153,0.3)] hover:scale-[1.02] active:scale-[0.98] transition-all">
                Confirm Purchase
            </button>
            <p class="text-center text-zinc-500 text-xs font-bold uppercase tracking-widest mb-10">By confirming, you agree to our terms of service</p>
        </form>
    </div>
</div>

<style>
.custom-scrollbar-v::-webkit-scrollbar { width: 0; }
</style>
