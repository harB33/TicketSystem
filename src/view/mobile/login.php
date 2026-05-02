<logo class="h-[50%] w-full flex flex-col items-center justify-center">
    <img src="./logo/logo.png" alt="" class="w-1/2">
</logo>
<form action="post" class="flex flex-col h-[50%] w-full justify-center items-center">
    <div class="flex flex-col items-center justify-start h-full w-[80%] gap-4">
        <input type="text" placeholder="email" class="px-6 py-4 rounded-full w-full text-lg text-[#525252] font-bold bg-[#919191]">
        <input type="password" placeholder="password" class="px-6 py-4 rounded-full w-full text-lg text-[#525252] font-bold bg-[#919191]">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center">
                <input type="checkbox" checked="checked" class="checkbox border checkbox-primary rounded-full" style="border-radius: 100% !important; box-shadow: none !important;" />
                <span class="ml-2 text-sm opacity-75">Remember me</span>
            </div>
            <button type="submit" class=" p-2 border border-primary rounded-full w-1/2 bg-primary" onclick="showPage('pickAnArtist'); return false;"><p class="font-ballmer text-lg translate-y-1">sign in</p></button>
        </div>
        <p class="opacity-75">Don't have an account? <a href="#" onclick="showPage('register'); return false;" class="text-primary">Sign up</a></p>
    </div>
</form>