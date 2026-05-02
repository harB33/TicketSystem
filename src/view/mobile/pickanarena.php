<div class="flex flex-col w-full relative min-h-screen">
    <div class="h-[15vh] w-full fixed top-0 flex flex-col items-center justify-center bg-black z-20">
        <p class="font-ballmer text-2xl p-4 sticky">pick the venues that you prefer</p>
    </div>
    
    <div class="flex flex-col gap-4 p-4 z-10 mt-32 mb-40">
        <label class="relative block w-full rounded-3xl h-36 cursor-pointer select-none overflow-hidden border border-zinc-700/50 hover:border-primary/50 bg-zinc-900/40">
            <input type="checkbox" name="arena" value="phil_arena" class="sr-only peer">
            <div class="absolute inset-0 bg-linear-to-br from-primary/5 to-zinc-900 opacity-0 peer-checked:opacity-100 peer-checked:border-2 peer-checked:border-primary rounded-3xl transition-all duration-300"></div>
            <div class="absolute top-6 right-6 w-8 h-8 rounded-full border-4 border-white bg-transparent peer-checked:bg-primary transition-colors duration-200 z-20"></div>
            <div class="relative flex flex-col justify-between h-full p-6 z-10">
                <div class="flex items-center gap-2 text-primary">
                    <div class="w-1.5 h-6 bg-current"></div>
                    <svg class="w-11 h-6" viewBox="0 0 44 24" fill="currentColor">
                        <path d="M0 0H32A12 12 0 0 0 32 24H0Z" />
                    </svg>
                </div>
                <p class="font-ballmer text-4xl text-white tracking-tight leading-none">phil.arena</p>
            </div>
        </label>
        <label class="relative block w-full rounded-3xl h-36 cursor-pointer select-none overflow-hidden border border-zinc-700/50 hover:border-[#77e652]/50 bg-zinc-900/40">
            <input type="checkbox" name="arena" value="moa_arena" class="sr-only peer">
            <div class="absolute inset-0 bg-linear-to-br from-[#77e652]/5 to-zinc-900 opacity-0 peer-checked:opacity-100 peer-checked:border-2 peer-checked:border-[#77e652] rounded-3xl transition-all duration-300"></div>
            <div class="absolute top-6 right-6 w-8 h-8 rounded-full border-4 border-white bg-transparent peer-checked:bg-[#77e652] transition-colors duration-200 z-20"></div>
            <div class="relative flex flex-col justify-between h-full p-6 z-10">
                <div class="flex items-center gap-2 text-[#77e652]">
                    <div class="w-1.5 h-6 bg-current"></div>
                    <svg class="w-11 h-6" viewBox="0 0 44 24" fill="currentColor">
                        <path d="M0 0H32A12 12 0 0 0 32 24H0Z" />
                    </svg>
                </div>
                <p class="font-ballmer text-4xl text-white tracking-tight leading-none">moa.arena</p>
            </div>
        </label>
        <label class="relative block w-full rounded-3xl h-36 cursor-pointer select-none overflow-hidden border border-zinc-700/50 hover:border-[#919191]/50 bg-zinc-900/40">
            <input type="checkbox" name="arena" value="araneta_col" class="sr-only peer">
            <div class="absolute inset-0 bg-linear-to-br from-[#919191]/5 to-zinc-900 opacity-0 peer-checked:opacity-100 peer-checked:border-2 peer-checked:border-[#919191] rounded-3xl transition-all duration-300"></div>
            <div class="absolute top-6 right-6 w-8 h-8 rounded-full border-4 border-white bg-transparent peer-checked:bg-[#919191] transition-colors duration-200 z-20"></div>
            <div class="relative flex flex-col justify-between h-full p-6 z-10">
                <div class="flex items-center gap-2 text-[#919191]">
                    <div class="w-1.5 h-6 bg-current"></div>
                    <svg class="w-11 h-6" viewBox="0 0 44 24" fill="currentColor">
                        <path d="M0 0H32A12 12 0 0 0 32 24H0Z" />
                    </svg>
                </div>
                <p class="font-ballmer text-4xl text-white tracking-tight leading-none">araneta.col</p>
            </div>
        </label>
    </div>
    <div class="p-10 fixed bottom-0 bg-linear-to-t from-black to-black/0 h-[25%] w-full flex flex-col justify-end items-center z-20">
        <button onclick="window.location.href='?page=featured';" class="bg-primary max-w-2xl max-h-13 text-white p-2 rounded-full w-1/2"><p class="font-ballmer text-2xl translate-y-1">next</p></button>
    </div>
</div>