<div class="flex flex-col w-full relative min-h-screen">
    <div class="h-[15vh] w-full flex flex-col items-center justify-center bg-black z-20">
        <img src="./logo/featured.png" alt="" class="w-2/3">
    </div>
    <div>
        <p id="live-timestamp" class="text-white text-5xl font-aubette text-center"><?php date_default_timezone_set('Asia/Singapore'); echo date("H:i:s"); ?></p>
    </div>
    <div>
        <div class="p-4 flex flex-col gap-2">
            <p class="font-tschichold text-white">ara coloseum</p>
            <div class="flex gap-4 z-10 w-full overflow-x-auto">
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
        </div>
        <div>
            <p class="font-tschichold text-white">araneta coloseum</p>
            <div class="flex gap-4 z-10 w-full overflow-x-auto">
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
            </div>
        </div>
        <div>
            <p class="font-tschichold text-white">araneta coloseum</p>
            <div class="flex gap-4 z-10 w-full overflow-x-auto">
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
                <div class="bg-gray-800 rounded-3xl h-36 w-2/3 flex-shrink-0"></div>
            </div>
        </div>
    </div>
</div>
<script>
    function updateTimestamp() {
        const now = new Date();
        // const year = now.getFullYear();
        // const month = String(now.getMonth() + 1).padStart(2, '0');
        // const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        document.getElementById('live-timestamp').textContent = `${hours} ${minutes} ${seconds}`;
    }
    updateTimestamp();
    setInterval(updateTimestamp, 1000);
</script>