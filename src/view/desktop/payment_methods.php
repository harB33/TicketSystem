<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ?page=login");
    exit();
}

// Handle Add Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $provider = $_POST['provider'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $account_name = $_POST['account_name'] ?? '';
    
    // Check if it's the first one to make it default
    $check_sql = "SELECT COUNT(*) as cnt FROM user_payment_methods WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $is_first = (mysqli_fetch_assoc($res)['cnt'] == 0);
    $is_default = $is_first ? 1 : 0;
    
    $insert_sql = "INSERT INTO user_payment_methods (user_id, provider, account_number, account_name, is_default) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "isssi", $user_id, $provider, $account_number, $account_name, $is_default);
    mysqli_stmt_execute($stmt);
    header("Location: ?page=payment_methods");
    exit();
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $method_id = (int)$_POST['method_id'];
    $del_sql = "DELETE FROM user_payment_methods WHERE method_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($stmt, "ii", $method_id, $user_id);
    mysqli_stmt_execute($stmt);
    header("Location: ?page=payment_methods");
    exit();
}

// Handle Set Default
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_default') {
    $method_id = (int)$_POST['method_id'];
    mysqli_query($conn, "UPDATE user_payment_methods SET is_default = 0 WHERE user_id = $user_id");
    $up_sql = "UPDATE user_payment_methods SET is_default = 1 WHERE method_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $up_sql);
    mysqli_stmt_bind_param($stmt, "ii", $method_id, $user_id);
    mysqli_stmt_execute($stmt);
    header("Location: ?page=payment_methods");
    exit();
}

// Fetch Payment Methods
$sql = "SELECT * FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$methods = [];
while ($row = mysqli_fetch_assoc($result)) {
    $methods[] = $row;
}
?>

<div class="flex flex-col w-full relative min-h-screen bg-black overflow-y-auto custom-scrollbar-v pb-32">
    <!-- Header -->
    <div class="max-w-3xl w-full mx-auto flex items-center flex-col h-full">
        <div class="h-[15vh] w-full shrink-0 flex items-end justify-between pb-4 bg-black z-20 sticky top-0 px-6 border-b border-white/5">
            <div class="flex items-center gap-4">
                <a href="?page=profile" class="p-3 bg-white/5 rounded-2xl border border-white/10 hover:bg-white/10 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-8 bg-primary rounded-full"></div>
                    <p class="font-aubette text-white text-3xl font-bold uppercase">Payment Methods</p>
                </div>
            </div>
        </div>

        <div class="w-full px-6 mt-10 flex flex-col gap-6">
            <?php foreach ($methods as $method): ?>
                <div class="bg-white/5 backdrop-blur-2xl border <?= $method['is_default'] ? 'border-primary/50 shadow-[0_0_20px_rgba(255,102,153,0.15)]' : 'border-white/10' ?> rounded-3xl p-6 flex items-center justify-between group">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 rounded-2xl bg-zinc-900 border border-white/10 flex items-center justify-center shrink-0">
                            <?php if (stripos($method['provider'], 'visa') !== false): ?>
                                <p class="text-white font-bold italic text-xl">VISA</p>
                            <?php elseif (stripos($method['provider'], 'mastercard') !== false): ?>
                                <div class="flex">
                                    <div class="w-6 h-6 rounded-full bg-red-500 opacity-80 mix-blend-screen"></div>
                                    <div class="w-6 h-6 rounded-full bg-yellow-500 opacity-80 mix-blend-screen -ml-3"></div>
                                </div>
                            <?php elseif (stripos($method['provider'], 'gcash') !== false): ?>
                                <p class="text-blue-400 font-bold italic text-sm">GCash</p>
                            <?php else: ?>
                                <svg class="w-8 h-8 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col">
                            <p class="text-white font-bold text-xl mb-1"><?= htmlspecialchars($method['provider']) ?></p>
                            <p class="text-white/50 font-mono text-sm tracking-widest">•••• <?= substr(htmlspecialchars($method['account_number']), -4) ?></p>
                            <p class="text-white/40 text-xs mt-1"><?= htmlspecialchars($method['account_name']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if ($method['is_default']): ?>
                            <span class="px-3 py-1 bg-primary/20 text-primary border border-primary/30 rounded-full text-[10px] uppercase tracking-widest font-bold">Default</span>
                        <?php else: ?>
                            <form method="POST" class="m-0">
                                <input type="hidden" name="action" value="set_default">
                                <input type="hidden" name="method_id" value="<?= $method['method_id'] ?>">
                                <button type="submit" class="px-3 py-1 bg-white/5 text-white/60 hover:text-white border border-white/10 rounded-full text-[10px] uppercase tracking-widest font-bold transition-colors">Set Default</button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete this payment method?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="method_id" value="<?= $method['method_id'] ?>">
                            <button type="submit" class="p-2 text-white/30 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="mt-8 bg-zinc-900/40 border border-white/5 rounded-[2.5rem] p-8 backdrop-blur-sm relative z-50">
                <h3 class="text-white font-aubette text-xl mb-6 uppercase tracking-[0.2em]">Add New Method</h3>
                <form method="POST" class="flex flex-col gap-5">
                    <input type="hidden" name="action" value="add">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex flex-col gap-2 relative" id="desktop-providerSelectContainer" style="z-index: 100;">
                            <label class="text-white/40 text-[10px] font-bold uppercase tracking-widest px-4">Provider / Card Type</label>
                            <button type="button" id="desktop-providerTrigger" class="w-full bg-black/50 border border-white/10 rounded-2xl px-6 py-5 text-white flex items-center justify-between focus:outline-none focus:border-primary transition-all group/trigger cursor-pointer">
                                <span id="desktop-selectedProviderDisplay" class="text-white/20">Select Provider</span>
                                <svg class="w-5 h-5 text-white/20 group-hover/trigger:text-primary transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <input type="hidden" name="provider" id="desktop-providerHiddenInput" required>
                            
                            <!-- Premium Dropdown (Mobile-style but Desktop IDs) -->
                            <div id="desktop-providerDropdown" class="hidden absolute top-[calc(100%+8px)] left-0 w-full bg-zinc-900/95 backdrop-blur-3xl border border-white/10 rounded-2xl overflow-hidden z-50 shadow-2xl">
                                <!-- GCash -->
                                <div class="desktop-provider-option px-6 py-4 hover:bg-primary/20 cursor-pointer transition-all border-b border-white/5 last:border-0 flex items-center justify-between group/opt" data-value="GCash">
                                    <div class="flex flex-col">
                                        <span class="text-white group-hover/opt:text-primary transition-colors text-lg font-bold">GCash</span>
                                        <span class="text-white/30 text-[10px] font-bold uppercase tracking-widest mt-0.5">E-Wallet</span>
                                    </div>
                                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                                        <p class="text-blue-400 font-bold italic text-[10px]">GCash</p>
                                    </div>
                                </div>
                                <!-- Maya -->
                                <div class="desktop-provider-option px-6 py-4 hover:bg-primary/20 cursor-pointer transition-all border-b border-white/5 last:border-0 flex items-center justify-between group/opt" data-value="Maya">
                                    <div class="flex flex-col">
                                        <span class="text-white group-hover/opt:text-primary transition-colors text-lg font-bold">Maya</span>
                                        <span class="text-white/30 text-[10px] font-bold uppercase tracking-widest mt-0.5">E-Wallet</span>
                                    </div>
                                    <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                                        <p class="text-green-400 font-bold italic text-[10px]">Maya</p>
                                    </div>
                                </div>
                                <!-- Visa -->
                                <div class="desktop-provider-option px-6 py-4 hover:bg-primary/20 cursor-pointer transition-all border-b border-white/5 last:border-0 flex items-center justify-between group/opt" data-value="Visa">
                                    <div class="flex flex-col">
                                        <span class="text-white group-hover/opt:text-primary transition-colors text-lg font-bold">Visa</span>
                                        <span class="text-white/30 text-[10px] font-bold uppercase tracking-widest mt-0.5">Credit / Debit Card</span>
                                    </div>
                                    <p class="text-white font-bold italic text-sm">VISA</p>
                                </div>
                                <!-- Mastercard -->
                                <div class="desktop-provider-option px-6 py-4 hover:bg-primary/20 cursor-pointer transition-all border-b border-white/5 last:border-0 flex items-center justify-between group/opt" data-value="Mastercard">
                                    <div class="flex flex-col">
                                        <span class="text-white group-hover/opt:text-primary transition-colors text-lg font-bold">Mastercard</span>
                                        <span class="text-white/30 text-[10px] font-bold uppercase tracking-widest mt-0.5">Credit / Debit Card</span>
                                    </div>
                                    <div class="flex">
                                        <div class="w-4 h-4 rounded-full bg-red-500 opacity-80 mix-blend-screen"></div>
                                        <div class="w-4 h-4 rounded-full bg-yellow-500 opacity-80 mix-blend-screen -ml-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-white/40 text-[10px] font-bold uppercase tracking-widest px-2">Account / Card Number</label>
                            <input type="text" name="account_number" id="desktop-accountNumber" required placeholder="•••• •••• •••• 1234" class="w-full bg-black/50 border border-white/10 rounded-2xl px-6 py-4 text-white font-mono placeholder-white/20 focus:outline-none focus:border-primary transition-colors">
                        </div>
                    </div>

                    <div id="desktop-cardExtraFields" class="hidden grid grid-cols-2 gap-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-white/40 text-[10px] font-bold uppercase tracking-widest px-2">Expires On</label>
                            <input type="text" name="expiry_date" id="desktop-expiry" placeholder="MM/YY" maxlength="5" pattern="\d{2}/\d{2}" class="w-full bg-black/50 border border-white/10 rounded-2xl px-6 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary transition-colors">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-white/40 text-[10px] font-bold uppercase tracking-widest px-2">CVV</label>
                            <input type="text" name="cvv" id="desktop-cvv" placeholder="•••" minlength="3" maxlength="3" pattern="\d{3}" class="w-full bg-black/50 border border-white/10 rounded-2xl px-6 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary transition-colors">
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-white/40 text-[10px] font-bold uppercase tracking-widest px-2">Account Name</label>
                        <input type="text" name="account_name" required placeholder="Name on account" class="w-full bg-black/50 border border-white/10 rounded-2xl px-6 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <button type="submit" class="mt-2 w-full py-5 bg-primary hover:bg-primary-dark text-white rounded-2xl font-bold text-lg transition-all active:scale-[0.98]">
                        Save Payment Method
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const trigger = document.getElementById('desktop-providerTrigger');
    const dropdown = document.getElementById('desktop-providerDropdown');
    const display = document.getElementById('desktop-selectedProviderDisplay');
    const hiddenInput = document.getElementById('desktop-providerHiddenInput');
    const options = document.querySelectorAll('.desktop-provider-option');
    const cardFields = document.getElementById('desktop-cardExtraFields');
    const accountInput = document.getElementById('desktop-accountNumber');
    const cvvInput = document.getElementById('desktop-cvv');
    const expiryInput = document.getElementById('desktop-expiry');

    // Toggle dropdown
    if (trigger) {
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });
    }

    // Format account/card number
    accountInput.addEventListener('input', function(e) {
        const provider = hiddenInput.value;
        let value = this.value.replace(/\D/g, '');
        
        if (provider === 'Visa' || provider === 'Mastercard') {
            if (value.length > 16) value = value.slice(0, 16);
            const parts = value.match(/.{1,4}/g) || [];
            this.value = parts.join('-');
        } else if (provider === 'GCash' || provider === 'Maya') {
            if (value.length > 11) value = value.slice(0, 11);
            
            let formatted = '';
            if (value.length > 0) {
                formatted = value.slice(0, 4);
                if (value.length > 4) {
                    formatted += '-' + value.slice(4, 7);
                    if (value.length > 7) {
                        formatted += '-' + value.slice(7, 11);
                    }
                }
            }
            this.value = formatted;
        }
    });

    // Format expiry date (MM/YY)
    expiryInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 4) value = value.slice(0, 4);
        
        if (value.length > 2) {
            this.value = value.slice(0, 2) + '/' + value.slice(2);
        } else {
            this.value = value;
        }
    });

    // Ensure CVV is numeric only
    cvvInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });

    // Selection logic
    options.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            display.textContent = value;
            display.classList.remove('text-white/20');
            display.classList.add('text-white');
            hiddenInput.value = value;
            dropdown.classList.add('hidden');

            // Reset input and formatting based on selection
            accountInput.value = '';
            if (value === 'Visa' || value === 'Mastercard') {
                accountInput.placeholder = '••••-••••-••••-1234';
                accountInput.maxLength = 19;
                accountInput.minLength = 19;
                cardFields.classList.remove('hidden');
                cardFields.querySelectorAll('input').forEach(input => {
                    input.required = true;
                });
            } else if (value === 'GCash' || value === 'Maya') {
                accountInput.placeholder = '09•• ••• ••••';
                accountInput.maxLength = 13;
                accountInput.minLength = 13;
                cardFields.classList.add('hidden');
                cardFields.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
            } else {
                accountInput.placeholder = '•••• •••• •••• 1234';
                accountInput.removeAttribute('maxLength');
                accountInput.removeAttribute('minLength');
                cardFields.classList.add('hidden');
                cardFields.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
            }
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (dropdown && !dropdown.contains(e.target) && !trigger.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
});
</script>
