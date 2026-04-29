<logo class="h-[50%] w-full flex flex-col items-center justify-center">
    <img src="./logo/logo.png" alt="" class="w-1/2">
</logo>
<form action="post" class="flex flex-col h-[50%] w-full justify-center items-center">
    <div class="flex flex-col items-center justify-start h-full w-[80%] gap-4">
        <input type="text" placeholder="email" class="px-6 py-4 border border-primary rounded-full w-full text-xl font-bold">
        <input type="password" placeholder="password" class="px-6 py-4 border border-primary rounded-full w-full text-xl font-bold">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center">
                <input type="checkbox" checked="checked" class="checkbox border checkbox-primary" />
                <span class="ml-2 text-sm ">Remember me</span>
            </div>
            <button type="submit" class="p-4 max-h-15.5 border border-primary rounded-full w-1/2 font-ballmer text-2xl">sign in</button>
        </div>
        <p>Don't have an account? <a href="./register.php" class="text-primary">Sign up</a></p>
    </div>
</form>