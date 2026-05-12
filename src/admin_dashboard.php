<?php
include_once __DIR__ . '/ticket_db/connectdb.php'; 
$message = '';
$current_tab = 'event';

// Ensure pricing table exists and helper functions
create_event_section_prices_table($conn);

function create_event_section_prices_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS event_section_prices (
        esp_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id INT UNSIGNED NOT NULL,
        section_id INT UNSIGNED NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (esp_id),
        UNIQUE KEY `idx_event_section` (`event_id`, `section_id`),
        KEY `event_id` (`event_id`),
        KEY `section_id` (`section_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $sql);
}

function insert_section_prices($conn, $event_id, $prices) {
    if (!is_array($prices)) return;
    $del_stmt = mysqli_prepare($conn, "DELETE FROM event_section_prices WHERE event_id = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $event_id);
    mysqli_stmt_execute($del_stmt);
    $ins_stmt = mysqli_prepare($conn, "INSERT INTO event_section_prices (event_id, section_id, price) VALUES (?, ?, ?)");
    foreach ($prices as $section_id => $price) {
        $section_id = (int)$section_id;
        $price = str_replace(',', '', $price);
        $price = floatval($price);
        if ($price < 0) $price = 0;
        mysqli_stmt_bind_param($ins_stmt, "iid", $event_id, $section_id, $price);
        mysqli_stmt_execute($ins_stmt);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    

    if ($action === 'add_artist' || $action === 'edit_artist') {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $genre = mysqli_real_escape_string($conn, $_POST['genre']);
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
        
        if ($action === 'add_artist') {
            $stmt = mysqli_prepare($conn, "INSERT INTO artists (name, genre, bio, image_url) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $genre, $bio, $image_url);
            if (mysqli_stmt_execute($stmt)) $message = success_msg("Artist added!");
            else $message = error_msg("Error adding artist: " . mysqli_error($conn));
        } else {
            $id = (int)$_POST['artist_id'];
            $stmt = mysqli_prepare($conn, "UPDATE artists SET name=?, genre=?, bio=?, image_url=? WHERE artist_id=?");
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $genre, $bio, $image_url, $id);
            if (mysqli_stmt_execute($stmt)) $message = success_msg("Artist updated!");
            else $message = error_msg("Error updating artist: " . mysqli_error($conn));
        }
        $current_tab = 'artist';
    }
    elseif ($action === 'delete_artist') {
        $id = (int)$_POST['artist_id'];

        mysqli_query($conn, "DELETE FROM event_lineup WHERE artist_id = $id");
        $stmt = mysqli_prepare($conn, "DELETE FROM artists WHERE artist_id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) $message = success_msg("Artist deleted!");
        else $message = error_msg("Error deleting artist: " . mysqli_error($conn));
        $current_tab = 'artist';
    }
    

    elseif ($action === 'edit_venue') {
        $id = (int)$_POST['venue_id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $capacity = (int)$_POST['capacity'];
        $stmt = mysqli_prepare($conn, "UPDATE venues SET name=?, address=?, city=?, capacity=? WHERE venue_id=?");
        mysqli_stmt_bind_param($stmt, "sssii", $name, $address, $city, $capacity, $id);
        if (mysqli_stmt_execute($stmt)) $message = success_msg("Venue updated!");
        else $message = error_msg("Error updating venue: " . mysqli_error($conn));
        $current_tab = 'venue';
    }


    elseif ($action === 'add_event' || $action === 'edit_event') {
        $event_name = mysqli_real_escape_string($conn, $_POST['event_name']);
        $venue_id = (int)$_POST['venue_id'];
        $event_description = mysqli_real_escape_string($conn, $_POST['event_description']);
        $start_date = $_POST['event_start_datetime'];
        $end_date = $_POST['event_end_datetime'];
        $status = $_POST['event_status'];

        if ($action === 'add_event') {
            $sql = "INSERT INTO events (event_name, venue_id, event_description, event_start_datetime, event_end_datetime, event_status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sissss", $event_name, $venue_id, $event_description, $start_date, $end_date, $status);
            if (mysqli_stmt_execute($stmt)) {
                $event_id = mysqli_insert_id($conn);
                insert_lineup($conn, $event_id, $_POST['artists'] ?? []);
                insert_section_prices($conn, $event_id, $_POST['section_price'] ?? []);
                $message = success_msg("Concert successfully published!");
            } else $message = error_msg("Error adding event: " . mysqli_error($conn));
        } else {
            $id = (int)$_POST['event_id'];
            $sql = "UPDATE events SET event_name=?, venue_id=?, event_description=?, event_start_datetime=?, event_end_datetime=?, event_status=? WHERE event_id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sissssi", $event_name, $venue_id, $event_description, $start_date, $end_date, $status, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_query($conn, "DELETE FROM event_lineup WHERE event_id = $id");
                insert_lineup($conn, $id, $_POST['artists'] ?? []);
                mysqli_query($conn, "DELETE FROM event_section_prices WHERE event_id = $id");
                insert_section_prices($conn, $id, $_POST['section_price'] ?? []);
                $message = success_msg("Concert updated!");
            } else $message = error_msg("Error updating event: " . mysqli_error($conn));
        }
        $current_tab = 'event';
    }
    elseif ($action === 'delete_event') {
        $id = (int)$_POST['event_id'];
        mysqli_query($conn, "DELETE FROM event_lineup WHERE event_id = $id");
        mysqli_query($conn, "DELETE FROM event_section_prices WHERE event_id = $id");
        $stmt = mysqli_prepare($conn, "DELETE FROM events WHERE event_id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) $message = success_msg("Event deleted!");
        else $message = error_msg("Error deleting event: " . mysqli_error($conn));
        $current_tab = 'event';
    }
}

function insert_lineup($conn, $event_id, $artists) {
    if (is_array($artists)) {
        foreach ($artists as $index => $artist_id) {
            $artist_id = (int)$artist_id;
            $is_headliner = ($index === 0) ? 1 : 0;
            $l_stmt = mysqli_prepare($conn, "INSERT INTO event_lineup (event_id, artist_id, is_headliner) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($l_stmt, "iii", $event_id, $artist_id, $is_headliner);
            mysqli_stmt_execute($l_stmt);
        }
    }
}

function success_msg($txt) { return "<div class='bg-[#7ed957]/20 border border-[#7ed957] text-[#7ed957] px-6 py-4 rounded-xl mb-6 font-bold'>$txt</div>"; }
function error_msg($txt) { return "<div class='bg-red-500/20 border border-red-500 text-red-400 px-6 py-4 rounded-xl mb-6 font-bold'>$txt</div>"; }


$venues = [];
$v_res = mysqli_query($conn, "SELECT * FROM venues ORDER BY name ASC");
if ($v_res) while($r = mysqli_fetch_assoc($v_res)) $venues[] = $r;

$artists = [];
$a_res = mysqli_query($conn, "SELECT * FROM artists ORDER BY name ASC");
if ($a_res) while($r = mysqli_fetch_assoc($a_res)) $artists[] = $r;

$sections_by_venue = [];
$s_res = mysqli_query($conn, "SELECT * FROM seating_sections ORDER BY venue_id ASC, section_name ASC");
if ($s_res) while($r = mysqli_fetch_assoc($s_res)) $sections_by_venue[$r['venue_id']][] = $r;

$events = [];
$e_res = mysqli_query($conn, "SELECT e.*, v.name as venue_name FROM events e LEFT JOIN venues v ON e.venue_id = v.venue_id ORDER BY event_start_datetime DESC");
if ($e_res) {
    while($r = mysqli_fetch_assoc($e_res)) {
        $l_res = mysqli_query($conn, "SELECT artist_id FROM event_lineup WHERE event_id = {$r['event_id']}");
        $r['lineup'] = [];
        if ($l_res) while($lr = mysqli_fetch_assoc($l_res)) $r['lineup'][] = $lr['artist_id'];
        $p_res = mysqli_query($conn, "SELECT section_id, price FROM event_section_prices WHERE event_id = {$r['event_id']}");
        $r['section_prices'] = [];
        if ($p_res) while($pr = mysqli_fetch_assoc($p_res)) $r['section_prices'][$pr['section_id']] = $pr['price'];
        $events[] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="min-h-screen bg-black text-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Event Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#ff6699' },
                    fontFamily: { ballmer: ['sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #18181b; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 8px; }
    </style>
</head>
<body class="p-4 md:p-10 font-sans selection:bg-primary selection:text-white pb-32">

    <div class="max-w-5xl mx-auto">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold font-ballmer text-white tracking-tight">Staff <span class="text-primary">Portal</span></h1>
                <p class="text-zinc-400 mt-2">Manage concerts, venues, and artists.</p>
            </div>
            <a href="index.php" class="px-6 py-2 rounded-full border border-zinc-700 hover:bg-zinc-800 transition text-sm font-bold">Back to Site</a>
        </div>

        <?= $message ?>


        <div class="flex gap-4 mb-8 border-b border-zinc-800 pb-4 overflow-x-auto">
            <button onclick="switchTab('event')" id="tab-btn-event" class="px-6 py-3 rounded-full <?= $current_tab == 'event' ? 'bg-primary text-white' : 'bg-zinc-900 text-zinc-400 border border-zinc-800' ?> font-bold transition-all whitespace-nowrap">
                Concerts
            </button>
            <button onclick="switchTab('artist')" id="tab-btn-artist" class="px-6 py-3 rounded-full <?= $current_tab == 'artist' ? 'bg-primary text-white' : 'bg-zinc-900 text-zinc-400 border border-zinc-800' ?> font-bold transition-all whitespace-nowrap">
                Artists
            </button>
            <button onclick="switchTab('venue')" id="tab-btn-venue" class="px-6 py-3 rounded-full <?= $current_tab == 'venue' ? 'bg-primary text-white' : 'bg-zinc-900 text-zinc-400 border border-zinc-800' ?> font-bold transition-all whitespace-nowrap">
                Venues
            </button>
        </div>


        <div id="section-event" class="<?= $current_tab == 'event' ? 'block' : 'hidden' ?>">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Concerts</h2>
                <button onclick="openEventForm()" class="px-4 py-2 bg-primary text-white rounded-lg font-bold hover:scale-105 transition">+ Create</button>
            </div>


            <div id="form-event-container" class="bg-zinc-900/40 p-6 md:p-10 rounded-3xl border border-zinc-800/50 mb-8 hidden">
                <h3 id="form-event-title" class="text-xl font-bold mb-4 text-primary">Create Concert</h3>
                <form method="POST" class="flex flex-col gap-6">
                    <input type="hidden" name="action" id="event_action" value="add_event">
                    <input type="hidden" name="event_id" id="event_id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">Concert Name</label>
                            <input type="text" name="event_name" id="event_name" required class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">Venue</label>
                            <select name="venue_id" id="event_venue_id" required class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition appearance-none cursor-pointer">
                                <option value="" disabled selected>Select a Venue...</option>
                                <?php foreach($venues as $v): ?>
                                    <option value="<?= $v['venue_id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">Start Date & Time</label>
                            <input type="datetime-local" name="event_start_datetime" id="event_start_datetime" required class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">End Date & Time</label>
                            <input type="datetime-local" name="event_end_datetime" id="event_end_datetime" required class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 mt-4">
                        <label class="font-bold text-zinc-300 ml-2">Select Artists (Lineup)</label>
                        <div class="bg-zinc-800 p-4 rounded-2xl border border-zinc-700 max-h-60 overflow-y-auto custom-scrollbar">
                            <?php if(empty($artists)): ?>
                                <p class="text-zinc-500 italic p-2">No artists found. Please add artists first.</p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <?php foreach($artists as $art): ?>
                                        <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-700 cursor-pointer transition border border-transparent hover:border-zinc-600">
                                            <input type="checkbox" name="artists[]" value="<?= $art['artist_id'] ?>" id="artist_chk_<?= $art['artist_id'] ?>" class="w-5 h-5 rounded border-zinc-600 text-primary focus:ring-primary bg-zinc-900 cursor-pointer">
                                            <span class="text-white font-bold"><?= htmlspecialchars($art['name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 mt-4">
                        <label class="font-bold text-zinc-300 ml-2">Section Pricing</label>
                        <div class="bg-zinc-800 p-4 rounded-2xl border border-zinc-700">
                            <p id="section-pricing-placeholder" class="text-zinc-500 italic p-2">Select a venue to set section prices.</p>
                            <div id="section-pricing-list" class="grid grid-cols-1 gap-2"></div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 mt-2">
                        <label class="font-bold text-zinc-300 ml-2">Event Description</label>
                        <textarea name="event_description" id="event_description" rows="3" class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition"></textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-bold text-zinc-300 ml-2">Publish Status</label>
                        <select name="event_status" id="event_status" class="px-6 py-4 rounded-2xl w-full md:w-1/3 text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition appearance-none cursor-pointer">
                            <option value="Draft" selected>Draft (Hidden)</option>
                            <option value="Published">Published (Live)</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-4 mt-4">
                        <button type="button" onclick="closeForm('form-event-container')" class="px-6 py-4 bg-zinc-800 text-zinc-300 rounded-full font-bold hover:bg-zinc-700 transition">Cancel</button>
                        <button type="submit" id="btn-save-event" class="px-10 py-4 bg-primary text-white rounded-full font-bold text-xl hover:scale-105 active:scale-95 transition shadow-lg shadow-primary/25">Save Concert</button>
                    </div>
                </form>
            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach($events as $ev): ?>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <h3 class="text-xl font-bold"><?= htmlspecialchars($ev['event_name']) ?></h3>
                                <span class="px-2 py-1 text-xs rounded-lg <?= $ev['event_status'] == 'Published' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' ?>"><?= $ev['event_status'] ?></span>
                            </div>
                            <p class="text-sm text-zinc-400 mt-1"><?= htmlspecialchars($ev['venue_name'] ?? 'Unknown Venue') ?></p>
                            <p class="text-sm text-zinc-500 mt-2"><?= date('M j, Y g:i A', strtotime($ev['event_start_datetime'])) ?></p>
                        </div>
                        <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-zinc-800/50">
                            <button onclick='editEvent(<?= json_encode($ev) ?>)' class="px-3 py-1 bg-zinc-800 hover:bg-zinc-700 rounded text-sm transition">Edit</button>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');" class="inline">
                                <input type="hidden" name="action" value="delete_event">
                                <input type="hidden" name="event_id" value="<?= $ev['event_id'] ?>">
                                <button type="submit" class="px-3 py-1 bg-red-500/20 hover:bg-red-500/40 text-red-400 rounded text-sm transition">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>


        <div id="section-artist" class="<?= $current_tab == 'artist' ? 'block' : 'hidden' ?>">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Artists</h2>
                <button onclick="openArtistForm()" class="px-4 py-2 bg-primary text-white rounded-lg font-bold hover:scale-105 transition">+ Add</button>
            </div>


            <div id="form-artist-container" class="bg-zinc-900/40 p-6 md:p-10 rounded-3xl border border-zinc-800/50 mb-8 hidden">
                <h3 id="form-artist-title" class="text-xl font-bold mb-4 text-primary">Add Artist</h3>
                <form method="POST" class="flex flex-col gap-6">
                    <input type="hidden" name="action" id="artist_action" value="add_artist">
                    <input type="hidden" name="artist_id" id="artist_id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">Artist Name</label>
                            <input type="text" name="name" id="artist_name" required class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">Genre</label>
                            <input type="text" name="genre" id="artist_genre" class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-bold text-zinc-300 ml-2">Image URL</label>
                        <input type="url" name="image_url" id="artist_image_url" class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-bold text-zinc-300 ml-2">Biography</label>
                        <textarea name="bio" id="artist_bio" rows="4" class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition"></textarea>
                    </div>

                    <div class="flex justify-end gap-4 mt-4">
                        <button type="button" onclick="closeForm('form-artist-container')" class="px-6 py-4 bg-zinc-800 text-zinc-300 rounded-full font-bold hover:bg-zinc-700 transition">Cancel</button>
                        <button type="submit" id="btn-save-artist" class="px-10 py-4 bg-primary text-white rounded-full font-bold text-xl hover:scale-105 active:scale-95 transition shadow-lg shadow-primary/25">Save Artist</button>
                    </div>
                </form>
            </div>


            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach($artists as $art): ?>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-zinc-800 mb-3 overflow-hidden">
                            <?php if(!empty($art['image_url'])): ?>
                                <img src="<?= htmlspecialchars($art['image_url']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-zinc-600">No Img</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="font-bold text-sm line-clamp-1"><?= htmlspecialchars($art['name']) ?></h3>
                        <p class="text-xs text-zinc-500 mb-3 line-clamp-1"><?= htmlspecialchars($art['genre']) ?></p>
                        <div class="flex gap-2 mt-auto">
                            <button onclick='editArtist(<?= json_encode($art) ?>)' class="px-2 py-1 bg-zinc-800 hover:bg-zinc-700 rounded text-xs transition">Edit</button>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this artist?');" class="inline">
                                <input type="hidden" name="action" value="delete_artist">
                                <input type="hidden" name="artist_id" value="<?= $art['artist_id'] ?>">
                                <button type="submit" class="px-2 py-1 bg-red-500/20 hover:bg-red-500/40 text-red-400 rounded text-xs transition">Del</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>


        <div id="section-venue" class="<?= $current_tab == 'venue' ? 'block' : 'hidden' ?>">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Venues</h2>
                <p class="text-sm text-zinc-400">Edit existing venues</p>
            </div>


            <div id="form-venue-container" class="bg-zinc-900/40 p-6 md:p-10 rounded-3xl border border-zinc-800/50 mb-8 hidden">
                <h3 class="text-xl font-bold mb-4 text-primary">Edit Venue</h3>
                <form method="POST" class="flex flex-col gap-6">
                    <input type="hidden" name="action" value="edit_venue">
                    <input type="hidden" name="venue_id" id="venue_id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">Venue Name</label>
                            <input type="text" name="name" id="venue_name" required class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">Capacity</label>
                            <input type="number" name="capacity" id="venue_capacity" class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">Address</label>
                            <input type="text" name="address" id="venue_address" class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-zinc-300 ml-2">City</label>
                            <input type="text" name="city" id="venue_city" class="px-6 py-4 rounded-2xl w-full text-lg text-white font-bold bg-zinc-800 border border-zinc-700 focus:border-primary focus:outline-none transition">
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mt-4">
                        <button type="button" onclick="closeForm('form-venue-container')" class="px-6 py-4 bg-zinc-800 text-zinc-300 rounded-full font-bold hover:bg-zinc-700 transition">Cancel</button>
                        <button type="submit" class="px-10 py-4 bg-primary text-white rounded-full font-bold text-xl hover:scale-105 active:scale-95 transition shadow-lg shadow-primary/25">Save Venue</button>
                    </div>
                </form>
            </div>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach($venues as $v): ?>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-lg"><?= htmlspecialchars($v['name']) ?></h3>
                            <p class="text-sm text-zinc-400 mt-1"><?= htmlspecialchars($v['city'] ?? 'No city') ?></p>
                            <p class="text-xs text-zinc-500 mt-1">Capacity: <?= htmlspecialchars($v['capacity'] ?? 'N/A') ?></p>
                        </div>
                        <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-zinc-800/50">
                            <button onclick='editVenue(<?= json_encode($v) ?>)' class="px-3 py-1 bg-zinc-800 hover:bg-zinc-700 rounded text-sm transition">Edit</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>


    <script>
        function switchTab(tab) {
            ['event', 'artist', 'venue'].forEach(t => {
                const sec = document.getElementById('section-' + t);
                const btn = document.getElementById('tab-btn-' + t);
                if (t === tab) {
                    sec.classList.remove('hidden');
                    btn.className = "px-6 py-3 rounded-full bg-primary text-white font-bold transition-all whitespace-nowrap";
                } else {
                    sec.classList.add('hidden');
                    btn.className = "px-6 py-3 rounded-full bg-zinc-900 text-zinc-400 border border-zinc-800 font-bold transition-all whitespace-nowrap";
                }
            });
        }

        function closeForm(id) {
            document.getElementById(id).classList.add('hidden');
        }

        const sectionsByVenue = <?= json_encode($sections_by_venue) ?>;

        function populateSectionPricing(venueId, existingPrices) {
            const placeholder = document.getElementById('section-pricing-placeholder');
            const list = document.getElementById('section-pricing-list');
            if (!list) return;
            list.innerHTML = '';
            if (!venueId || !sectionsByVenue[venueId] || sectionsByVenue[venueId].length === 0) {
                if (placeholder) placeholder.classList.remove('hidden');
                return;
            }
            if (placeholder) placeholder.classList.add('hidden');
            sectionsByVenue[venueId].forEach(section => {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-3 p-3 rounded-xl hover:bg-zinc-800 transition border border-transparent hover:border-zinc-700';

                const left = document.createElement('div');
                left.innerHTML = `<div class="text-white font-bold">${section.section_name}</div><div class="text-xs text-zinc-500">Capacity: ${section.capacity}</div>`;

                const input = document.createElement('input');
                input.type = 'number';
                input.step = '0.01';
                input.min = '0';
                input.name = 'section_price[' + section.section_id + ']';
                input.className = 'px-4 py-2 rounded-xl bg-zinc-900 border border-zinc-700 text-white font-bold w-32 text-right';
                if (existingPrices && existingPrices[section.section_id] !== undefined) {
                    input.value = existingPrices[section.section_id];
                }

                row.appendChild(left);
                row.appendChild(input);
                list.appendChild(row);
            });
        }


        function openEventForm() {
            document.getElementById('form-event-container').classList.remove('hidden');
            document.getElementById('form-event-title').innerText = 'Create Concert';
            document.getElementById('event_action').value = 'add_event';
            document.getElementById('event_id').value = '';
            document.getElementById('event_name').value = '';
            document.getElementById('event_venue_id').value = '';
            document.getElementById('event_start_datetime').value = '';
            document.getElementById('event_end_datetime').value = '';
            document.getElementById('event_description').value = '';
            document.getElementById('event_status').value = 'Draft';
            document.getElementById('btn-save-event').innerText = 'Save Concert';
            

            document.querySelectorAll('input[name="artists[]"]').forEach(cb => cb.checked = false);
            populateSectionPricing(document.getElementById('event_venue_id').value, {});
        }

        const _venueSelect = document.getElementById('event_venue_id');
        if (_venueSelect) {
            _venueSelect.addEventListener('change', function(e) {
                populateSectionPricing(e.target.value, {});
            });
        }

        function editEvent(data) {
            openEventForm();
            document.getElementById('form-event-title').innerText = 'Edit Concert';
            document.getElementById('event_action').value = 'edit_event';
            document.getElementById('event_id').value = data.event_id;
            document.getElementById('event_name').value = data.event_name;
            document.getElementById('event_venue_id').value = data.venue_id;
            

            const formatDt = (dtStr) => dtStr ? dtStr.replace(' ', 'T') : '';
            document.getElementById('event_start_datetime').value = formatDt(data.event_start_datetime);
            document.getElementById('event_end_datetime').value = formatDt(data.event_end_datetime);
            
            document.getElementById('event_description').value = data.event_description || '';
            document.getElementById('event_status').value = data.event_status;
            document.getElementById('btn-save-event').innerText = 'Update Concert';


            if (data.lineup && data.lineup.length > 0) {
                data.lineup.forEach(aid => {
                    const cb = document.getElementById('artist_chk_' + aid);
                    if (cb) cb.checked = true;
                });
            }
            populateSectionPricing(data.venue_id, data.section_prices || {});

            document.getElementById('form-event-container').scrollIntoView({behavior: "smooth"});
        }


        function openArtistForm() {
            document.getElementById('form-artist-container').classList.remove('hidden');
            document.getElementById('form-artist-title').innerText = 'Add Artist';
            document.getElementById('artist_action').value = 'add_artist';
            document.getElementById('artist_id').value = '';
            document.getElementById('artist_name').value = '';
            document.getElementById('artist_genre').value = '';
            document.getElementById('artist_image_url').value = '';
            document.getElementById('artist_bio').value = '';
            document.getElementById('btn-save-artist').innerText = 'Save Artist';
        }

        function editArtist(data) {
            openArtistForm();
            document.getElementById('form-artist-title').innerText = 'Edit Artist';
            document.getElementById('artist_action').value = 'edit_artist';
            document.getElementById('artist_id').value = data.artist_id;
            document.getElementById('artist_name').value = data.name;
            document.getElementById('artist_genre').value = data.genre || '';
            document.getElementById('artist_image_url').value = data.image_url || '';
            document.getElementById('artist_bio').value = data.bio || '';
            document.getElementById('btn-save-artist').innerText = 'Update Artist';
            
            document.getElementById('form-artist-container').scrollIntoView({behavior: "smooth"});
        }


        function editVenue(data) {
            document.getElementById('form-venue-container').classList.remove('hidden');
            document.getElementById('venue_id').value = data.venue_id;
            document.getElementById('venue_name').value = data.name;
            document.getElementById('venue_address').value = data.address || '';
            document.getElementById('venue_city').value = data.city || '';
            document.getElementById('venue_capacity').value = data.capacity || '';
            
            document.getElementById('form-venue-container').scrollIntoView({behavior: "smooth"});
        }
    </script>
</body>
</html>