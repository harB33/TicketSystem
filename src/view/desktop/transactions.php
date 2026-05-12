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

// Check user verification
$user_sql = "SELECT first_name, last_name, phone_number FROM users WHERE user_id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_res = mysqli_stmt_get_result($user_stmt);
$user_data = mysqli_fetch_assoc($user_res);

$is_verified = !empty($user_data['first_name']) && !empty($user_data['last_name']) && !empty($user_data['phone_number']);

// Handle verification form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_account'])) {
    $fn = trim($_POST['first_name'] ?? '');
    $ln = trim($_POST['last_name'] ?? '');
    $pn = trim($_POST['phone_number'] ?? '');
    if ($fn && $ln && $pn) {
        $upd = "UPDATE users SET first_name = ?, last_name = ?, phone_number = ? WHERE user_id = ?";
        $stmt_upd = mysqli_prepare($conn, $upd);
        mysqli_stmt_bind_param($stmt_upd, "sssi", $fn, $ln, $pn, $user_id);
        if (mysqli_stmt_execute($stmt_upd)) {
            $is_verified = true;
            $user_data['first_name'] = $fn;
            $user_data['last_name'] = $ln;
            $user_data['phone_number'] = $pn;
        }
    } else {
        $verify_error = "Please fill in all fields to continue.";
    }
}

// Fetch event details
$sql = "SELECT e.event_name, e.event_start_datetime, v.name as venue_name, ss.section_name, esp.price, e.max_tickets_per_user 
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

$max_tickets_allowed = (isset($details['max_tickets_per_user']) && $details['max_tickets_per_user'] > 0) ? $details['max_tickets_per_user'] : 4;

// Calculate already bought tickets by this user for this event
$bought_sql = "SELECT COUNT(*) as bought FROM order_items oi 
               JOIN orders o ON oi.order_id = o.order_id 
               JOIN tickets t ON oi.ticket_id = t.ticket_id 
               WHERE o.user_id = ? AND t.event_id = ? AND o.status = 'Completed'";
$bought_stmt = mysqli_prepare($conn, $bought_sql);
mysqli_stmt_bind_param($bought_stmt, "ii", $user_id, $event_id);
mysqli_stmt_execute($bought_stmt);
$bought_res = mysqli_stmt_get_result($bought_stmt);
$bought_row = mysqli_fetch_assoc($bought_res);
$already_bought = $bought_row['bought'] ?? 0;

$available_to_buy = max(0, $max_tickets_allowed - $already_bought);

// Fetch Payment Methods
$methods_sql = "SELECT * FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
$methods_stmt = mysqli_prepare($conn, $methods_sql);
mysqli_stmt_bind_param($methods_stmt, "i", $user_id);
mysqli_stmt_execute($methods_stmt);
$methods_res = mysqli_stmt_get_result($methods_stmt);
$payment_methods = [];
while ($row = mysqli_fetch_assoc($methods_res)) {
    $payment_methods[] = $row;
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout']) && $is_verified) {
    $payment_method = $_POST['payment_method'] ?? 'Unknown';
    $qty = (int)($_POST['qty'] ?? 1);
    
    if ($qty < 1 || $qty > $available_to_buy) {
        $error = "Invalid ticket quantity. You can only buy up to " . $available_to_buy . " more ticket(s).";
    } else {
        $total_amount = $details['price'] * $qty;
        
        mysqli_begin_transaction($conn);
        try {
            // Create Order
            $sql = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'Completed')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "id", $user_id, $total_amount);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);
            
            for ($i = 0; $i < $qty; $i++) {
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
                } else {
                    // Generate a ticket if none exists
                    $sql = "INSERT INTO tickets (event_id, section_id, price, status) VALUES (?, ?, ?, 'Sold')";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iid", $event_id, $section_id, $details['price']);
                    mysqli_stmt_execute($stmt);
                    $ticket_id = mysqli_insert_id($conn);
                }
                
                // Create Order Item
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
        
        <?php if (!$is_verified): ?>
            <!-- Verification Form -->
            <div class="bg-zinc-900 rounded-[2.5rem] p-8 border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)]">
                <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path></svg>
                </div>
                <h2 class="text-white font-aubette text-2xl mb-2">Verification Required</h2>
                <p class="text-white/60 text-sm mb-8">Before you can purchase tickets, we need to verify your identity. Please complete your profile details below.</p>
                
                <?php if(isset($verify_error)): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-xl mb-6 text-sm">
                        <?= htmlspecialchars($verify_error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="flex flex-col gap-5">
                    <input type="hidden" name="verify_account" value="1">
                    <div class="flex flex-col gap-2">
                        <label class="text-white/40 text-[10px] uppercase tracking-widest font-bold ml-4">First Name</label>
                        <input type="text" name="first_name" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" placeholder="Enter your first name">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-white/40 text-[10px] uppercase tracking-widest font-bold ml-4">Last Name</label>
                        <input type="text" name="last_name" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" placeholder="Enter your last name">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-white/40 text-[10px] uppercase tracking-widest font-bold ml-4">Phone Number</label>
                        <input type="tel" name="phone_number" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" placeholder="Enter your phone number">
                    </div>
                    <button type="submit" class="w-full py-5 mt-4 bg-primary rounded-2xl text-white font-bold text-lg uppercase tracking-widest shadow-[0_10px_30px_rgba(255,102,153,0.3)] hover:scale-[1.02] active:scale-[0.98] transition-all">
                        Complete Verification
                    </button>
                </form>
            </div>
        <?php else: ?>
        
            <?php if(isset($error)): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-xl">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if($available_to_buy <= 0): ?>
                <div class="bg-zinc-900 rounded-[2.5rem] p-8 border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)] text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-red-500/20 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h2 class="text-white font-aubette text-2xl mb-2">Limit Reached</h2>
                    <p class="text-white/60 text-sm mb-6 max-w-sm">You have already purchased the maximum number of tickets allowed (<?= $max_tickets_allowed ?>) for this event.</p>
                    <a href="?page=event&id=<?= $event_id ?>" class="px-8 py-4 bg-white/10 hover:bg-white/20 rounded-2xl text-white font-bold transition-all">Go Back to Event</a>
                </div>
            <?php else: ?>
                <form method="POST" class="flex flex-col gap-8">
                    <input type="hidden" name="checkout" value="1">
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
                                    <span class="text-white/60 text-xs uppercase tracking-widest font-bold">Tickets (<?= htmlspecialchars($details['section_name']) ?>)</span>
                                    <span class="text-primary font-bold text-lg">₱<?= number_format($details['price'], 2) ?> each</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-white/40 text-xs font-bold uppercase tracking-widest">Qty</span>
                                    <select name="qty" id="ticketQty" class="bg-black/50 border border-white/20 text-white rounded-xl px-4 py-2 font-bold outline-none cursor-pointer hover:border-primary transition-colors appearance-none text-center">
                                        <?php for($i=1; $i<=$available_to_buy; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="text-white font-aubette text-2xl">Total</span>
                                <span class="text-primary font-aubette text-4xl" id="totalAmountDisplay">₱<?= number_format($details['price'], 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-zinc-900 rounded-[2.5rem] p-8 border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)]">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-white font-aubette text-2xl">Payment Method</h2>
                            <a href="?page=payment_methods" class="text-primary text-xs font-bold uppercase tracking-widest hover:underline">Manage</a>
                        </div>
                        
                        <?php if (empty($payment_methods)): ?>
                            <div class="p-6 bg-white/5 rounded-2xl border border-white/10 text-center flex flex-col items-center">
                                <p class="text-white/60 text-sm mb-4">No payment methods found.</p>
                                <a href="?page=payment_methods" class="inline-block px-6 py-3 bg-primary rounded-xl text-white font-bold text-sm">Add Payment Method</a>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 gap-4">
                                <?php foreach($payment_methods as $index => $pm): ?>
                                    <label class="relative cursor-pointer group">
                                        <input type="radio" name="payment_method" value="<?= htmlspecialchars($pm['provider'] . ' - ' . substr($pm['account_number'], -4)) ?>" class="peer sr-only" <?= $index === 0 ? 'checked' : '' ?>>
                                        <div class="p-5 rounded-2xl border-2 border-white/10 bg-white/5 text-white peer-checked:border-primary peer-checked:bg-primary/10 transition-all flex items-center justify-between hover:bg-white/10">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 rounded-xl bg-black/50 flex items-center justify-center shrink-0">
                                                    <?php if (stripos($pm['provider'], 'visa') !== false): ?>
                                                        <span class="font-bold italic text-sm">VISA</span>
                                                    <?php elseif (stripos($pm['provider'], 'mastercard') !== false): ?>
                                                        <div class="flex">
                                                            <div class="w-4 h-4 rounded-full bg-red-500 opacity-80 mix-blend-screen"></div>
                                                            <div class="w-4 h-4 rounded-full bg-yellow-500 opacity-80 mix-blend-screen -ml-2"></div>
                                                        </div>
                                                    <?php elseif (stripos($pm['provider'], 'gcash') !== false): ?>
                                                        <span class="text-blue-400 font-bold italic text-[10px]">GCash</span>
                                                    <?php else: ?>
                                                        <svg class="w-6 h-6 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-lg leading-none mb-1"><?= htmlspecialchars($pm['provider']) ?></span>
                                                    <span class="text-white/50 font-mono text-xs tracking-widest">•••• <?= substr(htmlspecialchars($pm['account_number']), -4) ?></span>
                                                </div>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-2 border-white/20 peer-checked:border-primary peer-checked:bg-primary flex items-center justify-center transition-colors">
                                                <svg class="w-4 h-4 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" <?= empty($payment_methods) ? 'disabled' : '' ?> class="w-full py-6 bg-primary rounded-[2rem] text-white font-bold text-2xl uppercase tracking-widest shadow-[0_20px_50px_rgba(255,102,153,0.3)] hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:pointer-events-none">
                        Confirm Purchase
                    </button>
                    <p class="text-center text-zinc-500 text-xs font-bold uppercase tracking-widest mb-10">By confirming, you agree to our terms of service</p>
                </form>
                
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const qtySelect = document.getElementById('ticketQty');
                        const totalDisplay = document.getElementById('totalAmountDisplay');
                        const unitPrice = <?= $details['price'] ?>;
                        
                        if(qtySelect && totalDisplay) {
                            qtySelect.addEventListener('change', (e) => {
                                const qty = parseInt(e.target.value);
                                const total = unitPrice * qty;
                                totalDisplay.textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            });
                        }
                    });
                </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.custom-scrollbar-v::-webkit-scrollbar { width: 0; }
select option { background: #18181b; color: white; }
</style>
