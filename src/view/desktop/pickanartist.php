<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$user_id = $_SESSION['user_id'] ?? 1;

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_artists'])) {
    if (!isset($_POST['artists']) || count($_POST['artists']) < 3) {
        $error_msg = "Please select at least 3 artists before proceeding.";
    } else {
        $stmt_del = mysqli_prepare($conn, "DELETE FROM user_artist_likes WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $user_id);
        mysqli_stmt_execute($stmt_del);

        $stmt_ins = mysqli_prepare($conn, "INSERT INTO user_artist_likes (user_id, artist_id) VALUES (?, ?)");
        
        foreach ($_POST['artists'] as $artist_id) {
            $aid = (int)$artist_id;
            mysqli_stmt_bind_param($stmt_ins, "ii", $user_id, $aid);
            mysqli_stmt_execute($stmt_ins);
        }

        header("Location: ?page=pickanarena");
        exit();
    }
}

$all_artists = [];
$res = mysqli_query($conn, "SELECT artist_id, name, image_url, genre FROM artists ORDER BY RAND()");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $all_artists[] = $row;
    }
}

// Split artists for recommendation engine
$initial_artists = array_slice($all_artists, 0, 15);
$pool_artists = array_slice($all_artists, 15);
?>

<div class="flex flex-col w-full h-full relative overflow-y-auto custom-scrollbar-v">

    <form method="POST" action="?page=pickanarena" class="w-full flex flex-col h-full relative">
        
        <div class="h-[15vh] w-full sticky top-0 flex flex-col items-center justify-center bg-black z-30 shrink-0">
            <p class="font-ballmer text-2xl p-4 text-white text-center">pick three or more artist that you listen to</p>

            <?php if(!empty($error_msg)): ?>
                <p class="text-primary font-bold animate-pulse text-sm -mt-2 pb-2"><?= htmlspecialchars($error_msg) ?></p>
            <?php endif; ?>
        </div>

        <div id="artists-grid" class="grid grid-cols-3 gap-4 p-4 z-10 pb-40">
            <?php if(empty($initial_artists)): ?>
                <p class="col-span-3 text-center text-zinc-500 mt-10">No artists available yet. Please add some via the Staff Portal.</p>
            <?php endif; ?>

            <?php foreach($initial_artists as $artist): ?>
                <label class="relative border border-white/10 rounded-xl aspect-square overflow-hidden cursor-pointer select-none bg-white/5 block hover:border-primary/50 transition-colors animate-fade-in">

                    <?php if(!empty($artist['image_url'])): ?>
                        <img src="<?= htmlspecialchars($artist['image_url']) ?>" alt="<?= htmlspecialchars($artist['name']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex flex-col items-center justify-center p-2 bg-zinc-900">
                            <svg class="w-8 h-8 text-zinc-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                        </div>
                    <?php endif; ?>

                    <div class="absolute bottom-0 w-full bg-linear-to-t from-black/90 to-transparent p-2 text-center">
                        <span class="text-white text-xs font-bold truncate block"><?= htmlspecialchars($artist['name']) ?></span>
                    </div>

                    <input type="checkbox" name="artists[]" id="chk_<?= $artist['artist_id'] ?>" value="<?= $artist['artist_id'] ?>" class="sr-only peer" onchange="handleArtistClick(<?= $artist['artist_id'] ?>, '<?= htmlspecialchars(addslashes($artist['genre'])) ?>')" <?php echo (isset($_POST['artists']) && in_array($artist['artist_id'], $_POST['artists'])) ? 'checked' : ''; ?>>
                    
                    <div class="absolute inset-0 bg-primary opacity-0 peer-checked:opacity-50 transition-opacity duration-200"></div>
                    
                    <div class="absolute inset-0 opacity-0 peer-checked:opacity-100 flex items-center justify-center transition-opacity duration-200">
                        <div class="w-14 h-14 border-4 border-white rounded-full flex items-center justify-center z-10">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="p-10 fixed bottom-0 left-0 bg-linear-to-t from-black via-black/80 to-transparent h-[20%] w-full flex flex-col justify-end items-center z-30 pointer-events-none">
            <button type="submit" name="submit_artists" class="bg-primary max-w-2xl max-h-13 text-white p-2 rounded-full w-1/2 pointer-events-auto hover:scale-105 active:scale-95 transition">
                <p class="font-ballmer text-2xl translate-y-1">next</p>
            </button>
        </div>
    </form>
</div>

<style>
.custom-scrollbar-v::-webkit-scrollbar { width: 0; }
@keyframes popIn {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.animate-pop-in {
    animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
.animate-fade-in {
    animation: popIn 0.3s ease-out forwards;
}
</style>

<script>
    const poolArtists = <?php echo json_encode($pool_artists); ?>;
    const grid = document.getElementById('artists-grid');

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function handleArtistClick(artistId, genre) {
        const checkbox = document.getElementById('chk_' + artistId);
        if (!checkbox || !checkbox.checked) return;

        let matches = [];
        // Support finding similar genres by splitting them (e.g. "Pop/Soul" -> matches "Pop")
        const targetGenres = genre.toLowerCase().split(/[/\s,]+/);

        for (let i = 0; i < poolArtists.length; i++) {
            const poolGenreStr = (poolArtists[i].genre || '').toLowerCase();
            const isMatch = targetGenres.some(g => poolGenreStr.includes(g));

            if (isMatch) {
                matches.push(poolArtists[i]);
                poolArtists.splice(i, 1);
                i--;
                if (matches.length === 3) break;
            }
        }

        // If we didn't find 3 related artists, just pop random ones so it always gives 3
        while (matches.length < 3 && poolArtists.length > 0) {
            matches.push(poolArtists.shift());
        }

        let insertAfterNode = checkbox.closest('label');

        matches.forEach((artist, index) => {
            setTimeout(() => {
                const div = document.createElement('label');
                div.className = "relative border border-white/10 rounded-xl aspect-square overflow-hidden cursor-pointer select-none bg-white/5 block hover:border-primary/50 transition-colors animate-pop-in";
                
                const imgHtml = artist.image_url 
                    ? `<img src="${escapeHtml(artist.image_url)}" class="w-full h-full object-cover">` 
                    : `<div class="w-full h-full flex items-center justify-center bg-zinc-900"><svg class="w-8 h-8 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg></div>`;

                const safeGenre = escapeHtml(artist.genre || '');
                
                div.innerHTML = `
                    ${imgHtml}
                    <div class="absolute bottom-0 w-full bg-linear-to-t from-black/90 to-transparent p-2 text-center">
                        <span class="text-white text-xs font-bold truncate block">${escapeHtml(artist.name)}</span>
                    </div>
                    <input type="checkbox" name="artists[]" id="chk_${artist.artist_id}" value="${artist.artist_id}" class="sr-only peer" onchange="handleArtistClick(${artist.artist_id}, '${safeGenre.replace(/'/g, "\\'")}')">
                    <div class="absolute inset-0 bg-primary opacity-0 peer-checked:opacity-50 transition-opacity duration-200"></div>
                    <div class="absolute inset-0 opacity-0 peer-checked:opacity-100 flex items-center justify-center transition-opacity duration-200">
                        <div class="w-14 h-14 border-4 border-white rounded-full flex items-center justify-center z-10">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    </div>
                `;
                
                if (insertAfterNode && insertAfterNode.parentNode) {
                    insertAfterNode.insertAdjacentElement('afterend', div);
                    insertAfterNode = div; // Update reference so the next one is inserted after this
                } else {
                    grid.appendChild(div); // Fallback
                }
            }, index * 150); // Stagger the animation
        });
    }
</script>