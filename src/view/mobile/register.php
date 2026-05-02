<logo class="h-[50%] w-full flex flex-col items-center justify-center">
    <img src="./logo/register.png" alt="" class="w-2/3">
</logo>
<form action="post" class="flex flex-col h-[50%] w-full justify-center items-center -translate-y-27.5">
    <div class="flex flex-col items-center justify-start h-full w-[80%] gap-4">
        <input type="text" placeholder="email" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191]">
        <input type="password" placeholder="password" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191]">
        <div class="grid grid-cols-3 gap-4 w-[94%]">
            <div class="h-full w-full flex items-center justify-center border border-primary rounded-full p-2">
            </div>
            <div class="h-full w-full flex items-center justify-center border border-[#ffde59] rounded-full p-2">
            </div>
            <div class="h-full w-full flex items-center justify-center border border-[#7ed957] rounded-full p-2">
            </div>
        </div>
        <input type="password" placeholder="confirm password" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191]">
        <div class="flex items-center justify-end w-full">
            <button type="submit" class=" p-2 border border-primary rounded-full w-1/2 bg-primary"><p class="font-ballmer text-lg translate-y-1">sign up</p></button>
        </div>
        <p class="opacity-75">Already have an account? <a href="#" onclick="showPage('login'); return false;" class="text-primary">Sign in</a></p>
        </div>
    </div>
</form>
